<?php
require '../../php/db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../role.html");
    exit;
}

$userId = $_SESSION['user_id'];


$stmt = $pdo->prepare("SELECT full_name, rhu FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$rhu = $user ? $user['rhu'] : 'N/A';
$username = $user ? $user['full_name'] : 'N/A';




$from_date = $_GET['from_date'] ?? '';
$to_date   = $_GET['to_date'] ?? '';
$medicine = isset($_GET['medicine']) ? (array)$_GET['medicine'] : [];
$sex       = $_GET['sex'] ?? '';
$age_group = $_GET['age_group'] ?? '';
$barangay  = $_GET['purok'] ?? ''; // Use 'purok' for barangay filter
$subcategory = $_GET['subcategory'] ?? '';

$params = [];

// Base query: get patients who received medicines (without hardcoding)
$sql = "
SELECT 
    p.patient_id,
    CONCAT(p.last_name, ', ', p.first_name, ' ', COALESCE(p.middle_name, ''), ' ', COALESCE(p.extension, '')) AS full_name,
    p.address,
    p.date_of_birth,
    p.age,
    p.sex,
    p.philhealth_member_no,
    MIN(md.dispensed_date) AS date_dispensed
FROM rhu_medicine_dispensed md
JOIN rhu_consultations c ON md.consultation_id = c.consultation_id
JOIN patients p ON c.patient_id = p.patient_id
WHERE 1=1
";

// Date filter (index-friendly, no DATE() wrapper)
if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND md.dispensed_date BETWEEN :from_date AND :to_date";
    $params['from_date'] = $from_date . " 00:00:00"; // start of day
    $params['to_date']   = $to_date . " 23:59:59";   // end of day
}

// Medicine filter
if (!empty($medicine)) {
    if (!is_array($medicine)) {
        $medicine = [$medicine]; // force into array if string
    }
    $placeholders = [];
    foreach ($medicine as $i => $med) {
        $ph = ":medicine_$i";
        $placeholders[] = $ph;
        $params["medicine_$i"] = $med;
    }
    $sql .= " AND md.medicine_name IN (" . implode(',', $placeholders) . ")";
    $medicine_list = $medicine; // Only selected medicines
} else {
    // Fetch all distinct medicines (for columns)
    $med_stmt = $pdo->query("SELECT DISTINCT medicine_name FROM rhu_medicine_dispensed ORDER BY medicine_name ASC");
    $medicine_list = $med_stmt->fetchAll(PDO::FETCH_COLUMN);
}



// 🔹 Medicine subcategory filter
// 🔹 Medicine subcategory filter
if (!empty($subcategory)) {
    if (!is_array($subcategory)) {
        $subcategory = [$subcategory]; // force into array if string
    }

    $placeholders = [];
    foreach ($subcategory as $i => $sub) {
        $ph = ":subcategory_$i";
        $placeholders[] = $ph;
        $params["subcategory_$i"] = $sub;
    }

    // Join to custom_options to get sub_category info
    $sql .= " AND md.medicine_name IN (
        SELECT value 
        FROM custom_options 
        WHERE sub_category IN (" . implode(',', $placeholders) . ")
    )";

    $subcategory_list = $subcategory; // only selected subcategories
} else {
    // 🔹 Fetch all distinct subcategories from custom_options
    $sub_stmt = $pdo->query("SELECT DISTINCT sub_category FROM custom_options WHERE category = 'medicine' ORDER BY sub_category ASC");
    $subcategory_list = $sub_stmt->fetchAll(PDO::FETCH_COLUMN);
}

