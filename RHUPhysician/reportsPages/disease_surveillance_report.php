<?php
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

// Filter: selected month (format: YYYY-MM)
$selected_month = $_GET['month'] ?? date('Y-m');

// Prepare SQL
$sql = "
    SELECT 
        rc.diagnosis AS disease_condition,
        COUNT(rc.consultation_id) AS num_cases,
        CONCAT(
            SUM(CASE WHEN p.age BETWEEN 0 AND 5 THEN 1 ELSE 0 END), ' (0–5), ',
            SUM(CASE WHEN p.age BETWEEN 6 AND 12 THEN 1 ELSE 0 END), ' (6–12), ',
            SUM(CASE WHEN p.age BETWEEN 13 AND 19 THEN 1 ELSE 0 END), ' (13–19), ',
            SUM(CASE WHEN p.age BETWEEN 20 AND 59 THEN 1 ELSE 0 END), ' (20–59), ',
            SUM(CASE WHEN p.age >= 60 THEN 1 ELSE 0 END), ' (60+)'
        ) AS age_group_affected,
        u.barangay
    FROM rhu_consultations rc
    INNER JOIN patients p ON rc.patient_id = p.patient_id
    LEFT JOIN users u ON u.user_id = rc.doctor_id
    WHERE rc.diagnosis IS NOT NULL 
      AND rc.diagnosis != ''
      AND DATE_FORMAT(rc.consultation_date, '%Y-%m') = :selected_month
    GROUP BY rc.diagnosis, u.barangay
    ORDER BY num_cases DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['selected_month' => $selected_month]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Compute total per disease
$disease_totals = [];

foreach ($rows as $row) {
    $disease = $row['disease_condition'];
    $cases = (int)$row['num_cases'];

    if (!isset($disease_totals[$disease])) {
        $disease_totals[$disease] = 0;
    }
    $disease_totals[$disease] += $cases;
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

	<title>Referral Intake Summary Report</title>
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
                <span id="userGreeting">Hello RHU Physician!</span>
            </div>
			<a href="#" class="profile">
				<img src="../../img/doctor.png">
			</a>
		</nav>


		<main>
            
            <div class="head-title">
                <div class="left">
                  <h1>Referral Intake Summary Report</h1>
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
<form method="GET" class="filter-form" style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;">
<label for="month">Filter by Month:</label>
<input type="month" name="month" id="month" value="<?= htmlspecialchars($filter_month) ?>">

  <button type="submit" class="btn-filter" style="background-color: #1c538a; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer;">
    Filter
  </button>

  

    <button onclick="exportTableToExcel('reportTable')" class="btn btn-success">📁 Export to Excel</button>
<button onclick="exportTableToPDF()" class="btn btn-danger">📄 Export to PDF</button>

    <button onclick="printDiv()" class="btn-print">🖨️ Print this page</button>
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
</div>
<div class="report-content">
<!-- Summary Section -->
<br>


<!-- Selected Filters Section -->

<br>
<table border="1" cellpadding="8" cellspacing="0"> 
<thead>
        <tr>
            <th>Disease/Condition</th>
            <th>Number of Cases</th>
            <th>Age Group Affected</th>
            <th>Barangay</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['disease_condition']) ?></td>
                <td><?= $row['num_cases'] ?></td>
                <td><?= htmlspecialchars($row['age_group_affected']) ?></td>
                <td><?= htmlspecialchars($row['barangay'] ?? 'N/A') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<br> <br>
<div class="summary">
    <h3>Total Cases per Disease</h3>
    <?php foreach ($disease_totals as $disease => $total_number): ?>
        <p><strong>      <?= htmlspecialchars($disease) ?>:</strong> <?= $total_number ?></p>
    <?php endforeach; ?>
</div> 

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
    const divContents = document.querySelector(".print-area").innerHTML;

    const printWindow = window.open('', '', 'height=700,width=900');
    printWindow.document.write('<html><head><title>Print Report</title>');
    
    // OPTIONAL: Minimal styles for cleaner layout
    printWindow.document.write(`
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; color: black; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #000; padding: 4px; text-align: left; }
            thead { background-color: #f0f0f0; }
        </style>
    `);

    printWindow.document.write('</head><body>');
    printWindow.document.write(divContents);
    printWindow.document.write('</body></html>');

    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
}




function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'block';
    return false; // Prevent the default link behavior
}

function closeModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

function proceedLogout() {
    window.location.href = '../role.html';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('logoutModal');
    if (event.target == modal) {
        closeModal();
    }
}
fetch('../php/getUserName.php')
    .then(response => response.json())
    .then(data => {
        if (data.full_name) {
            document.getElementById('userGreeting').textContent = `Hello, ${data.full_name}!`;
        } else {
            document.getElementById('userGreeting').textContent = 'Hello, Physician!';
        }
    })
    .catch(error => {
        console.error('Error fetching user name:', error);
        document.getElementById('userGreeting').textContent = 'Hello, Physician!';
    });

</script>

</body>
</html>
