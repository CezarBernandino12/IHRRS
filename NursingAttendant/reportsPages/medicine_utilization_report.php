<?php
require '../../php/db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "User is not logged in.";
    exit;
}

$userId = $_SESSION['user_id'];

$from_date = $_GET['from_date'] ?? '';
$to_date   = $_GET['to_date'] ?? '';
$medicine = isset($_GET['medicine']) ? (array)$_GET['medicine'] : [];
$sex       = $_GET['sex'] ?? '';
$age_group = $_GET['age_group'] ?? '';
$barangay  = $_GET['purok'] ?? ''; // Use 'purok' for barangay filter

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
    p.philhealth_member_no
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
        SELECT p.patient_id, dm.medicine_name, SUM(dm.quantity_dispensed) AS qty
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

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" href="../../img/logo.png">
	<link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
	<link rel="stylesheet" href="../css/reportsDesign.css">
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
				<a href="#" id="updateReferrals">
					<i class="bx bxs-user"></i>
					<span class="text">Pending Referrals</span>
				</a>
			</li>
			
			<script>
			document.getElementById("updateReferrals").addEventListener("click", function (event) {
				event.preventDefault(); // Prevent default navigation
			
				fetch("php/update_referrals.php") // Call PHP file
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
				<a href="../../role.html" class="logout" onclick="return confirmLogout()">
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
      <h2>Medicine Utilization Report - RHU</h2> <br>
       

    <!-- Filter Modal Trigger -->
   
        <div class="form-submit">
               <button type="button" class="btn-export" id="openFilterModal">Filter</button>
        <button type="button" class="btn-export" onclick="exportTableToExcel('reportTable')">Export to Excel</button>
        <button type="button" class="btn-export" onclick="exportTableToPDF()">Export to PDF</button>
        <button type="button" class="btn-print" onclick="printDiv()">Print this page</button>
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

            if ($barangay) renderTag('Barangay', 'purok', $barangay);
           
            if (
                !$from_date && !$to_date && !$sex && !$age_group &&
                !$medicine && !$barangay
            ) {
                echo '<span style="color:#888;">All</span>';
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
                        <div class="form-item">
                            <label for="from_date">From:</label>
                            <input type="date" name="from_date" id="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
                        </div>
                        <div class="form-item">
                            <label for="to_date">To:</label>
                            <input type="date" name="to_date" id="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
                        </div>
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
<div class="print-header" style="text-align: center;">
  <h3>Republic of the Philippines</h3>
  <p>Department of Health</p>
 
  <h2>Rural Health Unit</h2>
  <br> 
  <h2>DOH MAINTAINANCE MEDICINE UTILIZATION REPORT</h2>
   (<?php
$filters = [];
if ($from_date) $filters[] = "From <strong>" . htmlspecialchars($from_date) . "</strong>";
if ($to_date) $filters[] = "To <strong>" . htmlspecialchars($to_date) . "</strong>";
if (!empty($_GET['medicine'])) {
    $medicine_list = is_array($_GET['medicine']) ? $_GET['medicine'] : [$_GET['medicine']];
    $filters[] = "Medicine: <strong>" . implode(', ', array_map('htmlspecialchars', $medicine_list)) . "</strong>";
}

if ($sex) $filters[] = "Sex: <strong>" . htmlspecialchars($sex) . "</strong>";
if ($age_group) {
    $age_labels = [
        'child' => 'Child (0–12)',
        'teen' => 'Teen (13–19)',
        'adult' => 'Adult (20–59)',
        'senior' => 'Senior (60+)'
    ];
    $filters[] = "Age Group: <strong>" . ($age_labels[$age_group] ?? htmlspecialchars($age_group)) . "</strong>";
}
if ($barangay) $filters[] = "Barangay: <strong>" . htmlspecialchars($barangay) . "</strong>";

echo $filters ? implode("&nbsp; | &nbsp;", $filters) : "All Records";
?>)</h3> <br><br><br>
</div>
<div class="report-content">
<style>
    @media print {
        .chart-title { 
           display: none;
        }
    }
</style>


<!-- Chart Visibility Controls -->
<div style="margin: 20px;" class="chart-title">
    <h3>Charts:</h3>
    <label><input type="checkbox" id="toggleSexChart"> Show Patients by Sex</label> <br>
    <label><input type="checkbox" id="toggleAgeGroupChart"> Show Age Group</label> <br>
    <label><input type="checkbox" id="toggleBarangayChart"> Show Barangays</label> <br>


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



<!-- Selected Filters Section -->

<br>



<!-- Summary Section -->
<div class="summary-container">
    <div class="summary">
        <h3><i class="bx bx-file"></i> Summary:</h3>
        <ul class="summary-list">
             <li>
                <strong>Report Generated On:</strong> <?= date('Y-m-d H:i:s') ?>
            </li>
            <li><strong>Total Patients in Report:</strong> <?= count($rows) ?></li>
            <li>
                <strong>By Sex:</strong>
                Male – <?= $sex_counts['Male'] ?? 0 ?>,
                Female – <?= $sex_counts['Female'] ?? 0 ?>
            </li>
            <li>
                <strong>By Age Group:</strong>
                Children – <?= $age_group_counts['0–12'] ?? 0 ?>,
                Teens – <?= $age_group_counts['13–19'] ?? 0 ?>,
                Adults – <?= $age_group_counts['20–59'] ?? 0 ?>,
                Seniors – <?= $age_group_counts['60+'] ?? 0 ?>
            </li>
            
            <li>
                <strong>Patients Given per Barangay:</strong>
                <ul>
                    <?php foreach ($barangay_counts as $barangay => $count): ?>
                        <li>Barangay <?= htmlspecialchars($barangay) ?>: <?= $count ?></li>
                    <?php endforeach; ?>
                </ul>
            </li>
         <li>
    <strong>Dispensed Medicines:</strong>
    <?php if (!empty($medicine_list)): ?>
        <ul>
            <table border="1" cellpadding="4" cellspacing="0">
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
                        if ($total_dispensed > 0): // Only show if quantity > 0
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($medicine) ?></td>
                            <td><?= $total_dispensed ?></td>
                        </tr>
                    <?php endif; endforeach; ?>
                </tbody>
            </table>
        </ul>
    <?php else: ?>
        All Medicines
    <?php endif; ?>
</li>

        </ul>
    </div>
</div>

 <h3>Detailed Visit Report</h3>
<!-- Patient Table -->
<table id="reportTable" border="1" cellpadding="8" cellspacing="0"> 
<thead>
        <tr>
        <th>Patient Name</th>
            <th>Address</th>
            <th>Age</th>
            <th>Date of Birth</th>
            <th>Gender</th>
            <th>PhilHealth No.</th>
            <?php foreach ($medicine_list as $med): ?>
            <th><?= htmlspecialchars($med) ?></th>
            <?php endforeach; ?>
            <th>Signature</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($patient_meds as $pm): $row = $pm['row']; ?>
        <tr>
        <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($row['address']) ?></td>
            <td><?= htmlspecialchars($row['age']) ?></td>
            <td><?= htmlspecialchars($row['date_of_birth']) ?></td>
            <td><?= htmlspecialchars($row['sex']) ?></td>
            <td><?= htmlspecialchars($row['philhealth_member_no']) ?></td>
            <?php foreach ($medicine_list as $med): ?>
            <td><?= $pm['medicines'][$med] ?></td>
            <?php endforeach; ?>
            <td></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<br> <br>


</div> </div> </div>

<!-- jsPDF and html2canvas libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
function exportTableToExcel(tableID, filename = 'report') {
    const dataType = 'application/vnd.ms-excel';
    const table = document.getElementById(tableID);
    const tableHTML = table.outerHTML.replace(/ /g, '%20');
    const downloadLink = document.createElement('a');

    document.body.appendChild(downloadLink);
    downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
    downloadLink.download = filename + '.xls';
    downloadLink.click();
    document.body.removeChild(downloadLink);
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
    // Get chart images from the original canvases
    function getChartImage(id, title) {
        const canvas = document.getElementById(id);
        if (canvas && canvas.toDataURL) {
            return `<div style="text-align:center;margin-bottom:20px;">
                        <h3 style="margin-bottom:8px;">${title}</h3>
                        <img src="${canvas.toDataURL('image/png')}" style="max-width:100%;height:auto;">
                    </div>`;
        }
        return '';
    }

    // Collect chart images with titles
    let chartsHTML = '';
  if (document.getElementById("toggleSexChart").checked) {
        chartsHTML += getChartImage('sexPieChart', 'Patients by Sex');
    }
    if (document.getElementById("toggleAgeGroupChart").checked) {
        chartsHTML += getChartImage('ageGroupBarChart', 'Age Group');
    }
        if (document.getElementById("toggleBarangayChart").checked) {
            chartsHTML += getChartImage('barangayBarChart', 'Patient Counts per Barangay');
        }

    chartsHTML += getChartImage('medicineLineChart', 'Quantity of Dispensed Medicines Over Time');

    // Clone the print area (table and summary)
    const originalArea = document.querySelector(".print-area").cloneNode(true);

    // Add 'Signature' column to header
    const headerRow = originalArea.querySelector("thead tr");
    if (headerRow && !headerRow.querySelector('th:last-child').textContent.includes('Signature')) {
        const signatureHeader = document.createElement("th");
        signatureHeader.textContent = "Signature";
        headerRow.appendChild(signatureHeader);

        // Add 'Signature' cell to each row in tbody
        const rows = originalArea.querySelectorAll("tbody tr");
        rows.forEach(row => {
            const signatureCell = document.createElement("td");
            signatureCell.style.height = "30px";
            signatureCell.textContent = "";
            row.appendChild(signatureCell);
        });
    }

    // Get the print header HTML
    const printHeader = document.querySelector('.print-header').outerHTML;

    // Remove the header from the cloned area so it doesn't appear twice
    const headerInClone = originalArea.querySelector('.print-header');
    if (headerInClone) headerInClone.remove();

    // Remove all canvases from the cloned area (since we use chart images instead)
    const canvases = originalArea.querySelectorAll('canvas');
    canvases.forEach(c => c.parentNode.removeChild(c));

    // Create print window and write content
    const printWindow = window.open('', '', 'height=900,width=1100');
    printWindow.document.write('<html><head><title>Print Report</title>');
    printWindow.document.write(`
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; color: black; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #000; padding: 4px; text-align: left; }
            thead { background-color: #f0f0f0; }
            img { display: block; margin: 0 auto; max-width: 100%; height: auto; }
            h3 { margin: 10px 0 5px 0; }
        </style>
    `);
    printWindow.document.write('</head><body>');
    printWindow.document.write(printHeader);            // Print header first
    printWindow.document.write(chartsHTML);             // Then charts
    printWindow.document.write(originalArea.innerHTML); // Then table and summary
    printWindow.document.write('</body></html>');

    printWindow.document.close();
    printWindow.focus();

    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}



document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to all delete icons
 	fetch('../php/getUserName.php')
        .then(response => response.json())
        .then(data => {
            if (data.full_name) {
                document.getElementById('userGreeting').textContent = `Hello, ${data.full_name}!`;
            } else {
                document.getElementById('userGreeting').textContent = 'Hello, User!';
            }
        })
        .catch(error => {
            console.error('Error fetching user name:', error);
            document.getElementById('userGreeting').textContent = 'Hello, User!';
        });
});

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
