<?php
require '../../php/db_connect.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    echo "User is not logged in.";
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
$medicine = $_GET['medicine'] ?? '';
$bhw_id = $_GET['bhw_id'] ?? '';

// Build SQL
$sql = "SELECT m.*, v.visit_date, v.bhw_id, p.first_name, p.last_name, p.sex, p.age, u.full_name AS bhw_name
        FROM bhs_medicine_dispensed m
        JOIN bhs_visits v ON m.visit_id = v.visit_id
        JOIN patients p ON v.patient_id = p.patient_id
        LEFT JOIN users u ON v.bhw_id = u.user_id
        WHERE 1=1";

$params = [];

if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND DATE(m.dispensed_date) BETWEEN :from_date AND :to_date";
    $params['from_date'] = $from_date;
    $params['to_date'] = $to_date;
}

if (!empty($medicine)) {
    $sql .= " AND m.medicine_name LIKE :medicine";
    $params['medicine'] = "%$medicine%";
}

if (!empty($bhw_id)) {
    $sql .= " AND v.bhw_id = :bhw_id";
    $params['bhw_id'] = $bhw_id;
}

$sql .= " ORDER BY m.dispensed_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Get list of BHWs for dropdown
$bhw_stmt = $pdo->query("SELECT user_id, full_name FROM users WHERE role = 'BHW'");
$bhws = $bhw_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" href="../../img/logo.png">
	<link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/reportsDesign.css">

	<title>Medicine Dispensation Report</title>
</head>
<body>
 
	<!-- Sidebar Section -->
<section id="sidebar">
		<a href="#" class="brand">
			<img src="../../img/logo.png" alt="RHULogo" class="logo">
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
		</ul>
		<ul class="side-menu">
			<li>
				<a href="../role.html" class="logout" onclick="return confirmLogout()">
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
				<img src="../../img/profile.jpg">
			</a>
		</nav>

		<main>
            
            <div class="head-title">
                <div class="left">
                  <h1>Medicine Dispensation Report</h1>
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
            <label for="medicine">Medicine:</label>
            <input type="text" name="medicine" id="medicine" class="form-control" value="<?= htmlspecialchars($medicine) ?>" placeholder="Enter medicine name">
        </div>

        <div class="form-item">
            <label for="bhw">BHW:</label>
            <select name="bhw_id" id="bhw" class="form-control">
                <option value="">All</option>
                <?php foreach ($bhws as $bhw): ?>
                    <option value="<?= $bhw['user_id'] ?>" <?= $bhw_id == $bhw['user_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($bhw['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-item-wrapper">
            <button type="submit" class="btn-submit">Filter</button>
        </div>
    </div>

    <div class="form-submit">
        <button type="button" class="btn-export" onclick="exportTableToExcel('reportTable')">📁 Export to Excel</button>
        <button type="button" class="btn-export" onclick="exportTableToPDF()">📄 Export to PDF</button>
        <button type="button" class="btn-print" onclick="printDiv()">🖨️ Print this page</button>
    </div>
</form>


<div class="main-content">


<div class="print-area">
<div class="print-header" style="text-align: center;">
  <h3>Republic of the Philippines</h3>
  <p>Province of Camarines Norte</p>
  <h3>Municipality of Daet</h3>
  <h2><?php echo htmlspecialchars($barangayName); ?></h2>
  <br> 
  <h2>MEDICINE DISPENSATION REPORT</h2>
</div>
<div class="report-content">


<?php if ($rows): ?>
    <div class="summary-container">
    <div class="summary">
        <h4><i class="bx bx-filter-alt"></i> Factors Applied:</h4>
        <ul class="summary-list">
            <?php if (!empty($from_date) && !empty($to_date)): ?>
                <li><strong>Date Range:</strong> <?= htmlspecialchars($from_date) ?> to <?= htmlspecialchars($to_date) ?></li>
            <?php endif; ?>
            <?php if (!empty($medicine)): ?>
                <li><strong>Medicine:</strong> <?= htmlspecialchars($medicine) ?></li>
            <?php endif; ?>
            <?php if (!empty($bhw_id)): ?>
                <?php
                    $selected_bhw = array_filter($bhws, fn($b) => $b['user_id'] == $bhw_id);
                    $bhw_name = $selected_bhw ? reset($selected_bhw)['full_name'] : 'Unknown BHW';
                ?>
                <li><strong>BHW:</strong> <?= htmlspecialchars($bhw_name) ?></li>
            <?php endif; ?>
            <?php if (empty($from_date) && empty($to_date) && empty($medicine) && empty($bhw_id)): ?>
                <li><em>No filters applied. Showing all records.</em></li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="summary">
        <strong><i class="bx bx-file"></i> Total Records:</strong> <?= count($rows) ?>
    </div>
</div>



    <table>
        <thead>
            <tr>
                <th>Dispensed Date</th>
                <th>Patient Name</th>
                <th>Sex</th>
                <th>Age</th>
                <th>Medicine Name</th>
                <th>Quantity</th>
                <th>Visit Date</th>
                <th>BHW</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['dispensed_date']) ?></td>
                <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                <td><?= htmlspecialchars($row['sex']) ?></td>
                <td><?= htmlspecialchars($row['age']) ?></td>
                <td><?= htmlspecialchars($row['medicine_name']) ?></td>
                <td><?= htmlspecialchars($row['quantity_dispensed']) ?></td>
                <td><?= htmlspecialchars($row['visit_date']) ?></td>
                <td><?= htmlspecialchars($row['bhw_name']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
<?php else: ?>
    <p>No records found for the selected filters.</p>
<?php endif; ?>
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
