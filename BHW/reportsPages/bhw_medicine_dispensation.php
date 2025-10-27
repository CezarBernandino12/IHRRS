<?php
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


// Get filters
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
// Accept multiple medicines
$medicine = isset($_GET['medicine']) ? (array)$_GET['medicine'] : [];
$bhw_id = $_GET['bhw'] ?? '';
$sex = $_GET['sex'] ?? '';
$age_group = $_GET['age_group'] ?? '';

// Build SQL
$sql = "SELECT m.*, v.visit_date, v.recorded_by, p.first_name, p.last_name, p.sex, p.age, u.full_name AS bhw_name
        FROM bhs_medicine_dispensed m
        JOIN patient_assessment v ON m.visit_id = v.visit_id
        JOIN patients p ON v.patient_id = p.patient_id
        LEFT JOIN users u ON v.recorded_by = u.user_id
        WHERE p.address LIKE :barangay";

$params = [];
$params['barangay'] = '%' . $barangayName . '%';


if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND DATE(m.dispensed_date) BETWEEN :from_date AND :to_date";
    $params['from_date'] = $from_date;
    $params['to_date'] = $to_date;
}

if (!empty($medicine)) {
    // Build placeholders for each selected medicine
    $placeholders = [];
    foreach ($medicine as $i => $med) {
        $ph = ":medicine_$i";
        $placeholders[] = $ph;
        $params["medicine_$i"] = $med;
    }
    $sql .= " AND m.medicine_name IN (" . implode(',', $placeholders) . ")";
}

