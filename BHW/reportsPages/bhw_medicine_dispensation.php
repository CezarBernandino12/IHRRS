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


// Get filters
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
// Accept multiple medicines
$medicine = isset($_GET['medicine']) ? (array)$_GET['medicine'] : [];
$bhw_id = $_GET['bhw'] ?? '';
$sex = $_GET['sex'] ?? '';
$age_group = $_GET['age_group'] ?? '';

// Build SQL
$sql = "SELECT m.*, v.visit_date, v.recorded_by, p.first_name, p.last_name, p.sex, p.age, u.full_name AS bhw_name
        FROM bhs_medicine_dispensed m
        JOIN patient_assessment v ON m.visit_id = v.visit_id
        JOIN patients p ON v.patient_id = p.patient_id
        LEFT JOIN users u ON v.recorded_by = u.user_id
        WHERE p.address LIKE :barangay";

$params = [];
$params['barangay'] = '%' . $barangayName . '%';


if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND DATE(m.dispensed_date) BETWEEN :from_date AND :to_date";
    $params['from_date'] = $from_date;
    $params['to_date'] = $to_date;
}

if (!empty($medicine)) {
    // Build placeholders for each selected medicine
    $placeholders = [];
    foreach ($medicine as $i => $med) {
        $ph = ":medicine_$i";
        $placeholders[] = $ph;
        $params["medicine_$i"] = $med;
    }
    $sql .= " AND m.medicine_name IN (" . implode(',', $placeholders) . ")";
}

