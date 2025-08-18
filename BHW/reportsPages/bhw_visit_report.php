<?php
// Connect to DB
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



// Initialize filters
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$sex = $_GET['sex'] ?? '';
$age_group = $_GET['age_group'] ?? '';
$purok = $_GET['purok'] ?? ''; // Use 'purok' for address filtering
$bmi = $_GET['bmi'] ?? '';

// Build query with filters
$sql = "SELECT v.*, p.first_name, p.last_name, p.age, p.sex FROM bhs_visits v 
        JOIN patients p ON v.patient_id = p.patient_id 
        WHERE p.address LIKE :barangay"; // Always require barangay match

$params = [];
$params['barangay'] = '%' . $barangayName . '%'; // Always set this param

if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND DATE(v.visit_date) BETWEEN :from_date AND :to_date";
    $params['from_date'] = $from_date;
    $params['to_date'] = $to_date;
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

if (!empty($purok)) {
    // Use 'purok' for address filtering
    $sql .= " AND p.address LIKE :purok";
    $params['purok'] = '%' . $purok . '%';
}

if (!empty($bmi)) {
    switch ($bmi) {
        case 'underweight': $sql .= " AND v.bmi < 18.5"; break;
        case 'normal':  $sql .= " AND v.bmi >= 18.5 AND v.bmi <= 24.9"; break;
        case 'overweight': $sql .= " AND v.bmi >= 25 AND v.bmi <= 29.9"; break;
        case 'class1':  $sql .= " AND v.bmi >= 30 AND v.bmi <= 34.9"; break;
        case 'class2': $sql .= " AND v.bmi >= 35 AND v.bmi <= 39.9"; break;
        case 'class3':$sql .= " AND v.bmi >= 40"; break;
    }
}

