<?php
// Connect to DB
require '../../php/db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../role.html");
    exit;
}

$userId = $_SESSION['user_id'];// or however you store the logged-in user's ID

// Fetch user info
$stmt = $pdo->prepare("SELECT full_name, rhu FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$rhu = $user ? $user['rhu'] : 'N/A';
$username = $user ? $user['full_name'] : 'N/A';



// Initialize filters
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$sex = $_GET['sex'] ?? '';
$age_group = $_GET['age_group'] ?? '';
$purok = $_GET['purok'] ?? ''; // Use 'purok' for address filtering
$diagnosis = isset($_GET['diagnosis']) ? (array)$_GET['diagnosis'] : [];
$diagnosis_status = $_GET['diagnosis_status'] ?? '';
$barangay = $_GET['barangay'] ?? '';

// Build query with filters
$sql = "SELECT r.*, p.first_name, p.last_name, p.age, p.sex, p.address 
        FROM rhu_consultations r 
        JOIN patients p ON r.patient_id = p.patient_id 
        WHERE 1=1"; // Always true, so we can safely append filters

$params = [];

if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND DATE(r.consultation_date) BETWEEN :from_date AND :to_date";
    $params['from_date'] = $from_date;
    $params['to_date'] = $to_date;
}

if (!empty($sex)) {
    $sql .= " AND p.sex = :sex";
    $params['sex'] = $sex;
}

if (!empty($age_group)) {
    switch ($age_group) {
        case 'child':  $sql .= " AND p.age < 13"; break;
        case 'teen':   $sql .= " AND p.age BETWEEN 13 AND 19"; break;
        case 'adult':  $sql .= " AND p.age BETWEEN 20 AND 59"; break;
        case 'senior': $sql .= " AND p.age >= 60"; break;
    }
}

if (!empty($diagnosis)) {
    $placeholders = [];
    foreach ($diagnosis as $i => $diag) {
        $ph = ":diagnosis_$i";
        $placeholders[] = "r.diagnosis LIKE $ph";
        $params["diagnosis_$i"] = '%' . $diag . '%';
    }
    // Join with OR so it matches any selected diagnosis
    $sql .= " AND (" . implode(" OR ", $placeholders) . ")";
}
if (!empty($diagnosis_status)) {
    $sql .= " AND r.diagnosis_status = :diagnosis_status";
    $params['diagnosis_status'] = $diagnosis_status;
}
if (!empty($purok)) {
    $sql .= " AND p.address LIKE :purok";
    $params['purok'] = '%' . $purok . '%';
}

if (!empty($barangay) && $barangay !== 'N/A') {
    $sql .= " AND p.address LIKE :barangay";
    $params['barangay'] = '%' . $barangay . '%';
}

