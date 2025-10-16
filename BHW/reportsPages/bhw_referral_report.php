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


// Get filter values
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$referral_status = $_GET['status'] ?? '';
$bhw_id = $_GET['bhw'] ?? '';
$sex = $_GET['sex'] ?? '';
$age_group = $_GET['age_group'] ?? '';
$status = $_GET['status'] ?? '';


// Fetch BHWs for dropdown
$bhw_stmt = $pdo->query("SELECT user_id, full_name FROM users WHERE role = 'BHW'");
$bhws = $bhw_stmt->fetchAll();

// Build SQL
$sql = "SELECT r.*, p.first_name, p.last_name, p.sex, p.age, v.visit_date, v.chief_complaints, u.full_name AS bhw_name
        FROM referrals r
        JOIN patient_assessment v ON r.visit_id = v.visit_id
        JOIN patients p ON r.patient_id = p.patient_id
        JOIN users u ON r.referred_by = u.user_id
        WHERE p.address LIKE :barangay";

$params = [];
$params['barangay'] = '%' . $barangayName . '%';

if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND DATE(r.referral_date) BETWEEN :from AND :to";
    $params['from'] = $from_date;
    $params['to'] = $to_date;
}

if (!empty($referral_status)) {
    $sql .= " AND r.referral_status = :status";
    $params['status'] = $referral_status;
}