// If the user did NOT explicitly select medicines but DID select subcategory(ies),
// limit the medicine_list to only medicines in those subcategories.
if (empty($medicine) && !empty($subcategory)) {
    // Use positional placeholders to fetch medicines for the selected subcategories
    $ph = implode(',', array_fill(0, count($subcategory), '?'));
    $med_sql = "
        SELECT DISTINCT co.value
        FROM custom_options co
        JOIN rhu_medicine_dispensed md ON md.medicine_name = co.value
        WHERE co.category = 'medicine'
          AND co.sub_category IN ($ph)
        ORDER BY co.value ASC
    ";
    $med_stmt = $pdo->prepare($med_sql);
    $med_stmt->execute($subcategory);
    $medicine_list = $med_stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Sex filter
if (!empty($sex)) {
    $sql .= " AND p.sex = :sex";
    $params['sex'] = $sex;
}

// Age group filter (compute from date_of_birth, safer than p.age column)
if (!empty($age_group)) {
    switch ($age_group) {
        case 'child':  
            $sql .= " AND TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) < 13"; 
            break;
        case 'teen':   
            $sql .= " AND TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) BETWEEN 13 AND 19"; 
            break;
        case 'adult':  
            $sql .= " AND TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) BETWEEN 20 AND 59"; 
            break;
        case 'senior': 
            $sql .= " AND TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) >= 60"; 
            break;
    }
}

// Barangay filter
if (!empty($barangay) && $barangay !== 'N/A') {
    $sql .= " AND p.address LIKE :barangay";
    $params['barangay'] = '%' . $barangay . '%';
}


$sql .= " GROUP BY p.patient_id ORDER BY p.last_name, p.first_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ensure $rows is populated with patient data for the charts
$rows = $patients;

// Initialize $periods and $medicine_series for the line graph
$periods = [];
$medicine_series = [];

// Only fetch dispensed data for selected medicines
if (!empty($medicine_list)) {
    // Prepare placeholders for selected medicines
    $med_placeholders = implode(',', array_fill(0, count($medicine_list), '?'));
    $dispensed_data_stmt = $pdo->prepare("
        SELECT DATE(dm.dispensed_date) AS period, dm.medicine_name, SUM(dm.quantity_dispensed) AS qty
        FROM rhu_medicine_dispensed dm
        WHERE dm.medicine_name IN ($med_placeholders)
        GROUP BY DATE(dm.dispensed_date), dm.medicine_name
        ORDER BY DATE(dm.dispensed_date)
    ");
    $dispensed_data_stmt->execute($medicine_list);
} else {
    // No filter, show all medicines
    $dispensed_data_stmt = $pdo->query("
        SELECT DATE(dm.dispensed_date) AS period, dm.medicine_name, SUM(dm.quantity_dispensed) AS qty
        FROM rhu_medicine_dispensed dm
        GROUP BY DATE(dm.dispensed_date), dm.medicine_name
        ORDER BY DATE(dm.dispensed_date)
    ");
}

while ($row = $dispensed_data_stmt->fetch(PDO::FETCH_ASSOC)) {
    $period = $row['period'];
    $medicine = $row['medicine_name'];
    $quantity = $row['qty'];

    if (!in_array($period, $periods)) {
        $periods[] = $period;
        // Add new period to all existing medicine_series arrays
        foreach ($medicine_series as &$series) {
            $series[$period] = 0;
        }
        unset($series);
    }
    if (!isset($medicine_series[$medicine])) {
        // Initialize with all periods so far
        $medicine_series[$medicine] = array_fill_keys($periods, 0);
    }
    $medicine_series[$medicine][$period] = $quantity;
}

// Filter $medicine_series to only selected medicines (if filter applied)
if (!empty($medicine_list)) {
    $medicine_series = array_intersect_key($medicine_series, array_flip($medicine_list));
}


// Initialize patient_meds
$patient_meds = [];
foreach ($patients as $p) {
    $key = $p['patient_id'];
    $patient_meds[$key] = [
        'row' => $p,
        'medicines' => array_fill_keys($medicine_list, 0)
    ];
}


// Get dispensed quantities
if (count($patient_meds) > 0) {
    $ids = array_keys($patient_meds);
    $id_placeholders = implode(',', array_fill(0, count($ids), '?'));
    $med_placeholders = implode(',', array_fill(0, count($medicine_list), '?'));

    $disp_sql = "
        SELECT p.patient_id, dm.medicine_name, dm.dispensed_date, SUM(dm.quantity_dispensed) AS qty
        FROM rhu_medicine_dispensed dm
        JOIN rhu_consultations c ON dm.consultation_id = c.consultation_id
        JOIN patients p ON c.patient_id = p.patient_id
        WHERE p.patient_id IN ($id_placeholders)
        " . (!empty($medicine_list) ? " AND dm.medicine_name IN ($med_placeholders)" : "") . "
        GROUP BY p.patient_id, dm.medicine_name
    ";

    $disp_stmt = $pdo->prepare($disp_sql);
    $disp_stmt->execute(array_merge($ids, $medicine_list));

    while ($disp_row = $disp_stmt->fetch(PDO::FETCH_ASSOC)) {
        $pid = $disp_row['patient_id'];
        if (isset($patient_meds[$pid]['medicines'][$disp_row['medicine_name']])) {
            $patient_meds[$pid]['medicines'][$disp_row['medicine_name']] = $disp_row['qty'];
        }
    }
}

        //ADDED GENERATED REPORT FOR ACTIVITY LOG
        $stmt_log = $pdo->prepare("INSERT INTO logs (
            user_id, action, performed_by
        ) VALUES (
            :user_id, :action, :performed_by
        )");
        $stmt_log->execute([
            ':user_id' => $_SESSION['user_id'],
            ':action' => "Generated RHU Medicine Dispensation Report",
            ':performed_by' => $_SESSION['user_id']
        ]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" href="../../img/logo.png">
	<link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
	<link rel="stylesheet" href="../css/reportsDesign.css">
    <link rel="stylesheet" href="../css/logout.css">
    	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

	<title>Medicine Utilization Report</title>
</head>
<body>


<!-- Sidebar Section -->
	<section id="sidebar">
		<a href="#" class="brand">
			<img src="../../img/logo.png" alt="RHULogo" class="logo">
			<span class="text">IHRRS</span>
		</a>
		<ul class="side-menu top">
			<li>
				<a href="../dashboard.html">
					<i class="bx bxs-dashboard"></i>
					<span class="text">Dashboard</span>
				</a>
			</li>
			<li>
				<a href= "../ITR.html">
					<i class="bx bxs-user"></i>
					<span class="text">Add New ITR</span>
				</a>
			</li>
			<li>
				<a href="../pending.html" id="updateReferrals">
					<i class="bx bxs-user"></i>
					<span class="text">Pending Referrals</span>
				</a>
			</li>
			
			<script>
			document.getElementById("updateReferrals").addEventListener("click", function (event) {
				event.preventDefault(); // Prevent default navigation
			
				fetch("../php/update_referrals.php") // Call PHP file
				.then(response => response.json())
				.then(data => {
					console.log(data.message); // Log success message (optional)
					window.location.href = "../pending.html"; // Redirect after updating
				})
				.catch(error => {
					console.error("Error updating referrals:", error);
					window.location.href = "../pending.html"; // Still redirect even if an error occurs
				});
			});
			</script>

				<li>
				<a href="../followUpConsultations.html">
					<i class="bx bxs-user"></i>
					<span class="text">Follow-Up Visits</span>
				</a>
			</li>
			
			<li>
				<a href="../searchPatient.html">
					<i class="bx bxs-notepad"></i>
					<span class="text">Patient Records</span>
				</a>
			</li>
			<li>
                <a href="../history.html">
                    <i class="bx bx-history"></i>
                    <span class="text">Referral History</span>
                </a>
            </li>
			<li class="active">
				<a href="../reports.html">
					<i class="bx bx-notepad"></i>
					<span class="text">Reports</span>
				</a>	
			</li>
		</ul>
		<ul class="side-menu">
			<li>
				<a href="#" class="logout" onclick="return confirmLogout()">
               <i class="bx bxs-log-out-circle"></i>
                <span class="text">Logout</span>
                </a>			
			</li>
		</ul>
	</section>
	<!-- Main Content Section -->
	<section id="content">
		 <nav>
			<form action="#">
				
			</form>
			<div class="greeting">
                <span id="userGreeting">Hello Nurse!</span>
            </div>
			<a href="#" class="profile">
				<img src="../../img/nurse.png">
			</a>
		</nav>



		<main>
            
            <div class="head-title">
                <div class="left">
                  <h1>Medicine Utilization</h1>
                  <ul class="breadcrumb">
                    <li><a href="#">Referral Intake Summary Report</a></li>
                    <li><i class="bx bx-chevron-right"></i></li>
                    <li><a class="active" href="#" onclick="history.back(); return false;">Go back</a></li>
                  </ul>
                </div>
              </div>

      <br> <br>
            </div>

<div class="history-container">


<!-- Filter Form -->

<form method="GET" class="filter-form">
      <h2>Medicine Utilization Report - <?php echo htmlspecialchars($rhu); ?></h2> <br>
       

    <!-- Filter Modal Trigger -->
   
        <div class="form-submit" style="margin-top: -10px;">
        <button type="button" class="btn-export" id="openFilterModal">Select Filters</button>
    </div>

    <!-- Modern Filter Tags Display -->
    <div class="selected-filters" style="margin: 20px 0;">
        <h3 style="margin-bottom: 10px;"><i class="bx bx-filter-alt"></i> Selected Filters:</h3>
        <div id="filterTags" style="display: flex; flex-wrap: wrap; gap: 8px;">
            <?php
            // Helper for tag rendering
            function renderTag($label, $param, $value) {
                $display = htmlspecialchars($label . ': ' . $value);
                $url = $_GET;

                // Special handling for multi-value filters like medicine[]
                if (substr($param, -2) === '[]') {
                    $base = substr($param, 0, -2);
                    if (isset($url[$base]) && is_array($url[$base])) {
                        // Remove only the specific value
                        $url[$base] = array_values(array_diff($url[$base], [$value]));
                        // If empty, unset to avoid empty param in URL
                        if (empty($url[$base])) unset($url[$base]);
                    }
                    // For building query, use param[] syntax
                    $query = http_build_query($url);
                    // Fix for [] in query string
                    $query = preg_replace('/%5B\d+%5D=/', '%5B%5D=', $query);
                } else {
                    unset($url[$param]);
                    $query = http_build_query($url);
                }

                echo '<span class="filter-tag" style="background:#e3e6ea;color:#222;padding:6px 12px;border-radius:16px;display:inline-flex;align-items:center;font-size:14px;">';
                echo $display;
                echo ' <a href="?' . $query . '" style="margin-left:8px;color:#888;text-decoration:none;font-weight:bold;" title="Remove filter">&times;</a>';
                echo '</span>';
            }

            // Render tags for each filter if set
            if ($from_date) renderTag('From', 'from_date', $from_date);
            if ($to_date) renderTag('To', 'to_date', $to_date);
            if ($sex) renderTag('Sex', 'sex', $sex);
          
            if ($age_group) {
                $age_labels = [
                    'child' => 'Child (0–12)', 'teen' => 'Teen (13–19)',
                    'adult' => 'Adult (20–59)', 'senior' => 'Senior (60+)'
                ];
                renderTag('Age Group', 'age_group', $age_labels[$age_group] ?? ucfirst($age_group));
            }
           if (!empty($_GET['medicine'])) {
    foreach ((array)$_GET['medicine'] as $med) {
        renderTag('Medicine', 'medicine[]', $med);
    }
}
           if (!empty($_GET['subcategory'])) {
    foreach ((array)$_GET['subcategory'] as $med) {
        renderTag('Medicine Type', 'subcategory[]', $med);
    }
}

            if ($barangay) renderTag('Barangay', 'purok', $barangay);
           
            if (
                !$from_date && !$to_date && !$sex && !$age_group &&
                !$medicine && !$barangay && !$subcategory
            ) {
                echo '<span style="color:#888;">None</span>';
            }
            ?>
        </div>
    </div>
    <style>
        .filter-tag a:hover { color: #e15759; }
    </style>


  <!-- Filter Modal -->
    <div id="filterModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>Apply Filters</h3>
            </div>
            <form method="GET" id="filterForm">
                <div class="modal-body">
                    <div class="form-row">
                           <!-- From Date -->
                        <div class="form-item">
                            <label for="from_date">From:</label>
                            <input type="date" name="from_date" id="from_date" class="form-control" value="<?= $from_date ? htmlspecialchars($from_date) : '' ?>"  placeholder="Select date">
                        </div>
                        <!-- To Date -->
                        <div class="form-item">
                            <label for="to_date">To:</label>
                            <input type="date" name="to_date" id="to_date" class="form-control" value="<?= $to_date ? htmlspecialchars($to_date) : '' ?>"  placeholder="Select date">
                        </div>
                        <!--
                        <div class="form-item">
                            <label for="sex">Sex:</label>
                            <select name="sex" id="sex" class="form-control">
                                <option value="" <?= $sex == '' ? 'selected' : '' ?>>All</option>
                                <option value="Male" <?= $sex == 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $sex == 'Female' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>
                        <div class="form-item">
                            <label for="age_group">Age Group:</label>
                            <select name="age_group" id="age_group" class="form-control">
                                <option value="">All</option> 
                                <option value="child" <?= $age_group == 'child' ? 'selected' : '' ?>>Child (0–12)</option>
                                <option value="teen" <?= $age_group == 'teen' ? 'selected' : '' ?>>Teen (13–19)</option>
                                <option value="adult" <?= $age_group == 'adult' ? 'selected' : '' ?>>Adult (20–59)</option>
                                <option value="senior" <?= $age_group == 'senior' ? 'selected' : '' ?>>Senior (60+)</option>
                            </select> </div>
                            
                      <div class="form-item" style="margin-top: -140px;">
                            <label for="purok">Barangay:</label>
                            <select name="purok" id="purok" class="form-control">
                                <option value="">All</option>
                                <?php
                                // Fetch distinct barangay names from custom_options
                                $barangay_stmt = $pdo->prepare("SELECT DISTINCT category FROM custom_options WHERE category LIKE 'Barangay%' ORDER BY category");
                                $barangay_stmt->execute();
                                $selected_purok = $_GET['purok'] ?? '';
                                while ($row = $barangay_stmt->fetch()) {
                                    $value = $row['category'];
                                    $selected = ($selected_purok === $value) ? 'selected' : '';
                                    echo "<option value=\"" . htmlspecialchars($value) . "\" $selected>" . htmlspecialchars($value) . "</option>";
                                }
                                ?>
                            </select>
                        </div>  -->

                    <div class="form-item">
                        <label for="medicine">Given Medicine:</label>
                        <div id="medicine-checkboxes" style="max-height:150px;overflow-y:auto;border:1px solid #ccc;padding:8px;border-radius:6px;">
                            <?php
                            // Fetch medicines for checkboxes
                            $medicine_stmt = $pdo->prepare("SELECT DISTINCT medicine_name FROM rhu_medicine_dispensed ORDER BY medicine_name ASC");
                            $medicine_stmt->execute();
                            // Support multiple selection from GET
                            $selected_medicines = isset($_GET['medicine']) ? (array)$_GET['medicine'] : [];
                          while ($row = $medicine_stmt->fetch(PDO::FETCH_ASSOC)) {
    $value = $row['medicine_name'];  // correct column
    $checked = in_array($value, $selected_medicines) ? 'checked' : '';
    echo '<label style="display:block;margin-bottom:4px;text-align:left;font-weight:300;">';
    echo '<input type="checkbox" name="medicine[]" value="' . htmlspecialchars($value) . '" ' . $checked . '> ';
    echo htmlspecialchars($value);
    echo '</label>';
}

                            ?>
                        </div>
                        <small style="color:#888;">You may select multiple medicines.</small>
                    </div>

                   <div class="form-item">
                        <label for="subcategory">Medicine Category:</label>
                        <div id="medicine-checkboxes" style="max-height:150px;overflow-y:auto;border:1px solid #ccc;padding:8px;border-radius:6px;">
                            <?php
                            // Fetch subcategory for checkboxes
                            $subcategory_stmt = $pdo->prepare("SELECT DISTINCT sub_category FROM custom_options WHERE category = 'medicine' ORDER BY sub_category ASC");
                            $subcategory_stmt->execute();
                            // Support multiple selection from GET
                            $selected_subcategory = isset($_GET['subcategory']) ? (array)$_GET['subcategory'] : [];
                          while ($row = $subcategory_stmt->fetch(PDO::FETCH_ASSOC)) {
    $value = $row['sub_category'];  // correct column
    $checked = in_array($value, $selected_subcategory) ? 'checked' : '';
    echo '<label style="display:block;margin-bottom:4px;text-align:left;font-weight:300;">';
    echo '<input type="checkbox" name="subcategory[]" value="' . htmlspecialchars($value) . '" ' . $checked . '> ';
    echo htmlspecialchars($value);
    echo '</label>';
}

                            ?>
                        </div>
                        <small style="color:#888;">You may select multiple types.</small>
                    </div>

                        </div>
                    </div>
                     <div class="modal-footer" style="text-align:right;">
                    <button type="button" class="btn" id="closeFilterModal">Cancel</button>
                    <button type="submit" class="btn-submit">Apply Filter</button>
                </div>
              
               
            </form>
              </div>
        </div>



    <script>
    // Modal logic for filter
    document.getElementById('openFilterModal').onclick = function() {
        document.getElementById('filterModal').style.display = 'block';
    };

       // Initialize Flatpickr AFTER the modal is visible
    setTimeout(() => {
        // Check if Flatpickr instances already exist, destroy them first
        const fromDateInput = document.getElementById('from_date');
        const toDateInput = document.getElementById('to_date');
        
        if (fromDateInput._flatpickr) {
            fromDateInput._flatpickr.destroy();
        }
        if (toDateInput._flatpickr) {
            toDateInput._flatpickr.destroy();
        }
        
        // Initialize Flatpickr on visible elements
        flatpickr("#from_date", {
            dateFormat: "Y-m-d",
            allowInput: true,
            disableMobile: true
        });
        
        flatpickr("#to_date", {
            dateFormat: "Y-m-d",
            allowInput: true,
            disableMobile: true
        });
    }, 100); // Small delay to ensure modal is fully visible

    document.getElementById('closeFilterModal').onclick = function() {
        document.getElementById('filterModal').style.display = 'none';
    };
    // Submit modal form
    document.getElementById('filterForm').onsubmit = function() {
        document.getElementById('filterModal').style.display = 'none';
        return true; // allow form submit
    };
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        var modal = document.getElementById('filterModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
    </script>
</form>







<div class="main-content">


<div class="print-area">
<!-- Two-logo letterhead -->
<div class="print-letterhead">
  <img src="../../img/daet_logo.png" alt="Left Logo" class="print-logo">
  <div class="print-heading">
    <div class="ph-line-1">Republic of the Philippines</div>
    <div class="ph-line-1">Department of Health</div>
    <div class="ph-line-1">Province of Camarines Norte</div>
    <div class="ph-line-2">Municipality of Daet</div>
    <div class="ph-line-3"><?php echo htmlspecialchars($rhu); ?></div>
   
  </div>
  <img src="../../img/mho_logo.png" alt="Right Logo" class="print-logo">
</div>
<hr class="print-rule">


<div class="report-content">

<div class="title">
     <h2>DOH MAINTAINANCE MEDICINE UTILIZATION REPORT</h2>
    <div class="print-sub">
      (<?php
        $filters = [];
                         if ($from_date || $to_date) {
    $readable_from = $from_date ? date("F j, Y", strtotime($from_date)) : '';
    $readable_to   = $to_date ? date("F j, Y", strtotime($to_date)) : '';

    // Combine them in a single display
    $filters[] = "<strong>" . trim($readable_from . ($readable_to ? " — " . $readable_to : '')) . "</strong>";
} 
        if (!empty($_GET['medicine'])) {
          $medicine_list = is_array($_GET['medicine']) ? $_GET['medicine'] : [$_GET['medicine']];
          $filters[] = "Medicine: <strong>" . implode(', ', array_map('htmlspecialchars', $medicine_list)) . "</strong>";
        }
        if (!empty($_GET['subcategory'])) {
          $subcategory_list = is_array($_GET['subcategory']) ? $_GET['subcategory'] : [$_GET['subcategory']];
          $filters[] = "Medicine Type: <strong>" . implode(', ', array_map('htmlspecialchars', $subcategory_list)) . "</strong>";
        }
        if ($sex) $filters[] = "Sex: <strong>" . htmlspecialchars($sex) . "</strong>";
        if ($age_group) {
          $age_labels = [
            'child' => 'Child (0–12)', 'teen' => 'Teen (13–19)',
            'adult' => 'Adult (20–59)', 'senior' => 'Senior (60+)'
          ];
          $filters[] = "Age Group: <strong>" . ($age_labels[$age_group] ?? htmlspecialchars($age_group)) . "</strong>";
        }
        if ($barangay) $filters[] = "Barangay: <strong>" . htmlspecialchars($barangay) . "</strong>";
        echo $filters ? implode("&nbsp; | &nbsp;", $filters) : "All Records";
      ?>)
    </div>
</div>

    <style>
  .print-letterhead { display: none; }
    .title { text-align: center; display: none;}

  @media print {
     .title {
        display: block;
    }
    .print-letterhead { display: block; }

    .print-letterhead{
  display: grid;
  grid-template-columns: 72px auto 72px;  /* widened logo columns */
  align-items: center;
  justify-content: center;
  column-gap: 60px;                       /* increased space between logos and heading */
  margin: 0 auto 18px;
  text-align: center;
  width: fit-content;
    }
    .print-logo{ width:64px; height:64px; object-fit:contain; }
    .print-heading{ line-height:1.1; color:#000; }
    .print-heading .ph-line-1{ font-size:12pt; font-weight:500;}
    .print-heading .ph-line-2{ font-size:12pt; font-weight:500;}
    .print-heading .ph-line-3{ font-size:12pt; font-weight:600;}
    .print-heading .ph-line-4{ font-size:12pt; font-weight:600; margin-top:15px; letter-spacing:.3px; }
    .print-sub{ font-size:12pt; margin-top:4px; }
    .print-rule{ height:1px; border:0; background:#cfd8e3; margin:8px 0 12px; }

    /* keep your existing print hides working */
    .chart-title, .form-submit { display: none !important; }
  }
</style>

<style>

    /* Add breathing room above the summary */
.summary-container {
  margin-top: 32px;
}

/* Two-column summary table */
.summary-table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
  font-size: 16px;
}

.summary-table th,
.summary-table td {
  border: 1px solid #d5d7db;
  padding: 8px 12px;
  vertical-align: top;
  text-align: left;
  word-wrap: break-word;
}

.summary-table th {
  background: #f2f4f7;
  font-weight: 600;
}

/* Hide the “Summary” title on print only; keep spacing a bit larger */
@media print {
  .summary > h3 { 
    display: none !important;
  }
  .summary-container { 
    margin-top: 40px; 
  }
}

    @media print {
        .chart-title { 
           display: none;
        }
         .form-submit { 
           display: none;
        }
        
    .report-table-container {
        margin-top: -20px !important;
        margin-bottom: 40px !important;
        }
      
    }

   #generated_by {
  display: block;           
  margin: 22px 0 0 48px;    
  color: #000;
}

#generated_by .sig-label {
  font-size: 14px;
  margin-bottom: 16px;
}

#generated_by .sig-line {
  width: 250px;           
  border: 0;
  border-top: 1.5px solid #000;
  margin: 26px 0 6px;       
}

#generated_by .sig-name {
  font-weight: 600;
  font-size: 16px;
  margin-top: 4px;
}

#generated_by .sig-title {
  font-size: 13px;
  color: #333;
}

/* Print sizing (optional, nicer on paper) */
@media print {
  #generated_by {  margin: 20mm 0 0 0;}
  #generated_by .sig-label { font-size: 12pt; }
  #generated_by .sig-name  { font-size: 12pt; }
  #generated_by .sig-title { font-size: 11pt; }
  #generated_by .sig-line  { width: 45mm; border-top-width: 1px; margin: 10mm 0 3mm; }
}
</style>


<!-- Chart Visibility Controls 
<div style="margin: 20px;" class="chart-title">
    <h3>Charts:</h3>
    <label><input type="checkbox" id="toggleSexChart"> Show Patients by Sex</label> <br>
    <label><input type="checkbox" id="toggleAgeGroupChart"> Show Age Group</label> <br>
    <label><input type="checkbox" id="toggleBarangayChart"> Show Barangays</label> <br> -->


</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const chartMapping = {
        toggleSexChart: "sexChart",
        toggleAgeGroupChart: "ageGroupChart",
        toggleBarangayChart: "barangayChart"
    };

    Object.keys(chartMapping).forEach(toggleId => {
        const checkbox = document.getElementById(toggleId);
        const chartElement = document.getElementById(chartMapping[toggleId]);

        if (checkbox && chartElement) {
            checkbox.addEventListener("change", () => {
                chartElement.style.display = checkbox.checked ? "block" : "none";
            });

            // Initialize state
            chartElement.style.display = checkbox.checked ? "block" : "none";
        }
    });
});
</script>


    <!-- Pie Chart Section -->
    <div id="sexChart" style="max-width: 400px; margin: 30px auto 0 auto; text-align:center; display: none;">
      <h3 class="chart-title">Patients by Sex</h3>
        <canvas id="sexPieChart"></canvas>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Prepare data for the pie chart (Sex distribution)
        <?php
            $sex_counts = ['Male' => 0, 'Female' => 0];
            foreach ($rows as $row) {
                if (isset($sex_counts[$row['sex']])) {
                    $sex_counts[$row['sex']]++;
                }
            }
        ?>
        const sexLabels = <?= json_encode(array_keys($sex_counts)) ?>;
        const sexData = <?= json_encode(array_values($sex_counts)) ?>;

        if (sexData.reduce((a, b) => a + b, 0) > 0) {
            const ctx = document.getElementById('sexPieChart').getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: sexLabels,
                    datasets: [{
                        data: sexData,
                        backgroundColor: ['#4e79a7', '#f28e2b'],
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' },
                        title: { display: false }
                    }
                }
            });
        }
    </script>

    <br><br>
    <!-- Age Group Distribution Bar Chart -->
    <div id="ageGroupChart" style="max-width: 500px; margin: 30px auto 0 auto; text-align:center; display: none;">
        <h3 class="chart-title">Age Groups</h3>
        <canvas id="ageGroupBarChart"></canvas>
    </div>
    <script>
        <?php
        $age_group_counts = [
            '0–12' => 0,
            '13–19' => 0,
            '20–59' => 0,
            '60+' => 0
        ];
        foreach ($rows as $row) {
            $age = (int)$row['age'];
            if ($age >= 0 && $age <= 12) {
                $age_group_counts['0–12']++;
            } elseif ($age >= 13 && $age <= 19) {
                $age_group_counts['13–19']++;
            } elseif ($age >= 20 && $age <= 59) {
                $age_group_counts['20–59']++;
            } elseif ($age >= 60) {
                $age_group_counts['60+']++;
            }
        }
        ?>
        const ageGroupLabels = <?= json_encode(array_keys($age_group_counts)) ?>;
        const ageGroupData = <?= json_encode(array_values($age_group_counts)) ?>;

        if (ageGroupData.reduce((a, b) => a + b, 0) > 0) {
            const ctxBar = document.getElementById('ageGroupBarChart').getContext('2d');
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: ageGroupLabels,
                    datasets: [{
                        label: 'Patient Count',
                        data: ageGroupData,
                        backgroundColor: ['#4e79a7', '#f28e2b', '#e15759', '#76b7b2'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        title: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Patient Count' }
                        },
                        x: {
                            title: { display: true, text: 'Age Group' }
                        }
                    }
                }
            });
        }
    </script>

    <br><br>
    <!-- Address Distribution Bar Chart -->
    <div id="barangayChart" style="max-width: 500px; margin: 30px auto 0 auto; text-align:center; display: none;">
      <h3 class="chart-title">Patient Counts per Barangay</h3>
        <canvas id="barangayBarChart"></canvas>
    </div>
    <script>
        <?php
        // Prepare barangay counts based on filtered patients (unique by patient_id)
        $barangay_counts = [];
        $unique_patients_address = [];
        foreach ($rows as $row) {
            // Use patient_id if available, otherwise fallback to full_name+dob for uniqueness
            $unique_key = ($row['full_name'] ?? '') . ($row['date_of_birth'] ?? '');
            if (!isset($unique_patients_address[$unique_key])) {
                // Extract barangay from the address (same logic as patient_summary_report.php)
                $address_parts = explode(' - ', $row['address']);
                $barangay = isset($address_parts[1]) ? explode(' ', $address_parts[1])[1] : 'Unknown';
                $barangay_counts[$barangay] = ($barangay_counts[$barangay] ?? 0) + 1;
                $unique_patients_address[$unique_key] = true;
            }
        }
        ?>
        const barangayLabels = <?= json_encode(array_keys($barangay_counts)) ?>;
        const barangayData = <?= json_encode(array_values($barangay_counts)) ?>;

        if (barangayData.reduce((a, b) => a + b, 0) > 0) {
            const ctxBarangay = document.getElementById('barangayBarChart').getContext('2d');
            new Chart(ctxBarangay, {
                type: 'bar',
                data: {
                    labels: barangayLabels,
                    datasets: [{
                        label: 'Patient Count',
                        data: barangayData,
                        backgroundColor: '#76b7b2',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        title: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Patient Count' }
                        },
                        x: {
                            title: { display: true, text: 'Barangay' }
                        }
                    }
                }
            });
        }
    </script>
    <!-- Line Graph: Quantity of Dispensed Medicines Over Time -->
