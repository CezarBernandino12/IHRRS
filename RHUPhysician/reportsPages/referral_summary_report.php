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
$stmt = $pdo->prepare("SELECT rhu FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$rhu = $user ? $user['rhu'] : 'N/A';


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
    WHERE 1 = 1
";

if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND r.referral_date >= :from_date AND r.referral_date < DATE_ADD(:to_date, INTERVAL 1 DAY)";
    $params['from_date'] = $from_date;   // e.g., 2025-01-01
    $params['to_date']   = $to_date;     // e.g., 2025-01-31
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
    
<style>
    .form-submit-bottom {
    justify-content: flex-start !important;
  }
  #reportTable th {
    cursor: pointer;
    position: relative;
    user-select: none;
  }
  #reportTable th .sort-indicator {
    margin-left: 6px;
    font-size: 11px;
    opacity: 0.7;
  }
  #reportTable th.is-sorted-asc  .sort-indicator::after { content: "▲"; }
  #reportTable th.is-sorted-desc .sort-indicator::after { content: "▼"; }
  #reportTable th.no-sort { cursor: default; }
</style>


<!-- Sidebar Section -->
	<section id="sidebar">
		<a href="#" class="brand">
			<img src="../../img/logo.png" alt="RHULogo" class="logo">
			<span class="text">Physician</span>
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
					<i class="bx bxs-search"></i>
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
   
        <div class="form-submit">
               <button type="button" class="btn-export" id="openFilterModal">Filter</button>
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

            // If no filters, show "All"
            if (
                !$from_date && !$to_date && !$status && !$barangay
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
    <img src="../../img/Plogo.png" alt="Left Logo" class="print-logo">
    <div class="print-heading">
      <div class="ph-line-1">Republic of the Philippines</div>
      <div class="ph-line-1">Province of Camarines Norte</div>
      <div class="ph-line-2">Municipality of Daet</div>
      <div class="ph-line-3"><?= htmlspecialchars($rhu) ?></div>
      <div class="ph-line-4">REFERRAL INTAKE SUMMARY REPORT</div>
      <div class="print-sub">
        (<?php
          $parts = [];
          if (!empty($from_date)) $parts[] = "From <strong>" . htmlspecialchars($from_date) . "</strong>";
          if (!empty($to_date))   $parts[] = "To <strong>" . htmlspecialchars($to_date) . "</strong>";
          if (!empty($status))    $parts[] = "Status: <strong>" . htmlspecialchars($status) . "</strong>";
          if (!empty($barangay))  $parts[] = "Barangay: <strong>" . htmlspecialchars($barangay) . "</strong>";
          echo $parts ? implode(" &nbsp;|&nbsp; ", $parts) : "All Records";
        ?>)
      </div>
    </div>
    <img src="../../img/RHUlogo.png" alt="Right Logo" class="print-logo">
  </div>
  <hr class="print-rule">
</div>

<div class="report-content">
<!-- Summary Section -->
 <style>
@media print {
  .summary > h3 { display: none !important; }
  .summary-container { margin-top: 50px; }
}

  .print-only-letterhead { display: none; }
  @media print {
    .print-only-letterhead { display: block; }
    .print-header { display: none !important; }
    .print-letterhead{
      display: grid;
      grid-template-columns: 64px auto 64px;
      align-items: center;
      justify-content: center;
      column-gap: 30px;
      margin: 0 auto 10px;
      text-align: center;
      width: fit-content;
    }
    .print-logo{ width:64px; height:64px; object-fit:contain; }
    .print-heading{ line-height:1.1; color:#000; }
    .print-heading .ph-line-1{ font-size:12pt; font-weight:500; margin-bottom:4px;}
    .print-heading .ph-line-2{ font-size:14pt; font-weight:500; margin-bottom:4px;}
    .print-heading .ph-line-3{ font-size:11pt; font-weight:500; margin-bottom:4px;}
    .print-heading .ph-line-4{ font-size:12pt; font-weight:600; margin-top:15px; letter-spacing:.3px; }
    .print-sub{ font-size:10.5pt; margin-top:4px; }
    .print-rule{ height:1px; border:0; background:#cfd8e3; margin:8px 0 12px; }
    .chart-title, .form-submit { display: none !important; }
  }
</style>

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



<br>
<div class="report-table-container">
<table border="1" cellpadding="8" cellspacing="0" id="reportTable"> 

        <thead>
        <tr>
            <th data-type="string">Barangay</th>
            <th data-type="number">Total Referrals Received</th>
            <th data-type="number">Completed</th>
            <th data-type="number">Uncompleted</th>
            <th data-type="number">Pending</th>
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
 </div>
<br> <br>
<div class="summary">
  <h3>Summary:</h3>

  <table class="summary-table" id="summaryTable">
    <thead>
      <tr>
        <th>Metric</th>
        <th>Value</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>Report Generated On</td>
        <td><?= date('F j, Y g:i:s A') ?></td>
      </tr>
      <tr>
        <td>Total Referrals Received</td>
        <td class="num"><?= (int)$total_received ?></td>
      </tr>
      <tr>
        <td>Completed Referrals</td>
        <td class="num"><?= (int)$total_completed ?></td>
      </tr>
      <tr>
        <td>Uncompleted Referrals</td>
        <td class="num"><?= (int)$total_uncompleted ?></td>
      </tr>
      <tr>
        <td>Pending Referrals</td>
        <td class="num"><?= (int)$total_pending ?></td>
      </tr>
      <?php if (isset($total_cancelled)) : ?>
      <tr>
        <td>Cancelled Referrals</td>
        <td class="num"><?= (int)$total_cancelled ?></td>
      </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<div class="form-submit form-submit-bottom" style="margin-top: 24px; display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
  <button type="button" class="btn-export" onclick="exportTableToExcel('reportTable')">Export to Excel</button>
  <button type="button" class="btn-export" onclick="exportTableToPDF()">Export to PDF</button>
  <button type="button" class="btn-print"  onclick="printDiv()">Print this page</button>
</div>

</div> </div>
<style>
.summary-table thead th:nth-child(2) {
  text-align: left;
}



  .summary-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 8px;
  }
  .summary-table th, .summary-table td {
    border: 1px solid #dfe3e8;
    padding: 8px 10px;
    font-size: 14px;
  }
  .summary-table thead th {
    background: #f6f8fa;
    text-align: left;
    font-weight: 600;
  }

.summary-table td.num {
  text-align: left;             
  font-variant-numeric: tabular-nums;
  font-feature-settings: "tnum";     
  white-space: nowrap;           
  padding-right: 10px;         
}

  @media print {
    .summary-table th, .summary-table td { border-color: #000; }

     .report-table-container {
      margin-top: 80px !important;
      margin-bottom: 40px !important;
    }
  }
  
</style>

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
        
// Prefer the print-only letterhead; fallback to .print-header
const headerBlock = document.querySelector('.print-only-letterhead') || document.querySelector('.print-header');
if (headerBlock) {
  const headerClone = headerBlock.cloneNode(true);
  headerClone.querySelectorAll('script').forEach(s => s.remove());
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
        if (headerRow && !headerRow.querySelector('th:last-child')?.textContent.includes('Signature')) {
            const signatureHeader = document.createElement('th');
            signatureHeader.textContent = 'Signature';
            headerRow.appendChild(signatureHeader);
            
            // Add empty signature cells for each row
            const rows = tableClone.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const signatureCell = document.createElement('td');
                signatureCell.textContent = ''; // Empty for Excel
                row.appendChild(signatureCell);
            });
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
  const area = document.querySelector(".print-area");
  if (!area) { alert("Nothing to print."); return; }

  const clone = area.cloneNode(true);

  // Remove charts/controls from clone
  clone.querySelectorAll('canvas, .chart-title, #statusChart, #barangayBarChart, #statusPieChart, #noDataMessage, #noBarDataMessage')
       .forEach(el => el.remove());

  // ✅ Add Signature column ONLY to the main report table
  const reportTable = clone.querySelector("#reportTable");
  if (reportTable) {
    const headerRow = reportTable.querySelector("thead tr");
    if (headerRow) {
      const lastTh = headerRow.lastElementChild;
      const hasSignature = lastTh && /Signature/i.test((lastTh.textContent || '').trim());
      if (!hasSignature) {
        const th = document.createElement("th");
        th.textContent = "Signature";
        headerRow.appendChild(th);
        reportTable.querySelectorAll("tbody tr").forEach(tr => {
          const td = document.createElement("td");
          td.style.height = "30px";
          tr.appendChild(td);
        });
      }
    }
  }

  (function() {
  const table = document.getElementById('reportTable');
  if (!table) return;

  const thead = table.tHead || table.querySelector('thead');
  const tbody = table.tBodies[0];

  // Add arrow placeholders to sortable headers
  [...thead.querySelectorAll('th')].forEach(th => {
    if (th.classList.contains('no-sort')) return;
    const ind = document.createElement('span');
    ind.className = 'sort-indicator';
    th.appendChild(ind);
  });

  function parseDate(v) {
    const t = (v || '').trim();
    if (/^\d{4}-\d{2}-\d{2}(?:\s+\d{2}:\d{2}(?::\d{2})?)?$/.test(t)) {
      return new Date(t.replace(' ', 'T'));
    }
    const d = new Date(t);
    return isNaN(d.getTime()) ? null : d;
  }

  function detectType(colIdx) {
    const th = thead.querySelectorAll('th')[colIdx];
    if (th?.dataset?.type) return th.dataset.type;
    for (const tr of tbody.rows) {
      const txt = (tr.cells[colIdx]?.textContent || '').trim();
      if (!txt) continue;
      const d = parseDate(txt);
      if (d) return 'date';
      const n = txt.replace(/,/g, '');
      if (!isNaN(n) && n !== '') return 'number';
      return 'string';
    }
    return 'string';
  }

  function cellValue(tr, idx, type) {
    const raw = (tr.cells[idx]?.textContent || '').trim();
    if (type === 'number') {
      const n = parseFloat(raw.replace(/,/g, ''));
      return isNaN(n) ? Number.NEGATIVE_INFINITY : n;
    }
    if (type === 'date') {
      const d = parseDate(raw);
      return d ? d.getTime() : Number.NEGATIVE_INFINITY;
    }
    return raw.toLowerCase();
  }

  function clearHeaderStates(exceptIdx) {
    [...thead.querySelectorAll('th')].forEach((th, i) => {
      if (i !== exceptIdx) th.classList.remove('is-sorted-asc', 'is-sorted-desc');
    });
  }

  function sortBy(idx, dir) {
    const type = detectType(idx);
    const rows = [...tbody.rows];

    rows.sort((a, b) => {
      const va = cellValue(a, idx, type);
      const vb = cellValue(b, idx, type);
      if (va < vb) return dir === 'asc' ? -1 : 1;
      if (va > vb) return dir === 'asc' ?  1 : -1;
      return 0;
    });

    const frag = document.createDocumentFragment();
    rows.forEach(r => frag.appendChild(r));
    tbody.appendChild(frag);
  }

  // Click handlers
  [...thead.querySelectorAll('th')].forEach((th, idx) => {
    if (th.classList.contains('no-sort')) return;
    th.addEventListener('click', () => {
      const isAsc = th.classList.contains('is-sorted-asc');
      const nextDir = isAsc ? 'desc' : 'asc';
      clearHeaderStates(idx);
      th.classList.toggle('is-sorted-asc',  nextDir === 'asc');
      th.classList.toggle('is-sorted-desc', nextDir === 'desc');
      sortBy(idx, nextDir);
    });
  });

  // Default sort: column 1 (“Total Referrals Received”) DESC
  const defaultCol = 1, defaultDir = 'desc';
  const defaultTh = thead.querySelectorAll('th')[defaultCol];
  if (defaultTh && !defaultTh.classList.contains('no-sort')) {
    defaultTh.classList.add(defaultDir === 'asc' ? 'is-sorted-asc' : 'is-sorted-desc');
    sortBy(defaultCol, defaultDir);
  }
})();

  // Prefer letterhead block
  const headerSource = document.querySelector(".print-only-letterhead") || document.querySelector(".print-header");
  const headerHTML = headerSource ? headerSource.outerHTML : "";

  // Don’t duplicate header inside body content
  const headerInClone = clone.querySelector(".print-only-letterhead, .print-header");
  if (headerInClone) headerInClone.remove();

  const w = window.open("", "", "height=900,width=1100");
  if (!w) { alert("Please enable pop-ups to print."); return; }

  w.document.write(`<!doctype html><html><head><title>Print Report</title>
    <meta charset="utf-8">
    <style>
      body { font-family: Arial, sans-serif; font-size: 12px; color:#000; margin: 20px; }
      table { width: 100%; border-collapse: collapse; }
      th, td { border: 1px solid #000; padding: 4px; text-align: left; vertical-align: middle; }
      thead { background:#f0f0f0; }

      /* 🔒 Keep Summary table tidy and two-column */
      .summary-table { table-layout: fixed; }
      .summary-table th:first-child, .summary-table td:first-child { width: 65%; }
      .summary-table th:nth-child(2), .summary-table td:nth-child(2) { width: 35%; }

      .print-only-letterhead { display:block; }
      .print-letterhead{
        display:grid; grid-template-columns:64px auto 64px; align-items:center; justify-content:center;
        column-gap:14px; margin:0 auto 10px; text-align:center; width:fit-content;
      }
      .print-logo{ width:64px; height:64px; object-fit:contain; }
      .print-heading{ line-height:1.1; color:#000; }
      .print-heading .ph-line-1{ font-size:12pt; font-weight:500; }
      .print-heading .ph-line-2{ font-size:14pt; font-weight:800; }
      .print-heading .ph-line-3{ font-size:11pt; font-weight:500; }
      .print-heading .ph-line-4{ font-size:12pt; font-weight:800; margin-top:4px; letter-spacing:.3px; }
      .print-sub{ font-size:10.5pt; margin-top:4px; }
      .print-rule{ height:1px; border:0; background:#cfd8e3; margin:8px 0 12px; }
    </style>
  </head><body>`);
  w.document.write(headerHTML);
  w.document.write(clone.innerHTML);
  w.document.write(`</body></html>`);
  w.document.close();
  w.onload = () => { try { w.focus(); w.print(); } finally { w.close(); } };
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