// Add this condition to filter by barangay in address
if (!empty($barangayName) && $barangayName !== 'N/A') {
    $sql .= " AND p.address LIKE :barangay";
    $params['barangay'] = '%' . $barangayName . '%';
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$visits = $stmt->fetchAll();

// Calculate summary data
$total_patients = count(array_unique(array_column($visits, 'patient_id')));
$total_medicines_dispensed = 0;
$medicine_counts = [];

foreach ($visits as $visit) {
    // Get medicines dispensed for this visit
    $med_stmt = $pdo->prepare("SELECT * FROM bhs_medicine_dispensed WHERE visit_id = ?");
    $med_stmt->execute([$visit['visit_id']]);
    $meds = $med_stmt->fetchAll();

    if ($meds) {
        foreach ($meds as $med) {
            $total_medicines_dispensed += $med['quantity_dispensed'];
            $medicine_counts[$med['medicine_name']] = ($medicine_counts[$med['medicine_name']] ?? 0) + $med['quantity_dispensed'];
        }
    }
}

// Find the most dispensed medicine
arsort($medicine_counts);
$most_dispensed_medicine = key($medicine_counts);
$most_dispensed_quantity = current($medicine_counts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" href="../../img/logo.png">
	<link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
	<link rel="stylesheet" href="../css/reportsDesign.css">
	<title>Dispensary</title>
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
                  <h1>Dispensary</h1>
                  <ul class="breadcrumb">
                    <li><a href="#">BHS Dispensary Report</a></li>
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
    <h2>Dispensary - <?php echo htmlspecialchars($barangayName); ?></h2> <br>
   
    
    <!-- Filter Modal Trigger -->
   
        <div class="form-submit">
               <button type="button" class="btn-export" id="openFilterModal">Filter</button>
        <button type="button" class="btn-export" onclick="exportTableToExcel('reportTable')">Export to Excel</button>
        <button type="button" class="btn-export" onclick="exportTableToPDF()">Export to PDF</button>
        <button type="button" class="btn-print" onclick="printDiv()">Print this page</button>
    </div>

    <!-- Filter Modal -->
    <div id="filterModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>Apply Filters</h3>
            </div>
            <form method="GET" id="filterForm">
                <div class="modal-body">
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
                            <label for="sex">Sex:</label>
                            <select name="sex" id="sex" class="form-control">
                                <option value="">All</option>
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
                            <label for="purok">Address (by purok):</label>
                            <select name="purok" id="purok" class="form-control">
                                <option value="">All</option>
                                <?php
                                // Fetch puroks that match the barangay name in the value
                                $purok_stmt = $pdo->prepare("SELECT value FROM custom_options WHERE value LIKE ?");
                                $purok_stmt->execute(['%' . $barangayName . '%']);
                                $selected_purok = $_GET['purok'] ?? '';
                                while ($row = $purok_stmt->fetch()) {
                                    $value = $row['value'];
                                    $selected = ($selected_purok === $value) ? 'selected' : '';
                                    echo "<option value=\"" . htmlspecialchars($value) . "\" $selected>" . htmlspecialchars($value) . "</option>";
                                }
                                ?>
                            </select>
                        </div>


                             <div class="form-item">
                            <label for="bmi">BMI:</label>
                            <select name="bmi" id="bmi" class="form-control">
                                <option value="">All</option> </div>
                                <option value="underweight" <?= $bmi == 'underweight' ? 'selected' : '' ?>>Underweight (less than 18.5 )</option>
                                <option value="normal" <?= $bmi == 'normal' ? 'selected' : '' ?>>Normal (18.5 to 24.9)</option>
                                <option value="overweight" <?= $bmi == 'overweight' ? 'selected' : '' ?>>Overweight (25 to 29.9)</option>
                                <option value="class1" <?= $bmi == 'class1' ? 'selected' : '' ?>>Class 1 - Moderate obesity (30 to 34.9)</option>
                                <option value="class2" <?= $bmi == 'class2' ? 'selected' : '' ?>>Class 2 - Severe obesity (35 to 39.9)</option>
                                <option value="class3" <?= $bmi == 'class3' ? 'selected' : '' ?>>Class 3 - Morbid obesity (40 or greater)</option>
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
  <h2><?php echo htmlspecialchars($barangayName); ?></h2>
  <br> 
  <h2>DISPENSARY</h2>
</div>
<div class="report-content">



<!-- Table with Visit Details -->
<?php if ($visits): ?>
	<table id="reportTable">
    <thead>
        <tr>
            <th>Visit Date</th>
            <th>Patient Name</th>
            <th>Sex</th>
            <th>Age</th>
            <th>BMI</th>
            <th>Weight</th>
            <th>Height</th>
            <th>Blood Pressure</th>
            <th>Temperature</th>
            <th>Chest Rate</th>
            <th>Respiratory Rate</th>
            <th>Chief Complaints</th>
            <th>Treatment</th>
            <th>Dispensed Medicines</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($visits as $visit): ?>
        <tr>
            <td><?= date('Y-m-d', strtotime($visit['visit_date'])) ?></td>
            <td><?= htmlspecialchars($visit['first_name'] . ' ' . $visit['last_name']) ?></td>
            <td><?= htmlspecialchars($visit['sex']) ?></td>
            <td><?= htmlspecialchars($visit['age']) ?></td>
            <td><?= htmlspecialchars($visit['bmi']) ?></td>
            <td><?= htmlspecialchars($visit['weight']) ?></td>
            <td><?= htmlspecialchars($visit['height']) ?></td>
            <td><?= htmlspecialchars($visit['blood_pressure']) ?></td>
            <td><?= htmlspecialchars($visit['temperature']) ?></td>
            <td><?= htmlspecialchars($visit['chest_rate']) ?></td>
            <td><?= htmlspecialchars($visit['respiratory_rate']) ?></td>
            <td><?= htmlspecialchars($visit['chief_complaints']) ?></td>
            <td><?= htmlspecialchars($visit['treatment']) ?></td>
            <td>
                <?php
                // Get dispensed medicines for this visit
                $med_stmt = $pdo->prepare("SELECT * FROM bhs_medicine_dispensed WHERE visit_id = ?");
                $med_stmt->execute([$visit['visit_id']]);
                $meds = $med_stmt->fetchAll();

                if ($meds) {
                    foreach ($meds as $med) {
                        echo htmlspecialchars($med['medicine_name']) . ' (Qty: ' . $med['quantity_dispensed'] . ')<br>';
                    }
                } else {
                    echo "No medicines dispensed.";
                }
                ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
           <br>
<!-- Summary Section -->
<div class="summary-container">
    <div class="summary">
        <h3><i class="bx bx-file"></i> Summary:</h3>
        <ul class="summary-list">
            <li><strong>Total Patients:</strong> <?= $total_patients ?></li>
            <li><strong>Total Medicines Dispensed:</strong> <?= $total_medicines_dispensed ?></li>
            <li><strong>Most Dispensed Medicine:</strong> <?= htmlspecialchars($most_dispensed_medicine) ?> (<?= $most_dispensed_quantity ?>)</li>
        </ul>
    </div>

    <div class="summary">
        <h3><i class="bx bx-filter-alt"></i> Selected Filters:</h3>
        <ul class="summary-list">
            <li><strong>From:</strong> <?= $from_date ? htmlspecialchars($from_date) : 'All' ?></li>
            <li><strong>To:</strong> <?= $to_date ? htmlspecialchars($to_date) : 'All' ?></li>
            <li><strong>Sex:</strong> <?= $sex ? htmlspecialchars($sex) : 'All' ?></li>
            <li><strong>Age Group:</strong> <?= $age_group ? ucfirst(htmlspecialchars($age_group)) : 'All' ?></li>
        </ul>
    </div>
</div>
<?php else: ?>
    <p>No visits found for the selected filters.</p>
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


function printDiv() {
    const originalTable = document.querySelector(".print-area").cloneNode(true);

    // Add 'Signature' column to header
    const headerRow = originalTable.querySelector("thead tr");
    const signatureHeader = document.createElement("th");
    signatureHeader.textContent = "Signature";
    headerRow.appendChild(signatureHeader);

    // Add 'Signature' cell to each row in tbody
    const rows = originalTable.querySelectorAll("tbody tr");
    rows.forEach(row => {
        const signatureCell = document.createElement("td");
        signatureCell.style.height = "30px"; // optional: space for actual signature
        signatureCell.textContent = ""; // blank cell for signature
        row.appendChild(signatureCell);
    });

    // Create print window and write content
    const printWindow = window.open('', '', 'height=700,width=900');
    printWindow.document.write('<html><head><title>Print Report</title>');

    printWindow.document.write(`
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; color: black; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #000; padding: 4px; text-align: left; }
            thead { background-color: #f0f0f0; }
        </style>
    `);

    printWindow.document.write('</head><body>');
    printWindow.document.write(originalTable.outerHTML);  // Use modified table
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