<div style="max-width: 700px; margin: 30px auto 0 auto; text-align:center;">
   <h3 class="chart-title">Quantity of Dispensed Medicines Over Time</h3>
    <canvas id="medicineLineChart"></canvas>
</div>
<script>
    // Prepare datasets for line graph
    const lineLabels = <?= json_encode($periods) ?>;
    const medicineLineDatasets = [
        <?php foreach ($medicine_series as $med => $series): ?>
        {
            label: <?= json_encode($med) ?>,
            data: <?= json_encode(array_values($series)) ?>,
            fill: false,
            borderColor: '#' + Math.floor(Math.random()*16777215).toString(16),
            tension: 0.1
        },
        <?php endforeach; ?>
    ];

    if (lineLabels.length > 0) {
        const ctxLine = document.getElementById('medicineLineChart').getContext('2d');
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: lineLabels,
                datasets: medicineLineDatasets
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Quantity Dispensed' }
                    },
                    x: {
                        title: { display: true, text: 'Period' }
                    }
                }
            }
        });
    }
</script>

<!-- Patient Table -->
<div class="report-table-container">
<table id="reportTable" border="1" cellpadding="8" cellspacing="0"> 
<thead>
        <tr>
            <th>Date Given</th>
            <th>Patient Name</th>
            <th>Address</th>
            <th>Age</th>
            <th>Date of Birth</th>
            <th>Gender</th>
            <th>PhilHealth No.</th>
            <?php foreach ($medicine_list as $med): ?>
            <th><?= htmlspecialchars($med) ?></th>
            <?php endforeach; ?>
           
        </tr>
    </thead>

   <?php
