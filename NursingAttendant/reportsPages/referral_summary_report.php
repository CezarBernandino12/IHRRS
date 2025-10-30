<?php
// Connect to DB
// Connect to DB
require '../../php/db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "User is not logged in.";
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT full_name, rhu FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$rhu = $user ? $user['rhu'] : 'N/A';
$username = $user ? $user['full_name'] : 'N/A';



// Filters
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$status = $_GET['status'] ?? '';
$barangay = $_GET['barangay'] ?? '';

$params = [];

$sql = "
    SELECT 
        u.barangay,
        SUM(CASE WHEN r.referral_status IN ('Completed', 'Uncompleted', 'Pending') THEN 1 ELSE 0 END) AS total_referrals,
        SUM(CASE WHEN r.referral_status = 'Completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN r.referral_status = 'Uncompleted' THEN 1 ELSE 0 END) AS uncompleted,
        SUM(CASE WHEN r.referral_status = 'Pending' THEN 1 ELSE 0 END) AS pending
    FROM referrals r
    LEFT JOIN users u ON r.referred_by = u.user_id
    WHERE u.barangay IS NOT NULL AND u.barangay != ''
";


// Add date filter if present
if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND DATE(r.referral_date) BETWEEN :from_date AND :to_date";
    $params['from_date'] = $from_date;
    $params['to_date'] = $to_date;
}
if (!empty($status)) {
    $sql .= " AND r.referral_status = :status";
    $params['status'] = $status;
}
if (!empty($barangay)) {
    $sql .= " AND u.barangay = :barangay";
    $params['barangay'] = $barangay;
}

// Final group and order clause (only add once!)
$sql .= " GROUP BY u.barangay ORDER BY u.barangay";

