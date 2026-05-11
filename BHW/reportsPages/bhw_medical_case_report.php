<?php
require '../../php/db_connect.php';
require_once __DIR__ . '/../php/session_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../role");
    exit;
} 

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT barangay FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$barangayName = $user ? $user['barangay'] : 'N/A';


$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$sex = $_GET['sex'] ?? '';
$age_group = $_GET['age_group'] ?? '';
$purok = $_GET['purok'] ?? ''; 
$diagnosis = $_GET['diagnosis'] ?? '';
$diagnosis_status = $_GET['diagnosis_status'] ?? '';


$sql = "SELECT r.*, p.first_name, p.last_name, p.age, p.sex, p.address FROM rhu_consultations r 
        JOIN patients p ON r.patient_id = p.patient_id 
        WHERE p.address LIKE :barangay"; 

$params = [];
$params['barangay'] = '%' . $barangayName . '%'; 

if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND DATE(r.consultation_date) BETWEEN :from_date AND :to_date";
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
if (!empty($diagnosis)) {
    if (is_array($diagnosis)) {
        $orParts = [];
        foreach ($diagnosis as $i => $diag) {
            $key = "diagnosis_$i";
            $orParts[] = "r.diagnosis LIKE :$key";
            $params[$key] = '%' . $diag . '%';
        }
        $sql .= " AND (" . implode(' OR ', $orParts) . ")";
    } else {
        $sql .= " AND r.diagnosis LIKE :diagnosis";
        $params['diagnosis'] = '%' . $diagnosis . '%';
    }
}
if (!empty($diagnosis_status)) {
    $sql .= " AND r.diagnosis_status = :diagnosis_status";
    $params['diagnosis_status'] = $diagnosis_status;
}
if (!empty($purok)) {
  
    $sql .= " AND p.address LIKE :purok";
    $params['purok'] = '%' . $purok . '%';
}

if (!empty($barangayName) && $barangayName !== 'N/A') {
    $sql .= " AND p.address LIKE :barangay";
    $params['barangay'] = '%' . $barangayName . '%';
}

$sql .= " ORDER BY r.consultation_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$visits = $stmt->fetchAll();
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
     <link rel="stylesheet" href="../css/sidebar.css">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <title>Medical Cases Report</title>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

	<!-- Sidebar Section -->
<section id="sidebar">
    <a href="#" class="sidebar-brand">
        <img src="../../img/logo.png" alt="RHU Logo" class="brand-logo">
        <div class="brand-text">
            <span class="brand-name">Hello BHW</span>
        </div>
    </a>

    <div class="sidebar-scroll">
        <p class="sidebar-section-label">Main Menu</p>
        <ul class="side-menu top">
            <li>
                <a href="../dashboard" data-tooltip="Dashboard">
                    <i class="bx bxs-dashboard nav-icon"></i>
                    <span class="nav-label">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="../ITR" data-tooltip="Add New ITR">
                    <i class="bx bxs-notepad nav-icon"></i>
                    <span class="nav-label">Add New ITR</span>
                </a>
            </li>
            <li>
                <a href="../searchPatient" data-tooltip="Patient Records">
                    <i class="bx bxs-search nav-icon"></i>
                    <span class="nav-label">Patient Records</span>
                </a>
            </li>
            <li>
                <a href="../History" data-tooltip="Referral History">
                    <i class="bx bx-history nav-icon"></i>
                    <span class="nav-label">Referral History</span>
                </a>
            </li>
            <li class="active">
                <a href="../reports" data-tooltip="Reports">
                    <i class="bx bx-notepad nav-icon"></i>
                    <span class="nav-label">Reports</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-divider"></div>

        <ul class="side-menu">
            <li>
                <a href="#" class="logout" data-tooltip="Logout" onclick="return confirmLogout()">
                    <i class="bx bxs-log-out-circle nav-icon"></i>
                    <span class="nav-label">Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <img src="../../img/bhw.png" alt="BHW User">
            <div class="sidebar-user-info">
                <div class="user-name" id="sidebarUserName">BHW User</div>
                <div class="user-role">Barangay Health Worker</div>
            </div>
        </div>
    </div>
</section>

	<!-- Main Content Section -->
	<section id="content">
    <nav>
            <button class="nav-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="bx bx-menu"></i>
            </button>

            <div class="nav-greeting greeting">
                <span id="userGreeting">Hello BHW!</span>
            </div>

            <a href="#" class="nav-profile profile">
                <img src="../../img/bhw.png" alt="BHW Profile">
            </a>
        </nav>


		<main>
            
            <div class="head-title">
                <div class="left">
                  <h1>Medical Cases</h1>
                  <ul class="breadcrumb">
                    <li><a href="#">DCM Report</a></li>
                    <li><i class="bx bx-chevron-right"></i></li>
                    <li><a class="active" href="#" onclick="history.back(); return false;">Go back</a></li>
                  </ul>
                </div>
              </div>

<div class="history-container">

    
	

<!-- Filter Form -->
<div class="filter-form">
    <h2>Medical Cases Monitoring Report - BHS <?php echo htmlspecialchars($barangayName); ?>   </h2> <br>

    
    <!-- Filter Modal Trigger -->

        <div class="form-submit">
            <button type="button" class="btn-export" id="openFilterModal">
                <i class="bx bx-filter-alt"></i> Select Filters
            </button>
            <button type="button" class="btn-export" onclick="exportTableToExcel('reportTable')">
                <i class="bx bx-spreadsheet"></i> Export to Excel
            </button>
            <button type="button" class="btn-print" onclick="printDiv()">
                <i class="bx bx-printer"></i> Print Report
            </button>
        </div>


    <!-- Modern Filter Tags Display -->
    <div class="selected-filters">
        <h3><i class="bx bx-filter-alt"></i> Selected Filters:</h3>
        <div id="filterTags">
            <?php
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

if ($from_date) {
    $readable_from = date("F j, Y", strtotime($from_date)); 
    renderTag('From', 'from_date', $readable_from);
}