// Sort visits (patient meds) from latest to oldest by nested date_dispensed
usort($patient_meds, function($a, $b) {
    return strtotime($b['row']['date_dispensed']) - strtotime($a['row']['date_dispensed']);
});
?>
<tbody>
    <?php foreach ($patient_meds as $pm): $row = $pm['row']; ?>
    <tr>
        <td><?= htmlspecialchars($row['date_dispensed']) ?></td>
        <td><?= htmlspecialchars($row['full_name']) ?></td>
        <td><?= htmlspecialchars($row['address']) ?></td>
        <td><?= htmlspecialchars($row['age']) ?></td>
        <td><?= htmlspecialchars($row['date_of_birth']) ?></td>
        <td><?= htmlspecialchars($row['sex']) ?></td>
        <td><?= htmlspecialchars(!empty($row['philhealth_member_no']) ? $row['philhealth_member_no'] : 'N/A') ?></td>
        <?php foreach ($medicine_list as $med): ?>
            <td><?= htmlspecialchars($pm['medicines'][$med] ?? '') ?></td>
        <?php endforeach; ?>
       
    </tr>
    <?php endforeach; ?>
</tbody>

</table>
<br> <br>
</div> 

<div class="summary-container">
  <div class="summary">
    <h3><i class="bx bx-file"></i> Summary</h3>

    <table class="summary-table">
      <colgroup>
        <col style="width:30%">
        <col style="width:70%">
      </colgroup>
      <tbody>
        <tr>
          <th>Report Generated On</th>
          <td><?= date('F j, Y g:i:s A') ?></td>
        </tr>
        <tr>
          <th>Total Patients in Report</th>
          <td><?= count($rows) ?></td>
        </tr>

      </tbody>
    </table>

   <table style="width:100%; border-collapse:collapse; margin-top:12px;">
  <thead>
    <tr>
      <th colspan="2" style="border:1px solid #d5d7db; padding:10px; background:#f8f9fa;">By Sex</th>
      <th colspan="2" style="border:1px solid #d5d7db; padding:10px; background:#f8f9fa;">By Age Group</th>
    </tr>
    <tr>
      <th style="border:1px solid #d5d7db; padding:6px;">Sex</th>
      <th style="border:1px solid #d5d7db; padding:6px;">Count</th>
      <th style="border:1px solid #d5d7db; padding:6px;">Age Group</th>
      <th style="border:1px solid #d5d7db; padding:6px;">Count</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="border:1px solid #d5d7db; padding:6px;">Male</td>
      <td style="border:1px solid #d5d7db; padding:6px; text-align:center;">
        <?= $sex_counts['Male'] ?? 0 ?>
      </td>
      <td style="border:1px solid #d5d7db; padding:6px;">Children (0–12)</td>
      <td style="border:1px solid #d5d7db; padding:6px; text-align:center;">
        <?= $age_group_counts['0–12'] ?? 0 ?>
      </td>
    </tr>
    <tr>
      <td style="border:1px solid #d5d7db; padding:6px;">Female</td>
      <td style="border:1px solid #d5d7db; padding:6px; text-align:center;">
        <?= $sex_counts['Female'] ?? 0 ?>
      </td>
      <td style="border:1px solid #d5d7db; padding:6px;">Teens (13–19)</td>
      <td style="border:1px solid #d5d7db; padding:6px; text-align:center;">
        <?= $age_group_counts['13–19'] ?? 0 ?>
      </td>
    </tr>
    <tr>
      <td style="border:1px solid #d5d7db; padding:6px;"></td>
      <td style="border:1px solid #d5d7db; padding:6px;"></td>
      <td style="border:1px solid #d5d7db; padding:6px;">Adults (20–59)</td>
      <td style="border:1px solid #d5d7db; padding:6px; text-align:center;">
        <?= $age_group_counts['20–59'] ?? 0 ?>
      </td>
    </tr>
    <tr>
      <td style="border:1px solid #d5d7db; padding:6px;"></td>
      <td style="border:1px solid #d5d7db; padding:6px;"></td>
      <td style="border:1px solid #d5d7db; padding:6px;">Seniors (60+)</td>
      <td style="border:1px solid #d5d7db; padding:6px; text-align:center;">
        <?= $age_group_counts['60+'] ?? 0 ?>
      </td>
    </tr>
  </tbody>