// For debugging
// echo "<pre>$sql</pre>";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Totals
$total_received = 0;
$total_completed = 0;
$total_uncompleted = 0;
$total_pending = 0;



        //ADDED GENERATED REPORT FOR ACTIVITY LOG
        $stmt_log = $pdo->prepare("INSERT INTO logs (
            user_id, action, performed_by
        ) VALUES (
            :user_id, :action, :performed_by
        )");
        $stmt_log->execute([
            ':user_id' => $_SESSION['user_id'],
            ':action' => "Generated RHU Referral Summary Report",
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

	<title>Referral Summary Report</title>
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
                  <h1>Referral Summary Report</h1>
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

 <h2>Referral Intake Summary Report - <?php echo htmlspecialchars($rhu); ?></h2> <br>


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
            if ($status) renderTag('Status', 'status', $status);
            if ($barangay) renderTag('Barangay', 'barangay', $barangay);

            // If no filters, show "None"
            if (
                !$from_date && !$to_date && !$status && !$barangay
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
                            <label for="status">Status:</label>
                            <select name="status" id="status" class="form-control">
                                <option value="" <?= $status == '' ? 'selected' : '' ?>>All</option>
                                <option value="Pending" <?= $status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Completed" <?= $status == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="Uncompleted" <?= $status == 'Uncompleted' ? 'selected' : '' ?>>Uncompleted</option>
                                <option value="Cancelled" <?= $status == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="form-item">
                            <label for="barangay">Barangay:</label>
                            <select name="barangay" id="barangay" class="form-control">
                                <option value="">All</option>
                                <?php
                                // Fetch puroks that match the barangay name in the value
                                $barangay_stmt = $pdo->prepare("SELECT DISTINCT u.barangay AS value FROM referrals r LEFT JOIN users u ON r.referred_by = u.user_id WHERE u.barangay IS NOT NULL ORDER BY u.barangay;");
                                $barangay_stmt->execute();     


                                $selected_barangay = $_GET['barangay'] ?? '';
                                while ($row = $barangay_stmt->fetch()) {
                                    $value = $row['value'];
                                    $selected = ($selected_barangay === $value) ? 'selected' : '';
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
<!-- PRINT-ONLY LETTERHEAD (shows only when printing) -->
<div class="print-only-letterhead">
  <div class="print-letterhead">
    <img src="../../img/daet_logo.png" alt="Left Logo" class="print-logo">
    <div class="print-heading">
      <div class="ph-line-1">Republic of the Philippines</div>
      <div class="ph-line-1">Province of Camarines Norte</div>
      <div class="ph-line-2">Municipality of Daet</div>
      <div class="ph-line-3"><?= htmlspecialchars($rhu) ?></div>
 
    </div>
    <img src="../../img/mho_logo.png" alt="Right Logo" class="print-logo">
  </div>
  <hr class="print-rule">
</div>
<!-- /PRINT-ONLY LETTERHEAD -->

<div class="report-content">



<div class="title">
         <h2>REFERRAL INTAKE SUMMARY REPORT</h2>
<div class="print-sub">
(<?php
  $filters = [];

  if ($from_date || $to_date) {
      $readable_from = $from_date ? date("F j, Y", strtotime($from_date)) : '';
      $readable_to   = $to_date ? date("F j, Y", strtotime($to_date)) : '';

      // Combine into a readable range
      $filters[] = "<strong>" . trim($readable_from . ($readable_to ? " — " . $readable_to : '')) . "</strong>";
  }

  // Print the date range if available
  if (!empty($filters)) {
      echo implode(" ", $filters);  // ✅ actually outputs the date
  }

  if (!empty($status)) {
      echo " &nbsp;|&nbsp; Status: <strong>" . htmlspecialchars($status) . "</strong>";
  }

  if (!empty($barangay)) {
      echo " &nbsp;|&nbsp; Barangay: <strong>" . htmlspecialchars($barangay) . "</strong>";
  }

  if (empty($from_date) && empty($to_date) && empty($status) && empty($barangay)) {
      echo "All Records";
  }
?>)
</div>

</div>

 <style>

  .title { text-align: center; display: none;}
    /* Space above the summary section */
.summary-container {
  margin-top: 32px;
}

/* Two-column table styling */
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

/* Print-only tweaks: hide the Summary title, add a touch more space */
@media print {
  .summary > h3 {
    display: none !important;
  }
  .summary-container {
    margin-top: 40px;
  }
}

    @media print {
         .title {
        display: block;
    }
        .chart-title { 
           display: none;
        }
        .form-submit { 
           display: none;
        }
        
 .report-table-container {
      margin-top: 20px !important;
      margin-bottom: 40px !important;
    }
    .summary-table th,
.summary-table td {
  border: 1px solid #000000ff;
  padding: 8px 12px;
  vertical-align: top;
  text-align: left;
  word-wrap: break-word;
}

    } 
</style>

<style>
  /* Hide the print letterhead on screen */
  .print-only-letterhead { display: none; }

  @media print {
    .print-only-letterhead { display: block; }

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
    .print-sub{ font-size:12pt; margin-top:4px; }
    .print-rule{ height:1px; border:0; background:#cfd8e3; margin:8px 0 12px; }

    /* keep your existing print hides working */
    .chart-title, .form-submit { display: none !important; }
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
  #generated_by .sig-line  { display: block; width: 45mm; border-top-width: 1px; margin: 10mm 0 3mm; }
}
</style>

<!-- Chart Visibility Controls -->
<div style="margin: 20px;" class="chart-title">
    <h3>Charts:</h3>
    <label><input type="checkbox" id="toggleStatusChart"> Show Referral Status</label> <br>


</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const chartMapping = {
        toggleStatusChart: "statusChart"
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

<br>
<!-- Pie Chart Section -->
<div id="statusChart" style="max-width: 400px; margin: 30px auto 0 auto; text-align:center; display: none;">
    <h3 class="chart-title">Referral Status</h3>
    <canvas id="statusPieChart"></canvas>
    <p id="noDataMessage" style="display:none; color:#666; margin-top:10px;">No data available</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Prepare data for the pie chart (referral distribution)
    <?php
        $total_received = 0;
        $total_completed = 0;
        $total_uncompleted = 0;
        $total_pending = 0;

        foreach ($rows as $row) {
            $total_received += $row['total_referrals'];
            $total_completed += $row['completed'];
            $total_uncompleted += $row['uncompleted'];
            $total_pending += $row['pending'];
        }

        $referral_counts = [
            'Pending' => $total_pending,
            'Completed' => $total_completed,
            'Uncompleted' => $total_uncompleted
        ];
    ?>

    const referralLabels = <?= json_encode(array_keys($referral_counts)) ?>;
    const referralData   = <?= json_encode(array_values($referral_counts)) ?>;

    const total = referralData.reduce((a, b) => a + b, 0);

    if (total > 0) {
        // Show chart, hide message
        document.getElementById('statusPieChart').style.display = 'block';
        document.getElementById('noDataMessage').style.display = 'none';

        const ctx = document.getElementById('statusPieChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: referralLabels,
                datasets: [{
                    data: referralData,
                    backgroundColor: [
                        '#4e79a7', '#59bd77ff', '#e15759'
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
    } else {
        // Hide chart, show message
        document.getElementById('statusPieChart').style.display = 'none';
        document.getElementById('noDataMessage').style.display = 'block';
    }
</script>

<!-- Bar Chart Section -->
<div style="max-width: 600px; margin: 30px auto; text-align:center;">
    <h3 class="chart-title">Total Referrals Received Per Barangay</h3>
    <canvas id="barangayBarChart"></canvas>
    <p id="noBarDataMessage" style="display:none; color:#666; margin-top:10px;">No data available</p>
</div>

<script>
    // Prepare data for the bar chart (referrals per barangay)
    <?php
        $barangay_labels = [];
        $barangay_referrals = [];

        foreach ($rows as $row) {
            $barangay_labels[] = $row['barangay'];
            $barangay_referrals[] = $row['total_referrals'];
        }
    ?>

    const barangayLabels = <?= json_encode($barangay_labels) ?>;
    const barangayData = <?= json_encode($barangay_referrals) ?>;

    const totalBarangayReferrals = barangayData.reduce((a, b) => a + b, 0);

    if (totalBarangayReferrals > 0) {
        // Show chart, hide message
        document.getElementById('barangayBarChart').style.display = 'block';
        document.getElementById('noBarDataMessage').style.display = 'none';

        const ctxBar = document.getElementById('barangayBarChart').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: barangayLabels,
                datasets: [{
                    label: 'Total Referrals',
                    data: barangayData,
                    backgroundColor: '#4e79a7',
                    borderColor: '#4e79a7',
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
                    x: { title: { display: true, text: 'Barangay' } },
                    y: { title: { display: true, text: 'Total Referrals' }, beginAtZero: true }
                }
            }
        });
    } else {
        // Hide chart, show message
        document.getElementById('barangayBarChart').style.display = 'none';
        document.getElementById('noBarDataMessage').style.display = 'block';
    }
</script>

<div class="report-table-container">
<table id="reportTable"> 
    <thead>
        <tr>
            <th>Barangay</th>
            <th>Total Referrals Received</th>
            <th>Completed</th>
            <th>Uncompleted</th>
            <th>Pending</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $total_received = 0;
		$total_completed = 0;
		$total_uncompleted = 0;
        $total_pending = 0;
        foreach ($rows as $row): 
            $total_received += $row['total_referrals'];
			$total_completed += $row['completed'];
			$total_uncompleted += $row['uncompleted'];
            $total_pending += $row['pending'];
        ?>
        <tr>
            <td><?= htmlspecialchars($row['barangay']) ?></td>
            <td><?= $row['total_referrals'] ?></td>
            <td><?= $row['completed'] ?></td>
            <td><?= $row['uncompleted'] ?></td>
            <td><?= $row['pending'] ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<br> <br>
<div class="summary-container">
  <div class="summary">
    <h3>Report Details</h3>

    <table class="summary-table">
      <colgroup>
        <col style="width:40%">
        <col style="width:60%">
      </colgroup>
      <tbody>
        <tr>
          <th>Report Generated On</th>
          <td><?= date('F j, Y g:i:s A') ?></td>
        </tr>
        <tr>
          <th>Total Referrals Received</th>
          <td><?= $total_received ?></td>
        </tr>
        <tr>
          <th>Completed Referrals</th>
          <td><?= $total_completed ?></td>
        </tr>
        <tr>
          <th>Uncompleted Referrals</th>
          <td><?= $total_uncompleted ?></td>
        </tr>
        <tr>
          <th>Pending Referrals</th>
          <td><?= $total_pending ?></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<span id="generated_by"></span>

</div> </div>



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

<script>
function exportTableToExcel(tableID, filename = 'Referral Summary Report') {
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



//PRINT
function printDiv() {
  const originalArea = document.querySelector(".print-area");
  if (!originalArea) {
    alert("Error: Missing .print-area on page.");
    return;
  }

  const clone = originalArea.cloneNode(true);

  // Remove all charts/controls in the clone
  clone.querySelectorAll('canvas').forEach(c => c.remove());
  clone.querySelectorAll('.chart-title').forEach(el => el.remove());

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
          .print-heading .ph-line-2{ font-size:14pt; font-weight:800; }
          .print-heading .ph-line-3{ font-size:11pt; font-weight:500; }
          .print-heading .ph-line-4{ font-size:12pt; font-weight:800; margin-top:4px; letter-spacing:.3px; }
          .print-sub{ font-size:12pt; margin-top:4px; }
          .print-rule{ height:1px; border:0; background:#cfd8e3; margin:8px 0 12px; }
        </style>
      </head>
      <body>${clone.innerHTML}</body>
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