if ($to_date) {
    $readable_to = date("F j, Y", strtotime($to_date)); 
    renderTag('To', 'to_date', $readable_to);
}

          if ($diagnosis) {
                foreach ($diagnosis as $diag) { 
                    renderTag('Diagnosis', 'diagnosis[]', $diag);
                }
            }
            if ($sex) renderTag('Sex', 'sex', $sex);
            if ($age_group) {
                $age_labels = [
                    'child' => 'Child (0–12)', 'teen' => 'Teen (13–19)',
                    'adult' => 'Adult (20–59)', 'senior' => 'Senior (60+)'
                ];
                renderTag('Age Group', 'age_group', $age_labels[$age_group] ?? ucfirst($age_group));
            }
            if ($purok) renderTag('Barangay', 'purok', $purok);
            if ($diagnosis_status) renderTag('Status', 'diagnosis_status', $diagnosis_status);
     
            if (
                !$from_date && !$to_date && !$sex && !$age_group &&
                !$purok && !$diagnosis_status && !$diagnosis
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
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="bx bx-filter-alt" style="margin-right:8px;color:var(--blue);"></i>Apply Filters</h3>
            </div>
            <form method="GET" id="filterForm">
                <div class="modal-body">
                    <div class="form-row">
                        <!-- From Date -->
                        <div class="form-item">
                            <label for="from_date">From:</label>
                            <input type="date" name="from_date" id="from_date" class="form-control" value="<?= $from_date ? htmlspecialchars($from_date) : '' ?>"  placeholder="Select date">
                        </div>
                        <!-- To Date -->
                        <div class="form-item">
                            <label for="to_date">To:</label>
                            <input type="date" name="to_date" id="to_date" class="form-control" value="<?= $to_date ? htmlspecialchars($to_date) : '' ?>"  placeholder="Select date">
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
                            <label for="purok">Address:</label>
                            <select name="purok" id="purok" class="form-control">
                                <option value="">All</option>
                               <?php

$barangay_stmt = $pdo->prepare("
    SELECT DISTINCT address 
    FROM patients 
    WHERE address LIKE :barangayName 
    ORDER BY address
");
$barangay_stmt->execute([':barangayName' => "%$barangayName%"]);

$selected_purok = $_GET['purok'] ?? '';

while ($row = $barangay_stmt->fetch(PDO::FETCH_ASSOC)) {
    $value = $row['address']; 
    $selected = ($selected_purok === $value) ? 'selected' : '';
    echo "<option value=\"" . htmlspecialchars($value) . "\" $selected>" 
         . htmlspecialchars($value) . "</option>";
}
?>

                            </select>
                        </div> 
                           <div class="form-item">
                            <label for="diagnosis_status">Status:</label>
                            <select name="diagnosis_status" id="diagnosis_status" class="form-control">
                                <option value="">All</option> 
                                <option value="Ongoing" <?= $diagnosis_status == 'Ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                <option value="Treated" <?= $diagnosis_status == 'Treated' ? 'selected' : '' ?>>Treated</option>
                                <option value="Deceased" <?= $diagnosis_status == 'Deceased' ? 'selected' : '' ?>>Deceased</option>
                           
                            </select> </div>

                                  <div class="form-item">
                        <label for="diagnosis">Diagnoses:</label>
                        <div id="diagnosis-checkboxes" class="checkbox-scroll">
                            <?php
                            $diagnosis_stmt = $pdo->prepare("SELECT value FROM custom_options WHERE category = 'diagnosis' ");
                            $diagnosis_stmt->execute();
                            $selected_diagnosis = isset($_GET['diagnosis']) ? (array)$_GET['diagnosis'] : [];
                            while ($row = $diagnosis_stmt->fetch()) {
                                $value = $row['value'];
                                $checked = in_array($value, $selected_diagnosis) ? 'checked' : '';
                                echo '<label class="checkbox-option">';
                                echo '<input type="checkbox" name="diagnosis[]" value="' . htmlspecialchars($value) . '" ' . $checked . '> ';
                                echo htmlspecialchars($value);
                                echo '</label>';
                            }
                            ?>
                        </div>
                        <small style="color:#888;">You may select multiple diagnoses.</small>
                    </div>

                            
                        </div>
                    </div>
                     <div class="modal-footer">
                    <button type="button" class="btn" id="closeFilterModal">Cancel</button>
                    <button type="submit" class="btn-submit">Apply Filters</button>
                </div>
              
               
            </form>
              </div>
        </div>


  
    </div>

    <script>
    document.getElementById('openFilterModal').onclick = function() {
        document.getElementById('filterModal').style.display = 'block';
    };

    setTimeout(() => {
        const fromDateInput = document.getElementById('from_date');
        const toDateInput = document.getElementById('to_date');
        
        if (fromDateInput._flatpickr) {
            fromDateInput._flatpickr.destroy();
        }
        if (toDateInput._flatpickr) {
            toDateInput._flatpickr.destroy();
        }
        
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
    }, 100);

    document.getElementById('closeFilterModal').onclick = function() {
        document.getElementById('filterModal').style.display = 'none';
    };
    document.getElementById('filterForm').onsubmit = function() {
        document.getElementById('filterModal').style.display = 'none';
        return true; 
    };

    window.addEventListener('click', function(event) {
        var modal = document.getElementById('filterModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
    </script>


</div>

<div class="main-content">


<div class="print-area">
<!-- Two-logo, centered letterhead -->
<div class="print-letterhead">
  <img src="../../img/daet_logo.png" alt="Municipality / Station Logo" class="print-logo" aria-hidden="true">

  <div class="print-heading">
    <div class="ph-line-1">Republic of the Philippines</div>
    <div class="ph-line-2">Daet, Camarines Norte</div>
    <div class="ph-line-3">Municipality of Daet</div>
    <div class="ph-line-4"><?php echo htmlspecialchars($barangayName); ?></div>
    <hr class="print-rule">

  </div>

  <img src="../../img/mho_logo.png" alt="RHU Logo" class="print-logo" aria-hidden="true"> <br>

  
</div>


<div class="report-content">
<div class="title">
    <h2 class="print-title">MEDICAL CASE MONITORING REPORT</h2>

    <div class="print-sub">
      (<?php
        $filters = [];
if ($from_date || $to_date) {
    $readable_from = $from_date ? date("F j, Y", strtotime($from_date)) : '';
    $readable_to   = $to_date ? date("F j, Y", strtotime($to_date)) : '';

  
    $filters[] = "<strong>" . trim($readable_from . ($readable_to ? " — " . $readable_to : '')) . "</strong>";
}

        if ($diagnosis) {
            $diagnosis_list = is_array($diagnosis) ? $diagnosis : [$diagnosis];
            $filters[] = "Diagnosis: <strong>" . implode(', ', array_map('htmlspecialchars', $diagnosis_list)) . "</strong>";
        }
        if ($diagnosis_status) $filters[] = "Status: <strong>" . htmlspecialchars($diagnosis_status) . "</strong>";
        if ($sex) $filters[] = "Sex: <strong>" . htmlspecialchars($sex) . "</strong>";
        if ($age_group) {
            $age_labels = [
              'child'=>'Child (0–12)','teen'=>'Teen (13–19)',
              'adult'=>'Adult (20–59)','senior'=>'Senior (60+)'
            ];
            $filters[] = "Age Group: <strong>" . ($age_labels[$age_group] ?? htmlspecialchars($age_group)) . "</strong>";
        }
        if ($purok) $filters[] = "Barangay: <strong>" . htmlspecialchars($purok) . "</strong>";
        echo $filters ? implode("&nbsp; | &nbsp;", $filters) : "All Records";
      ?>)
    </div>
</div>
 
<style>
  .print-letterhead,
  .print-rule { display: none; }

  .title { text-align: center; display: none;}

  @media print {
    .print-letterhead,
    .print-rule { display: block; }
    .title {
        display: block;
    }
    .report-table-container {
        font-size: 11pt;
    }

  }
  
    @media print {
       
        .chart-title { 
           display: none;
        }
        .form-submit { 
           display: none;
        }
        .summary-list{
            font-size: 16px;
        }
        .generated-by{
            font-size: 16px;
        }

    }
</style>
<style>

     .print-only-letterhead { display: none; }

  @media print {
    .print-only-letterhead { display: block; }
 .print-letterhead {
  display: grid;
  grid-template-columns: 72px auto 72px;  /* widened logo columns */
  align-items: center;
  justify-content: center;
  column-gap: 60px;                       /* increased space between logos and heading */
  margin: 0 auto 18px;
  text-align: center;
  width: fit-content;
}

.print-logo {
  width: 72px;                            /* matched to column width for proportion */
  height: 72px;
  object-fit: contain;
}

.print-heading {
  line-height: 1.1;
  color: #000;
}

.print-heading > * {
  margin: 0;
}

.ph-line-1 {
  font-size: 12pt;
  font-weight: 500;
  margin-bottom: 4px;
}

.ph-line-2 {
  font-size: 14pt;
  font-weight: 500;
  margin-bottom: 4px;
}

.ph-line-3 {
  font-size: 12pt;
  font-weight: 500;
  margin-bottom: 4px;
}

.ph-line-4 {
  font-size: 12pt;
  font-weight: 500;
  margin-top: 4px;
}

.print-rule {
  height: 1px;
  border: 0;
  background: #cfd8e3;
  margin-top: 15px;
}

.print-title {
  font-size: 14pt;
  font-weight: 600;
  letter-spacing: 0.3px;
  color: #000;
}

.print-sub {
  font-size: 12pt;
}

  @media print {
    .form-submit, .chart-title { display:none !important; } /* keep your existing print hides */
    .print-letterhead{ margin-bottom:12mm; }
  }
  }
</style>
<style>
  .summary-container { margin-top: 58px; } 
  .summary h3 { margin-bottom: 12px; }

  .kv-table, .mini-table, .purok-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 14px;
    font-size: 14px;
  }
  .kv-table th, .kv-table td,
  .mini-table th, .mini-table td,
  .purok-table th, .purok-table td {
    border: 1px solid #d1d5db;
    padding: 8px 10px;
  }
  .kv-table th { width: 40%; text-align: left; background: #f8fafc; }
  .mini-wrap {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 14px;
  }
  .mini-table th { text-align: left; }
  .purok-table th {  text-align: left; }

  @media print {
    .summary-container { margin-top: 80px; }
    .kv-table, .mini-table, .purok-table { font-size: 12pt; border: 1px solid #000000ff;}
  }
    @media print {
    .summary-container .summary h3 { 
      display: none !important; 
    }
    .summary-container .kv-table { margin-top: 0 !important; }

       .report-table-container {
 
      margin-bottom: 40px !important;
    }
    
  }

  #generated_by {
  display: block;           
  margin: 22px 0 0 48px;    
  color: #000;
}

#generated_by .sig-label {
  font-size: 16px;
  margin-bottom: 16px;
}

#generated_by .sig-line {
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
  font-size: 16px;
  color: #333;
}

/* Print sizing (optional, nicer on paper) */
@media print {
  #generated_by {  margin: 60mm 0 0 10mm;}
  #generated_by .sig-label { font-size: 12pt; }
  #generated_by .sig-name  { font-size: 12pt; }
  #generated_by .sig-title { font-size: 12pt; }
  #generated_by .sig-line  { width: 45mm; border-top-width: 1px; margin: 10mm 0 3mm; }
}
</style>


<style>
/* ═══════════════════════════════════════════════
   UI CONSISTENCY OVERRIDES - BHW MEDICAL CASES
═══════════════════════════════════════════════ */
#content main {
  padding: 32px 28px;
  max-height: calc(100vh - 56px);
  overflow-y: auto;
}

#content main .head-title { margin-bottom: 8px; }
#content main .head-title .left h1 {
  font-size: 28px;
  font-weight: 800;
  color: var(--navy);
  letter-spacing: -.4px;
  margin-bottom: 6px;
}

.history-container { width: 100%; }

.filter-form {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  padding: 28px 32px 24px;
  margin-bottom: 24px;
  box-shadow: var(--shadow-sm);
}

.filter-form h2 {
  font-size: 17px;
  font-weight: 700;
  color: var(--navy);
  letter-spacing: -.2px;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-form h2::before {
  content: '';
  width: 4px;
  height: 18px;
  background: var(--blue);
  border-radius: 2px;
  flex-shrink: 0;
}

.form-submit {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  align-items: center;
  margin-top: 0 !important;
}

.selected-filters {
  margin-top: 20px;
}

.selected-filters h3 {
  font-size: 13px;
  font-weight: 600;
  color: var(--grey-700);
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 6px;
  text-transform: uppercase;
  letter-spacing: .06em;
}

#filterTags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 8px;
}

.filter-tag {
  background: var(--blue-pale) !important;
  color: var(--navy) !important;
  border: 1px solid var(--border) !important;
  padding: 5px 12px !important;
  border-radius: 20px !important;
  font-size: 13px !important;
  font-weight: 500 !important;
  display: inline-flex !important;
  align-items: center !important;
  gap: 6px !important;
}

.filter-tag a {
  color: var(--grey-500) !important;
  margin-left: 2px !important;
  text-decoration: none !important;
  font-weight: 700 !important;
}
.filter-tag a:hover { color: var(--red) !important; }

.modal-content {
  width: 90%;
  max-width: 620px !important;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px 20px;
}

.form-item label {
  font-size: 12.5px;
  font-weight: 600;
  color: var(--grey-700);
  text-transform: uppercase;
  letter-spacing: .05em;
}

.checkbox-scroll {
  max-height: 170px;
  overflow-y: auto;
  border: 1.5px solid var(--border);
  border-radius: var(--r-sm);
  background: var(--grey-100);
  padding: 8px 10px;
}

.checkbox-option {
  display: flex !important;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
  text-align: left;
  font-weight: 500 !important;
  font-size: 13px;
  color: var(--grey-700);
  text-transform: none !important;
  letter-spacing: 0 !important;
}

.chart-controls-panel {
  background: var(--grey-100);
  border: 1px solid var(--border-soft);
  border-radius: var(--r-md);
  padding: 16px 20px;
  margin: 0 0 24px;
}

.chart-controls-panel h3 {
  font-size: 13px;
  font-weight: 700;
  color: var(--grey-700);
  text-transform: uppercase;
  letter-spacing: .07em;
  margin-bottom: 12px;
}

.chart-toggle-group {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.chart-toggle-group label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 7px 14px;
  background: var(--white);
  border: 1.5px solid var(--border);
  border-radius: 20px;
  font-size: 13px;
  font-weight: 500;
  color: var(--grey-700);
  cursor: pointer;
  user-select: none;
}

.chart-toggle-group label:has(input:checked) {
  background: var(--navy);
  border-color: var(--navy);
  color: var(--white);
}

.report-chart-card {
  background: var(--white);
  border: 1px solid var(--border-soft);
  border-radius: var(--r-lg);
  box-shadow: var(--shadow-xs);
  padding: 20px;
  margin: 24px auto 0;
  text-align: center;
}

.line-chart-card {
  width: 100%;
  max-width: 980px;
  min-height: 390px;
}
.small-chart-card { max-width: 460px; }
.medium-chart-card { max-width: 540px; }

.report-chart-card canvas {
  width: 100% !important;
  height: 280px !important;
  max-height: 280px;
}

.line-chart-card canvas {
  height: 320px !important;
  max-height: 320px;
}

.report-charts-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(280px, 1fr));
  gap: 24px;
  align-items: stretch;
  margin: 24px 0 6px;
}