if (!empty($bhw_id)) {
    $sql .= " AND r.referred_by = :bhw";
    $params['bhw'] = $bhw_id;
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

$sql .= " ORDER BY r.referral_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
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

	<title>Referrals Report</title>
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
                  <h1>Referrals</h1>
                  <ul class="breadcrumb">
                    <li><a href="#">Referral Report</a></li>
                    <li><i class="bx bx-chevron-right"></i></li>
                    <li><a class="active" href="#" onclick="history.back(); return false;">Go back</a></li>
                  </ul>
                </div>
              </div>

      <br> <br>
            </div>

<div class="history-container">


<form method="GET" class="filter-form">
    <h2>Referral Report - BHS <?php echo htmlspecialchars($barangayName); ?></h2> <br>

   
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
            if ($status) renderTag('Status', 'status', $status);
            if ($bhw_id) renderTag('Bhw', 'bhw', $bhw_id);
            if ($age_group) {
                $age_labels = [
                    'child' => 'Child (0–12)', 'teen' => 'Teen (13–19)',
                    'adult' => 'Adult (20–59)', 'senior' => 'Senior (60+)'
                ];
                renderTag('Age Group', 'age_group', $age_labels[$age_group] ?? ucfirst($age_group));
            }
        
            // If no filters, show "All"
            if (
                !$from_date && !$to_date && !$sex && !$age_group &&
                !$status && !$bhw_id 
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
            <label for="status">Status:</label>
            <select name="status" id="status">
                <option value="">-- All --</option>
                <option value="Pending" <?= $referral_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Completed" <?= $referral_status === 'Completed' ? 'selected' : '' ?>>Completed</option>
                <option value="Uncompleted" <?= $referral_status === 'Uncompleted' ? 'selected' : '' ?>>Uncompleted</option>
                <option value="Canceled" <?= $referral_status === 'Canceled' ? 'selected' : '' ?>>Canceled</option>
            </select>
        </div>

        <div class="form-item">
            <label for="bhw">Referred by:</label>
            <select name="bhw" id="bhw">
                <option value="">-- All --</option>
                <?php foreach ($bhws as $bhw): ?>
                    <option value="<?= $bhw['user_id'] ?>" <?= $bhw['user_id'] == $bhw_id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($bhw['full_name']) ?>
                    </option>
                <?php endforeach; ?>
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
    <img src="../../img/RHUlogo.png" alt="Left Logo"  class="print-logo">
    <div class="print-heading">
      <div class="ph-line-1">Republic of the Philippines</div>
      <div class="ph-line-1">Province of Camarines Norte</div>
      <div class="ph-line-2">Municipality of Daet</div>
      <div class="ph-line-3"><?php echo htmlspecialchars($barangayName); ?></div>
      <div class="ph-line-4">BHS REFERRAL REPORT</div>
      <div class="print-sub">
        (<?php
          $filters = [];
          if ($from_date) $filters[] = "From <strong>" . htmlspecialchars($from_date) . "</strong>";
          if ($to_date)   $filters[] = "To <strong>" . htmlspecialchars($to_date) . "</strong>";
          if ($referral_status) $filters[] = "Status: <strong>" . htmlspecialchars($status) . "</strong>";
          if ($bhw_id) { $bhw_name=''; foreach ($bhws as $bhw) { if ($bhw['user_id']==$bhw_id){ $bhw_name=$bhw['full_name']; break; } }
                         $filters[] = "Referred by: <strong>" . htmlspecialchars($bhw_name) . "</strong>"; }
          if ($sex) $filters[] = "Sex: <strong>" . htmlspecialchars($sex) . "</strong>";
          if ($age_group) {
            $age_labels = ['child'=>'Child (0–12)','teen'=>'Teen (13–19)','adult'=>'Adult (20–59)','senior'=>'Senior (60+)'];
            $filters[] = "Age Group: <strong>" . ($age_labels[$age_group] ?? htmlspecialchars($age_group)) . "</strong>";
          }
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
    <label><input type="checkbox" id="toggleReferralChart"> Show Visits With and Without Referral</label> <br>


</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const chartMapping = {
        toggleReferralChart: "referralChart"
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


<?php
// Count sent referrals (matches current filters)
$referral_count = count($rows);

// Build visits-without-referral query using same filters
$visit_sql = "SELECT COUNT(*) FROM patient_assessment v
    JOIN patients p ON v.patient_id = p.patient_id
    WHERE p.address LIKE :barangay
    AND v.visit_id NOT IN (SELECT visit_id FROM referrals)";
$visit_params = ['barangay' => '%' . $barangayName . '%'];

// Apply filters to visits (reuse logic)
if (!empty($from_date) && !empty($to_date)) {
    $visit_sql .= " AND DATE(v.visit_date) BETWEEN :from AND :to";
    $visit_params['from'] = $from_date;
    $visit_params['to'] = $to_date;
}
if (!empty($sex)) {
    $visit_sql .= " AND p.sex = :sex";
    $visit_params['sex'] = $sex;
}
if (!empty($age_group)) {
    switch ($age_group) {
        case 'child': $visit_sql .= " AND p.age < 13"; break;
        case 'teen':  $visit_sql .= " AND p.age BETWEEN 13 AND 19"; break;
        case 'adult': $visit_sql .= " AND p.age BETWEEN 20 AND 59"; break;
        case 'senior':$visit_sql .= " AND p.age >= 60"; break;
    }
}
if (!empty($bhw_id)) {
    $visit_sql .= " AND v.recorded_by = :bhw";
    $visit_params['bhw'] = $bhw_id;
}

$visit_stmt = $pdo->prepare($visit_sql);
$visit_stmt->execute($visit_params);
$visits_without_referral = (int)$visit_stmt->fetchColumn();
?>

<!-- Pie Chart Section -->
<div id="referralChart" class="chart" style="max-width:400px;margin:30px auto 30px auto; display: none;">
    <canvas id="referralPieChart"></canvas>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('referralPieChart').getContext('2d');
const referralCount = <?= $referral_count ?>;
const visitsWithoutReferral = <?= $visits_without_referral ?>;
const pieChart = new Chart(ctx, {
    type: 'pie',
    data: {
        labels: ['Sent Referrals', 'Visits Without Referral'],
        datasets: [{
            data: [referralCount, visitsWithoutReferral],
            backgroundColor: ['#1c538a', '#e3e6ea'],
            borderColor: ['#1c538a', '#e3e6ea'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            title: {
                display: true,
                text: 'Referrals vs. Visits Without Referral'
            }
        }
    }
});
</script>


<!-- Referral Status Distribution Pie Chart -->
<?php
// Count referral statuses based on current filters
$status_counts = [
    'Completed' => 0,
    'Uncompleted' => 0,
    'Pending' => 0,
    'Canceled' => 0
];
foreach ($rows as $row) {
    $status = ucfirst(strtolower($row['referral_status']));
    if (isset($status_counts[$status])) {
        $status_counts[$status]++;
    }
}
?>

<div class="chart" style="max-width:400px;margin:30px auto 30px auto;">
    <canvas id="statusPieChart"></canvas>
</div>
<script>
const statusCtx = document.getElementById('statusPieChart').getContext('2d');
const statusData = {
    labels: ['Completed', 'Uncompleted', 'Pending', 'Canceled'],
    datasets: [{
        data: [
            <?= $status_counts['Completed'] ?>,
            <?= $status_counts['Uncompleted'] ?>,
            <?= $status_counts['Pending'] ?>,
            <?= $status_counts['Canceled'] ?>
        ],
        backgroundColor: [
            '#2e8540',    // Completed - green
            '#d83933',    // Uncompleted - red
            '#1c538a',    // Pending - blue
            '#888888'     // Canceled - gray
        ],
        borderColor: [
            '#2e8540',
            '#d83933',
            '#1c538a',
            '#888888'
        ],
        borderWidth: 1
    }]
};
const statusPieChart = new Chart(statusCtx, {
    type: 'pie',
    data: statusData,
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            title: {
                display: true,
                text: 'Referral Status Distribution'
            }
        }
    }
});
</script>



<?php if ($rows): ?>
     <div class="report-table-container">
<table id="reportTable">
    <thead>
        <tr>
            <th>Referral Date</th>
            <th>Patient</th>
            <th>Sex</th>
            <th>Age</th>
            <th>Visit Date</th>
            <th>Chief Complaints</th>
            <th>Referral Status</th>
            <th>Referred By</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['referral_date']) ?></td>
            <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
            <td><?= htmlspecialchars($row['sex']) ?></td>
            <td><?= htmlspecialchars($row['age']) ?></td>
            <td><?= htmlspecialchars($row['visit_date']) ?></td>
            <td><?= htmlspecialchars($row['chief_complaints']) ?></td>
            <td>
                <?php 
                $statusClass = '';
                switch(strtolower($row['referral_status'])) {
                    case 'pending':
                        $statusClass = 'status-pending';
                        break;
                    case 'completed':
                        $statusClass = 'status-completed';
                        break;
                    case 'uncompleted':
                    case 'canceled':
                        $statusClass = 'status-uncompleted';
                        break;
                }
                ?>
                <span class="referral-status <?php echo $statusClass; ?>">
                    <?php echo htmlspecialchars($row['referral_status']); ?>
                </span>
            </td>
            <td><?= htmlspecialchars($row['bhw_name']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
     <br>
     
<!-- Summary -->
<div class="summary-container">
    <div class="summary">
        <h4><i class="bx bx-filter-alt"></i>Summary:</h4>
    <ul  class="summary-list">
         <li>
                <strong>Report Generated On:</strong> <?= date('Y-m-d H:i:s') ?>
            </li>
        <li><strong>Total Referrals:</strong> <?= $referral_count ?></li>
        <li><strong>Visits With Referral:</strong> <?= $referral_count ?></li>
        <li><strong>Visits Without Referral:</strong> <?= $visits_without_referral ?></li>
        <li><strong>Pending Referrals:</strong> <?= $status_counts['Pending'] ?></li>
        <li><strong>Completed Referrals:</strong> <?= $status_counts['Completed'] ?></li>
        <li><strong>Uncompleted Referrals:</strong> <?= $status_counts['Uncompleted'] ?></li>
        <li><strong>Canceled Referrals:</strong> <?= $status_counts['Canceled'] ?></li>
    </ul>
    </div>

</div>


    <br> <br>
     <span id="generated_by"></span>

<?php else: ?>
    <p>No referrals found for the selected filters.</p>
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
function exportTableToExcel(tableID, filename = 'Referral Summary Report') {
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

// Make sure the status styling is preserved in exports
function addExportStyles(doc) {
    doc.addStyle(`
        .referral-status { font-weight: bold; }
        .status-pending { color: #1c538a; }
        .status-completed { color: #2e8540; }
        .status-uncompleted, .status-canceled { color: #d83933; }
    `);
}
function printDiv() {
  // 1) Header (prefer new, fallback to old)
  const headerEl = document.querySelector('.print-letterhead, .print-header');
  const printHeader = headerEl ? headerEl.outerHTML : '';

  // 2) Clone area
  const area = document.querySelector('.print-area');
  if (!area) return;
  const clone = area.cloneNode(true);

  // 3) Replace canvases with images for each known chart
  const chartIds = ['referralPieChart', 'statusPieChart'];
  chartIds.forEach(id => {
    const live = document.getElementById(id);
    const inClone = clone.querySelector('#' + id);
    if (live && inClone) {
      const img = document.createElement('img');
      img.src = live.toDataURL('image/png');
      img.style.maxWidth = '100%';
      img.style.height = 'auto';
      inClone.parentNode.replaceChild(img, inClone);
    }
  });

  // Remove any other canvases that might be inside the clone
  clone.querySelectorAll('canvas').forEach(c => c.remove());

  // 4) Avoid duplicating header
  const headerInClone = clone.querySelector('.print-letterhead, .print-header');
  if (headerInClone) headerInClone.remove();

  // 5) Open window & print
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
          /* Letterhead in print window */
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
          /* Status colors */
          .referral-status { font-weight:bold; padding:4px 8px; border-radius:3px; display:inline-block; }
          .status-pending { color:#1c538a; border-left:2px solid #1c538a; }
          .status-completed { color:#2e8540; border-left:2px solid #2e8540; }
          .status-uncompleted, .status-canceled { color:#d83933; border-left:2px solid #d83933; }
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