$sql .= " ORDER BY r.consultation_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$visits = $stmt->fetchAll();


        //ADDED GENERATED REPORT FOR ACTIVITY LOG
        $stmt_log = $pdo->prepare("INSERT INTO logs (
            user_id, action, performed_by
        ) VALUES (
            :user_id, :action, :performed_by
        )");
        $stmt_log->execute([
            ':user_id' => $_SESSION['user_id'],
            ':action' => "Generated RHU Medical Cases Report",
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

	<title>Medical Cases Report</title>
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
                <span id="userGreeting">Hello User!</span>
            </div>
			<a href="#" class="profile">
				<img src="../../img/nurse.png">
			</a>
		</nav>


		<main>
            
            <div class="head-title">
                <div class="left">
                  <h1>Medical Cases</h1>
                  <ul class="breadcrumb">
                    <li><a href="#">MC Report</a></li>
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
    <h2>Medical Cases Monitoring Report - <?php echo htmlspecialchars($rhu); ?>   </h2> <br>

    
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
                unset($url[$param]);
                $query = http_build_query($url);
                echo '<span class="filter-tag" style="background:#e3e6ea;color:#222;padding:6px 12px;border-radius:16px;display:inline-flex;align-items:center;font-size:14px;">';
                echo $display;
                echo ' <a href="?' . $query . '" style="margin-left:8px;color:#888;text-decoration:none;font-weight:bold;" title="Remove filter">&times;</a>';
                echo '</span>';
            }

            // Render tags for each filter if set
            if ($from_date) renderTag('From', 'from_date', $from_date);
            if ($to_date) renderTag('To', 'to_date', $to_date);
          if ($diagnosis) {
                foreach ($diagnosis as $diag) {
                    renderTag('Diagnosis', 'diagnosis[]', $diag);
                }
            }
            if ($sex) renderTag('Sex', 'sex', $sex);
            if ($age_group) {
                $age_labels = [
                    'child' => 'Child (0–12)', 'teen' => 'Teen (13–19)',
                    'adult' => 'Adult (20–59)', 'senior' => 'Senior (60+)'
                ];
                renderTag('Age Group', 'age_group', $age_labels[$age_group] ?? ucfirst($age_group));
            }
            if ($purok) renderTag('Barangay', 'purok', $purok);
            if ($diagnosis_status) renderTag('Status', 'diagnosis_status', $diagnosis_status);
     
            // If no filters, show "All"
            if (
                !$from_date && !$to_date && !$sex && !$age_group &&
                !$purok && !$diagnosis_status && !$diagnosis
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
                            <input type="text" name="from_date" id="from_date" class="form-control" value="<?= $from_date ? htmlspecialchars($from_date) : '' ?>"  placeholder="Select date">
                        </div>
                        <!-- To Date -->
                        <div class="form-item">
                            <label for="to_date">To:</label>
                            <input type="text" name="to_date" id="to_date" class="form-control" value="<?= $to_date ? htmlspecialchars($to_date) : '' ?>"  placeholder="Select date">
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

                        
                           <div class="form-item">
                            <label for="diagnosis_status">Status:</label>
                            <select name="diagnosis_status" id="diagnosis_status" class="form-control">
                                <option value="">All</option> 
                                <option value="Ongoing" <?= $diagnosis_status == 'Ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                <option value="Treated" <?= $diagnosis_status == 'Treated' ? 'selected' : '' ?>>Treated</option>
                                <option value="Deceased" <?= $diagnosis_status == 'Deceased' ? 'selected' : '' ?>>Deceased</option>
                           
                            </select> </div>

                                  <div class="form-item">
                        <label for="diagnosis">Diagnoses:</label>
                        <div id="medicine-checkboxes" style="max-height:150px;overflow-y:auto;border:1px solid #ccc;padding:8px;border-radius:6px;">
                            <?php
                            // Fetch medicines for checkboxes
                            $diagnosis_stmt = $pdo->prepare("SELECT value FROM custom_options WHERE category = 'diagnosis' ");
                            $diagnosis_stmt->execute();
                            // Support multiple selection from GET
                            $selected_diagnosis = isset($_GET['diagnosis']) ? (array)$_GET['diagnosis'] : [];
                            while ($row = $diagnosis_stmt->fetch()) {
                                $value = $row['value'];
                                $checked = in_array($value, $selected_diagnosis) ? 'checked' : '';
                                echo '<label style="display:block;margin-bottom:4px;text-align:left;font-weight:300;">';
                                echo '<input type="checkbox" name="diagnosis[]" value="' . htmlspecialchars($value) . '" ' . $checked . '> ';
                                echo htmlspecialchars($value);
                                echo '</label>';
                            }
                            ?>
                        </div>
                        <small style="color:#888;">You may select multiple diagnoses.</small>
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
  <img src="../../img/Plogo.png" alt="Left Logo" class="print-logo">
  <div class="print-heading">
    <div class="ph-line-1">Republic of the Philippines</div>
    <div class="ph-line-1">Province of Camarines Norte</div>
    <div class="ph-line-2">Municipality of Daet</div>
    <div class="ph-line-3"><?php echo htmlspecialchars($rhu); ?></div>
    <div class="ph-line-4">MEDICAL CASE MONITORING REPORT</div>
    <div class="print-sub">
      (<?php
        $filters = [];
        if ($from_date) $filters[] = "From <strong>" . htmlspecialchars($from_date) . "</strong>";
        if ($to_date)   $filters[] = "To <strong>" . htmlspecialchars($to_date) . "</strong>";
        if ($diagnosis) {
          $diagnosis_list = is_array($diagnosis) ? $diagnosis : [$diagnosis];
          $filters[] = "Diagnosis: <strong>" . implode(', ', array_map('htmlspecialchars', $diagnosis_list)) . "</strong>";
        }
        if ($diagnosis_status) $filters[] = "Status: <strong>" . htmlspecialchars($diagnosis_status) . "</strong>";
        if ($sex) $filters[] = "Sex: <strong>" . htmlspecialchars($sex) . "</strong>";
        if ($age_group) {
          $age_labels = [
            'child' => 'Child (0–12)', 'teen' => 'Teen (13–19)',
            'adult' => 'Adult (20–59)', 'senior' => 'Senior (60+)'
          ];
          $filters[] = "Age Group: <strong>" . ($age_labels[$age_group] ?? htmlspecialchars($age_group)) . "</strong>";
        }
        if ($purok) $filters[] = "Barangay: <strong>" . htmlspecialchars($purok) . "</strong>";
        echo $filters ? implode("&nbsp; | &nbsp;", $filters) : "All Records";
      ?>)
    </div>
  </div>
  <img src="../../img/RHUlogo.png" alt="Right Logo" class="print-logo">
</div>
<hr class="print-rule">


<div class="report-content">
<style>
      .print-letterhead { display: none; }

  @media print {
    .print-letterhead { display: block; }
  .print-letterhead{
    display:grid;
    grid-template-columns:64px auto 64px;
    align-items:center;
    justify-content:center;
    column-gap:14px;
    margin:0 auto 10px;
    text-align:center;
    width:fit-content;
  }
  .print-logo{ width:64px; height:64px; object-fit:contain; }
  .print-heading{ line-height:1.1; color:#0d2546; }
  .print-heading .ph-line-1{ font-size:12pt; font-weight:500; }
  .print-heading .ph-line-2{ font-size:14pt; font-weight:500; }
  .print-heading .ph-line-3{ font-size:11pt; font-weight:500; }
  .print-heading .ph-line-4{ font-size:12pt; font-weight:600; margin-top:4px; letter-spacing:.3px; }
  .print-sub{ font-size:10.5pt; margin-top:4px; }
  .print-rule{ height:1px; border:0; background:#cfd8e3; margin:8px 0 12px; }
}
</style>

<style>

.summary-container { 
  margin-top: 32px;          
}

/* Summary table styling */
.summary-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 16px;
}

.case-table,
.summary-table,
.report-table-container table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;     
}

.case-table th, .case-table td,
.summary-table th, .summary-table td,
.report-table-container th, .report-table-container td {
  border: 1px solid #d5d7db;
  padding: 8px 12px;
  vertical-align: middle;
  text-align: center;
  word-wrap: break-word;    
}

.case-table th.tl,
.case-table td:nth-child(1) {    
  text-align: left;
}

.case-table thead th {
  white-space: nowrap;
}

.summary-list,
.summary-list li {
  list-style: none;
  padding-left: 0;
  margin-left: 0;
}


@media print {
  .case-table thead { display: table-header-group; }
  .case-table tr, .summary-table tr, #reportTable tr {
    page-break-inside: avoid;
    break-inside: avoid;
  }
}

.summary-table th,
.summary-table td {
  border: 1px solid #d5d7db;
  padding: 8px 12px;
  vertical-align: top;
  text-align: left;
}

.summary-table th {
  width: 260px;
  background: #f2f4f7;
  font-weight: 600;
}

@media print {
  .summary-container { margin-top: 40px; } /* a bit more space when printing */
}

    @media print {
        .chart-title { 
           display: none;
        }
         .form-submit { 
           display: none;
        }
        .summary-list{
            font-size: 16px;
        }
        .generated-by{
            font-size: 16px;
        }
    }
    @media print {
  .summary > h3 { 
    display: none !important;
  }
}

</style>

<!-- Chart Visibility Controls -->
<div style="margin: 20px;" class="chart-title">
    <h3>Charts:</h3>
    <label><input type="checkbox" id="toggleSexChart"> Show Patients by Sex</label> <br>
    <label><input type="checkbox" id="toggleAgeGroupChart"> Show Age Group</label> <br>

</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const chartMapping = {
        toggleSexChart: "sexChart",
        toggleAgeGroupChart: "ageGroupChart"
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


<!-- Disease Frequency Over Time Line Chart -->
<div style="max-width: 800px; margin: 30px auto 0 auto; text-align:center;">
    <h3 class="chart-title">Medical Cases</h3>
    <canvas id="casesLineChart"></canvas>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    <?php
    // Prepare disease frequency with unique patients across all dates
    $disease_dates = [];
    $seen = []; // Track diagnosis+patient globally

    foreach ($visits as $visit) {
        $diag = $visit['diagnosis'] ?? '';
        $date = isset($visit['consultation_date']) ? date('Y-m-d', strtotime($visit['consultation_date'])) : '';
        $patient = $visit['patient_id'] ?? null;

        if ($diag && $date && $patient) {
            // Create unique key for this diagnosis+patient
            $key = $diag . '_' . $patient;

            // Skip if already counted (ensures deduplication across all dates)
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;

            // Ensure structure
            if (!isset($disease_dates[$diag])) $disease_dates[$diag] = [];
            if (!isset($disease_dates[$diag][$date])) $disease_dates[$diag][$date] = 0;

            // Count unique patient on their first appearance only
            $disease_dates[$diag][$date]++;
        }
    }

    // Collect all unique dates
    $all_dates = [];
    foreach ($disease_dates as $diag => $dates) {
        foreach ($dates as $date => $count) {
            $all_dates[$date] = true;
        }
    }
    $all_dates = array_keys($all_dates);
    sort($all_dates);

    // Prepare datasets for Chart.js
    $datasets = [];
    $colors = [
        '#4e79a7', '#f28e2b', '#e15759', '#76b7b2', '#59a14f', '#edc949',
        '#af7aa1', '#ff9da7', '#9c755f', '#bab0ab', '#b07aa1', '#7a9cb0'
    ];
    $colorIndex = 0;
    foreach ($disease_dates as $diag => $dates) {
        $data = [];
        $runningTotal = 0; // accumulate counts over time

        foreach ($all_dates as $date) {
            if (isset($dates[$date])) {
                $runningTotal += $dates[$date]; // add first-time patients
            }
            $data[] = $runningTotal; // cumulative line
        }

        $datasets[] = [
            'label' => $diag,
            'data' => $data,
            'fill' => false,
            'borderColor' => $colors[$colorIndex % count($colors)],
            'backgroundColor' => $colors[$colorIndex % count($colors)],
            'tension' => 0.2
        ];
        $colorIndex++;
    }
    ?>
    const diseaseLineLabels = <?= json_encode($all_dates) ?>;
    const diseaseLineDatasets = <?= json_encode($datasets) ?>;

    if (diseaseLineDatasets.length > 0 && diseaseLineLabels.length > 0) {
        const ctxDiseaseLine = document.getElementById('casesLineChart').getContext('2d');
        new Chart(ctxDiseaseLine, {
            type: 'line',
            data: {
                labels: diseaseLineLabels,
                datasets: diseaseLineDatasets
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Unique Patients' }
                    },
                    x: {
                        title: { display: true, text: 'Date' }
                    }
                }
            }
        });
    }
