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
$bmi = $_GET['bmi'] ?? '';
$medication = $_GET['medication'] ?? '';


// Build query with filters
$sql = "SELECT v.*, p.first_name, p.last_name, p.age, p.sex, p.address FROM patient_assessment v 
        JOIN patients p ON v.patient_id = p.patient_id 
        JOIN users u_rec ON v.recorded_by = u_rec.user_id
        WHERE u_rec.rhu = ?";  // Only show consultations from same RHU

$params = [$rhu]; // First parameter is the current user's RHU



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
    // Use 'purok' for address filtering
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



// Add this condition to filter by barangay in address
if (!empty($barangayName) && $barangayName !== 'N/A') {
    $sql .= " AND p.address LIKE :barangay";
    $params['barangay'] = '%' . $barangayName . '%';
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$visits = $stmt->fetchAll();

// Calculate summary data
$total_patients = count(array_unique(array_column($visits, 'patient_id')));

        //ADDED GENERATED REPORT FOR ACTIVITY LOG
        $stmt_log = $pdo->prepare("INSERT INTO logs (
            user_id, action, performed_by
        ) VALUES (
            :user_id, :action, :performed_by
        )");
        $stmt_log->execute([
            ':user_id' => $_SESSION['user_id'],
            ':action' => "Generated RHU Patient Visit Summary Report",
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
    <title>Patient Summary Report</title>
</head>
<body>


<!-- Sidebar Section -->
	<section id="sidebar">
		<a href="#" class="brand">
			<img src="../../img/logo.png" alt="RHULogo" class="logo">
			<span class="text">Nurse</span>
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
                  <h1>Patient Summary</h1>
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
    <h2>Patient Summary Report - <?php echo htmlspecialchars($rhu); ?></h2> <br>
   
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
           
         

            // If no filters, show "All"
            if (
                !$from_date && !$to_date && !$sex && !$age_group &&
                !$purok && !$bmi && !$medication
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
       <h2>Patients Summary Report</h2>
    <div class="print-sub">
      (<?php
        $filters = [];
                   if ($from_date || $to_date) {
    $readable_from = $from_date ? date("F j, Y", strtotime($from_date)) : '';
    $readable_to   = $to_date ? date("F j, Y", strtotime($to_date)) : '';

    // Combine them in a single display
    $filters[] = "<strong>" . trim($readable_from . ($readable_to ? " — " . $readable_to : '')) . "</strong>";
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
        if ($purok) $filters[] = "Barangay: <strong>" . htmlspecialchars($purok) . "</strong>";
        if ($bmi) $filters[] = "BMI: <strong>" . htmlspecialchars($bmi) . "</strong>";
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
     .report-table-container {
        font-size: 11pt;
      margin-top: -90px !important;
      margin-bottom: 40px !important;
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
  .print-heading .ph-line-1{ font-size:12pt; font-weight:500; margin-bottom:3px;}
  .print-heading .ph-line-2{ font-size:14pt; font-weight:500; margin-bottom:3px;}
  .print-heading .ph-line-3{ font-size:11pt; font-weight:500; margin-bottom:3px;}
  .print-heading .ph-line-4{ font-size:12pt; font-weight:600; margin-top:15px; letter-spacing:.3px; }
  .print-sub{ font-size:11pt; margin-top:4px; }
  .print-rule{ height:1px; border:0; background:#cfd8e3; margin:8px 0 12px; }
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
    display: none;
  width: 200px;           
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
  #generated_by {  margin: 60mm 0 0 10mm;}
  #generated_by .sig-label { font-size: 12pt; }
  #generated_by .sig-name  { font-size: 12pt; }
  #generated_by .sig-title { font-size: 11pt; }
  #generated_by .sig-line  {display: block; width: 45mm; border-top-width: 1px; margin: 10mm 0 3mm; }
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

.summary-table2 {
margin-top:6px;
 border-collapse: collapse;
  width: 100%;
}

/* Print-only: hide the "Summary" title; add a bit more top spacing */
@media print {
  .summary > h3 { 
    display: none !important;
  }
  .summary-container { 
    margin-top: 40px;
  }
  .summary-table th,
.summary-table td {
  border: 1px solid #000000ff;
  padding: 8px 12px;
  vertical-align: top;
  text-align: left;
  word-wrap: break-word;
}
.summary-table2 th,
.summary-table2 td {
  border: 1px solid #000000ff !important;
}
}

    @media print {
       
         .form-submit { 
           display: none;
        }
    
    }
</style>


<!-- Chart Visibility Controls -->
<div style="margin: 20px;" class="chart-title">
    <h3>Charts:</h3>
    <label><input type="checkbox" id="toggleSexChart"> Show Patients by Sex</label> <br>
    <label><input type="checkbox" id="toggleAgeGroupChart"> Show Age Group</label> <br>
    <label><input type="checkbox" id="toggleBMIChart"> Show Patients by BMI</label> <br>


</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const chartMapping = {
        toggleSexChart: "sexChart",
        toggleAgeGroupChart: "ageGroupChart",
        toggleBMIChart: "bmiChart",

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

<br><br>


    <!-- Age Group Distribution Bar Chart -->
    <div id="ageGroupChart" style="max-width: 500px; margin: 30px auto 0 auto; text-align:center; display: none;">
          <h3 class="chart-title">Age Groups</h3>
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

<br><br>
    <!-- BMI Category Pie Chart -->
    <div id="bmiChart" style="max-width: 400px; margin: 30px auto 0 auto; text-align:center; display: none;">
            <h3 class="chart-title">Patients by BMI Category</h3>
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

    <br><br>

     <!-- Address Distribution Bar Chart -->
    <div style="max-width: 500px; margin: 30px auto 0 auto; text-align:center;">
    <h3 class="chart-title">Patients by Barangay</h3>
        <canvas id="barangayBarChart">
         
        </canvas>
    </div>
    <script>
        <?php
        // Prepare barangay counts based on filtered visits (unique patients)
        $barangay_counts = [];
        $unique_patients_address = [];
        foreach ($visits as $visit) {
            $pid = $visit['patient_id'];
            if (!isset($unique_patients_address[$pid])) {
                // Extract barangay from the address
                $address_parts = explode(' - ', $visit['address']);
                $barangay = isset($address_parts[1]) ? explode(' ', $address_parts[1])[1] : 'Others';
                $barangay_counts[$barangay] = ($barangay_counts[$barangay] ?? 0) + 1;
                $unique_patients_address[$pid] = true;
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

<br><br>

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
            <th>Address</th>
            
          
        </tr>
    </thead>
    
<?php
// Sort visits from latest to oldest by visit_date
usort($visits, function($a, $b) {
    return strtotime($b['visit_date']) - strtotime($a['visit_date']);
});
?>
<tbody>
    <?php foreach ($visits as $visit): ?>
        <tr>
            <td><?= date('Y-m-d', strtotime($visit['visit_date'])) ?></td>
            <td><?= htmlspecialchars($visit['first_name'] . ' ' . $visit['last_name']) ?></td>
            <td><?= htmlspecialchars($visit['sex']) ?></td>
            <td><?= htmlspecialchars($visit['age']) ?></td>
            <td><?= htmlspecialchars($visit['bmi']) ?></td>
            <td><?= htmlspecialchars($visit['weight']) ?></td>
            <td><?= htmlspecialchars($visit['height']) ?></td>
            <td><?= htmlspecialchars($visit['address']) ?></td>
        </tr>
    <?php endforeach; ?>
</tbody>

</table>
</div>
<?php else: ?>
    <p>No visits found for the selected filters.</p>
<?php endif; ?>

           <!-- Summary Section -->
<div class="summary-container">
  <div class="summary">
    <h3><i class="bx bx-file"></i> Report Details</h3>

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
          <td><?= $total_patients ?></td>
        </tr>
        <tr>
          <th>By Sex</th>
          <td>
            Male — <?= $sex_counts['Male'] ?? 0 ?>,
            Female — <?= $sex_counts['Female'] ?? 0 ?>
          </td>
        </tr>
        <tr>
          <th>By Age Group</th>
          <td>
            0–5: <?= $age_group_counts['0–5'] ?? 0 ?>,
            6–17: <?= $age_group_counts['6–17'] ?? 0 ?>,
            18–59: <?= $age_group_counts['18–59'] ?? 0 ?>,
            60+: <?= $age_group_counts['60+'] ?? 0 ?>
          </td>
        </tr>
        <tr>
          <th>By BMI</th>
          <td>
            Underweight — <?= $bmi_categories['Underweight'] ?? 0 ?>,
            Normal — <?= $bmi_categories['Normal'] ?? 0 ?>,
            Overweight — <?= $bmi_categories['Overweight'] ?? 0 ?>,
            Obese — <?= ($bmi_categories['Class 1'] ?? 0) + ($bmi_categories['Class 2'] ?? 0) + ($bmi_categories['Class 3'] ?? 0) ?>
          </td>
        </tr>
      </tbody>
    </table>

    <div style="margin-top:12px;">
      <strong style="font-size: 12pt;">Patient Counts per Barangay:</strong>
    <table class="summary-table2">
      <thead>
        <tr>
        <th style="border: 1px solid #d5d7db; padding: 8px; text-align: left;">Barangay</th>
        <th style="border: 1px solid #d5d7db; padding: 8px; text-align: left;">Patient Count</th>
        </tr>
      </thead>
      <tbody>
        <?php
        // reuse the counts you already computed just above
        foreach ($barangay_counts as $barangay => $count) {
        echo "<tr>";
        echo "<td style='border: 1px solid #d5d7db; padding: 8px;'>" . htmlspecialchars($barangay) . "</td>";
        echo "<td style='border: 1px solid #d5d7db; padding: 8px;'>" . $count . "</td>";
        echo "</tr>";
        }
        ?>
      </tbody>
    </table>
    </div>
  </div>
</div>


<div id="generated_by"></div>
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
  const originalArea = document.querySelector(".print-area");
  const headerEl = document.querySelector(".print-letterhead");
  if (!originalArea || !headerEl) {
    alert("Error: Missing .print-area or .print-letterhead on page.");
    return;
  }

  const clone = originalArea.cloneNode(true);

  // Remove header duplication in the clone
  const headerInClone = clone.querySelector('.print-letterhead');
  if (headerInClone) headerInClone.remove();
  const ruleInClone = clone.querySelector('.print-rule');
  if (ruleInClone) ruleInClone.remove();

  // Remove all charts/controls in the clone
  clone.querySelectorAll('canvas').forEach(c => c.remove());
  clone.querySelectorAll('#sexChart, #ageGroupChart, #bmiChart, #barangayBarChart').forEach(el => el.remove());
  clone.querySelectorAll('.chart-title').forEach(el => el.remove());
  const chartControls = Array.from(clone.querySelectorAll('div')).find(d => d.textContent?.trim().startsWith('Charts:'));
  if (chartControls) chartControls.remove();

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

          /* header visuals */
          .print-letterhead{
            display:grid; grid-template-columns:64px auto 64px;
            align-items:center; justify-content:center; column-gap:14px;
            margin:0 auto 10px; text-align:center; width:fit-content;
          }
          .print-logo{ width:64px; height:64px; object-fit:contain; }
          .print-heading{ line-height:1.1; color:#000; }
          .print-heading .ph-line-1{ font-size:12pt; font-weight:500; }
          .print-heading .ph-line-2{ font-size:14pt; font-weight:800; }
          .print-heading .ph-line-3{ font-size:11pt; font-weight:500; }
          .print-heading .ph-line-4{ font-size:12pt; font-weight:800; margin-top:4px; letter-spacing:.3px; }
          .print-sub{ font-size:11pt; margin-top:4px; }
          .print-rule{ height:1px; border:0; background:#cfd8e3; margin:8px 0 12px; }
        </style>
      </head>
      <body>
        ${headerEl.outerHTML}
        <hr class="print-rule">
        ${clone.innerHTML}
      </body>
    </html>
  `);
  w.document.close();
  w.focus();
  setTimeout(() => { w.print(); w.close(); }, 500);
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
