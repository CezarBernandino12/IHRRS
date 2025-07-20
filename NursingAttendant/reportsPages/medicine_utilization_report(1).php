<?php

require '../../php/db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "User is not logged in.";
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch user info

$selected_month = $_GET['month'] ?? date('Y-m');

// Query
$sql = "
SELECT 
    CONCAT(p.last_name, ', ', p.first_name, ' ', COALESCE(p.middle_name, ''), ' ', COALESCE(p.extension, '')) AS full_name,
    p.address,
    p.date_of_birth,
    p.age,
    p.sex,
    p.philhealth_member_no,
    SUM(CASE WHEN md.medicine_name = 'AMLODIPINE' THEN md.quantity_dispensed ELSE 0 END) AS amlodipine,
    SUM(CASE WHEN md.medicine_name = 'LOSARTAN' THEN md.quantity_dispensed ELSE 0 END) AS losartan,
    SUM(CASE WHEN md.medicine_name = 'METOPROLOL' THEN md.quantity_dispensed ELSE 0 END) AS metoprolol,
    SUM(CASE WHEN md.medicine_name = 'SIMVASTATIN' THEN md.quantity_dispensed ELSE 0 END) AS simvastatin,
    SUM(CASE WHEN md.medicine_name = 'METFORMIN' THEN md.quantity_dispensed ELSE 0 END) AS metformin,
    SUM(CASE WHEN md.medicine_name = 'GLICLAZIDE' THEN md.quantity_dispensed ELSE 0 END) AS gliclazide
FROM rhu_medicine_dispensed md
JOIN rhu_consultations c ON md.consultation_id = c.consultation_id
JOIN patients p ON c.patient_id = p.patient_id
WHERE DATE_FORMAT(md.dispensed_date, '%Y-%m') = :selected_month
GROUP BY p.patient_id
HAVING 
    amlodipine > 0 OR
    losartan > 0 OR
    metoprolol > 0 OR
    simvastatin > 0 OR
    metformin > 0 OR
    gliclazide > 0
ORDER BY p.last_name, p.first_name
";


$stmt = $pdo->prepare($sql);
$stmt->execute(['selected_month' => $selected_month]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" href="../img/logo.png">
	<link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
	<link rel="stylesheet" href="../../BHW/css/history.css">
    <link rel="stylesheet" href="reportsDesign.css">

	<title>Referral Intake Summary Report</title>
</head>
<body>

	<!-- Sidebar Section -->
<section id="sidebar">
		<a href="#" class="brand">
			<img src="../img/logo.png" alt="RHULogo" class="logo">
			<span class="text">Hello User</span>
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
				<div class="form-input">
					<input type="search" placeholder="Search...">
					<button type="submit" class="search-btn">
						<i class="bx bx-search"></i>
					</button>
				</div>
			</form>
			<a href="notif.html" class="notification">
				<i class="bx bxs-bell"></i>
			</a>
			<a href="#" class="profile">
				<img src="../img/profile.jpg">
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
  <p>Department of Health</p>
 
  <h2><?php echo htmlspecialchars($barangayName); ?></h2>
  <br> 
  <h2>DOH MAINTAINANCE MEDICINE UTILIZATION REPORT</h2>
</div>
<div class="report-content">
<!-- Summary Section -->
<br>


<!-- Selected Filters Section -->

<br>
<table border="1" cellpadding="8" cellspacing="0"> 
<thead>
        <tr>
        <th>Patient Name</th>
            <th>Address</th>
            <th>Age</th>
            <th>Date of Birth</th>
            <th>Gender</th>
            <th>PhilHealth No.</th>
            <th>Amlodipine</th>
            <th>Losartan</th>
            <th>Metoprolol</th>
            <th>Simvastatin</th>
            <th>Metformin</th>
            <th>Gliclazide</th>
            <th>Signature</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
        <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($row['address']) ?></td>
            <td><?= htmlspecialchars($row['age']) ?></td>
            <td><?= htmlspecialchars($row['date_of_birth']) ?></td>
            <td><?= htmlspecialchars($row['sex']) ?></td>
            <td><?= htmlspecialchars($row['philhealth_member_no']) ?></td>
            <td><?= $row['amlodipine'] ?></td>
            <td><?= $row['losartan'] ?></td>
            <td><?= $row['metoprolol'] ?></td>
            <td><?= $row['simvastatin'] ?></td>
            <td><?= $row['metformin'] ?></td>
            <td><?= $row['gliclazide'] ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<br> <br>


</div> </div> </div>

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
</script>

</body>
</html>
