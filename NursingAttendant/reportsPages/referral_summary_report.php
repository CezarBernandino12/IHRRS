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
$stmt = $pdo->prepare("SELECT barangay FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$barangayName = $user ? $user['barangay'] : 'N/A';

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

 <h2>RHU Referral Intake Summary Report</h2> <br>


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
<div class="print-header" style="text-align: center;">
  <h3>Republic of the Philippines</h3>
  <p>Province of Camarines Norte</p>
  <h3>Municipality of Daet</h3>
  <h2>Rural Health Unit</h2>
  <br> 
  <h2>REFERRAL INTAKE SUMMARY REPORT</h2>
  <h3></h3>
</div>
<div class="report-content">
<!-- Summary Section -->
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

<div class="summary">
    <h3>Summary:</h3>
     
    <p><strong>Report Generated On:</strong> <?= date('Y-m-d H:i:s') ?></p>
	<p><strong>Total Referrals Received:</strong> <?= $total_received ?></p>
	<p><strong>Completed Referrals:</strong> <?= $total_completed ?></p>
	<p><strong>Uncompleted Referrals:</strong> <?= $total_uncompleted ?></p>
	<p><strong>Pending Referrals:</strong> <?= $total_pending ?></p>
</div> 


<br>
<div class="report-table-container">
<table border="1" cellpadding="8" cellspacing="0" id="reportTable"> 
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
 </div>
<br> <br>


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
    if (document.getElementById('toggleStatusChart').checked) {
        chartsHTML += getChartImage('statusPieChart', 'Referral Status');
    }
    chartsHTML += getChartImage('barangayBarChart', 'Total Referrals Received Per Barangay');

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

    // Remove all canvases from the cloned area (since we replace them with images)
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
    printWindow.document.write(printHeader);          // Print header first
    printWindow.document.write(chartsHTML);           // Then charts
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