.report-charts-grid .report-chart-card {
  width: 100%;
  max-width: none;
  min-height: 340px;
  margin: 0;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
}

.report-charts-grid.single-chart {
  grid-template-columns: minmax(280px, 540px);
  justify-content: center;
}

.chart-hidden {
  opacity: 0;
  transform: translateY(12px);
  max-height: 0;
  min-height: 0 !important;
  overflow: hidden;
  padding-top: 0 !important;
  padding-bottom: 0 !important;
  margin: 0 !important;
  border-width: 0 !important;
  pointer-events: none;
}

.chart-visible {
  opacity: 1;
  transform: translateY(0);
  animation: reportChartIn .28s ease-out both;
}

@keyframes reportChartIn {
  from { opacity: 0; transform: translateY(12px); }
  to { opacity: 1; transform: translateY(0); }
}

#reportTable th { cursor: pointer; user-select: none; }
#reportTable th .sort-indicator { margin-left: 6px; font-size: 10px; opacity: .7; }
#reportTable th.is-sorted-asc .sort-indicator::after { content: "▲"; }
#reportTable th.is-sorted-desc .sort-indicator::after { content: "▼"; }

.chart-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--navy);
  margin-bottom: 12px;
}

.print-area {
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  padding: 28px 32px;
  box-shadow: var(--shadow-sm);
}