if (!empty($bhw_id)) {
    $sql .= " AND v.recorded_by = :bhw_id";
    $params['bhw_id'] = $bhw_id;
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

$sql .= " ORDER BY m.dispensed_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Get list of BHWs for dropdown
$bhw_stmt = $pdo->query("SELECT user_id, full_name FROM users WHERE role = 'BHW'");
$bhws = $bhw_stmt->fetchAll();

        //ADDED GENERATED REPORT FOR ACTIVITY LOG
        $stmt_log = $pdo->prepare("INSERT INTO logs (
            user_id, action, performed_by
        ) VALUES (
            :user_id, :action, :performed_by
        )");
        $stmt_log->execute([
            ':user_id' => $_SESSION['user_id'],
            ':action' => "Generated BHS Medicine Dispensation Report",
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

	<title>Medicine Dispensation Report</title>
</head>
<body>
 
	<!-- Sidebar Section -->
<section id="sidebar">
		<a href="#" class="brand" style="display: flex; align-items: center;">
			<img src="../../img/logo.png" alt="RHULogo" class="logo">
			<span class="text">BHW</span>
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
                  <h1>Medicine Dispensation</h1>
                  <ul class="breadcrumb">
                    <li><a href="#">Medicine Dispensation Report</a></li>
                    <li><i class="bx bx-chevron-right"></i></li>
                    <li><a class="active" href="#" onclick="history.back(); return false;">Go back</a></li>
                  </ul>
                </div>
              </div>

      <br> <br>
            </div>

<div class="history-container">
	


<form method="GET" class="filter-form">
      <h2>Medicine Dispensation Report - BHS <?php echo htmlspecialchars($barangayName); ?></h2> <br>

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
            if ($bhw_id) {
                $bhw_name = '';
                foreach ($bhws as $bhw) {
                    if ($bhw['user_id'] == $bhw_id) {
                        $bhw_name = $bhw['full_name'];
                        break;
                    }
                }
                renderTag('Bhw', 'bhw', $bhw_name);
            }
            if ($age_group) {
                $age_labels = [
                    'child' => 'Child (0–12)', 'teen' => 'Teen (13–19)',
                    'adult' => 'Adult (20–59)', 'senior' => 'Senior (60+)'
                ];
                renderTag('Age Group', 'age_group', $age_labels[$age_group] ?? ucfirst($age_group));
            }
            if ($medicine) {
                foreach ($medicine as $med) {
                    renderTag('Medicine', 'medicine[]', $med);
                }
            }
            // If no filters, show "All"
            if (
                !$from_date && !$to_date && !$sex && !$age_group &&
                !$medicine && !$bhw_id 
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


                               <div class="form-item" style="margin-top: -100px;">
            <label for="bhw">Dispensed by:</label>
            <select name="bhw" id="bhw">
                <option value="">-- All --</option>
                <?php foreach ($bhws as $bhw): ?>
                    <option value="<?= $bhw['user_id'] ?>" <?= $bhw['user_id'] == $bhw_id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($bhw['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>


                    <div class="form-item">
                        <label for="medicine">Given Medicine:</label>
                        <div id="medicine-checkboxes" style="max-height:150px;overflow-y:auto;border:1px solid #ccc;padding:8px;border-radius:6px;">
                            <?php
                            // Fetch medicines for checkboxes
                            $medicine_stmt = $pdo->prepare("SELECT value FROM custom_options WHERE category = 'medicine' ");
                            $medicine_stmt->execute();
                            // Support multiple selection from GET
                            $selected_medicines = isset($_GET['medicine']) ? (array)$_GET['medicine'] : [];
                            while ($row = $medicine_stmt->fetch()) {
                                $value = $row['value'];
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

  <!-- Unified two-logo letterhead -->
  <div class="print-letterhead">
    <img src="../../img/daet_logo.png" alt="Left Logo"  class="print-logo">
    <div class="print-heading">
      <div class="ph-line-1">Republic of the Philippines</div>
      <div class="ph-line-1">Province of Camarines Norte</div>
      <div class="ph-line-2">Municipality of Daet</div>
      <div class="ph-line-3"><?php echo htmlspecialchars($barangayName); ?></div>

    </div>
    <img src="../../img/mho_logo.png" alt="Right Logo" class="print-logo">
  </div>
  <hr class="print-rule">


<div class="report-content">
<br><br>
<div class="title">
          <div class="ph-line-4">MEDICINE DISPENSATION REPORT</div>
      <div class="print-sub">
        (<?php
          $filters = [];
         if ($from_date || $to_date) {
    $readable_from = $from_date ? date("F j, Y", strtotime($from_date)) : '';
    $readable_to   = $to_date ? date("F j, Y", strtotime($to_date)) : '';

    // Combine them in a single display
    $filters[] = "<strong>" . trim($readable_from . ($readable_to ? " — " . $readable_to : '')) . "</strong>";
} 
          if ($medicine)  { $ml = is_array($medicine)?$medicine:[$medicine]; $filters[] = "Medicine: <strong>".implode(', ', array_map('htmlspecialchars',$ml))."</strong>"; }
          if ($bhw_id)    { $bhw_name=''; foreach ($bhws as $bhw){ if ($bhw['user_id']==$bhw_id){ $bhw_name=$bhw['full_name']; break; } }
                           $filters[] = "Given by: <strong>".htmlspecialchars($bhw_name)."</strong>"; }
          if ($sex)       $filters[] = "Sex: <strong>".htmlspecialchars($sex)."</strong>";
          if ($age_group) { $age_labels=['child'=>'Child (0–12)','teen'=>'Teen (13–19)','adult'=>'Adult (20–59)','senior'=>'Senior (60+)'];
                            $filters[]="Age Group: <strong>".($age_labels[$age_group]??htmlspecialchars($age_group))."</strong>"; }
          echo $filters ? implode("&nbsp; | &nbsp;", $filters) : "All Records";
        ?>)
      </div>
</div>
<style>
     .print-letterhead { display: none; }

  @media print {
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
  .print-heading{ line-height:1.1;   color: #000; }
  .print-heading .ph-line-1{ font-size:12pt; font-weight:500; }
  .print-heading .ph-line-2{ font-size:14pt; font-weight:500; }
  .print-heading .ph-line-3{ font-size:12pt; font-weight:500; }
  .print-heading .ph-line-4{ font-size:12pt; font-weight:600; margin-top:15px; letter-spacing:.3px; }
  .print-sub{ font-size:12pt;}
  .print-rule{ height:1px; border:0; background:#cfd8e3; margin:8px 0 12px; }
}
  @media print {
    .summary-container { margin-top: 24mm; }
    .summary-container .summary h4 { display: none !important; }
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
  #generated_by .sig-line  { width: 45mm; border-top-width: 1px; margin: 10mm 0 3mm; }
}
</style>

<style>

     .title { text-align: center; display: none;}

    @media print {
     
 .title {
        display: block;
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
        .chart { 
           display: none;
        }
        
    .report-table-container {
      margin-top: 20px !important;
      margin-bottom: 40px !important;
    }
    }
</style>
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div style="margin: 30px 0;" class="chart">
    <h3 style="margin-bottom:10px;"><i class="bx bx-line-chart"></i> Medicine Dispensation Trends</h3>
    <canvas id="dispensationChart" height="80"></canvas>
</div>

<script>
// Prepare data for the chart
const chartData = (() => {
    // Group by date and medicine
    const rows = <?php echo json_encode($rows); ?>;
    const medicines = [...new Set(rows.map(r => r.medicine_name))];
    // Get all unique dispensed dates (sorted)
    const dates = [...new Set(rows.map(r => r.dispensed_date))].sort();

    // Build a map: {medicine: {date: count}}
    const medDateCount = {};
    medicines.forEach(med => {
        medDateCount[med] = {};
        dates.forEach(date => {
            medDateCount[med][date] = 0;
        });
    });
    rows.forEach(r => {
        if (medDateCount[r.medicine_name] && medDateCount[r.medicine_name][r.dispensed_date] !== undefined) {
            medDateCount[r.medicine_name][r.dispensed_date] += Number(r.quantity_dispensed) || 1;
        }
    });

    // Prepare datasets for Chart.js
    const colors = [
        '#3366cc', '#dc3912', '#ff9900', '#109618', '#990099', '#0099c6', '#dd4477', '#66aa00', '#b82e2e', '#316395'
    ];
    const datasets = medicines.map((med, idx) => ({
        label: med,
        data: dates.map(date => medDateCount[med][date]),
        borderColor: colors[idx % colors.length],
        backgroundColor: colors[idx % colors.length] + '33',
        fill: false,
        tension: 0.2
    }));

    return {dates, datasets};
})();

const ctx = document.getElementById('dispensationChart').getContext('2d');
const dispensationChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: chartData.dates,
        datasets: chartData.datasets
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true, position: 'top' },
            title: { display: false }
        },
        scales: {
            x: { title: { display: true, text: 'Dispensed Date' } },
            y: { title: { display: true, text: 'Quantity Dispensed' }, beginAtZero: true }
        }
    }
});
</script>


 <div class="report-table-container">
    <table id="reportTable">
  <thead>
    <tr>
      <th data-abbr="Dispensed">Dispensed Date</th>
      <th data-abbr="Name">Patient Name</th>
      <th data-abbr="Sex">Sex</th>
      <th data-abbr="Age">Age</th>
      <th data-abbr="Medicine">Medicine Name</th>
      <th data-abbr="Qty">Quantity</th>
      <th data-abbr="Visit">Visit Date</th>
      <th data-abbr="BHW">Dispensed by</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td data-label="Dispensed Date"><?= htmlspecialchars($row['dispensed_date']) ?></td>
        <td data-label="Patient Name"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
        <td data-label="Sex"><?= htmlspecialchars($row['sex']) ?></td>
        <td data-label="Age"><?= htmlspecialchars($row['age']) ?></td>
        <td data-label="Medicine Name"><?= htmlspecialchars($row['medicine_name']) ?></td>
        <td data-label="Quantity"><?= htmlspecialchars($row['quantity_dispensed']) ?></td>
        <td data-label="Visit Date"><?= htmlspecialchars($row['visit_date']) ?></td>
        <td data-label="BHW"><?= htmlspecialchars($row['bhw_name']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>




<?php if ($rows): ?>
    <div class="summary-container">
    <div class="summary">
        <h4><i class="bx bx-filter-alt"></i>Summary:</h4>
        <tr>
  <th>Report Generated On</th>
  <td class="summary-mono">
    <?= htmlspecialchars(date('F jS, Y \a\t g:i A'), ENT_QUOTES, 'UTF-8') ?>
  </td>
</tr>

        <p><strong>Total Records:</strong> <?= count($rows) ?></p>
    <table class="summary-table" style="width: 100%; border-collapse: collapse; margin-top: 10px;">
        <thead>
            <tr>
                <th style="border: 1px solid #ccc; padding: 8px; text-align: left;">Medicine Name</th>
                <th style="border: 1px solid #ccc; padding: 8px; text-align: left;">Total Quantity Dispensed</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Summarize total quantity dispensed per medicine (after filters)
            $medicine_totals = [];
            foreach ($rows as $row) {
                $med = $row['medicine_name'];
                $qty = (int)$row['quantity_dispensed'];
                if (!isset($medicine_totals[$med])) {
                    $medicine_totals[$med] = 0;
                }
                $medicine_totals[$med] += $qty;
            }
            if ($medicine_totals) {
                foreach ($medicine_totals as $med => $total) {
                    echo '<tr>';
                    echo '<td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($med) . '</td>';
                    echo '<td style="border: 1px solid #ccc; padding: 8px;">' . $total . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr>';
                echo '<td colspan="2" style="border: 1px solid #ccc; padding: 8px; text-align: center;">No medicines dispensed for the selected filters.</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
    </div>

 
</div>

     <span id="generated_by"></span>
    
<?php else: ?>
    <p>No records found for the selected filters.</p>
<?php endif; ?>
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
        const printHeader = document.querySelector('.print-letterhead, .print-header');
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



//PRINT
function printDiv() {
  // 1) Grab header (new class first, fallback to old)
  const headerEl = document.querySelector('.print-letterhead, .print-header');
  const printHeader = headerEl ? headerEl.outerHTML : '';

  // 2) Clone the printable area
  const area = document.querySelector(".print-area");
  if (!area) return;
  const clone = area.cloneNode(true);

  // 3) Replace the chart canvas with an image inside the clone
  const liveCanvas = document.getElementById('dispensationChart');
  if (liveCanvas) {
    const img = document.createElement('img');
    img.src = liveCanvas.toDataURL("image/png");
    img.style.maxWidth = "100%";
    img.style.height = "auto";
    const toReplace = clone.querySelector('#dispensationChart');
    if (toReplace && toReplace.parentNode) {
      toReplace.parentNode.replaceChild(img, toReplace);
    }
  }
  // Remove any other canvases in the clone
  clone.querySelectorAll('canvas').forEach(c => c.remove());

  // 4) Don’t duplicate header if it exists inside the clone
  const headerInClone = clone.querySelector('.print-letterhead, .print-header');
  if (headerInClone) headerInClone.remove();

  // 5) Open print window
  const w = window.open('', '', 'height=900,width=1100');
  if (!w) { alert('Please allow pop-ups to print this report.'); return; }

  w.document.write(`
    <html>
      <head>
        <title>Print Report</title>
        <meta charset="utf-8" />
        <style>
          body { font-family: Arial, sans-serif; font-size: 16px; color: #000; }
          table { width: 100%; border-collapse: collapse; }
          th, td { border: 1px solid #000; padding: 4px; text-align: left; }
          thead { background-color: #f0f0f0; }
          /* letterhead styles in print window */
          .print-letterhead{ display:grid; grid-template-columns:64px auto 64px; align-items:center; justify-content:center; column-gap:14px; margin:0 auto 10px; text-align:center; width:fit-content; }
          .print-logo{ width:64px; height:64px; object-fit:contain; }
          .print-heading{ line-height:1.1; color:#000; }
          .print-heading .ph-line-1{ font-size:12pt; font-weight:500; }
          .print-heading .ph-line-2{ font-size:14pt; font-weight:800; }
          .print-heading .ph-line-3{ font-size:12pt; font-weight:500; }
          .print-heading .ph-line-4{ font-size:12pt; font-weight:800; margin-top:4px; letter-spacing:.3px; }
          .print-sub{ font-size:12pt;}
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

fetch('../php/getUserName.php')
  .then(response => response.json())
  .then(data => {
    const fullName = (data && data.full_name) ? data.full_name : '';

    // Keep the greeting as before
    document.getElementById('userGreeting').textContent =
      fullName ? `Hello, ${fullName}!` : 'Hello, BHW!';

    // Build the signature block content
    const gb = document.getElementById('generated_by');
    gb.innerHTML = `
      <div class="sig-label">Report Generated by:</div>
      <hr class="sig-line">
      <div class="sig-name"></div>
      <div class="sig-title">Barangay Health Worker</div>
    `;

    // Safely set the name text
    gb.querySelector('.sig-name').textContent = fullName || '________________';
  })
  .catch(() => {
    document.getElementById('userGreeting').textContent = 'Hello, BHW!';
    const gb = document.getElementById('generated_by');
    gb.innerHTML = `
      <div class="sig-label">Report Generated by:</div>
      <hr class="sig-line">
      <div class="sig-name">________________</div>
      <div class="sig-title">Barangay Health Worker</div>
    `;
  });


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