if (!empty($bhw_id)) {
    $sql .= " AND v.recorded_by = :bhw_id";
    $params['bhw_id'] = $bhw_id;
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

	<title>Medicine Dispensation Report</title>
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
				<a href="../dashboard.php">
					<i class="bx bxs-dashboard"></i>
					<span class="text">Dashboard</span>
				</a>
			</li>
			<li>
				<a href= "../ITR.php">
					<i class="bx bxs-user"></i>
					<span class="text">Add ITR</span>
				</a>
			</li>
			<li>
				<a href="../searchPatient.php">
					<i class="bx bxs-notepad"></i>
					<span class="text">Patient Records</span>
				</a>
			</li>

			<li>
				<a href="../history.php">
					<i class="bx bx-history"></i>
					<span class="text">Referral History</span>
				</a>
			</li>
            <li class="active">
				<a href="../reports.php">
					<i class="bx bx-notepad"></i>
					<span class="text">Reports</span>
				</a>
			</li>
        </ul>
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
                  <h1>Medicine Dispensation</h1>
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
      <h2>Medicine Dispensation Report - BHS <?php echo htmlspecialchars($barangayName); ?></h2> <br>

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

                // Special handling for multi-value filters like medicine[]
                if (substr($param, -2) === '[]') {
                    $base = substr($param, 0, -2);
                    if (isset($url[$base]) && is_array($url[$base])) {
                        // Remove only the specific value
                        $url[$base] = array_values(array_diff($url[$base], [$value]));
                        // If empty, unset to avoid empty param in URL
                        if (empty($url[$base])) unset($url[$base]);
                    }
                    // For building query, use param[] syntax
                    $query = http_build_query($url);
                    // Fix for [] in query string
                    $query = preg_replace('/%5B\d+%5D=/', '%5B%5D=', $query);
                } else {
                    unset($url[$param]);
                    $query = http_build_query($url);
                }

                echo '<span class="filter-tag" style="background:#e3e6ea;color:#222;padding:6px 12px;border-radius:16px;display:inline-flex;align-items:center;font-size:14px;">';
                echo $display;
                echo ' <a href="?' . $query . '" style="margin-left:8px;color:#888;text-decoration:none;font-weight:bold;" title="Remove filter">&times;</a>';
                echo '</span>';
            }

            // Render tags for each filter if set
            if ($from_date) renderTag('From', 'from_date', $from_date);
            if ($to_date) renderTag('To', 'to_date', $to_date);
            if ($sex) renderTag('Sex', 'sex', $sex);
            if ($bhw_id) renderTag('Bhw', 'bhw', $bhw_id);
            if ($age_group) {
                $age_labels = [
                    'child' => 'Child (0–12)', 'teen' => 'Teen (13–19)',
                    'adult' => 'Adult (20–59)', 'senior' => 'Senior (60+)'
                ];
                renderTag('Age Group', 'age_group', $age_labels[$age_group] ?? ucfirst($age_group));
            }
            if ($medicine) {
                foreach ($medicine as $med) {
                    renderTag('Medicine', 'medicine[]', $med);
                }
            }
            // If no filters, show "All"
            if (
                !$from_date && !$to_date && !$sex && !$age_group &&
                !$medicine && !$bhw_id 
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
                            <input type="text" name="from_date" id="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?> " placeholder="Select date">
                        </div>
                        <!-- To Date -->
                        <div class="form-item">
                            <label for="to_date">To:</label>
                            <input type="text" name="to_date" id="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?> " placeholder="Select date">
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
                        <label for="medicine">Given Medicine:</label>
                        <div id="medicine-checkboxes" style="max-height:150px;overflow-y:auto;border:1px solid #ccc;padding:8px;border-radius:6px;">
                            <?php
                            // Fetch medicines for checkboxes
                            $medicine_stmt = $pdo->prepare("SELECT value FROM custom_options WHERE category = 'medicine' ");
                            $medicine_stmt->execute();
                            // Support multiple selection from GET
                            $selected_medicines = isset($_GET['medicine']) ? (array)$_GET['medicine'] : [];
                            while ($row = $medicine_stmt->fetch()) {
                                $value = $row['value'];
                                $checked = in_array($value, $selected_medicines) ? 'checked' : '';
                                echo '<label style="display:block;margin-bottom:4px;text-align:left;font-weight:300;">';
                                echo '<input type="checkbox" name="medicine[]" value="' . htmlspecialchars($value) . '" ' . $checked . '> ';
                                echo htmlspecialchars($value);
                                echo '</label>';
                            }
                            ?>
                        </div>
                        <small style="color:#888;">You may select multiple medicines.</small>
                    </div>

        <div class="form-item">
            <label for="bhw">Dispensed by:</label>
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
<div class="print-header" style="text-align: center;">
  <h3>Republic of the Philippines</h3>
  <p>Province of Camarines Norte</p>
  <h3>Municipality of Daet</h3>
  <h2><?php echo htmlspecialchars($barangayName); ?></h2>
  <br> 
  <h2>MEDICINE DISPENSATION REPORT</h2>
</div>
<div class="report-content">

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div style="margin: 30px 0;">
    <h3 style="margin-bottom:10px;"><i class="bx bx-line-chart"></i> Medicine Dispensation Trends</h3>
    <canvas id="dispensationChart" height="80"></canvas>
</div>

<script>
// Prepare data for the chart
const chartData = (() => {
    // Group by date and medicine
    const rows = <?php echo json_encode($rows); ?>;
    const medicines = [...new Set(rows.map(r => r.medicine_name))];
    // Get all unique dispensed dates (sorted)
    const dates = [...new Set(rows.map(r => r.dispensed_date))].sort();

    // Build a map: {medicine: {date: count}}
    const medDateCount = {};
    medicines.forEach(med => {
        medDateCount[med] = {};
        dates.forEach(date => {
            medDateCount[med][date] = 0;
        });
    });
    rows.forEach(r => {
        if (medDateCount[r.medicine_name] && medDateCount[r.medicine_name][r.dispensed_date] !== undefined) {
            medDateCount[r.medicine_name][r.dispensed_date] += Number(r.quantity_dispensed) || 1;
        }
    });

    // Prepare datasets for Chart.js
    const colors = [
        '#3366cc', '#dc3912', '#ff9900', '#109618', '#990099', '#0099c6', '#dd4477', '#66aa00', '#b82e2e', '#316395'
    ];
    const datasets = medicines.map((med, idx) => ({
        label: med,
        data: dates.map(date => medDateCount[med][date]),
        borderColor: colors[idx % colors.length],
        backgroundColor: colors[idx % colors.length] + '33',
        fill: false,
        tension: 0.2
    }));

    return {dates, datasets};
})();

const ctx = document.getElementById('dispensationChart').getContext('2d');
const dispensationChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: chartData.dates,
        datasets: chartData.datasets
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true, position: 'top' },
            title: { display: false }
        },
        scales: {
            x: { title: { display: true, text: 'Dispensed Date' } },
            y: { title: { display: true, text: 'Quantity Dispensed' }, beginAtZero: true }
        }
    }
});
</script>