.report-table-container {
  width: 100%;
  border-radius: var(--r-lg);
  border: 1px solid var(--border);
  overflow: hidden;
  box-shadow: var(--shadow-md);
  background: var(--white);
  margin: 24px 0 32px !important;
}

.report-table-scroll {
  width: 100%;
  overflow-x: auto;
  max-height: 560px;
  overflow-y: auto;
}

#reportTable {
  width: 100%;
  min-width: 980px;
  border-collapse: collapse;
  font-size: 13.5px;
}

#reportTable thead {
  position: sticky;
  top: 0;
  z-index: 10;
}

#reportTable thead tr {
  background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
}

#reportTable th {
  padding: 13px 14px;
  text-align: left;
  font-size: 11.5px;
  font-weight: 700;
  color: rgba(255,255,255,.92);
  text-transform: uppercase;
  letter-spacing: .07em;
  white-space: nowrap;
  border-right: 1px solid rgba(255,255,255,.08);
}

#reportTable td {
  padding: 11px 14px;
  color: var(--grey-700);
  font-size: 13.5px;
  vertical-align: middle;
  border-right: 1px solid var(--border-soft);
  border-bottom: 1px solid var(--border-soft);
  white-space: nowrap;
}

#reportTable td:nth-child(2),
#reportTable td:nth-child(4),
#reportTable td:nth-child(7) {
  white-space: normal;
  word-break: break-word;
  min-width: 150px;
}

#reportTable tbody tr:nth-child(odd) { background: var(--white); }
#reportTable tbody tr:nth-child(even) { background: var(--grey-100); }
#reportTable tbody tr:hover { background: var(--blue-pale); }

.summary-container { margin-top: 32px !important; }
.summary h3 {
  font-size: 16px;
  font-weight: 700;
  color: var(--navy);
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
}

.kv-table,
.mini-table,
.purok-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  border: 1px solid var(--border);
  border-radius: var(--r-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  font-size: 14px;
  margin-bottom: 14px;
}

.kv-table th,
.kv-table td,
.mini-table th,
.mini-table td,
.purok-table th,
.purok-table td {
  border: 0 !important;
  border-bottom: 1px solid var(--border-soft) !important;
  padding: 12px 16px !important;
  text-align: left;
  vertical-align: top;
}

.kv-table th,
.mini-table thead th,
.purok-table thead th {
  background: var(--grey-100);
  color: var(--navy);
  font-weight: 700;
}

.kv-table tr:last-child th,
.kv-table tr:last-child td,
.mini-table tr:last-child td,
.purok-table tr:last-child td {
  border-bottom: 0 !important;
}

.mini-wrap {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
  margin-bottom: 14px;
}

#generated_by {
  display: block;
  margin: 48px 0 0 4px !important;
  color: var(--dark) !important;
}
#generated_by .sig-label {
  font-size: 12.5px !important;
  font-weight: 600;
  color: var(--grey-500);
  text-transform: uppercase;
  letter-spacing: .08em;
  margin-bottom: 60px;
  display: block;
}
#generated_by .sig-block {
  display: inline-block;
  text-align: center;
  min-width: 200px;
}
#generated_by .sig-line {
  display: block;
  border: none;
  border-top: 1.5px solid #000;
  width: 100%;
  margin: 0 0 4px;
}
#generated_by .sig-name {
  font-weight: 700;
  font-size: 15px !important;
  color: var(--navy);
  white-space: nowrap;
}
#generated_by .sig-title {
  font-size: 12px !important;
  color: var(--grey-500) !important;
}

