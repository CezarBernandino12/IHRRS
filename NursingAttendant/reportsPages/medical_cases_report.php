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
$stmt = $pdo->prepare("SELECT barangay FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$barangayName = $user ? $user['barangay'] : 'N/A';



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


if (!empty($barangayName) && $barangayName !== 'N/A') {
    $sql .= " AND p.address LIKE :barangay";
    $params['barangay'] = '%' . $barangayName . '%';
}

$sql .= " ORDER BY r.consultation_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$visits = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" href="../../img/logo.png">
	<link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
	<link rel="stylesheet" href="../css/reportsDesign.css">
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
					<span class="text">Add ITR</span>
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
    <h2>Medical Cases Monitoring Report - BHS <?php echo htmlspecialchars($barangayName); ?>   </h2> <br>
<h3>
<?php
$filters = [];
if ($from_date) $filters[] = "From <strong>" . htmlspecialchars($from_date) . "</strong>";
if ($to_date) $filters[] = "To <strong>" . htmlspecialchars($to_date) . "</strong>";
if ($diagnosis) {
    $diagnosis_list = is_array($diagnosis) ? $diagnosis : [$diagnosis];
    $filters[] = "Diagnosis: <strong>" . implode(', ', array_map('htmlspecialchars', $diagnosis_list)) . "</strong>";
    
}
if ($diagnosis_status) $filters[] = "Status: <strong>" . htmlspecialchars($diagnosis_status) . "</strong>";
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
if ($purok) $filters[] = "Barangay: <strong>" . htmlspecialchars($purok) . "</strong>";



echo $filters ? implode(" &nbsp; | &nbsp; ", $filters) : "All Records";
?>
</h3>

    
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


                           <div class="form-item">
                            <label for="diagnosis_status">Status:</label>
                            <select name="diagnosis_status" id="diagnosis_status" class="form-control">
                                <option value="">All</option> 
                                <option value="Ongoing" <?= $diagnosis_status == 'Ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                <option value="Treated" <?= $diagnosis_status == 'Treated' ? 'selected' : '' ?>>Treated</option>
                                <option value="Deceased" <?= $diagnosis_status == 'Deceased' ? 'selected' : '' ?>>Deceased</option>
                           
                            </select> </div>
                            
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
  <p>Province of Camarines Norte</p>
  <h3>Municipality of Daet</h3>
  <h2><?php echo htmlspecialchars($barangayName); ?></h2>
  <br> 
  <h2>MEDICAL CASE MONITORING REPORT</h2>
  <h3>(<?php
$filters = [];
if ($from_date) $filters[] = "From <strong>" . htmlspecialchars($from_date) . "</strong>";
if ($to_date) $filters[] = "To <strong>" . htmlspecialchars($to_date) . "</strong>";
if ($diagnosis) {
    $diagnosis_list = is_array($diagnosis) ? $diagnosis : [$diagnosis];
    $filters[] = "Diagnosis: <strong>" . implode(', ', array_map('htmlspecialchars', $diagnosis_list)) . "</strong>";
    
}
if ($diagnosis_status) $filters[] = "Status: <strong>" . htmlspecialchars($diagnosis_status) . "</strong>";
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
if ($purok) $filters[] = "Barangay: <strong>" . htmlspecialchars($purok) . "</strong>";



echo $filters ? implode("&nbsp; | &nbsp;", $filters) : "All Records";
?>)</h3>
</div>
<br> <br><br><br> 
<div class="report-content">

<style>
    @media print {
        .chart-title { 
           display: none;
        }
    }
</style>
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
    <div style="max-width: 400px; margin: 30px auto 0 auto; text-align:center;">
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
    <div style="max-width: 500px; margin: 30px auto 0 auto; text-align:center;">
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
            <li><strong>Total Unique Patients in Report:</strong> <?= $total_patients ?? 0 ?></li>
            <li>
                <strong>By Sex:</strong>
                Male – <?= isset($sex_counts['Male']) ? $sex_counts['Male'] : 0 ?>,
                Female – <?= isset($sex_counts['Female']) ? $sex_counts['Female'] : 0 ?>
            </li>
            <li>
                <strong>By Age Group:</strong>
                Children (0–5): <?= isset($age_group_counts['0–5']) ? $age_group_counts['0–5'] : 0 ?>,
                Children (6–17): <?= isset($age_group_counts['6–17']) ? $age_group_counts['6–17'] : 0 ?>,
                Adults (18–59): <?= isset($age_group_counts['18–59']) ? $age_group_counts['18–59'] : 0 ?>,
                Seniors (60+): <?= isset($age_group_counts['60+']) ? $age_group_counts['60+'] : 0 ?>
            </li>
<li>
    <strong>Diseases and Case Counts:</strong>
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
        echo "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse; margin-top:8px; width:100%; text-align:center;'>";
        echo "<thead style='background:#f2f2f2;'>";
        echo "<tr>
                <th>Disease</th>
                <th>Total Cases</th>
                <th>Male</th>
                <th>Female</th>
                <th>0–5</th>
                <th>6–17</th>
                <th>18–59</th>
                <th>60+</th>
              </tr>";
        echo "</thead><tbody>";

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

       
        </ul>
    </div>
</div>
<br>

<h3>Detailed Report</h3>
<!-- Table with Visit Details -->
<?php if ($visits && count($visits) > 0): ?>
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
        <tbody>
        <?php foreach ($visits as $visit): ?>
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
        </tbody>
    </table>
<?php else: ?>
    <p>No visits found for the selected filters.</p>
<?php endif; ?>
           
  
</div> </div> 




<div id="logoutModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Logout</h3>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to logout?</p>
            </div>
            <div class="modal-footer">
                <button onclick="closeModal()" class="btn yes">Cancel</button>
                <button onclick="proceedLogout()" class="btn no">Yes, Logout</button>
            </div>
        </div>
    </div>
</div>

<!-- jsPDF and html2canvas libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="../js/reports.js"></script>
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
      chartsHTML += getChartImage('casesLineChart', 'Medical Cases');
    chartsHTML += getChartImage('sexPieChart', 'Patients by Sex');
    chartsHTML += getChartImage('ageGroupBarChart', 'Age Groups');
  

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

    // *** REMOVE ALL CANVAS ELEMENTS FROM THE CLONED AREA ***
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
    printWindow.document.write(printHeader); // Print header at the very top
    printWindow.document.write(chartsHTML);  // Then charts
    printWindow.document.write(originalArea.innerHTML);  // Then table and summary
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
</script>

</body>
</html>
