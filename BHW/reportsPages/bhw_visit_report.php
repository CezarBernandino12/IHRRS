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
$bmi = $_GET['bmi'] ?? '';
$treatment = $_GET['treatment'] ?? '';

// Build query with filters
$sql = "SELECT v.*, p.first_name, p.last_name, p.age, p.sex, p.address FROM patient_assessment v 
        JOIN patients p ON v.patient_id = p.patient_id 
        WHERE p.address LIKE :barangay"; // Always require barangay match

$params = [];
$params['barangay'] = '%' . $barangayName . '%'; // Always set this param

if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND DATE(v.visit_date) BETWEEN :from_date AND :to_date";
    $params['from_date'] = $from_date;
    $params['to_date'] = $to_date;
}

if (!empty($sex)) {
    $sql .= " AND p.sex = :sex";
    $params['sex'] = $sex;
}

if (!empty($age_group)) {
    switch ($age_group) {
        case 'child': $sql .= " AND p.age < 13"; break;
        case 'teen':  $sql .= " AND p.age BETWEEN 13 AND 19"; break;
        case 'adult': $sql .= " AND p.age BETWEEN 20 AND 59"; break;
        case 'senior':$sql .= " AND p.age >= 60"; break;
    }
}

if (!empty($purok)) {
    // Use 'purok' for barangay filtering - check if address contains the barangay name
    $sql .= " AND p.address LIKE :purok";
    $params['purok'] = '%' . $purok . '%';
}

if (!empty($bmi)) {
    switch ($bmi) {
        case 'underweight': $sql .= " AND v.bmi < 18.5"; break;
        case 'normal':  $sql .= " AND v.bmi >= 18.5 AND v.bmi <= 24.9"; break;
        case 'overweight': $sql .= " AND v.bmi >= 25 AND v.bmi <= 29.9"; break;
        case 'class1':  $sql .= " AND v.bmi >= 30 AND v.bmi <= 34.9"; break;
        case 'class2': $sql .= " AND v.bmi >= 35 AND v.bmi <= 39.9"; break;
        case 'class3':$sql .= " AND v.bmi >= 40"; break;
    }
}

if (!empty($treatment)) {
    switch ($treatment) {
        case 'weighing': $sql .= " AND v.treatment LIKE '%weighing%' "; break;
        case 'immunization':  $sql .= " AND v.treatment LIKE '%immunization%' "; break;
        case 'bp': $sql .= " AND v.treatment LIKE '%bp%' "; break;
        case 'prenatal':  $sql .= " AND v.treatment LIKE '%prenatal%' "; break;
        case 'referred': $sql .= " AND v.treatment LIKE '%referred%' "; break;
    }
}


// Add this condition to filter by barangay in address
if (!empty($barangayName) && $barangayName !== 'N/A') {
    $sql .= " AND p.address LIKE :barangay";
    $params['barangay'] = '%' . $barangayName . '%';
}
$sql .= " ORDER BY v.visit_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$visits = $stmt->fetchAll();

// Calculate summary data
$total_patients = count(array_unique(array_column($visits, 'patient_id')));
$total_medicines_dispensed = 0;
$medicine_counts = [];

foreach ($visits as $visit) {
    // Get medicines dispensed for this visit
    $med_stmt = $pdo->prepare("SELECT * FROM bhs_medicine_dispensed WHERE visit_id = ?");
    $med_stmt->execute([$visit['visit_id']]);
    $meds = $med_stmt->fetchAll();

    if ($meds) {
        foreach ($meds as $med) {
            $total_medicines_dispensed += $med['quantity_dispensed'];
            $medicine_counts[$med['medicine_name']] = ($medicine_counts[$med['medicine_name']] ?? 0) + $med['quantity_dispensed'];
        }
    }
}

// Find the most dispensed medicine
arsort($medicine_counts);
$most_dispensed_medicine = key($medicine_counts);
$most_dispensed_quantity = current($medicine_counts);
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

	<title>Patient Visits Summary Report</title>