@media print {
  @page { size: landscape; margin: 1cm; }
  body * { visibility: hidden; }
  .print-area, .print-area * { visibility: visible; }
  .print-area {
    position: absolute;
    left: 0; top: 0;
    width: 100%;
    box-shadow: none;
    border: none;
    padding: 0;
    border-radius: 0;
  }
  .title { display: block !important; text-align: center; }
  .ph-line-4, .print-sub { text-align: center; }
  .print-letterhead { display: grid !important; }
  .chart-controls-panel,
  .chart-title,
  .report-chart-card,
  .line-chart-card,
  canvas,
  .report-charts-grid,
  .form-submit,
  nav,
  #sidebar { display: none !important; }
  /* Remove thead color */
  #reportTable thead tr { background: #fff !important; }
  #reportTable th { background: #fff !important; color: #000 !important; }
  /* Consistent font */
  body, table, th, td,
  #generated_by, .sig-label, .sig-name, .sig-title,
  .ph-line-4, .print-sub { font-family: Arial, sans-serif !important; }
  .report-table-container {
    box-shadow: none;
    border: 1px solid #000;
    border-radius: 0;
    margin: 18px 0 30px !important;
  }
  .report-table-scroll {
    max-height: none !important;
    overflow: visible !important;
  }
  #reportTable { min-width: unset; font-size: 10pt; }
  #reportTable th,
  #reportTable td {
    border: 1px solid #000 !important;
    color: #000 !important;
    background: transparent !important;
    padding: 6px 8px;
  }
  .kv-table,
  .mini-table,
  .purok-table {
    border: 1px solid #000 !important;
    box-shadow: none;
    border-radius: 0;
    font-size: 11pt;
  }
  .kv-table th,
  .kv-table td,
  .mini-table th,
  .mini-table td,
  .purok-table th,
  .purok-table td {
    border: 1px solid #000 !important;
  }
  #generated_by { margin: 50mm 0 0 10mm !important; }
  #generated_by .sig-label { font-size: 11px; margin-bottom: 60px; display: block; }
  #generated_by .sig-block { display: inline-block; text-align: center; }
  #generated_by .sig-line { display: block; border: none; border-top: 1.5px solid #000; width: 100%; margin: 0 0 4px; }
  #generated_by .sig-name { font-weight: 700; font-size: 12pt; white-space: nowrap; }
  #generated_by .sig-title { font-size: 11pt; }
}

@media (max-width: 900px) {
  .report-charts-grid,
  .report-charts-grid.single-chart {
    grid-template-columns: 1fr;
  }

  .report-charts-grid .report-chart-card {
    max-width: 540px;
    margin: 0 auto;
  }
}

@media (max-width: 768px) {
  #content main { padding: 20px 14px; }
  .filter-form { padding: 20px 18px; }
  .form-row { grid-template-columns: 1fr; }
  .modal-content { padding: 24px 20px 20px; }
  .mini-wrap { grid-template-columns: 1fr; }

  .report-table-container {
    border-radius: var(--r-md);
    box-shadow: var(--shadow-sm);
  }
  #reportTable { min-width: unset; }
  #reportTable thead { display: none; }
  #reportTable,
  #reportTable tbody,
  #reportTable tr,
  #reportTable td { display: block; width: 100%; }
  #reportTable tr {
    margin: 0 0 12px;
    padding: 14px 14px 8px;
    border: 1px solid var(--border);
    border-radius: var(--r-md);
    background: var(--white);
    box-shadow: var(--shadow-xs);
  }
  #reportTable td {
    border: 0 !important;
    border-bottom: 1px solid var(--border-soft) !important;
    padding: 9px 0;
    white-space: normal;
    font-size: 13px;
  }
  #reportTable td:last-child { border-bottom: none !important; }
  #reportTable td::before {
    content: attr(data-label);
    display: block;
    font-size: 10.5px;
    font-weight: 700;
    color: var(--grey-500);
    text-transform: uppercase;
    letter-spacing: .07em;
    margin-bottom: 2px;
  }

  .report-chart-card canvas {
    height: 240px !important;
    max-height: 240px;
  }

  .line-chart-card canvas {
    height: 280px !important;
    max-height: 280px;
  }
}
</style>

<!-- Chart Visibility Controls -->
<div class="chart chart-controls-panel">
    <h3>Charts:</h3>
    <div class="chart-toggle-group">
        <label><input type="checkbox" id="toggleSexChart"> Show Patients by Sex</label>
        <label><input type="checkbox" id="toggleAgeGroupChart"> Show Age Group</label>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const chartMapping = {
        toggleSexChart: "sexChart",
        toggleAgeGroupChart: "ageGroupChart"
    };
    const optionalGrid = document.getElementById('optionalChartsGrid');

    function resizeChartInside(chartElement) {
        if (!chartElement || typeof Chart === 'undefined' || typeof Chart.getChart !== 'function') return;
        const canvas = chartElement.querySelector('canvas');
        const chart = canvas ? Chart.getChart(canvas) : null;
        if (chart) {
            requestAnimationFrame(() => chart.resize());
            setTimeout(() => chart.resize(), 180);
        }
    }

    function syncOptionalChartLayout() {
        let visibleCount = 0;

        Object.keys(chartMapping).forEach(toggleId => {
            const checkbox = document.getElementById(toggleId);
            const chartElement = document.getElementById(chartMapping[toggleId]);
            if (!checkbox || !chartElement) return;

            const isVisible = checkbox.checked;
            chartElement.classList.toggle('chart-visible', isVisible);
            chartElement.classList.toggle('chart-hidden', !isVisible);

            if (isVisible) {
                visibleCount++;
                resizeChartInside(chartElement);
            }
        });

        if (optionalGrid) {
            optionalGrid.classList.toggle('single-chart', visibleCount <= 1);
        }
    }

    Object.keys(chartMapping).forEach(toggleId => {
        const checkbox = document.getElementById(toggleId);
        if (checkbox) checkbox.addEventListener("change", syncOptionalChartLayout);
    });

    syncOptionalChartLayout();
});
</script>


  <!-- Disease Frequency Over Time Line Chart -->
<div class="chart report-chart-card line-chart-card">
    <h3 class="chart-title">Medical Cases Frequency Over Time</h3>
    <canvas id="casesLineChart"></canvas>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    <?php

    $disease_dates = [];
    $seen = [];

    foreach ($visits as $visit) {
        $diag = $visit['diagnosis'] ?? '';
        $date = isset($visit['consultation_date']) ? date('Y-m-d', strtotime($visit['consultation_date'])) : '';
        $patient = $visit['patient_id'] ?? null;

        if ($diag && $date && $patient) {
            $key = "{$diag}_{$patient}";

        
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;

           
            if (!isset($disease_dates[$diag])) $disease_dates[$diag] = [];
            if (!isset($disease_dates[$diag][$date])) $disease_dates[$diag][$date] = 0;

            
            $disease_dates[$diag][$date]++;
        }
    }

   
    $all_dates = [];
    foreach ($disease_dates as $diag => $dates) {
        foreach ($dates as $date => $count) {
            $all_dates[$date] = true;
        }
    }
    $all_dates = array_keys($all_dates);
    sort($all_dates);

    // Prepare datasets for Chart.js
    $datasets = [];
    $colors = [
        '#4e79a7', '#f28e2b', '#e15759', '#76b7b2', '#59a14f', '#edc949',
        '#af7aa1', '#ff9da7', '#9c755f', '#bab0ab', '#b07aa1', '#7a9cb0'
    ];
    $colorIndex = 0;
    foreach ($disease_dates as $diag => $dates) {
        $data = [];
        $runningTotal = 0; // accumulate counts over time

        foreach ($all_dates as $date) {
            if (isset($dates[$date])) {
                $runningTotal += $dates[$date]; // add first-time patients
            }
            $data[] = $runningTotal; // cumulative line
        }

        $datasets[] = [
            'label' => $diag,
            'data' => $data,
            'fill' => false,
            'borderColor' => $colors[$colorIndex % count($colors)],
            'backgroundColor' => $colors[$colorIndex % count($colors)],
            'tension' => 0.2
        ];
        $colorIndex++;
    }
    ?>
    const diseaseLineLabels = <?= json_encode($all_dates) ?>;
    const diseaseLineDatasets = <?= json_encode($datasets) ?>;

    if (diseaseLineDatasets.length > 0 && diseaseLineLabels.length > 0) {
        const ctxDiseaseLine = document.getElementById('casesLineChart').getContext('2d');
        new Chart(ctxDiseaseLine, {
            type: 'line',
            data: {
                labels: diseaseLineLabels,
                datasets: diseaseLineDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 650, easing: 'easeOutQuart' },
                plugins: {
                    legend: { position: 'top' },
                    title: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Unique Patients' }
                    },
                    x: {
                        title: { display: true, text: 'Date' }
                    }
                }
            }
        });
    }