<?php if ($rows): ?>
    <div class="summary-container">
    <div class="summary">
        <h4><i class="bx bx-filter-alt"></i>Summary:</h4>
    <table class="summary-table" style="width: 100%; border-collapse: collapse; margin-top: 10px;">
        <thead>
            <tr>
                <th style="border: 1px solid #ccc; padding: 8px; text-align: left;">Medicine Name</th>
                <th style="border: 1px solid #ccc; padding: 8px; text-align: left;">Total Quantity Dispensed</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Summarize total quantity dispensed per medicine (after filters)
            $medicine_totals = [];
            foreach ($rows as $row) {
                $med = $row['medicine_name'];
                $qty = (int)$row['quantity_dispensed'];
                if (!isset($medicine_totals[$med])) {
                    $medicine_totals[$med] = 0;
                }
                $medicine_totals[$med] += $qty;
            }
            if ($medicine_totals) {
                foreach ($medicine_totals as $med => $total) {
                    echo '<tr>';
                    echo '<td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($med) . '</td>';
                    echo '<td style="border: 1px solid #ccc; padding: 8px;">' . $total . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr>';
                echo '<td colspan="2" style="border: 1px solid #ccc; padding: 8px; text-align: center;">No medicines dispensed for the selected filters.</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
    </div>


    <div class="summary">
        <strong><i class="bx bx-file"></i> Total Records:</strong> <?= count($rows) ?>
    </div>
</div>

<br><br>
<h3>Detailed Report</h3>
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

     <br>

    <br> <br>
     <span id="generated_by"></span>
    
<?php else: ?>
    <p>No records found for the selected filters.</p>
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



//PRINT
function printDiv() {
    const divContents = document.querySelector(".print-area").innerHTML;

    // Convert Chart.js canvas to image and replace in print content
    const chartCanvas = document.getElementById('dispensationChart');
    let chartImgHTML = '';
    if (chartCanvas) {
        const chartImg = chartCanvas.toDataURL("image/png");
        chartImgHTML = `<img id="printChartImg" src="${chartImg}" style="max-width:100%;height:auto;">`;
    }
    // Replace the canvas with the image in the print content
    let printContent = divContents.replace(
        /<canvas[^>]*id="dispensationChart"[^>]*>[\s\S]*?<\/canvas>/,
        chartImgHTML
    );

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
    printWindow.document.write(printContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();

    // Wait for the image to load before printing
    printWindow.onload = function() {
        const img = printWindow.document.getElementById('printChartImg');
        if (img) {
            img.onload = function() {
                printWindow.focus();
                printWindow.print();
                printWindow.close();
            };
            // If image is cached and already loaded
            if (img.complete) {
                img.onload();
            }
        } else {
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        }
    };
}
fetch('../php/getUserName.php')
    .then(response => response.json())
    .then(data => {
         if (data.full_name) {
            document.getElementById('userGreeting').textContent = `Hello, ${data.full_name}!`;
                   document.getElementById('generated_by').textContent = `Generated by: ${data.full_name}`;
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