</table>


    <!-- Keep your “Dispensed Medicines” block just below if you want -->
    <div style="margin-top:12px;">
      <strong style="font-size: 12pt;">Dispensed Medicines:</strong>
      <?php if (!empty($medicine_list)): ?>
        <table class="summary-table" style="margin-top:8px;">
          <colgroup>
            <col style="width:70%">
            <col style="width:30%">
          </colgroup>
          <thead>
            <tr>
              <th>Medicine</th>
              <th>Total Dispensed</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($medicine_list as $medicine):
              $total_dispensed = 0;
              foreach ($patient_meds as $pm) {
                $total_dispensed += $pm['medicines'][$medicine] ?? 0;
              }
              if ($total_dispensed > 0): ?>
                <tr>
                  <td><?= htmlspecialchars($medicine) ?></td>
                  <td><?= $total_dispensed ?></td>
                </tr>
            <?php endif; endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        All Medicines
      <?php endif; ?>
    </div>
  </div>
  
<span id="generated_by"></span>
</div>


<!-- Print Button at Bottom -->
   <div class="form-submit">
          <button type="button" class="btn-export" onclick="exportTableToExcel('reportTable')">Export to Excel</button>
        <button type="button" class="btn-export" onclick="exportTableToPDF()">Export to PDF</button>
       
    <button type="button" class="btn-print" onclick="printDiv()">
        <i class='bx bx-printer'></i>
        Print Report
    </button>