</script>


    <!-- Pie Chart Section: Sex Distribution -->
    <div id="sexChart" style="max-width: 400px; margin: 30px auto 0 auto; text-align:center; display:none;">
         <h3 class="chart-title">Patients by Sex</h3>
        <canvas id="sexPieChart"></canvas>
    </div>
    <script>
        <?php
            $sex_counts = ['Male' => 0, 'Female' => 0];
            $unique_patients = [];
            foreach ($visits as $visit) {
                $pid = $visit['patient_id'];
                if (!isset($unique_patients[$pid])) {
                    $unique_patients[$pid] = $visit['sex'];
                }
            }
            foreach ($unique_patients as $sex) {
                if (isset($sex_counts[$sex])) $sex_counts[$sex]++;
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
                        backgroundColor: [
                            '#4e79a7', '#f28e2b'
                        ],
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

    <!-- Age Group Distribution Bar Chart -->
    <div id="ageGroupChart" style="max-width: 500px; margin: 30px auto 0 auto; text-align:center; display: none;">
         <h3 class="chart-title">Age Groups</h3>
        <canvas id="ageGroupBarChart"></canvas>
    </div>
    <script>
        <?php
        $age_group_counts = [
            '0–5' => 0,
            '6–17' => 0,
            '18–59' => 0,
            '60+' => 0
        ];
        $unique_patients_age = [];
        foreach ($visits as $visit) {
            $pid = $visit['patient_id'];
            if (!isset($unique_patients_age[$pid])) {
                $age = (int)$visit['age'];
                if ($age >= 0 && $age <= 5) {
                    $age_group_counts['0–5']++;
                } elseif ($age >= 6 && $age <= 17) {
                    $age_group_counts['6–17']++;
                } elseif ($age >= 18 && $age <= 59) {
                    $age_group_counts['18–59']++;
                } elseif ($age >= 60) {
                    $age_group_counts['60+']++;
                }
                $unique_patients_age[$pid] = true;
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
                        backgroundColor: [
                            '#4e79a7', '#f28e2b', '#e15759', '#76b7b2'
                        ],
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



<br>

<!-- Table with Visit Details -->
<?php if ($visits && count($visits) > 0): ?>
         <div class="report-table-container">
    <table id="reportTable">
        <thead>
            <tr>
                <th>Date Diagnosed</th>
                <th>Diagnosis</th>
                <th>Status</th>
                <th>Patient Name</th>
                <th>Sex</th>
                <th>Age</th>
                <th>Address</th>
            </tr>
        </thead>
<?php
// Sort visits from latest to oldest by consultation_date
usort($visits, function($a, $b) {
    return strtotime($b['consultation_date']) - strtotime($a['consultation_date']);
});

// Track unique combinations of patient name + diagnosis
$seen = [];
$unique_visits = [];

foreach ($visits as $visit) {
    $key = strtolower(trim($visit['first_name'] . ' ' . $visit['last_name'] . '|' . $visit['diagnosis']));
    if (!isset($seen[$key])) {
        $seen[$key] = true;
        $unique_visits[] = $visit;
    }
}
?>
<tbody>
    <?php if (!empty($unique_visits)): ?>
        <?php foreach ($unique_visits as $visit): ?>
            <tr>
                <td><?= date('Y-m-d', strtotime($visit['consultation_date'])) ?></td>
                <td><?= htmlspecialchars($visit['diagnosis']) ?></td>
                <td><?= htmlspecialchars($visit['diagnosis_status']) ?></td>
                <td><?= htmlspecialchars($visit['first_name'] . ' ' . $visit['last_name']) ?></td>
                <td><?= htmlspecialchars($visit['sex']) ?></td>
                <td><?= htmlspecialchars($visit['age']) ?></td>
                <td><?= htmlspecialchars($visit['address']) ?></td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="7" style="text-align:center;">No unique records found</td></tr>
    <?php endif; ?>
</tbody>

    </table>
</div>
<?php else: ?>
    <p>No visits found for the selected filters.</p>
<?php endif; ?>
           
  
</div>


<!-- Summary Section -->
<?php
// Calculate total unique patients
$unique_patient_ids = [];
foreach ($visits as $visit) {
    $unique_patient_ids[$visit['patient_id']] = true;
}
$total_patients = count($unique_patient_ids);
?>
<div class="summary-container">
    <div class="summary">
        <h3><i class="bx bx-file"></i> Summary:</h3>
        <ul class="summary-list">
            <li>
    <strong>Case Counts:</strong>
    <?php
    // Prepare disease breakdown by sex and age group (unique patients only)
    $disease_summary = [];
    $seen = []; // track patient+diagnosis globally

    foreach ($visits as $visit) {
        $d = $visit['diagnosis'] ?? 'Unknown';
        $s = $visit['sex'] ?? 'Unknown';
        $a = (int)($visit['age'] ?? 0);
        $p = $visit['patient_id'] ?? null;

        if (!$p) continue;

        $key = $d . '_' . $p;

        // Skip if this patient already counted for this disease
        if (isset($seen[$key])) continue;
        $seen[$key] = true;

        if (!isset($disease_summary[$d])) {
            $disease_summary[$d] = [
                'total' => 0,
                'sex' => ['Male' => 0, 'Female' => 0],
                'age' => ['0–5' => 0, '6–17' => 0, '18–59' => 0, '60+' => 0]
            ];
        }

        // Count this unique patient once
        $disease_summary[$d]['total']++;

        // Count sex
        if (isset($disease_summary[$d]['sex'][$s])) {
            $disease_summary[$d]['sex'][$s]++;
        }

        // Count age group
        if ($a >= 0 && $a <= 5) {
            $disease_summary[$d]['age']['0–5']++;
        } elseif ($a >= 6 && $a <= 17) {
            $disease_summary[$d]['age']['6–17']++;
        } elseif ($a >= 18 && $a <= 59) {
            $disease_summary[$d]['age']['18–59']++;
        } elseif ($a >= 60) {
            $disease_summary[$d]['age']['60+']++;
        }
    }

    if (count($disease_summary) === 0) {
        echo "<p style='color:#888;'>No disease cases found for the selected filters.</p>";
    } else {
echo "<table class='case-table'>
  <colgroup>
    <col style='width:28%'>    <!-- Case -->
    <col style='width:12%'>    <!-- Total -->
    <col style='width:10%'>    <!-- Male -->
    <col style='width:10%'>    <!-- Female -->
    <col style='width:10%'>    <!-- 0–5 -->
    <col style='width:10%'>    <!-- 6–17 -->
    <col style='width:10%'>    <!-- 18–59 -->
    <col style='width:10%'>    <!-- 60+ -->
  </colgroup>
  <thead>
    <tr>
      <th class='tl'>Case</th>
      <th>Total</th>
      <th>Male</th>
      <th>Female</th>
      <th>0–5</th>
      <th>6–17</th>
      <th>18–59</th>
      <th>60+</th>
    </tr>
  </thead><tbody>";

        foreach ($disease_summary as $disease => $info) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($disease) . "</td>";
            echo "<td>{$info['total']}</td>";
            echo "<td>{$info['sex']['Male']}</td>";
            echo "<td>{$info['sex']['Female']}</td>";
            echo "<td>{$info['age']['0–5']}</td>";
            echo "<td>{$info['age']['6–17']}</td>";
            echo "<td>{$info['age']['18–59']}</td>";
            echo "<td>{$info['age']['60+']}</td>";
            echo "</tr>";
        }

        echo "</tbody></table>";
    }
    ?>
</li>
<br>
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
      <th>Total Unique Patients</th>
      <td><?= $total_patients ?? 0 ?></td>
    </tr>
    <tr>
      <th>By Sex</th>
      <td>Male — <?= $sex_counts['Male'] ?? 0 ?>, Female — <?= $sex_counts['Female'] ?? 0 ?></td>
    </tr>
    <tr>
      <th>By Age Group</th>
      <td>
        Young Children: <?= $age_group_counts['0–5'] ?? 0 ?>,
        Children: <?= $age_group_counts['6–17'] ?? 0 ?>,
        Adults: <?= $age_group_counts['18–59'] ?? 0 ?>,
        Seniors: <?= $age_group_counts['60+'] ?? 0 ?>
      </td>
    </tr>
  </tbody>
</table>


        </ul>
    </div>

<br> <br>
<div class="generated-by">
     <b>Report Generated By: </b><?php echo htmlspecialchars($username); ?> -  Nursing Attedant
</div>

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
<script src="../js/reports.js"></script>
<script>

    
function exportTableToExcel(tableID, filename = 'Medical Cases Report') {
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
        if (summary) {
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


function printDiv() {
  // 1) Use the new header
  const headerEl = document.querySelector('.print-letterhead');
  const printHeader = headerEl ? headerEl.outerHTML : '';

  // 2) Clone the area you already print
  const area = document.querySelector('.print-area');
  if (!area) return;
  const clone = area.cloneNode(true);

  // 3) Remove duplicate header inside the clone (if any)
  const headerInClone = clone.querySelector('.print-letterhead');
  if (headerInClone) headerInClone.remove();

  // 4) *** REMOVE ALL CHARTS FROM THE CLONE ***
  // hide the chart title block
  clone.querySelectorAll('.chart-title').forEach(n => n.remove());
  // whole “Patients by Sex” and “Age Groups” sections
  clone.querySelectorAll('#sexChart, #ageGroupChart').forEach(n => n.remove());
  // the line chart (canvas lives inside a wrapper div)
  const lineCanvas = clone.querySelector('#casesLineChart');
  if (lineCanvas) {
    const wrapper = lineCanvas.closest('div');
    if (wrapper) wrapper.remove(); else lineCanvas.remove();
  }
  // safety: remove any remaining canvases
  clone.querySelectorAll('canvas').forEach(n => n.remove());

  // 5) Print
  const w = window.open('', '', 'height=900,width=1100');
  if (!w) { alert('Please allow pop-ups to print this report.'); return; }
  w.document.write(`
    <html>
      <head>
        <title>Print Report</title>
        <meta charset="utf-8" />
        <style>
          body { font-family: Arial, sans-serif; font-size: 16px; color:#000; }
          table { width:100%; border-collapse:collapse; }
          th, td { border:1px solid #000; padding:4px; text-align:left; }
          thead { background:#f0f0f0; }

          .print-letterhead{
            display:grid; grid-template-columns:64px auto 64px;
            align-items:center; justify-content:center; column-gap:14px;
            margin:0 auto 10px; text-align:center; width:fit-content;
          }
          .print-logo{ width:64px; height:64px; object-fit:contain; }
          .print-heading{ line-height:1.1; color:#0d2546; }
          .print-heading .ph-line-1{ font-size:12pt; font-weight:500; }
          .print-heading .ph-line-2{ font-size:14pt; font-weight:800; }
          .print-heading .ph-line-3{ font-size:11pt; font-weight:500; }
          .print-heading .ph-line-4{ font-size:12pt; font-weight:800; margin-top:4px; letter-spacing:.3px; }
          .print-sub{ font-size:10.5pt; margin-top:4px; }
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
  setTimeout(() => { w.print(); w.close(); }, 300);
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

    function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'block';
    return false; // Prevent the default link behavior
}

function closeModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

function proceedLogout() {
    window.location.href = '../../role.html';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('logoutModal');
    if (event.target == modal) {
        closeModal();
    }
}

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