</script>


    <div id="optionalChartsGrid" class="report-charts-grid single-chart">
    <!-- Pie Chart Section: Sex Distribution -->
    <div id="sexChart" class="chart report-chart-card small-chart-card chart-hidden">
        <h3 class="chart-title">Patients by Sex</h3>
        <canvas id="sexPieChart"></canvas>
    </div>
    <script>
        <?php
            $sex_counts = ['Male' => 0, 'Female' => 0];
            $unique_patients = [];
            foreach ($visits as $visit) {
                $pid = $visit['patient_id'];
                if (!isset($unique_patients[$pid])) {
                    $unique_patients[$pid] = $visit['sex'];
                }
            }
            foreach ($unique_patients as $sex) {
                if (isset($sex_counts[$sex])) $sex_counts[$sex]++;
            }
        ?>
        const sexLabels = <?= json_encode(array_keys($sex_counts)) ?>;
        const sexData = <?= json_encode(array_values($sex_counts)) ?>;

        if (sexData.reduce((a, b) => a + b, 0) > 0) {
            const ctx = document.getElementById('sexPieChart').getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: sexLabels,
                    datasets: [{
                        data: sexData,
                        backgroundColor: [
                            '#4e79a7', '#f28e2b'
                        ],
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 650, easing: 'easeOutQuart' },
                    plugins: {
                        legend: { position: 'bottom' },
                        title: { display: false }
                    }
                }
            });
        }
    </script>

    <!-- Age Group Distribution Bar Chart -->
    <div id="ageGroupChart" class="chart report-chart-card medium-chart-card chart-hidden">
        <h3 class="chart-title">Age Groups</h3>
        <canvas id="ageGroupBarChart"></canvas>
    </div>
    </div>
    <script>
        <?php
        $age_group_counts = [
            '0–5' => 0,
            '6–17' => 0,
            '18–59' => 0,
            '60+' => 0
        ];
        $unique_patients_age = [];
        foreach ($visits as $visit) {
            $pid = $visit['patient_id'];
            if (!isset($unique_patients_age[$pid])) {
                $age = (int)$visit['age'];
                if ($age >= 0 && $age <= 5) {
                    $age_group_counts['0–5']++;
                } elseif ($age >= 6 && $age <= 17) {
                    $age_group_counts['6–17']++;
                } elseif ($age >= 18 && $age <= 59) {
                    $age_group_counts['18–59']++;
                } elseif ($age >= 60) {
                    $age_group_counts['60+']++;
                }
                $unique_patients_age[$pid] = true;
            }
        }
        ?>
        const ageGroupLabels = <?= json_encode(array_keys($age_group_counts)) ?>;
        const ageGroupData = <?= json_encode(array_values($age_group_counts)) ?>;

        if (ageGroupData.reduce((a, b) => a + b, 0) > 0) {
            const ctxBar = document.getElementById('ageGroupBarChart').getContext('2d');
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: ageGroupLabels,
                    datasets: [{
                        label: 'Patient Count',
                        data: ageGroupData,
                        backgroundColor: [
                            '#4e79a7', '#f28e2b', '#e15759', '#76b7b2'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 650, easing: 'easeOutQuart' },
                    plugins: {
                        legend: { display: false },
                        title: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Patient Count' }
                        },
                        x: {
                            title: { display: true, text: 'Age Group' }
                        }
                    }
                }
            });
        }
    </script>