</div>


</div> 
</div>
<div id="logoutModal" class="logout-modal">
    <div class="logout-modal-content">
        <div class="logout-modal-header">
            <h3>Confirm Logout</h3>
        </div>
        <div class="logout-modal-body">
            <p>Are you sure you want to logout?</p>
        </div>
        <div class="logout-modal-footer">
            <button onclick="closeModal()" class="logout-cancel-btn">Cancel</button>
            <button onclick="proceedLogout()" class="logout-confirm-btn">Yes, Logout</button>
        </div>
    </div>
</div>

</div>
<!-- jsPDF and html2canvas libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
function exportTableToExcel(tableID, filename = 'Medicine Utilization Report') {
    try {
        // Create a temporary div with the same content as print
        const tempDiv = document.createElement('div');
        tempDiv.style.position = 'absolute';
        tempDiv.style.left = '-9999px';
        tempDiv.style.top = '-9999px';
        
        // Clone the print header
        const printHeader = document.querySelector('.print-header');
        if (printHeader) {
            const headerClone = printHeader.cloneNode(true);
            // Remove any scripts or interactive elements
            const scripts = headerClone.querySelectorAll('script');
            scripts.forEach(script => script.remove());
            tempDiv.appendChild(headerClone);
        }
        
        // Clone the summary section
        const summary = document.querySelector('.summary-container');
        if ($summary) {
            const summaryClone = summary.cloneNode(true);
            const summaryScripts = summaryClone.querySelectorAll('script');
            summaryScripts.forEach(script => script.remove());
            tempDiv.appendChild(summaryClone);
        }
        
        // Clone and modify the table to include signature column
        const originalTable = document.getElementById(tableID);
        if (!originalTable) {
            alert('Table not found!');
            return;
        }
        
        const tableClone = originalTable.cloneNode(true);
        
        // Add signature header if not present
        const headerRow = tableClone.querySelector('thead tr');
    
        
        tempDiv.appendChild(tableClone);
        document.body.appendChild(tempDiv);
        
        // Create HTML content for Excel
        const htmlContent = `
            <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <meta charset="UTF-8">
                <!--[if gte mso 9]>
                <xml>
                    <x:ExcelWorkbook>
                        <x:ExcelWorksheets>
                            <x:ExcelWorksheet>
                                <x:Name>Report</x:Name>
                                <x:WorksheetOptions>
                                    <x:DisplayGridlines/>
                                </x:WorksheetOptions>
                            </x:ExcelWorksheet>
                        </x:ExcelWorksheets>
                    </x:ExcelWorkbook>
                </xml>
                <![endif]-->
            </head>
            <body>
                ${tempDiv.innerHTML}
            </body>
            </html>
        `;
        
        // Create blob and download
        const blob = new Blob([htmlContent], {
            type: 'application/vnd.ms-excel'
        });
        
        const downloadLink = document.createElement('a');
        downloadLink.href = URL.createObjectURL(blob);
        downloadLink.download = filename + '.xls';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        
        // Clean up
        setTimeout(() => {
            document.body.removeChild(downloadLink);
            document.body.removeChild(tempDiv);
            URL.revokeObjectURL(downloadLink.href);
        }, 100);
        
    } catch (error) {
        console.error('Excel export error:', error);
        alert('Error exporting to Excel: ' + error.message);
    }
}