</head>
<body>

	<!-- Sidebar Section -->
<section id="sidebar">
		<a href="#" class="brand" style="display: flex; align-items: center;">

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
				<a href="../History.html">
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
                <span id="userGreeting">Hello BHW!</span>
            </div>
			<a href="#" class="profile">
				<img src="../../img/bhw.png">
			</a>
		</nav>


		<main>
            
            <div class="head-title">
                <div class="left">
                  <h1>Patient Visits Summary</h1>
                  <ul class="breadcrumb">
                    <li><a href="#">Patient Report</a></li>
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
    <h2>Patient Summary Report - BHS <?php echo htmlspecialchars($barangayName); ?></h2> <br> 
 

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
            if ($sex) renderTag('Sex', 'sex', $sex);
            if ($age_group) {
                $age_labels = [
                    'child' => 'Child (0–12)', 'teen' => 'Teen (13–19)',
                    'adult' => 'Adult (20–59)', 'senior' => 'Senior (60+)'
                ];
                renderTag('Age Group', 'age_group', $age_labels[$age_group] ?? ucfirst($age_group));
            }
            if ($purok) renderTag('Barangay', 'purok', $purok);
            if ($bmi) {
                $bmi_labels = [
                    'underweight' => 'Underweight', 'normal' => 'Normal',
                    'overweight' => 'Overweight', 'class1' => 'Class 1',
                    'class2' => 'Class 2', 'class3' => 'Class 3'
                ];
                renderTag('BMI', 'bmi', $bmi_labels[$bmi] ?? $bmi);
            }
            if ($treatment) {
                $treat_labels = [
                    'weighing' => 'Weighing', 'immunization' => 'Immunization',
                    'bp' => 'Blood Pressure', 'prenatal' => 'Prenatal',
                    'referred' => 'Referred'
                ];
                renderTag('Treatment', 'treatment', $treat_labels[$treatment] ?? $treatment);
            }
           

            // If no filters, show "All"
            if (
                !$from_date && !$to_date && !$sex && !$age_group &&
                !$purok && !$bmi && !$treatment
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
                        <!-- From Date -->
                        <div class="form-item">
                            <label for="from_date">From:</label>
                            <input type="date" name="from_date" id="from_date" class="form-control" value="<?= $from_date ? htmlspecialchars($from_date) : '' ?>" placeholder="Select date">
                        </div>
                        <!-- To Date -->
                        <div class="form-item">
                            <label for="to_date">To:</label>
                            <input type="date" name="to_date" id="to_date" class="form-control" value="<?= $to_date ? htmlspecialchars($to_date) : '' ?>" placeholder="Select date">
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
                            <label for="purok">Address:</label>
                            <select name="purok" id="purok" class="form-control">
                                <option value="">All</option>
                               <?php
// Fetch distinct barangay addresses from patients
$barangay_stmt = $pdo->prepare("
    SELECT DISTINCT address 
    FROM patients 
    WHERE address LIKE :barangayName 
    ORDER BY address
");
$barangay_stmt->execute([':barangayName' => "%$barangayName%"]);

$selected_purok = $_GET['purok'] ?? '';

while ($row = $barangay_stmt->fetch(PDO::FETCH_ASSOC)) {
    $value = $row['address']; // ✅ use 'address' since that's what you selected
    $selected = ($selected_purok === $value) ? 'selected' : '';
    echo "<option value=\"" . htmlspecialchars($value) . "\" $selected>" 
         . htmlspecialchars($value) . "</option>";
}
?>

                            </select>
                        </div> 
                           <div class="form-item">
                            <label for="treatment">Treatment:</label>
                            <select name="treatment" id="treatment" class="form-control">
                                <option value="">All</option> 
                                <option value="weighing" <?= $treatment == 'weighing' ? 'selected' : '' ?>>Weighing</option>
                                <option value="immunization" <?= $treatment == 'immunization' ? 'selected' : '' ?>>Immunization</option>
                                <option value="bp" <?= $treatment == 'bp' ? 'selected' : '' ?>>Blood Pressure Reading</option>
                                <option value="prenatal" <?= $treatment == 'prenatal' ? 'selected' : '' ?>>Prenatal Check-up</option>
                                 <option value="referred" <?= $treatment == 'referred' ? 'selected' : '' ?>>Referred</option>
                            </select> </div>

                             <div class="form-item">
                            <label for="bmi">BMI:</label>
                            <select name="bmi" id="bmi" class="form-control">
                                <option value="">All</option> 
                                <option value="underweight" <?= $bmi == 'underweight' ? 'selected' : '' ?>>Underweight (less than 18.5 )</option>
                                <option value="normal" <?= $bmi == 'normal' ? 'selected' : '' ?>>Normal (18.5 to 24.9)</option>
                                <option value="overweight" <?= $bmi == 'overweight' ? 'selected' : '' ?>>Overweight (25 to 29.9)</option>
                                <option value="class1" <?= $bmi == 'class1' ? 'selected' : '' ?>>Class 1 - Moderate obesity (30 to 34.9)</option>
                                <option value="class2" <?= $bmi == 'class2' ? 'selected' : '' ?>>Class 2 - Severe obesity (35 to 39.9)</option>
                                <option value="class3" <?= $bmi == 'class3' ? 'selected' : '' ?>>Class 3 - Morbid obesity (40 or greater)</option>
                            </select> </div>

                           
                            
                        </div>
                    </div>
                     <!-- Filter Modal Footer Buttons -->
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
<div class="print-header" style="text-align: center;">
    <img src="../../img/RHUlogo.png" alt="RHU Logo" class="print-logo" style="height: 50px; width: auto;" />
  <h3>Republic of the Philippines</h3>
  <p>Province of Camarines Norte</p>
  <h3>Municipality of Daet</h3>
  <h2><?php echo htmlspecialchars($barangayName); ?></h2>
  <br> 
  <h2>Patient Visit Summary Report</h2>
    (<?php
$filters = [];
if ($from_date) $filters[] = "From <strong>" . htmlspecialchars($from_date) . "</strong>";
if ($to_date) $filters[] = "To <strong>" . htmlspecialchars($to_date) . "</strong>";



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
if ($bmi) $filters[] = "BMI: <strong>" . htmlspecialchars($bmi) . "</strong>";
if ($treatment) $filters[] = "Treatment: <strong>" . htmlspecialchars($treatment) . "</strong>";


echo $filters ? implode("&nbsp; | &nbsp;", $filters) : "All Records";
?>)</h3> <br><br><br>
</div>


<div class="report-content">

<style>
    @media print {
        .chart { 
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
</style>


<!-- Chart Visibility Controls -->
<div style="margin: 20px;" class="chart">
    <h3>Charts:</h3>
    <label><input type="checkbox" id="toggleSexChart"> Show Patients by Sex</label> <br>
    <label><input type="checkbox" id="toggleAgeGroupChart"> Show Age Group</label> <br>
    <label><input type="checkbox" id="toggleBMIChart"> Show Patients by BMI</label> <br>
    <label><input type="checkbox" id="toggleTreatmentChart"> Show Treatments</label> <br>

</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const chartMapping = {
        toggleSexChart: "sexChart",
        toggleAgeGroupChart: "ageGroupChart",
        toggleBMIChart: "bmiChart",
        toggleTreatmentChart: "treatmentChart"
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
    <div class="chart" id="sexChart" style="max-width: 400px; margin: 30px auto 0 auto; text-align:center; display: none;">
        <h3>Patients by Sex</h3>
        <canvas id="sexPieChart"></canvas>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Prepare data for the pie chart (Sex distribution)
        <?php
            // Count unique patients by sex for the current filtered visits
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
    <div class="chart" id="ageGroupChart" style="max-width: 500px; margin: 30px auto 0 auto; text-align:center; display: none;">
        <h3>Age Group</h3>
        <canvas id="ageGroupBarChart"></canvas>
    </div>
    <script>
        <?php
        // Prepare age group counts based on filtered visits (unique patients)
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


    <!-- BMI Category Pie Chart -->
    <div class="chart" id="bmiChart" style="max-width: 400px; margin: 30px auto 0 auto; text-align:center; display: none;">
        <h3>Patients by BMI Category</h3>
        <canvas id="bmiPieChart"></canvas>
    </div>
    <script>
    <?php
    // Prepare BMI category counts for unique patients (latest visit per patient)
    $bmi_categories = [
        'Underweight' => 0,
        'Normal' => 0,
        'Overweight' => 0,
        'Class 1' => 0,
        'Class 2' => 0,
        'Class 3' => 0
    ];
    $latest_bmi_per_patient = [];
    foreach ($visits as $visit) {
        $pid = $visit['patient_id'];
        // Only count the latest visit per patient for BMI
        if (!isset($latest_bmi_per_patient[$pid]) || strtotime($visit['visit_date']) > strtotime($latest_bmi_per_patient[$pid]['visit_date'])) {
            $latest_bmi_per_patient[$pid] = $visit;
        }
    }
    foreach ($latest_bmi_per_patient as $visit) {
        // Skip if BMI is empty, null, or not numeric
        if (!isset($visit['bmi']) || $visit['bmi'] === '' || !is_numeric($visit['bmi'])) {
            continue;
        }
        $bmi = floatval($visit['bmi']);
        if ($bmi < 18.5) {
            $bmi_categories['Underweight']++;
        } elseif ($bmi >= 18.5 && $bmi <= 24.9) {
            $bmi_categories['Normal']++;
        } elseif ($bmi >= 25 && $bmi <= 29.9) {
            $bmi_categories['Overweight']++;
        } elseif ($bmi >= 30 && $bmi <= 34.9) {
            $bmi_categories['Class 1']++;
        } elseif ($bmi >= 35 && $bmi <= 39.9) {
            $bmi_categories['Class 2']++;
        } elseif ($bmi >= 40) {
            $bmi_categories['Class 3']++;
        }
    }
    ?>
    const bmiLabels = <?= json_encode(array_keys($bmi_categories)) ?>;
    const bmiData = <?= json_encode(array_values($bmi_categories)) ?>;

    if (bmiData.reduce((a, b) => a + b, 0) > 0) {
        const ctxBMI = document.getElementById('bmiPieChart').getContext('2d');
        new Chart(ctxBMI, {
            type: 'pie',
            data: {
                labels: bmiLabels,
                datasets: [{
                    data: bmiData,
                    backgroundColor: [
                        '#b2df8a', // Underweight
                        '#1f77b4', // Normal
                        '#ffbb78', // Overweight
                        '#e15759', // Class 1
                        '#f28e2b', // Class 2
                        '#9467bd'  // Class 3
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

    <!-- Treatment Distribution Bar Chart -->
    <div class="chart" id="treatmentChart" style="max-width: 500px; margin: 30px auto 0 auto; text-align:center; display: none;">
        <h3>Treatments</h3>
        <canvas id="treatmentBarChart"></canvas>
    </div>
    <script>
    <?php
    // Prepare treatment counts (count each treatment keyword in filtered visits)
    $treatment_types = [
        'Weighing' => 0,
        'Immunization' => 0,
        'Blood Pressure Reading' => 0,
        'Prenatal Check-up' => 0,
        'Referred' => 0
    ];
    foreach ($visits as $visit) {
        $treat = strtolower($visit['treatment'] ?? '');
        if (strpos($treat, 'weighing') !== false) $treatment_types['Weighing']++;
        if (strpos($treat, 'immunization') !== false) $treatment_types['Immunization']++;
        if (strpos($treat, 'bp') !== false || strpos($treat, 'blood pressure') !== false) $treatment_types['Blood Pressure Reading']++;
        if (strpos($treat, 'prenatal') !== false) $treatment_types['Prenatal Check-up']++;
        if (strpos($treat, 'referred') !== false) $treatment_types['Referred']++;
    }
    ?>
    const treatmentLabels = <?= json_encode(array_keys($treatment_types)) ?>;
    const treatmentData = <?= json_encode(array_values($treatment_types)) ?>;

    if (treatmentData.reduce((a, b) => a + b, 0) > 0) {
        const ctxTreat = document.getElementById('treatmentBarChart').getContext('2d');
        new Chart(ctxTreat, {
            type: 'bar',
            data: {
                labels: treatmentLabels,
                datasets: [{
                    label: 'Treatment Count',
                    data: treatmentData,
                    backgroundColor: [
                        '#4e79a7', '#f28e2b', '#e15759', '#76b7b2', '#59a14f'
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
                        title: { display: true, text: 'Count' }
                    },
                    x: {
                        title: { display: true, text: 'Treatment' }
                    }
                }
            }
        });
    }
    </script>
<!-- Patient Address Distribution Bar Chart -->
<div class="chart" style="max-width: 600px; margin: 30px auto 0 auto; text-align:center;">
    <h3>Patient Address</h3>
    <canvas id="addressBarChart"></canvas>
</div>
<script>
<?php
// Prepare address counts for the bar chart (unique patient IDs only)
$unique_patient_addresses = [];
foreach ($visits as $visit) {
    $patient_id = $visit['patient_id'];
    $address = $visit['address'] ?? 'Unknown';
    $unique_patient_addresses[$patient_id] = $address;
}

// Count unique patients per address
$address_counts = [];
foreach ($unique_patient_addresses as $address) {
    $address_counts[$address] = ($address_counts[$address] ?? 0) + 1;
}

// Collapse counts by Purok (so Purok 1 in multiple barangays will add up together)
$purok_counts = [];
foreach ($address_counts as $addr => $count) {
    $parts = explode('-', $addr, 2);
    $purok = trim($parts[0]); // "Purok X"
    $purok_counts[$purok] = ($purok_counts[$purok] ?? 0) + $count;
}

// Sort by numeric purok order (extract number after "Purok")
uksort($purok_counts, function($a, $b) {
    preg_match('/\d+/', $a, $matchA);
    preg_match('/\d+/', $b, $matchB);
    $numA = $matchA[0] ?? 0;
    $numB = $matchB[0] ?? 0;
    return $numA - $numB;
});

$address_labels = array_keys($purok_counts);
$address_data = array_values($purok_counts);
?>
const addressLabels = <?= json_encode($address_labels) ?>;
const addressData = <?= json_encode($address_data) ?>;

if (addressData.length > 0 && addressData.reduce((a, b) => a + b, 0) > 0) {
    const ctxAddress = document.getElementById('addressBarChart').getContext('2d');
    new Chart(ctxAddress, {
        type: 'bar',
        data: {
            labels: addressLabels,
            datasets: [{
                label: 'Patient Count',
                data: addressData,
                backgroundColor: '#4e79a7',
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
                    title: { display: true, text: 'Purok' }
                }
            }
        }
    });
}
</script>



<!-- Table with Visit Details -->
<?php if ($visits): ?>
    <div class="report-table-container">
	<table id="reportTable">
    <thead>
        <tr>
            <th>Visit Date</th>
            <th>Patient Name</th>
            <th>Sex</th>
            <th>Age</th>
            <th>BMI</th>
            <th>Weight</th>
            <th>Height</th>
            <th>Blood Pressure</th>
            <th>Temperature</th>
            <th>Chest Rate</th>
            <th>Respiratory Rate</th>
            <th>Chief Complaints</th>
            <th>Treatment</th>
            <th>Address</th>
        </tr>
    </thead>
    <tbody>

    <?php foreach ($visits as $visit): ?>
       <tr>
  <td data-label="Visit Date"><?= date('Y-m-d', strtotime($visit['visit_date'])) ?></td>
  <td data-label="Patient Name"><?= htmlspecialchars($visit['first_name'] . ' ' . $visit['last_name']) ?></td>
  <td data-label="Sex"><?= htmlspecialchars($visit['sex']) ?></td>
  <td data-label="Age"><?= htmlspecialchars($visit['age']) ?></td>
  <td data-label="BMI"><?= htmlspecialchars($visit['bmi']) ?></td>
  <td data-label="Weight"><?= htmlspecialchars($visit['weight']) ?></td>
  <td data-label="Height"><?= htmlspecialchars($visit['height']) ?></td>
  <td data-label="Blood Pressure"><?= htmlspecialchars($visit['blood_pressure']) ?></td>
  <td data-label="Temperature"><?= htmlspecialchars($visit['temperature']) ?></td>
  <td data-label="Chest Rate"><?= htmlspecialchars($visit['chest_rate']) ?></td>
  <td data-label="Respiratory Rate"><?= htmlspecialchars($visit['respiratory_rate']) ?></td>
  <td data-label="Chief Complaints"><?= htmlspecialchars($visit['chief_complaints']) ?></td>
  <td data-label="Treatment"><?= htmlspecialchars($visit['treatment']) ?></td>
  <td data-label="Address"><?= htmlspecialchars($visit['address']) ?></td>
</tr>

    <?php endforeach; ?>
    </tbody>
</table> </div>
           <br>
  
<!-- Summary Section -->
<div class="summary-container">
    <div class="summary">
        <h3><i class="bx bx-file"></i> Summary:</h3>
        <ul class="summary-list">
             <li>
                <strong>Report Generated On:</strong> <?= date('Y-m-d H:i:s') ?>
            </li>
            <li><strong>Total Patients in Report:</strong> <?= $total_patients ?></li>
            <li>
                <strong>By Sex:</strong>
                Male – <?= $sex_counts['Male'] ?? 0 ?>,
                Female – <?= $sex_counts['Female'] ?? 0 ?>
            </li>
            <li>
                <strong>By Age Group:</strong>
                Children – <?= $age_group_counts['0–5'] + $age_group_counts['6–17'] ?>,
                Adults – <?= $age_group_counts['18–59'] ?>,
                Seniors – <?= $age_group_counts['60+'] ?>
            </li>
            <li>
                <strong>By BMI:</strong>
                Underweight – <?= $bmi_categories['Underweight'] ?? 0 ?>,
                Normal – <?= $bmi_categories['Normal'] ?? 0 ?>,
                Overweight – <?= $bmi_categories['Overweight'] ?? 0 ?>,
                Obese – <?= ($bmi_categories['Class 1'] ?? 0) + ($bmi_categories['Class 2'] ?? 0) + ($bmi_categories['Class 3'] ?? 0) ?>
            </li>
            
            <li>
                <strong>Most Common Treatment Given:</strong>
                <?php
                    // Find most common treatment
                    $max_treatment = '';
                    $max_treatment_count = 0;
                    foreach ($treatment_types as $treat => $count) {
                        if ($count > $max_treatment_count) {
                            $max_treatment = $treat;
                            $max_treatment_count = $count;
                        }
                    }
                    echo htmlspecialchars($max_treatment ?: 'N/A');
                ?>
            </li>
            <li>
                <li>
                    <strong>Patient Counts per Purok:</strong>
                    <ul>
                        <?php
                        // Calculate patient counts per barangay
                        $barangay_patient_counts = [];
                     foreach ($visits as $visit) {
    $patient_id = $visit['patient_id'];
    $address = $visit['address'] ?? 'Unknown';
    $unique_patient_addresses[$patient_id] = $address;
}
                        foreach ($unique_patient_addresses as $address) {
                            $parts = explode('-', $address, 2);
                            $barangay = trim($parts[0]); // "Purok X"
                            $barangay_patient_counts[$barangay] = ($barangay_patient_counts[$barangay] ?? 0) + 1;
                        }
                        // Sort by numeric purok order
                        uksort($barangay_patient_counts, function($a, $b) {
                            preg_match('/\d+/', $a, $matchA);
                            preg_match('/\d+/', $b, $matchB);
                            $numA = $matchA[0] ?? 0;
                            $numB = $matchB[0] ?? 0;
                            return $numA - $numB;
                        });
                        foreach ($barangay_patient_counts as $barangay => $count) {
                            echo '<li>' . htmlspecialchars($barangay) . ' – ' . $count . '</li>';
                        }

                        
                        ?>
                    </ul>
                </li>
            </li>
        </ul>
    </div>
    </div>

 
    <br> <br>
     <span id="generated_by" style="font-size: 16px;"></span>
</div>
<?php else: ?>
    <p>No visits found for the selected filters.</p>
<?php endif; ?>


<!-- Print Button at Bottom -->
   <div class="form-submit">
          <button type="button" class="btn-export" onclick="exportTableToExcel('reportTable')">Export to Excel</button>
        <button type="button" class="btn-export" onclick="exportTableToPDF()">Export to PDF</button>
       
    <button type="button" class="btn-print" onclick="printDiv()">
        <i class='bx bx-printer'></i>
        Print Report
    </button>
</div>

</div> </div> 




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

<!-- jsPDF and html2canvas libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>

    
function exportTableToExcel(tableID, filename = 'Patient Summary Report') {
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

   
    // Clone the print area (table and summary)
    const originalArea = document.querySelector(".print-area").cloneNode(true);

    // ✅ Add 'Signature' column only if not already present
    const headerRow = originalArea.querySelector("thead tr");


    // Get the print header HTML
    const printHeader = document.querySelector(".print-header").outerHTML;

    // Remove header from cloned area (avoid duplication)
    const headerInClone = originalArea.querySelector(".print-header");
    if (headerInClone) headerInClone.remove();

    // Create print window
    const printWindow = window.open("", "", "height=900,width=1100");
    printWindow.document.write("<html><head><title>Print Report</title>");
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
    printWindow.document.write("</head><body>");
    printWindow.document.write(printHeader);     // header first
    printWindow.document.write(originalArea.innerHTML); // then table + summary
    printWindow.document.write("</body></html>");

    printWindow.document.close();
    printWindow.focus();

    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}

fetch('../php/getUserName.php')
    .then(response => response.json())
    .then(data => {
        if (data.full_name) {
            document.getElementById('userGreeting').textContent = `Hello, ${data.full_name}!`;
            document.getElementById('generated_by').textContent = `Report Generated by: ${data.full_name} - BHW`;
        } else {
            document.getElementById('userGreeting').textContent = 'Hello, BHW!';
            document.getElementById('generated_by').textContent = 'Generated by: N/A';
        }
    })
    .catch(error => {
        console.error('Error fetching user name:', error);
        document.getElementById('userGreeting').textContent = 'Hello, BHW!';
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

    function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'block';
    return false; // Prevent the default link behavior
}

function closeModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

function proceedLogout() {
    wwindow.location.href = '../../ADMIN/php/logout.php'; 
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('logoutModal');
    if (event.target == modal) {
        closeModal();
    }
}

// Remove charts from the clone (if they exist inside .print-area)
const chartsInClone = originalArea.querySelectorAll("canvas, .chart-container");
chartsInClone.forEach(chart => chart.remove());

</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.getElementById("sidebar");

  function applyResponsiveSidebar() {
    if (window.innerWidth <= 1024) {
      sidebar.classList.add("hide");   // collapsed on small screens
    } else {
      sidebar.classList.remove("hide"); // expanded on larger screens
    }
  }

  applyResponsiveSidebar();
  window.addEventListener("resize", applyResponsiveSidebar);

  // keep the rest of your existing code (auth, stats, modals, etc.)
});
</script>

</body>
</html>
