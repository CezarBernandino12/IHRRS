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

// Fetch BHWs for dropdown
$bhw_stmt = $pdo->query("SELECT user_id, full_name FROM users WHERE role = 'BHW'");
$bhws = $bhw_stmt->fetchAll();

// Build SQL
$sql = "SELECT r.*, p.first_name, p.last_name, p.sex, p.age, v.visit_date, v.chief_complaints, u.full_name AS bhw_name
        FROM referrals r
        JOIN bhs_visits v ON r.visit_id = v.visit_id
        JOIN patients p ON r.patient_id = p.patient_id
        JOIN users u ON r.referred_by = u.user_id
        WHERE 1=1";

$params = [];

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
	<title>Referral Report</title>
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
                <span id="userGreeting">Hello BHW!</span>
            </div>
			<a href="#" class="profile">
				<img src="../../img/profile.png">
			</a>
		</nav>

		<main>
            
            <div class="head-title">
                <div class="left">
                  <h1>Referral Report</h1>
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
    <div class="form-row">
        <div class="form-item">
            <label for="from_date">From:</label>
            <input type="date" name="from_date" id="from_date" value="<?= htmlspecialchars($from_date) ?>">
        </div>

        <div class="form-item">
            <label for="to_date">To:</label>
            <input type="date" name="to_date" id="to_date" value="<?= htmlspecialchars($to_date) ?>">
        </div>

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
            <label for="bhw">BHW:</label>
            <select name="bhw" id="bhw">
                <option value="">-- All --</option>
                <?php foreach ($bhws as $bhw): ?>
                    <option value="<?= $bhw['user_id'] ?>" <?= $bhw['user_id'] == $bhw_id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($bhw['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-item-wrapper">
            <button type="submit">Filter</button>
        </div>
    </div>

    <div class="form-submit">
        <button type="button" onclick="exportTableToExcel('reportTable')">Export to Excel</button>
        <button type="button" onclick="exportTableToPDF()">Export to PDF</button>
        <button type="button" onclick="printDiv()">Print this page</button>
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
  <h2>BHS REFERRAL REPORT</h2>
</div>
<div class="report-content">


<!-- Summary -->
<div class="summary-container">
    <div class="summary">
        <h4><i class="bx bx-filter-alt"></i> Factors Applied:</h4>
        <ul class="summary-list">
            <?php if (!empty($from_date) && !empty($to_date)): ?>
                <li><strong>Date Range:</strong> <?= htmlspecialchars($from_date) ?> to <?= htmlspecialchars($to_date) ?></li>
            <?php endif; ?>
            <?php if (!empty($referral_status)): ?>
                <li><strong>Status:</strong> <?= htmlspecialchars($referral_status) ?></li>
            <?php endif; ?>
            <?php if (!empty($bhw_id)): ?>
                <?php
                    $selected_bhw = array_filter($bhws, fn($b) => $b['user_id'] == $bhw_id);
                    $bhw_name = $selected_bhw ? reset($selected_bhw)['full_name'] : 'Unknown BHW';
                ?>
                <li><strong>BHW:</strong> <?= htmlspecialchars($bhw_name) ?></li>
            <?php endif; ?>
            <?php if (empty($from_date) && empty($to_date) && empty($referral_status) && empty($bhw_id)): ?>
                <li><em>No filters applied. Showing all referrals.</em></li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="summary">
        <strong><i class="bx bx-file"></i> Total Records:</strong> <?= count($rows) ?>
    </div>
</div>



<?php if ($rows): ?>
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

<?php else: ?>
    <p>No referrals found for the selected filters.</p>
<?php endif; ?>
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
    </div> </div>

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
    const divContents = document.querySelector(".print-area").innerHTML;

    const printWindow = window.open('', '', 'height=700,width=900');
    printWindow.document.write('<html><head><title>Print Report</title>');
    
    // Include styles for printing
    printWindow.document.write(`
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; color: black; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #000; padding: 4px; text-align: left; }
            thead { background-color: #f0f0f0; }
            
            /* Enhanced Status Styling for Print */
            .referral-status {
                font-weight: bold;
                padding: 4px 8px;
                border-radius: 3px;
                display: inline-block;
            }
            .status-pending {
                color: #1c538a;
                border-left: 2px solid #1c538a;
            }
            .status-completed {
                color: #2e8540;
                border-left: 2px solid #2e8540;
            }
            .status-uncompleted, .status-canceled {
                color: #d83933;
                border-left: 2px solid #d83933;
            }
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
fetch('../php/getUserName.php')
    .then(response => response.json())
    .then(data => {
        if (data.full_name) {
            document.getElementById('userGreeting').textContent = `Hello, ${data.full_name}!`;
        } else {
            document.getElementById('userGreeting').textContent = 'Hello, BHW!';
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
    window.location.href = '../../role.html';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('logoutModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>


</body>
</html>