async function exportTableToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    const table = document.getElementById('reportTable');

    await html2canvas(table).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const imgProps = doc.getImageProperties(imgData);
        const pdfWidth = doc.internal.pageSize.getWidth();
        const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

        doc.addImage(imgData, 'PNG', 10, 10, pdfWidth - 20, pdfHeight);
        doc.save("report.pdf");
    });
}



//PRINT
function printDiv() {
  // Use the new header
  const headerEl = document.querySelector('.print-letterhead');
  const printHeader = headerEl ? headerEl.outerHTML : '';

  // Clone the print area
  const originalArea = document.querySelector(".print-area");
  if (!originalArea) return;
  const clone = originalArea.cloneNode(true);

  // Remove duplicate header inside the clone
  const headerInClone = clone.querySelector('.print-letterhead');
  if (headerInClone) headerInClone.remove();

  // Remove canvases from the clone
  clone.querySelectorAll('canvas').forEach(c => c.remove());

  // Open print window
  const w = window.open('', '', 'height=900,width=1100');
  w.document.write(`
    <html>
 <head>
        <title>Print Report</title>
        <meta charset="utf-8" />
        <style>
          body { font-family: Arial, sans-serif; font-size: 12px; color: black; }
          table { width: 100%; border-collapse: collapse; }
          th, td { border: 1px solid #000; padding: 4px; text-align: left; }
          thead { background-color: #f0f0f0; }
          img { display: block; margin: 0 auto; max-width: 100%; height: auto; }
          h3 { margin: 10px 0 5px 0; }

          /* same print-only rules inside the print window */
          .print-only { display: block; }
          .print-letterhead{
            display:grid; grid-template-columns:64px auto 64px;
            align-items:center; justify-content:center; column-gap:14px;
            margin:0 auto 10px; text-align:center; width:fit-content;
          }
          .print-logo{ width:64px; height:64px; object-fit:contain; }
          .print-heading{ line-height:1.1; color:#000; }
          .print-heading .ph-line-1{ font-size:12pt; font-weight:500; }
          .print-heading .ph-line-2{ font-size:12pt; font-weight:800; }
          .print-heading .ph-line-3{ font-size:12pt; font-weight:500; }
          .print-heading .ph-line-4{ font-size:12pt; font-weight:800; margin-top:4px; letter-spacing:.3px; }
          .print-sub{ font-size:12pt; margin-top:4px; }
          .print-rule{ height:1px; border:0; background:#cfd8e3; margin:8px 0 12px; }
        </style>
      </head>
      <body>
        ${printHeader}
        ${clone.innerHTML}
      </body>
    </html>
  `);
  w.document.close();
  w.focus();
  setTimeout(() => { w.print(); w.close(); }, 500);
}



