<?php
require '../../php/db_connect.php';
session_start();

// Check login
if (!isset($_SESSION['user_id'])) {
    echo "User is not logged in.";
    exit;
}

$userId = $_SESSION['user_id'];
$search = trim($_GET['search'] ?? '');
$patient_id = $_GET['patient_id'] ?? null;

// Fetch user info (barangay)
$stmt = $pdo->prepare("SELECT barangay FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
$barangayName = $user['barangay'] ?? 'N/A';

$patientInfo = null;
$matchedPatients = [];
$rows = [];
$lastVisitDate = 'N/A';

// Only perform search if input is provided
if (!empty($search)) {
    $stmt = $pdo->prepare("
        SELECT 
            patient_id,
            CONCAT(first_name, ' ', middle_name, ' ', last_name) AS full_name,
            sex,
            age,
            philhealth_member_no
        FROM patients
        WHERE CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE :search
        ORDER BY last_name
    ");
    $stmt->execute(['search' => "%$search%"]);
    $matchedPatients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If only one match and no patient ID selected, auto-use it
    if (count($matchedPatients) === 1 && !$patient_id) {
        $patientInfo = $matchedPatients[0];
        $patient_id = $patientInfo['patient_id'];
    } elseif ($patient_id) {
        // Find the selected patient from the matched list
        foreach ($matchedPatients as $p) {
            if ($p['patient_id'] == $patient_id) {
                $patientInfo = $p;
                break;
            }
        }
    }

    // Fetch treatment history only if a valid patient is selected
    if ($patientInfo && !empty($patientInfo['patient_id'])) {
        // Fetch the treatment history for the selected patient
        $sql = "
        SELECT 
            bv.visit_date,
            bv.chief_complaints AS complaint,
            rc.diagnosis,
            GROUP_CONCAT(DISTINCT md.medicine_name SEPARATOR ', ') AS treatment,
            bv.remarks
        FROM patient_assessment bv
        LEFT JOIN rhu_consultations rc ON bv.visit_id = rc.visit_id
        LEFT JOIN rhu_medicine_dispensed md ON rc.consultation_id = md.consultation_id
        WHERE bv.patient_id = :patient_id
        GROUP BY bv.visit_id
        ORDER BY bv.visit_date DESC
        LIMIT 25
        ";

        // Prepare the statement
        $stmt = $pdo->prepare($sql);

        // Bind the parameters
        $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

        // Execute the query
        $stmt->execute();

        // Fetch the results
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
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
				<a href="#" id="updateReferrals">
					<i class="bx bxs-user"></i>
					<span class="text">Pending Referrals</span>
				</a>
			</li>
			
			<script>
			document.getElementById("updateReferrals").addEventListener("click", function (event) {
				event.preventDefault(); // Prevent default navigation
			
				fetch("php/update_referrals.php") // Call PHP file
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
<form method="GET" class="filter-form">

    <input type="text" name="search" placeholder="Search patient..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>

  

    <button onclick="exportTableToExcel('reportTable')" class="btn btn-success">📁 Export to Excel</button>
<button onclick="exportTableToPDF()" class="btn btn-danger">📄 Export to PDF</button>

    <button onclick="printDiv()" class="btn-print">🖨️ Print this page</button>
</form>

<?php if (!empty($search) && count($matchedPatients) > 1 && !isset($patient_id)): ?>
    <div style="margin-bottom: 1rem;">
        <h4>Matched Patients:</h4>
        <ul style="list-style: none; padding-left: 0;">
            <?php foreach ($matchedPatients as $p): ?>
                <li style="margin: 5px 0;">
                    <a href="?search=<?= urlencode($search) ?>&patient_id=<?= urlencode($p['patient_id']) ?>">
                        <?= htmlspecialchars($p['full_name']) ?> - <?= htmlspecialchars($p['age']) ?> yrs / <?= htmlspecialchars($p['sex']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>




<div class="main-content">


<div class="print-area">
<div class="print-header" style="text-align: center;">
  <h3>Republic of the Philippines</h3>
  <p>Province of Camarines Norte</p>
  <h3>Municipality of Daet</h3>
  <h2>Rural Health Unit</h2>
  <br> 
  <h2>INDIVIDUAL TREATMENT HISTORY REPORT</h2>
</div>
<div class="report-content">
<!-- Summary Section -->
<br>

<?php if ($patientInfo): ?>
    <div style="margin-bottom: 1rem;">
        <strong>Patient Name:</strong> <?= htmlspecialchars($patientInfo['full_name']) ?><br>
        <strong>Sex/Age:</strong> <?= htmlspecialchars($patientInfo['sex']) ?> / <?= htmlspecialchars($patientInfo['age']) ?><br>
        <strong>PhilHealth No.:</strong> <?= htmlspecialchars($patientInfo['philhealth_member_no']) ?><br>
        <strong>Date of Last Visit:</strong> <?= date('Y-m-d', strtotime($lastVisitDate)) ?>
    </div>
<?php elseif (!empty($search)): ?>
    <p>No matching patient found.</p>
<?php endif; ?>

<!-- Selected Filters Section -->

<br>
<?php if (empty($rows)): ?>
    <p style="color:red;">No records found for this patient and month.</p>
<?php endif; ?>
<table border="1" cellpadding="8" cellspacing="0"> 
<thead>
        <tr>
            <th>Visit Date</th>
            <th>Complaint</th>
            <th>Diagnosis</th>
            <th>Treatment</th>
            <th>Remarks</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['visit_date']) ?></td>
            <td><?= htmlspecialchars($row['complaint']) ?></td>
            <td><?= htmlspecialchars($row['diagnosis']) ?></td>
            <td><?= htmlspecialchars($row['treatment']) ?></td>
            <td><?= htmlspecialchars($row['remarks']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<br> <br>


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