<!-- Table with Visit Details -->
<?php if ($visits && count($visits) > 0): ?>
    <div class="report-table-container">
      <div class="report-table-scroll">
      <table id="reportTable">
        <thead>
            <tr>
                <th data-type="date">Date Diagnosed</th>
                <th data-type="string">Diagnosis</th>
                <th data-type="string">Status</th>
                <th data-type="string">Patient Name</th>
                <th data-type="string">Sex</th>
                <th data-type="number">Age</th>
                <th data-type="string">Address</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($visits as $visit): ?>
            <tr>
                <td data-label="Date Diagnosed"><?= date('M d, Y', strtotime($visit['consultation_date'])) ?></td>
                <td data-label="Diagnosis"><?= htmlspecialchars($visit['diagnosis']) ?></td>
                <td data-label="Status"><?= htmlspecialchars($visit['diagnosis_status']) ?></td>
                <td data-label="Patient Name"><?= htmlspecialchars($visit['first_name'] . ' ' . $visit['last_name']) ?></td>
                <td data-label="Sex"><?= htmlspecialchars($visit['sex']) ?></td>
                <td data-label="Age"><?= htmlspecialchars($visit['age']) ?></td>
                <td data-label="Address"><?= htmlspecialchars($visit['address']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
      </div>
    </div>
    <br>
 
<?php else: ?>
    <p>No visits found for the selected filters.</p>
<?php endif; ?>


<!-- Summary Section -->
 <?php
function prettyDate($dateStr, $withTime = false) {
    $ts = strtotime($dateStr);
    if (!$ts) return htmlspecialchars($dateStr);
    return $withTime ? date('F d, Y H:i:s', $ts) : date('M d, Y', $ts);
}
?>

<?php
// Calculate total unique patients
$unique_patient_ids = [];
foreach ($visits as $visit) {
    $unique_patient_ids[$visit['patient_id']] = true;
}
$total_patients = count($unique_patient_ids);
?>
<?php
// --- Patient counts per Purok (unique patients) ---
$patient_purok = []; // pid => purok label
foreach ($visits as $v) {
    $pid = $v['patient_id'] ?? null;
    $addr = trim($v['address'] ?? '');
    if (!$pid) continue;

    // Try to extract "Purok X" from address; fallback to full address or "Unspecified"
    $purokLabel = 'Unspecified';
    if (preg_match('/purok\s*([A-Za-z0-9\-]+)/i', $addr, $m)) {
        $purokLabel = 'Purok ' . strtoupper($m[1]);
    } elseif ($addr !== '') {
        $purokLabel = $addr;
    }
    // First seen purok per patient wins
    if (!isset($patient_purok[$pid])) {
        $patient_purok[$pid] = $purokLabel;
    }
}
$purok_counts = [];
foreach ($patient_purok as $purok) {
    $purok_counts[$purok] = ($purok_counts[$purok] ?? 0) + 1;
}
// Sort by natural order (Purok 1, Purok 2, ...)
uksort($purok_counts, 'strnatcasecmp');

// --- Optional: BMI counts (unique patients) if your rows have bmi_category ---
$bmi_counts = ['Underweight' => 0, 'Normal' => 0, 'Overweight' => 0, 'Obese' => 0];
$seen_bmi = [];
foreach ($visits as $v) {
    $pid = $v['patient_id'] ?? null;
    if (!$pid || isset($seen_bmi[$pid])) continue;
    if (isset($v['bmi_category']) && isset($bmi_counts[$v['bmi_category']])) {
        $bmi_counts[$v['bmi_category']]++;
        $seen_bmi[$pid] = true;
    }
}
// If you don’t have BMI, this will remain all zeros and we’ll hide the row.

// --- Optional: most common treatment if your rows have `treatment` ---
$treatment_counts = [];
$seen_treatment = [];
foreach ($visits as $v) {
    $pid = $v['patient_id'] ?? null;
    if (!$pid) continue;
    // Count each patient once per treatment
    $treat = trim($v['treatment'] ?? '');
    if ($treat === '') continue;
    $key = $pid . '|' . $treat;
    if (isset($seen_treatment[$key])) continue;
    $seen_treatment[$key] = true;
    $treatment_counts[$treat] = ($treatment_counts[$treat] ?? 0) + 1;
}
arsort($treatment_counts);
$most_common_treatment = $treatment_counts ? array_key_first($treatment_counts) : '—';
?>

<div class="summary-container">
  <div class="summary">
  <h3><i class="bx bx-file"></i>Report Details</h3>
    <!-- Key values -->
    <table class="kv-table">
      <tr>
        <th>Report Generated On</th>
        <td><?= date('F d, Y H:i:s') ?></td>
      </tr>
      <tr>
        <th>Total Patients in Report</th>
        <td><?= $total_patients ?? 0 ?></td>
      </tr>
      <?php
      $has_bmi = array_sum($bmi_counts) > 0;
      if ($has_bmi): ?>
      <tr>
        <th>By BMI</th>
        <td>
          Underweight – <?= $bmi_counts['Underweight'] ?>,
          Normal – <?= $bmi_counts['Normal'] ?>,
          Overweight – <?= $bmi_counts['Overweight'] ?>,
          Obese – <?= $bmi_counts['Obese'] ?>
        </td>
      </tr>
      <?php endif; ?>
      <?php if (!empty($treatment_counts)): ?>
      <tr>
        <th>Most Common Treatment Given</th>
        <td><?= htmlspecialchars($most_common_treatment) ?></td>
      </tr>
      <?php endif; ?>
    </table>

    <!-- Two small tables side-by-side -->
    <div class="mini-wrap">
      <table class="mini-table">
        <thead><tr><th colspan="2">By Sex (Unique Patients)</th></tr></thead>
        <tbody>
          <tr><td>Male</td><td><?= $sex_counts['Male'] ?? 0 ?></td></tr>
          <tr><td>Female</td><td><?= $sex_counts['Female'] ?? 0 ?></td></tr>
        </tbody>
      </table>

      <table class="mini-table">
        <thead><tr><th colspan="2">By Age Group (Unique Patients)</th></tr></thead>
        <tbody>
          <tr><td>Children</td><td><?= $age_group_counts['6–17'] ?? 0 ?></td></tr>
          <tr><td>Adults</td><td><?= $age_group_counts['18–59'] ?? 0 ?></td></tr>
          <tr><td>Seniors</td><td><?= $age_group_counts['60+'] ?? 0 ?></td></tr>
        </tbody>
      </table>
    </div>

    <!-- Purok table -->
    <?php if (!empty($purok_counts)): ?>
    <table class="purok-table">
      <thead><tr><th colspan="2">Patient Counts per Purok (Unique Patients)</th></tr></thead>
      <tbody>
        <?php foreach ($purok_counts as $purok => $count): ?>
          <tr>
            <td><?= htmlspecialchars($purok) ?></td>
            <td><?= $count ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

   <span id="generated_by"></span>

</div><!-- /.report-content -->
</div><!-- /.print-area -->
</div><!-- /.main-content -->
</div><!-- /.history-container -->
</main>
</section>

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

<script>
(function() {
  const table = document.getElementById('reportTable');
  if (!table) return;
  const thead = table.tHead || table.querySelector('thead');
  const tbody = table.tBodies[0];
  if (!thead || !tbody) return;

  [...thead.querySelectorAll('th')].forEach(th => {
    if (!th.querySelector('.sort-indicator')) {
      const ind = document.createElement('span');
      ind.className = 'sort-indicator';
      th.appendChild(ind);
    }
  });

  function parseDate(v) {
    const t = (v || '').trim();
    const d = new Date(t);
    return isNaN(d.getTime()) ? null : d;
  }
  function detectType(idx) {
    const th = thead.querySelectorAll('th')[idx];
    if (th?.dataset?.type) return th.dataset.type;
    for (const tr of tbody.rows) {
      const t = (tr.cells[idx]?.textContent || '').trim();
      if (!t) continue;
      if (parseDate(t)) return 'date';
      if (!isNaN(t.replace(/,/g, ''))) return 'number';
      return 'string';
    }
    return 'string';
  }
  function val(tr, idx, type) {
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
  function sortBy(idx, dir) {
    const type = detectType(idx);
    const rows = [...tbody.rows].sort((a, b) => {
      const va = val(a, idx, type), vb = val(b, idx, type);
      if (va < vb) return dir === 'asc' ? -1 : 1;
      if (va > vb) return dir === 'asc' ? 1 : -1;
      return 0;
    });
    const frag = document.createDocumentFragment();
    rows.forEach(r => frag.appendChild(r));
    tbody.appendChild(frag);
  }
  [...thead.querySelectorAll('th')].forEach((th, idx) => {
    th.addEventListener('click', () => {
      const nextDir = th.classList.contains('is-sorted-asc') ? 'desc' : 'asc';
      [...thead.querySelectorAll('th')].forEach(h => h.classList.remove('is-sorted-asc', 'is-sorted-desc'));
      th.classList.add(nextDir === 'asc' ? 'is-sorted-asc' : 'is-sorted-desc');
      sortBy(idx, nextDir);
    });
  });
  const def = thead.querySelectorAll('th')[0];
  if (def) { def.classList.add('is-sorted-desc'); sortBy(0, 'desc'); }
})();
</script>

<!-- jsPDF and html2canvas libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>

    
function exportTableToExcel(tableID, filename = 'Medical Cases Report') {
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


function printDiv() {
  // Prefer the new letterhead; fall back to the old class if present
  const headerEl = document.querySelector('.print-letterhead, .print-header');
  const printHeader = headerEl ? headerEl.outerHTML : '';

  // Clone the printable area
  const originalArea = document.querySelector(".print-area").cloneNode(true);

  // Remove any header inside the clone (to avoid duplicate headers)
  const headerInClone = originalArea.querySelector('.print-letterhead, .print-header');
  if (headerInClone) headerInClone.remove();

  // Remove canvases and all chart elements from the clone
  originalArea.querySelectorAll('canvas, .chart-controls-panel, .report-chart-card, .line-chart-card, .report-charts-grid').forEach(el => el.remove());

  // Open print window
  const w = window.open('', '', 'height=900,width=1100');
  if (!w) { alert('Please allow pop-ups to print.'); return; }

  w.document.write(`
    <html>
      <head>
        <title>Print Report</title>
        <style>
          body { font-family: Arial, sans-serif; font-size: 13px; color: #000; }
          table { width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 12px; }
          th, td { border: 1px solid #000; padding: 4px 6px; text-align: left; font-family: Arial, sans-serif; }
          thead tr { background: #fff !important; }
          thead th { background: #fff !important; color: #000 !important; font-weight: 700; }
          img { display:block; margin:0 auto; max-width:100%; height:auto; }
          h3 { margin:10px 0 5px 0; font-family: Arial, sans-serif; }
          .chart-controls-panel, .report-chart-card, .line-chart-card,
          canvas, img[src^="data:"], .report-charts-grid { display: none !important; }

          /* Letterhead */
          .print-letterhead{display:grid;grid-template-columns:64px auto 64px;align-items:center;justify-content:center;column-gap:14px;margin:0 auto 14px;text-align:center;width:fit-content;}
          .print-logo{ width:80px; height:80px; object-fit:contain; }
          .print-heading{ line-height:1.1; color:#000; font-family:Arial,sans-serif; }
          .print-heading > *{ margin:0; }
          .ph-line-1{ font-size:12pt; font-weight:500; }
          .ph-line-2{ font-size:16pt; font-weight:500; }
          .ph-line-3{ font-size:12pt; font-weight:500; }
          .ph-line-4{ font-size:12pt; font-weight:800; margin-top:4px; text-align:center; font-family:Arial,sans-serif; }
          .print-rule{ height:1px; border:0; background:#cfd8e3; margin:8px 0 10px; }
          .title{ text-align:center; margin:8px 0; font-family:Arial,sans-serif; }
          .print-sub{ font-size:11pt; text-align:center; font-family:Arial,sans-serif; }

          /* Signature */
          #generated_by { margin-top: 48px; font-family: Arial, sans-serif; }
          .sig-label { font-size:11px; text-transform:uppercase; letter-spacing:.07em; color:#666; margin-bottom:60px; display:block; }
          .sig-block { display:inline-block; text-align:center; }
          .sig-line { display:block; border:none; border-top:1.5px solid #000; margin:0 0 4px; }
          .sig-name { font-weight:700; font-size:13px; white-space:nowrap; }
          .sig-title { font-size:11px; color:#666; }
        </style>
      </head>
      <body>
        ${printHeader}
        ${originalArea.innerHTML}
      </body>
    </html>
  `);
  w.document.close();
  w.focus();
  setTimeout(() => { w.print(); w.close(); }, 300);
}

fetch('../php/getUserName.php')
  .then(response => response.json())
  .then(data => {
    const fullName = (data && data.full_name) ? data.full_name : '';
    // Greet as before
    document.getElementById('userGreeting').textContent =
      fullName ? `Hello, ${fullName}!` : 'Hello, BHW!';

    // Build the signature block
    const gb = document.getElementById('generated_by');
    const name = fullName || '________________';
    gb.innerHTML = `<div class="sig-label">Report Generated by:</div>
      <div class="sig-block">
        <span class="sig-line"></span>
        <div class="sig-name"></div>
        <div class="sig-title">Barangay Health Worker</div>
      </div>`;
    gb.querySelector('.sig-name').textContent = name;
    const nameEl = gb.querySelector('.sig-name');
    const lineEl = gb.querySelector('.sig-line');
    requestAnimationFrame(() => { lineEl.style.width = nameEl.offsetWidth + 'px'; });

    const sidebarName = document.getElementById('sidebarUserName');
    if (sidebarName) {
      sidebarName.textContent = fullName || 'BHW User';
    }
  })
  .catch(() => {
    document.getElementById('userGreeting').textContent = 'Hello, BHW!';

    const sidebarName = document.getElementById('sidebarUserName');
    if (sidebarName) {
      sidebarName.textContent = 'BHW User';
    }

    const gb = document.getElementById('generated_by');
    gb.innerHTML = `<div class="sig-label">Report Generated by:</div>
      <div class="sig-block">
        <span class="sig-line" style="width:180px;"></span>
        <div class="sig-name">________________</div>
        <div class="sig-title">Barangay Health Worker</div>
      </div>`;
  });

function confirmLogout() {
    document.getElementById('logoutModal').classList.add('open');
    return false; // Prevent the default link behavior
}

function closeModal() {
    document.getElementById('logoutModal').classList.remove('open');
}

function proceedLogout() {
    window.location.href='../../ADMIN/php/logout'; 
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('logoutModal');
    if (event.target === modal) {
        closeModal();
    }
});
	// Check if user is logged in
fetch('../php/getUserId.php')
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            // User is not logged in, redirect to role selection page
            window.location.href='../auth/role';
        }
    })
    .catch(error => {
        console.error('Error checking session:', error);
        window.location.href='../auth/role';
    });

</script>

<script>
(function () {
  const sidebar = document.getElementById('sidebar');
  const toggle = document.getElementById('sidebarToggle');
  const overlay = document.getElementById('sidebarOverlay');
  const MOBILE_BP = 768;

  if (!sidebar || !toggle || !overlay) return;

  function isMobile() {
    return window.innerWidth <= MOBILE_BP;
  }

  function closeMobileSidebar() {
    sidebar.classList.remove('mobile-open');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
  }

  toggle.addEventListener('click', function () {
    if (isMobile()) {
      const open = sidebar.classList.toggle('mobile-open');
      overlay.classList.toggle('active', open);
      document.body.style.overflow = open ? 'hidden' : '';
    } else {
      sidebar.classList.toggle('collapsed');
    }
  });

  overlay.addEventListener('click', closeMobileSidebar);

  window.addEventListener('resize', function () {
    if (!isMobile()) {
      closeMobileSidebar();
    }
  });
})();
</script>

</body>
</html>