document.addEventListener('DOMContentLoaded', () => {
  fetch('../php/getUserName.php')
    .then(r => r.json())
    .then(data => {
      const fullName = (data && data.full_name) ? data.full_name : '';

      // Greeting (keep current behavior)
      document.getElementById('userGreeting').textContent =
        fullName ? `Hello, ${fullName}!` : 'Hello, User!';

      // Build the signature block
      const gb = document.getElementById('generated_by');
      gb.innerHTML = `
        <div class="sig-label">Report Generated by:</div>
        <hr class="sig-line">
        <div class="sig-name"></div>
        <div class="sig-title">Nursing Attendant</div>
      `;
      gb.querySelector('.sig-name').textContent = fullName || '________________';
    })
    .catch(() => {
      document.getElementById('userGreeting').textContent = 'Hello, User!';
      const gb = document.getElementById('generated_by');
      gb.innerHTML = `
        <div class="sig-label">Report Generated by:</div>
        <hr class="sig-line">
        <div class="sig-name">________________</div>
        <div class="sig-title">Nursing Attendant</div>
      `;
    });
});
// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('logoutModal');
    if (event.target == modal) {
        closeModal();
    }
}

    function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'block';
    return false; // Prevent the default link behavior
}

function closeModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

function proceedLogout() {
   window.location.href = '../../ADMIN/php/logout.php'; 
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('logoutModal');
    if (event.target == modal) {
        closeModal();
    }
}


	// Check if user is logged in
fetch('../php/getUserId.php')
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            // User is not logged in, redirect to role selection page
            window.location.href = '../role.html';
        }
    })
    .catch(error => {
        console.error('Error checking session:', error);
        window.location.href = '../role.html';
    });

</script>




</body>
</html>
