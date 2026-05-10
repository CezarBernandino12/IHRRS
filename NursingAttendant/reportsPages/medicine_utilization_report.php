<?php
require '../../php/db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../role");
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT full_name, rhu FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$rhu = $user ? $user['rhu'] : 'N/A';
$username = $user ? $user['full_name'] : 'N/A';

// Initialize filters
$from_date   = $_GET['from_date'] ?? '';
$to_date     = $_GET['to_date'] ?? '';
$medicine    = isset($_GET['medicine']) ? (array)$_GET['medicine'] : [];
$sex         = $_GET['sex'] ?? '';
$age_group   = $_GET['age_group'] ?? '';
$barangay    = $_GET['purok'] ?? ''; // Purok as barangay filter
$subcategory = $_GET['subcategory'] ?? '';

$params = [];

// Base query with RHU filter through dispensed_by
$sql = "
SELECT 
    p.patient_id,
    CONCAT(p.last_name, ', ', p.first_name, ' ', COALESCE(p.middle_name, ''), ' ', COALESCE(p.extension, '')) AS full_name,
    p.address,
    p.date_of_birth,
    p.age,
    p.sex,
    p.philhealth_member_no,
    MIN(md.dispensed_date) AS date_dispensed
FROM rhu_medicine_dispensed md
JOIN users u_disp ON md.dispensed_by = u_disp.user_id
JOIN rhu_consultations c ON md.consultation_id = c.consultation_id
JOIN patients p ON c.patient_id = p.patient_id
WHERE u_disp.rhu = :rhu
";

$params['rhu'] = $rhu;
 // Only show records from the same RHU as current user

// Date filter (index-friendly, no DATE() wrapper)
if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND md.dispensed_date BETWEEN :from_date AND :to_date";
    $params['from_date'] = $from_date . " 00:00:00"; // start of day
    $params['to_date']   = $to_date . " 23:59:59";   // end of day
}

// Medicine filter
if (!empty($medicine)) {
    if (!is_array($medicine)) {
        $medicine = [$medicine]; // force into array if string
    }
    $placeholders = [];
    foreach ($medicine as $i => $med) {
        $ph = ":medicine_$i";
        $placeholders[] = $ph;
        $params["medicine_$i"] = $med;
    }
    $sql .= " AND md.medicine_name IN (" . implode(',', $placeholders) . ")";
    $medicine_list = $medicine; // Only selected medicines
} else {
    // Fetch all distinct medicines (for columns)
    $med_stmt = $pdo->query("SELECT DISTINCT medicine_name FROM rhu_medicine_dispensed ORDER BY medicine_name ASC");
    $medicine_list = $med_stmt->fetchAll(PDO::FETCH_COLUMN);
}



// 🔹 Medicine subcategory filter
// 🔹 Medicine subcategory filter
if (!empty($subcategory)) {
    if (!is_array($subcategory)) {
        $subcategory = [$subcategory]; // force into array if string
    }

    $placeholders = [];
    foreach ($subcategory as $i => $sub) {
        $ph = ":subcategory_$i";
        $placeholders[] = $ph;
        $params["subcategory_$i"] = $sub;
    }

    // Join to custom_options to get sub_category info
    $sql .= " AND md.medicine_name IN (
        SELECT value 
        FROM custom_options 
        WHERE sub_category IN (" . implode(',', $placeholders) . ")
    )";

    $subcategory_list = $subcategory; // only selected subcategories
} else {
    // 🔹 Fetch all distinct subcategories from custom_options
    $sub_stmt = $pdo->query("SELECT DISTINCT sub_category FROM custom_options WHERE category = 'medicine' ORDER BY sub_category ASC");
    $subcategory_list = $sub_stmt->fetchAll(PDO::FETCH_COLUMN);
}

// If the user did NOT explicitly select medicines but DID select subcategory(ies),
// limit the medicine_list to only medicines in those subcategories.
if (empty($medicine) && !empty($subcategory)) {
    // Use positional placeholders to fetch medicines for the selected subcategories
    $ph = implode(',', array_fill(0, count($subcategory), '?'));
    $med_sql = "
        SELECT DISTINCT co.value
        FROM custom_options co
        JOIN rhu_medicine_dispensed md ON md.medicine_name = co.value
        WHERE co.category = 'medicine'
          AND co.sub_category IN ($ph)
        ORDER BY co.value ASC
    ";
    $med_stmt = $pdo->prepare($med_sql);
    $med_stmt->execute($subcategory);
    $medicine_list = $med_stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Sex filter
if (!empty($sex)) {
    $sql .= " AND p.sex = :sex";
    $params['sex'] = $sex;
}

// Age group filter (compute from date_of_birth, safer than p.age column)
if (!empty($age_group)) {
    switch ($age_group) {
        case 'child':  
            $sql .= " AND TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) < 13"; 
            break;
        case 'teen':   
            $sql .= " AND TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) BETWEEN 13 AND 19"; 
            break;
        case 'adult':  
            $sql .= " AND TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) BETWEEN 20 AND 59"; 
            break;
        case 'senior': 
            $sql .= " AND TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) >= 60"; 
            break;
    }
}

// Barangay filter
if (!empty($barangay) && $barangay !== 'N/A') {
    $sql .= " AND p.address LIKE :barangay";
    $params['barangay'] = '%' . $barangay . '%';
}


$sql .= " GROUP BY p.patient_id ORDER BY p.last_name, p.first_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params); //THIS IS LINE 159
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ensure $rows is populated with patient data for the charts
$rows = $patients;

// Initialize $periods and $medicine_series for the line graph
$periods = [];
$medicine_series = [];

// Fetch dispensed data for graph, respecting date and medicine filters
$graph_sql = "
    SELECT DATE(dm.dispensed_date) AS period, dm.medicine_name, SUM(dm.quantity_dispensed) AS qty
    FROM rhu_medicine_dispensed dm
    WHERE 1=1
";
$graph_params = [];

// Apply date filter to graph
if (!empty($from_date) && !empty($to_date)) {
    $graph_sql .= " AND dm.dispensed_date BETWEEN ? AND ?";
    $graph_params[] = $from_date . " 00:00:00";
    $graph_params[] = $to_date . " 23:59:59";
}

// Apply medicine filter to graph
if (!empty($medicine_list)) {
    $med_placeholders = implode(',', array_fill(0, count($medicine_list), '?'));
    $graph_sql .= " AND dm.medicine_name IN ($med_placeholders)";
    $graph_params = array_merge($graph_params, $medicine_list);
}

$graph_sql .= " GROUP BY DATE(dm.dispensed_date), dm.medicine_name ORDER BY DATE(dm.dispensed_date)";

$dispensed_data_stmt = $pdo->prepare($graph_sql);
$dispensed_data_stmt->execute($graph_params);

while ($row = $dispensed_data_stmt->fetch(PDO::FETCH_ASSOC)) {
    $period = $row['period'];
    $medicine = $row['medicine_name'];
    $quantity = $row['qty'];

    if (!in_array($period, $periods)) {
        $periods[] = $period;
        // Add new period to all existing medicine_series arrays
        foreach ($medicine_series as &$series) {
            $series[$period] = 0;
        }
        unset($series);
    }
    if (!isset($medicine_series[$medicine])) {
        // Initialize with all periods so far
        $medicine_series[$medicine] = array_fill_keys($periods, 0);
    }
    $medicine_series[$medicine][$period] = $quantity;
}

// Filter $medicine_series to only selected medicines (if filter applied)
if (!empty($medicine_list)) {
    $medicine_series = array_intersect_key($medicine_series, array_flip($medicine_list));
}


// Initialize patient_meds
$patient_meds = [];
foreach ($patients as $p) {
    $key = $p['patient_id'];
    $patient_meds[$key] = [
        'row' => $p,
        'medicines' => array_fill_keys($medicine_list, 0)
    ];
}


// Get dispensed quantities
if (count($patient_meds) > 0) {
    $ids = array_keys($patient_meds);
    $id_placeholders = implode(',', array_fill(0, count($ids), '?'));
    $med_placeholders = implode(',', array_fill(0, count($medicine_list), '?'));

    $disp_sql = "
        SELECT p.patient_id, dm.medicine_name, dm.dispensed_date, SUM(dm.quantity_dispensed) AS qty
        FROM rhu_medicine_dispensed dm
        JOIN rhu_consultations c ON dm.consultation_id = c.consultation_id
        JOIN patients p ON c.patient_id = p.patient_id
        WHERE p.patient_id IN ($id_placeholders)
        " . (!empty($medicine_list) ? " AND dm.medicine_name IN ($med_placeholders)" : "") . "
        GROUP BY p.patient_id, dm.medicine_name
    ";

    $disp_stmt = $pdo->prepare($disp_sql);
$disp_stmt->execute(array_merge($ids, $medicine_list));


    while ($disp_row = $disp_stmt->fetch(PDO::FETCH_ASSOC)) {
        $pid = $disp_row['patient_id'];
        if (isset($patient_meds[$pid]['medicines'][$disp_row['medicine_name']])) {
            $patient_meds[$pid]['medicines'][$disp_row['medicine_name']] = $disp_row['qty'];
        }
    }
}

        //ADDED GENERATED REPORT FOR ACTIVITY LOG
        $stmt_log = $pdo->prepare("INSERT INTO logs (
            user_id, action, performed_by
        ) VALUES (
            :user_id, :action, :performed_by
        )");
        $stmt_log->execute([
            ':user_id' => $_SESSION['user_id'],
            ':action' => "Generated RHU Medicine Dispensation Report",
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
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

	<title>Medicine Utilization Report</title>
</head>
<body>

<style>
/* Page-specific polish to match the unified reports UI. */
#content main {
  width: 100%;
  padding: 32px 28px;
  max-height: calc(100vh - 56px);
  overflow-y: auto;
}

.history-container,
.main-content {
  width: 100%;
}

.filter-form {
  background: var(--surface, #fff);
  border: 1px solid var(--border, #dde4ef);
  border-radius: var(--r-lg, 16px);
  padding: 28px 32px 24px;
  margin-bottom: 24px;
  box-shadow: var(--shadow-sm, 0 2px 8px rgba(13,45,82,.09));
}

.filter-form h2 {
  font-size: 17px;
  font-weight: 700;
  color: var(--navy, #0d2d52);
  letter-spacing: -.2px;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-form h2::before {
  content: '';
  display: inline-block;
  width: 4px;
  height: 18px;
  background: var(--blue, #1c6fba);
  border-radius: 2px;
  flex-shrink: 0;
}

.form-submit {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 0 !important;
}

.selected-filters {
  margin: 20px 0 0 !important;
}

.selected-filters h3 {
  font-size: 13px;
  font-weight: 600;
  color: var(--grey-700, #4a5568);
  margin-bottom: 10px !important;
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
  background: var(--blue-pale, #f0f6ff) !important;
  color: var(--navy, #0d2d52) !important;
  border: 1px solid var(--border, #dde4ef) !important;
  padding: 5px 12px !important;
  border-radius: 20px !important;
  font-size: 13px !important;
  font-weight: 500 !important;
  display: inline-flex !important;
  align-items: center !important;
  gap: 6px !important;
}

.filter-tag a {
  color: var(--grey-500, #8c96aa) !important;
  font-weight: 700 !important;
  font-size: 14px !important;
  line-height: 1;
  margin-left: 0 !important;
  text-decoration: none !important;
}

.filter-tag a:hover {
  color: var(--red, #e53e3e) !important;
}

.modal-content {
  max-width: 620px !important;
}

.checkbox-list {
  max-height: 170px;
  overflow-y: auto;
  background: var(--grey-100, #f8f9fc);
  border: 1.5px solid var(--border, #dde4ef);
  border-radius: var(--r-sm, 6px);
  padding: 10px 12px;
}

.checkbox-list label {
  display: flex !important;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px !important;
  text-align: left !important;
  font-size: 13px;
  font-weight: 500 !important;
  color: var(--grey-700, #4a5568);
  line-height: 1.35;
}

.checkbox-list label:last-child {
  margin-bottom: 0 !important;
}

.checkbox-list input[type="checkbox"] {
  width: 14px;
  height: 14px;
  accent-color: var(--blue, #1c6fba);
  flex: 0 0 auto;
}

.form-item small {
  color: var(--grey-500, #8c96aa) !important;
  font-size: 12px;
}

.print-area {
  background: var(--white, #fff);
  border: 1px solid var(--border, #dde4ef);
  border-radius: var(--r-lg, 16px);
  padding: 28px 32px;
  box-shadow: var(--shadow-sm, 0 2px 8px rgba(13,45,82,.09));
}

.print-letterhead {
  display: none;
}

.title {
  text-align: center;
  display: none;
}

.report-content {
  width: 100%;
}

.chart-card {
  width: 100%;
  max-width: 760px;
  margin: 24px auto 0;
  padding: 20px;
  text-align: center;
  background: var(--grey-100, #f8f9fc);
  border: 1px solid var(--border-soft, #edf0f7);
  border-radius: var(--r-md, 10px);
}

.chart-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--navy, #0d2d52);
  margin-bottom: 12px;
}

.report-table-container {
  margin-top: 24px;
}

.report-table-scroll {
  width: 100%;
  overflow: auto;
  max-height: 620px;
}

#reportTable {
  min-width: 1200px;
}

#reportTable th {
  cursor: pointer;
  position: relative;
  user-select: none;
}

#reportTable th .sort-indicator {
  margin-left: 6px;
  font-size: 11px;
  opacity: .7;
}

#reportTable th.is-sorted-asc .sort-indicator::after { content: "▲"; }
#reportTable th.is-sorted-desc .sort-indicator::after { content: "▼"; }

#reportTable td:nth-child(2),
#reportTable td:nth-child(3) {
  white-space: normal;
  word-break: break-word;
  min-width: 150px;
}

.summary-container {
  margin-top: 32px;
}

.summary-title,
.summary > h3 {
  font-size: 16px;
  font-weight: 700;
  color: var(--navy, #0d2d52);
  letter-spacing: -.1px;
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
}

.summary-title i,
.summary > h3 i {
  font-size: 18px;
  color: var(--blue, #1c6fba);
}

.summary-table,
.summary-table2 {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  border: 1px solid var(--border, #dde4ef);
  border-radius: var(--r-lg, 16px);
  overflow: hidden;
  box-shadow: var(--shadow-sm, 0 2px 8px rgba(13,45,82,.09));
  font-size: 14px;
  margin-top: 8px;
  table-layout: auto;
}

.summary-table th,
.summary-table2 th {
  background: var(--grey-100, #f8f9fc);
  font-weight: 600;
  color: var(--navy, #0d2d52);
  padding: 12px 16px !important;
  text-align: left;
  border-bottom: 1px solid var(--border-soft, #edf0f7) !important;
  border-right: 1px solid var(--border-soft, #edf0f7) !important;
}

.summary-table td,
.summary-table2 td {
  color: var(--grey-700, #4a5568);
  padding: 12px 16px !important;
  border-bottom: 1px solid var(--border-soft, #edf0f7) !important;
  border-right: 1px solid var(--border-soft, #edf0f7) !important;
  vertical-align: top;
}

.summary-table tr:last-child th,
.summary-table tr:last-child td,
.summary-table2 tr:last-child th,
.summary-table2 tr:last-child td {
  border-bottom: none !important;
}

.dispensed-summary-block {
  margin-top: 16px;
}

.dispensed-summary-block strong {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--navy, #0d2d52);
  font-size: 14px !important;
}

#generated_by {
  display: block;
  margin: 32px 0 0 4px;
  color: var(--dark, #0f1d31);
}

#generated_by .sig-label {
  font-size: 12.5px;
  font-weight: 600;
  color: var(--grey-500, #8c96aa);
  text-transform: uppercase;
  letter-spacing: .08em;
  margin-bottom: 20px;
}

#generated_by .sig-line {
  display: none;
  width: 200px;
  border: 0;
  border-top: 1.5px solid var(--dark, #0f1d31);
  margin: 26px 0 6px;
}

#generated_by .sig-name {
  font-weight: 700;
  font-size: 15px;
  color: var(--navy, #0d2d52);
  margin-top: 4px;
}

#generated_by .sig-title {
  font-size: 12.5px;
  color: var(--grey-500, #8c96aa);
  margin-top: 2px;
}



/* Balanced responsive chart layout for this report */
.chart-controls-panel {
  background: var(--grey-100, #f8f9fc);
  border: 1px solid var(--border-soft, #edf0f7);
  border-radius: var(--r-md, 10px);
  padding: 16px 20px;
  margin: 0 0 24px;
}

.chart-controls-panel h3 {
  font-size: 13px;
  font-weight: 700;
  color: var(--grey-700, #4a5568);
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
  background: var(--white, #fff);
  border: 1.5px solid var(--border, #dde4ef);
  border-radius: 20px;
  font-size: 13px;
  font-weight: 500;
  color: var(--grey-700, #4a5568);
  cursor: pointer;
  transition: border-color .18s, background .18s, color .18s, transform .12s;
  user-select: none;
}

.chart-toggle-group label:hover {
  transform: translateY(-1px);
  border-color: var(--blue, #1c6fba);
}

.chart-toggle-group label:has(input:checked) {
  background: var(--navy, #0d2d52);
  border-color: var(--navy, #0d2d52);
  color: var(--white, #fff);
}

.chart-toggle-group input[type="checkbox"] {
  width: 14px;
  height: 14px;
  accent-color: var(--white, #fff);
  cursor: pointer;
}

.medicine-charts-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(280px, 1fr));
  gap: 24px;
  align-items: stretch;
  margin: 0 0 28px;
  transition: all .28s ease;
}

.medicine-charts-grid.single-chart {
  grid-template-columns: minmax(280px, 760px);
  justify-content: center;
}

.medicine-charts-grid .chart-card {
  width: 100%;
  max-width: none !important;
  margin: 0 !important;
  min-height: 350px;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  animation: chartFadeUp .28s ease both;
}

.medicine-charts-grid .chart-card canvas {
  width: 100% !important;
  height: 280px !important;
  max-height: 280px;
}

#medicineLineChartWrap {
  grid-column: 1 / -1;
  max-width: 1000px !important;
  justify-self: center;
}

.medicine-charts-grid.single-chart #medicineLineChartWrap {
  grid-column: auto;
  max-width: 760px !important;
}

#medicineLineChartWrap canvas {
  height: 320px !important;
  max-height: 320px;
}

.medicine-chart-hidden {
  display: none !important;
}

.medicine-chart-visible {
  display: flex !important;
}

@keyframes chartFadeUp {
  from { opacity: 0; transform: translateY(14px) scale(.985); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

@media (max-width: 900px) {
  .medicine-charts-grid,
  .medicine-charts-grid.single-chart {
    grid-template-columns: 1fr;
  }
  #medicineLineChartWrap {
    grid-column: auto;
    max-width: none !important;
  }
}

@media (max-width: 480px) {
  .medicine-charts-grid .chart-card { min-height: 310px; }
  .medicine-charts-grid .chart-card canvas { height: 240px !important; max-height: 240px; }
  #medicineLineChartWrap canvas { height: 270px !important; max-height: 270px; }
}

@media print {
  @page { size: landscape; margin: 1cm; }

  .title { display: block !important; }
  .print-letterhead { display: grid !important; }
  .form-submit,
  .selected-filters,
  .chart-controls-panel,
  .medicine-chart-controls,
  .chart-toggle-group,
  .chart-card,
  nav,
  #sidebar,
  .sidebar-overlay {
    display: none !important;
  }

  .print-area {
    box-shadow: none;
    border: none;
    padding: 0;
    border-radius: 0;
  }

  .print-letterhead {
    grid-template-columns: 72px auto 72px;
    align-items: center;
    justify-content: center;
    column-gap: 60px;
    margin: 0 auto 18px;
    text-align: center;
    width: fit-content;
  }

  .print-logo { width: 64px; height: 64px; object-fit: contain; }
  .print-heading { line-height: 1.1; color: #000; }
  .print-heading .ph-line-1 { font-size: 12pt; font-weight: 500; }
  .print-heading .ph-line-2 { font-size: 14pt; font-weight: 800; }
  .print-heading .ph-line-3 { font-size: 12pt; font-weight: 600; }
  .print-sub { font-size: 12pt; margin-top: 4px; }
  .print-rule { height: 1px; border: 0; background: #cfd8e3; margin: 8px 0 12px; }

  .report-table-container {
    box-shadow: none;
    border: 1px solid #000;
    border-radius: 0;
    margin-top: 12px !important;
    margin-bottom: 40px !important;
  }

  .report-table-scroll {
    overflow: visible !important;
    max-height: none !important;
  }

  #reportTable {
    min-width: unset;
    font-size: 9pt;
  }

  #reportTable th,
  #reportTable td,
  .summary-table th,
  .summary-table td,
  .summary-table2 th,
  .summary-table2 td {
    border: 1px solid #000 !important;
    color: #000 !important;
    background: transparent !important;
  }

  #generated_by { margin: 20mm 0 0 0; }
  #generated_by .sig-label { font-size: 12pt; }
  #generated_by .sig-name { font-size: 12pt; }
  #generated_by .sig-title { font-size: 11pt; }
  #generated_by .sig-line { display: block; width: 45mm; border-top-width: 1px; margin: 10mm 0 3mm; }
}

@media (max-width: 768px) {
  #content main { padding: 20px 14px; }
  .filter-form,
  .print-area { padding: 20px 18px; }
  .form-row { grid-template-columns: 1fr; }
  .modal-content { padding: 24px 20px 20px; }
}
</style>

<!-- Sidebar Section -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

	<section id="sidebar">
		<a href="#" class="sidebar-brand">
			<img src="../../img/logo.png" alt="RHU Logo" class="brand-logo">
			<div class="brand-text">
				<span class="brand-name">Hello Nurse</span>
			</div>
		</a>

		<div class="sidebar-scroll">
			<div class="sidebar-section-label">Main Menu</div>
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
					<a href="../pending" id="updateReferrals" data-tooltip="Pending Referrals">
						<i class="bx bxs-user nav-icon"></i>
						<span class="nav-label">Pending Referrals</span>
					</a>
				</li>
				<li>
					<a href="../followUpConsultations" data-tooltip="Follow-Up Visits">
						<i class="bx bxs-user nav-icon"></i>
						<span class="nav-label">Follow-Up Visits</span>
					</a>
				</li>
				<li>
					<a href="../searchPatient" data-tooltip="Patient Records">
						<i class="bx bxs-search nav-icon"></i>
						<span class="nav-label">Patient Records</span>
					</a>
				</li>
				<li>
					<a href="../history" data-tooltip="Referral History">
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
				<img src="../../img/nurse.png" alt="Nurse User">
				<div class="sidebar-user-info">
					<div class="user-name" id="sidebarUserName">Nurse</div>
					<div class="user-role">Nursing Attendant</div>
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

        <div class="nav-search" style="position: relative;">
            <input type="search" id="patientSearch" placeholder="Enter patient name..." name="search" autocomplete="off">
            <button type="button" id="searchButton" aria-label="Search">
                <i class="bx bx-search"></i>
            </button>
            <div id="resultDropdown" class="dropdown-content"></div>
        </div>
        <span id="userGreeting" style="display:none;"></span>
    </nav>


		<main>
            
            <div class="head-title">
                <div class="left">
                  <h1>Medicine Utilization</h1>
                </div>
              </div>

<div class="history-container">


<!-- Filter Form -->

<div class="filter-form">
      <h2>Medicine Utilization Report - <?php echo htmlspecialchars($rhu); ?></h2>
       

    <!-- Filter Modal Trigger -->
   
        <div class="form-submit" style="margin-top: -10px;">
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

                echo '<span class="filter-tag">';
                echo $display;
                echo ' <a href="?' . $query . '" title="Remove filter">&times;</a>';
                echo '</span>';
            }

            // Render tags for each filter if set
            if ($from_date) renderTag('From', 'from_date', $from_date);
            if ($to_date) renderTag('To', 'to_date', $to_date);
            if ($sex) renderTag('Sex', 'sex', $sex);
          
            if ($age_group) {
                $age_labels = [
                    'child' => 'Child (0–12)', 'teen' => 'Teen (13–19)',
                    'adult' => 'Adult (20–59)', 'senior' => 'Senior (60+)'
                ];
                renderTag('Age Group', 'age_group', $age_labels[$age_group] ?? ucfirst($age_group));
            }
           if (!empty($_GET['medicine'])) {
    foreach ((array)$_GET['medicine'] as $med) {
        renderTag('Medicine', 'medicine[]', $med);
    }
}
           if (!empty($_GET['subcategory'])) {
    foreach ((array)$_GET['subcategory'] as $med) {
        renderTag('Medicine Type', 'subcategory[]', $med);
    }
}

            if ($barangay) renderTag('Barangay', 'purok', $barangay);
           
            if (
                !$from_date && !$to_date && !$sex && !$age_group &&
                !$medicine && !$barangay && !$subcategory
            ) {
                echo '<span style="color:#888;">None</span>';
            }
            ?>
        </div>
    </div>


  <!-- Filter Modal -->
    <div id="filterModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 600px;">
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
                        <!--
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
                            
                      <div class="form-item" style="margin-top: -140px;">
                            <label for="purok">Barangay:</label>
                            <select name="purok" id="purok" class="form-control">
                                <option value="">All</option>
                                <?php
                                // Fetch distinct barangay names from custom_options
                                $barangay_stmt = $pdo->prepare("SELECT DISTINCT category FROM custom_options WHERE category LIKE 'Barangay%' ORDER BY category");
                                $barangay_stmt->execute();
                                $selected_purok = $_GET['purok'] ?? '';
                                while ($row = $barangay_stmt->fetch()) {
                                    $value = $row['category'];
                                    $selected = ($selected_purok === $value) ? 'selected' : '';
                                    echo "<option value=\"" . htmlspecialchars($value) . "\" $selected>" . htmlspecialchars($value) . "</option>";
                                }
                                ?>
                            </select>
                        </div>  -->

                    <div class="form-item">
                        <label for="medicine">Given Medicine:</label>
                        <div class="checkbox-list">
                            <?php
                            // Fetch medicines for checkboxes
                            $medicine_stmt = $pdo->prepare("SELECT DISTINCT medicine_name FROM rhu_medicine_dispensed ORDER BY medicine_name ASC");
                            $medicine_stmt->execute();
                            // Support multiple selection from GET
                            $selected_medicines = isset($_GET['medicine']) ? (array)$_GET['medicine'] : [];
                          while ($row = $medicine_stmt->fetch(PDO::FETCH_ASSOC)) {
    $value = $row['medicine_name'];  // correct column
    $checked = in_array($value, $selected_medicines) ? 'checked' : '';
    echo '<label>';
    echo '<input type="checkbox" name="medicine[]" value="' . htmlspecialchars($value) . '" ' . $checked . '> ';
    echo htmlspecialchars($value);
    echo '</label>';
}

                            ?>
                        </div>
                        <small style="color:#888;">You may select multiple medicines.</small>
                    </div>

                   <div class="form-item">
                        <label for="subcategory">Medicine Category:</label>
                        <div class="checkbox-list">
                            <?php
                            // Fetch subcategory for checkboxes
                            $subcategory_stmt = $pdo->prepare("SELECT DISTINCT sub_category FROM custom_options WHERE category = 'medicine' ORDER BY sub_category ASC");
                            $subcategory_stmt->execute();
                            // Support multiple selection from GET
                            $selected_subcategory = isset($_GET['subcategory']) ? (array)$_GET['subcategory'] : [];
                          while ($row = $subcategory_stmt->fetch(PDO::FETCH_ASSOC)) {
    $value = $row['sub_category'];  // correct column
    $checked = in_array($value, $selected_subcategory) ? 'checked' : '';
    echo '<label>';
    echo '<input type="checkbox" name="subcategory[]" value="' . htmlspecialchars($value) . '" ' . $checked . '> ';
    echo htmlspecialchars($value);
    echo '</label>';
}

                            ?>
                        </div>
                        <small style="color:#888;">You may select multiple types.</small>
                    </div>

                        </div>
                    </div>
                     <div class="modal-footer">
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
</div>

<div class="main-content">


<div class="print-area">
<!-- Two-logo letterhead -->
<div class="print-letterhead">
  <img src="../../img/daet_logo.png" alt="Left Logo" class="print-logo">
  <div class="print-heading">
    <div class="ph-line-1">Republic of the Philippines</div>
    <div class="ph-line-1">Department of Health</div>
    <div class="ph-line-1">Province of Camarines Norte</div>
    <div class="ph-line-2">Municipality of Daet</div>
    <div class="ph-line-3"><?php echo htmlspecialchars($rhu); ?></div>
   
  </div>
  <img src="../../img/mho_logo.png" alt="Right Logo" class="print-logo">
</div>
<hr class="print-rule">


<div class="report-content">

<div class="title">
     <h2>MEDICINE UTILIZATION REPORT</h2>
    <div class="print-sub">
      (<?php
        $filters = [];
                         if ($from_date || $to_date) {
    $readable_from = $from_date ? date("F j, Y", strtotime($from_date)) : '';
    $readable_to   = $to_date ? date("F j, Y", strtotime($to_date)) : '';

    // Combine them in a single display
    $filters[] = "<strong>" . trim($readable_from . ($readable_to ? " — " . $readable_to : '')) . "</strong>";
} 
        if (!empty($_GET['medicine'])) {
          $medicine_list = is_array($_GET['medicine']) ? $_GET['medicine'] : [$_GET['medicine']];
          $filters[] = "Medicine: <strong>" . implode(', ', array_map('htmlspecialchars', $medicine_list)) . "</strong>";
        }
        if (!empty($_GET['subcategory'])) {
          $subcategory_list = is_array($_GET['subcategory']) ? $_GET['subcategory'] : [$_GET['subcategory']];
          $filters[] = "Medicine Type: <strong>" . implode(', ', array_map('htmlspecialchars', $subcategory_list)) . "</strong>";
        }
        if ($sex) $filters[] = "Sex: <strong>" . htmlspecialchars($sex) . "</strong>";
        if ($age_group) {
          $age_labels = [
            'child' => 'Child (0–12)', 'teen' => 'Teen (13–19)',
            'adult' => 'Adult (20–59)', 'senior' => 'Senior (60+)'
          ];
          $filters[] = "Age Group: <strong>" . ($age_labels[$age_group] ?? htmlspecialchars($age_group)) . "</strong>";
        }
        if ($barangay) $filters[] = "Barangay: <strong>" . htmlspecialchars($barangay) . "</strong>";
        echo $filters ? implode("&nbsp; | &nbsp;", $filters) : "All Records";
      ?>)
    </div>
</div>


    <!-- Chart Visibility Controls -->
    <div class="chart-controls-panel medicine-chart-controls">
      <h3>Charts</h3>
      <div class="chart-toggle-group">
        <label><input type="checkbox" id="toggleSexChart"> Patients by Sex</label>
        <label><input type="checkbox" id="toggleAgeGroupChart"> Age Groups</label>
        <label><input type="checkbox" id="toggleBarangayChart"> Patients by Barangay</label>
        <label><input type="checkbox" id="toggleMedicineLineChart" checked> Dispensed Medicines Over Time</label>
      </div>
    </div>

    <div id="medicineChartsGrid" class="medicine-charts-grid single-chart">

    <!-- Pie Chart Section -->
    <div id="sexChart" class="chart chart-card medicine-chart-hidden">
      <h3 class="chart-title">Patients by Sex</h3>
        <canvas id="sexPieChart"></canvas>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Prepare data for the pie chart (Sex distribution)
        <?php
            $sex_counts = ['Male' => 0, 'Female' => 0];
            foreach ($rows as $row) {
                if (isset($sex_counts[$row['sex']])) {
                    $sex_counts[$row['sex']]++;
                }
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
                        backgroundColor: ['#4e79a7', '#f28e2b'],
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: 6 },
                    plugins: {
                        legend: { position: 'bottom' },
                        title: { display: false }
                    }
                }
            });
        }
    </script>


    <!-- Age Group Distribution Bar Chart -->
    <div id="ageGroupChart" class="chart chart-card medicine-chart-hidden">
        <h3 class="chart-title">Age Groups</h3>
        <canvas id="ageGroupBarChart"></canvas>
    </div>
    <script>
        <?php
        $age_group_counts = [
            '0–12' => 0,
            '13–19' => 0,
            '20–59' => 0,
            '60+' => 0
        ];
        foreach ($rows as $row) {
            $age = (int)$row['age'];
            if ($age >= 0 && $age <= 12) {
                $age_group_counts['0–12']++;
            } elseif ($age >= 13 && $age <= 19) {
                $age_group_counts['13–19']++;
            } elseif ($age >= 20 && $age <= 59) {
                $age_group_counts['20–59']++;
            } elseif ($age >= 60) {
                $age_group_counts['60+']++;
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
                        backgroundColor: ['#4e79a7', '#f28e2b', '#e15759', '#76b7b2'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: 6 },
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


    <!-- Address Distribution Bar Chart -->
    <div id="barangayChart" class="chart chart-card medicine-chart-hidden">
      <h3 class="chart-title">Patient Counts per Barangay</h3>
        <canvas id="barangayBarChart"></canvas>
    </div>
    <script>
        <?php
        // Prepare barangay counts based on filtered patients (unique by patient_id)
        $barangay_counts = [];
        $unique_patients_address = [];
        foreach ($rows as $row) {
            // Use patient_id if available, otherwise fallback to full_name+dob for uniqueness
            $unique_key = ($row['full_name'] ?? '') . ($row['date_of_birth'] ?? '');
            if (!isset($unique_patients_address[$unique_key])) {
                // Extract barangay from the address (same logic as patient_summary_report.php)
                $address_parts = explode(' - ', $row['address']);
                $barangay = isset($address_parts[1]) ? explode(' ', $address_parts[1])[1] : 'Unknown';
                $barangay_counts[$barangay] = ($barangay_counts[$barangay] ?? 0) + 1;
                $unique_patients_address[$unique_key] = true;
            }
        }
        ?>
        const barangayLabels = <?= json_encode(array_keys($barangay_counts)) ?>;
        const barangayData = <?= json_encode(array_values($barangay_counts)) ?>;

        if (barangayData.reduce((a, b) => a + b, 0) > 0) {
            const ctxBarangay = document.getElementById('barangayBarChart').getContext('2d');
            new Chart(ctxBarangay, {
                type: 'bar',
                data: {
                    labels: barangayLabels,
                    datasets: [{
                        label: 'Patient Count',
                        data: barangayData,
                        backgroundColor: '#76b7b2',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: 6 },
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
                            title: { display: true, text: 'Barangay' }
                        }
                    }
                }
            });
        }
    </script>
    <!-- Line Graph: Quantity of Dispensed Medicines Over Time -->
<div id="medicineLineChartWrap" class="chart chart-card medicine-chart-visible">
   <h3 class="chart-title">Quantity of Dispensed Medicines Over Time</h3>
    <canvas id="medicineLineChart"></canvas>
</div>
<script>
    // Prepare datasets for line graph
    const lineLabels = <?= json_encode($periods) ?>;
    const medicineLineDatasets = [
        <?php foreach ($medicine_series as $med => $series): ?>
        {
            label: <?= json_encode($med) ?>,
            data: <?= json_encode(array_values($series)) ?>,
            fill: false,
            borderColor: '#' + Math.floor(Math.random()*16777215).toString(16),
            tension: 0.1
        },
        <?php endforeach; ?>
    ];

    if (lineLabels.length > 0) {
        const ctxLine = document.getElementById('medicineLineChart').getContext('2d');
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: lineLabels,
                datasets: medicineLineDatasets
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Quantity Dispensed' }
                    },
                    x: {
                        title: { display: true, text: 'Period' }
                    }
                }
            }
        });
    }
</script>

</div><!-- /medicineChartsGrid -->


<script>
/* Balanced chart toggle behavior */
document.addEventListener('DOMContentLoaded', () => {
  const grid = document.getElementById('medicineChartsGrid');
  const chartMapping = {
    toggleSexChart: 'sexChart',
    toggleAgeGroupChart: 'ageGroupChart',
    toggleBarangayChart: 'barangayChart',
    toggleMedicineLineChart: 'medicineLineChartWrap'
  };

  function resizeChartsInside(element) {
    if (!element || typeof Chart === 'undefined') return;
    setTimeout(() => {
      element.querySelectorAll('canvas').forEach(canvas => {
        const chart = Chart.getChart ? Chart.getChart(canvas) : null;
        if (chart) {
          chart.resize();
          chart.update('none');
        }
      });
    }, 280);
  }

  function syncGridLayout() {
    if (!grid) return;
    const visibleCount = Object.values(chartMapping).reduce((count, chartId) => {
      const el = document.getElementById(chartId);
      return count + (el && !el.classList.contains('medicine-chart-hidden') ? 1 : 0);
    }, 0);
    grid.classList.toggle('single-chart', visibleCount <= 1);
  }

  Object.entries(chartMapping).forEach(([toggleId, chartId]) => {
    const checkbox = document.getElementById(toggleId);
    const chartElement = document.getElementById(chartId);
    if (!checkbox || !chartElement) return;

    function syncVisibility() {
      const show = checkbox.checked;
      chartElement.classList.toggle('medicine-chart-hidden', !show);
      chartElement.classList.toggle('medicine-chart-visible', show);
      if (show) resizeChartsInside(chartElement);
      syncGridLayout();
    }

    checkbox.addEventListener('change', syncVisibility);
    syncVisibility();
  });
});
</script>

<!-- Patient Table -->
<div class="report-table-container">
<div class="report-table-scroll">
<table id="reportTable">
  <thead>
    <tr>
      <th data-type="date">Date Given</th>
      <th data-type="string">Patient Name</th>
      <th data-type="string">Address</th>
      <th data-type="number">Age</th>
      <th data-type="date">Date of Birth</th>
      <th data-type="string">Gender</th>
      <th data-type="string">PhilHealth No.</th>
      <?php foreach ($medicine_list as $med): ?>
        <th data-type="number"><?= htmlspecialchars($med) ?></th>
      <?php endforeach; ?>
    </tr>
  </thead>


   <?php
// Sort visits (patient meds) from latest to oldest by nested date_dispensed
usort($patient_meds, function($a, $b) {
    return strtotime($b['row']['date_dispensed']) - strtotime($a['row']['date_dispensed']);
});
?>
<tbody>
    <?php foreach ($patient_meds as $pm): $row = $pm['row']; ?>
    <tr>
        <td><?= htmlspecialchars($row['date_dispensed']) ?></td>
        <td><?= htmlspecialchars($row['full_name']) ?></td>
        <td><?= htmlspecialchars($row['address']) ?></td>
        <td><?= htmlspecialchars($row['age']) ?></td>
        <td><?= htmlspecialchars($row['date_of_birth']) ?></td>
        <td><?= htmlspecialchars($row['sex']) ?></td>
        <td><?= htmlspecialchars(!empty($row['philhealth_member_no']) ? $row['philhealth_member_no'] : 'N/A') ?></td>
        <?php foreach ($medicine_list as $med): ?>
            <td><?= htmlspecialchars($pm['medicines'][$med] ?? '') ?></td>
        <?php endforeach; ?>
       
    </tr>
    <?php endforeach; ?>
</tbody>

</table>
</div>
</div> 

<div class="summary-container">
  <div class="summary">
    <h3 class="summary-title"><i class="bx bx-file"></i> Report Details</h3>

    <table class="summary-table">
      <colgroup>
        <col style="width:30%">
        <col style="width:70%">
      </colgroup>
      <tbody>
        <tr>
          <th>Report Generated On</th>
          <td><?= date('F j, Y g:i:s A') ?></td>
        </tr>
        <tr>
          <th>Total Patients in Report</th>
          <td><?= count($rows) ?></td>
        </tr>

      </tbody>
    </table>

   <table class="summary-table2">
  <thead>
    <tr>
      <th style="border:1px solid #d5d7db; padding:6px;">Sex</th>
      <th style="border:1px solid #d5d7db; padding:6px;">Count</th>
      <th style="border:1px solid #d5d7db; padding:6px;">Age Group</th>
      <th style="border:1px solid #d5d7db; padding:6px;">Count</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="border:1px solid #d5d7db; padding:6px;">Male</td>
      <td style="border:1px solid #d5d7db; padding:6px; text-align:center;">
        <?= $sex_counts['Male'] ?? 0 ?>
      </td>
      <td style="border:1px solid #d5d7db; padding:6px;">Children (0–12)</td>
      <td style="border:1px solid #d5d7db; padding:6px; text-align:center;">
        <?= $age_group_counts['0–12'] ?? 0 ?>
      </td>
    </tr>
    <tr>
      <td style="border:1px solid #d5d7db; padding:6px;">Female</td>
      <td style="border:1px solid #d5d7db; padding:6px; text-align:center;">
        <?= $sex_counts['Female'] ?? 0 ?>
      </td>
      <td style="border:1px solid #d5d7db; padding:6px;">Teens (13–19)</td>
      <td style="border:1px solid #d5d7db; padding:6px; text-align:center;">
        <?= $age_group_counts['13–19'] ?? 0 ?>
      </td>
    </tr>
    <tr>
      <td style="border:1px solid #d5d7db; padding:6px;"></td>
      <td style="border:1px solid #d5d7db; padding:6px;"></td>
      <td style="border:1px solid #d5d7db; padding:6px;">Adults (20–59)</td>
      <td style="border:1px solid #d5d7db; padding:6px; text-align:center;">
        <?= $age_group_counts['20–59'] ?? 0 ?>
      </td>
    </tr>
    <tr>
      <td style="border:1px solid #d5d7db; padding:6px;"></td>
      <td style="border:1px solid #d5d7db; padding:6px;"></td>
      <td style="border:1px solid #d5d7db; padding:6px;">Seniors (60+)</td>
      <td style="border:1px solid #d5d7db; padding:6px; text-align:center;">
        <?= $age_group_counts['60+'] ?? 0 ?>
      </td>
    </tr>
  </tbody>
</table>


    <!-- Keep your “Dispensed Medicines” block just below if you want -->
    <div class="dispensed-summary-block">
      <strong><i class="bx bx-capsule"></i> Dispensed Medicines:</strong>
      <?php if (!empty($medicine_list)): ?>
        <table class="summary-table" style="margin-top:8px;">
          <colgroup>
            <col style="width:70%">
            <col style="width:30%">
          </colgroup>
          <thead>
            <tr>
              <th>Medicine</th>
              <th>Total Dispensed</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($medicine_list as $medicine):
              $total_dispensed = 0;
              foreach ($patient_meds as $pm) {
                $total_dispensed += $pm['medicines'][$medicine] ?? 0;
              }
              if ($total_dispensed > 0): ?>
                <tr>
                  <td><?= htmlspecialchars($medicine) ?></td>
                  <td><?= $total_dispensed ?></td>
                </tr>
            <?php endif; endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        All Medicines
      <?php endif; ?>
    </div>
  </div>
  
<span id="generated_by"></span>
</div>




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

<!-- jsPDF and html2canvas libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<!-- SheetJS for proper Excel export -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
/**
 * Converts an HTML <table> element into a 2D array of cell values,
 * handling colspan/rowspan by filling spanned cells with the same value.
 */
function tableToAoA(table) {
    const rows = table.querySelectorAll('tr');
    const grid = [];
    rows.forEach((tr, ri) => {
        if (!grid[ri]) grid[ri] = [];
        let ci = 0;
        tr.querySelectorAll('th, td').forEach(cell => {
            // Skip cells already filled by a previous rowspan
            while (grid[ri][ci] !== undefined) ci++;
            const text = cell.innerText.trim();
            const colspan = parseInt(cell.getAttribute('colspan') || '1');
            const rowspan = parseInt(cell.getAttribute('rowspan') || '1');
            for (let r = 0; r < rowspan; r++) {
                for (let c = 0; c < colspan; c++) {
                    if (!grid[ri + r]) grid[ri + r] = [];
                    grid[ri + r][ci + c] = (r === 0 && c === 0) ? text : text;
                }
            }
            ci += colspan;
        });
    });
    return grid;
}

function exportTableToExcel(tableID, filename = 'Medicine Utilization Report') {
    try {
        const wb = XLSX.utils.book_new();

        // ── Sheet 1: Patient Dispensation Table ──────────────────────────────
        const mainTable = document.getElementById(tableID);
        if (!mainTable) { alert('Patient table not found!'); return; }
        const mainAoA = tableToAoA(mainTable);
        const ws1 = XLSX.utils.aoa_to_sheet(mainAoA);
        // Auto column widths based on max content length
        const colWidths1 = mainAoA[0] ? mainAoA[0].map((_, ci) => ({
            wch: Math.min(40, Math.max(10, ...mainAoA.map(r => String(r[ci] ?? '').length)))
        })) : [];
        ws1['!cols'] = colWidths1;
        XLSX.utils.book_append_sheet(wb, ws1, 'Patient Records');

        // ── Sheet 2: Summary (Report Details + Sex/Age table) ─────────────────
        const summaryData = [];

        // Report meta info
        summaryData.push(['MEDICINE UTILIZATION REPORT']);
        summaryData.push(['<?php echo addslashes(htmlspecialchars_decode($rhu)); ?>']);
        summaryData.push(['Report Generated On', '<?php echo date('F j, Y g:i:s A'); ?>']);
        summaryData.push(['Total Patients in Report', <?php echo count($rows); ?>]);
        summaryData.push([]);

        // Sex / Age group summary table
        const summaryTable = document.querySelector('.summary-table2');
        if (summaryTable) {
            summaryData.push(['Sex & Age Group Summary']);
            const summaryAoA = tableToAoA(summaryTable);
            summaryAoA.forEach(row => summaryData.push(row));
        }

        summaryData.push([]);

        // Dispensed Medicines totals table
        const medTables = document.querySelectorAll('.summary-table');
        medTables.forEach(mt => {
            const firstHeader = mt.querySelector('th');
            // The dispensed medicines table has "Medicine" as first header
            if (firstHeader && firstHeader.innerText.trim() === 'Medicine') {
                summaryData.push(['Dispensed Medicines Summary']);
                const medAoA = tableToAoA(mt);
                medAoA.forEach(row => summaryData.push(row));
            }
        });

        const ws2 = XLSX.utils.aoa_to_sheet(summaryData);
        const colWidths2 = [{ wch: 35 }, { wch: 25 }];
        ws2['!cols'] = colWidths2;
        XLSX.utils.book_append_sheet(wb, ws2, 'Summary');

        // ── Download ──────────────────────────────────────────────────────────
        const dateStr = new Date().toISOString().slice(0, 10);
        XLSX.writeFile(wb, filename + ' ' + dateStr + '.xlsx');

    } catch (error) {
        console.error('Excel export error:', error);
        alert('Error exporting to Excel: ' + error.message);
    }
}


(function() {
  const table = document.getElementById('reportTable');
  if (!table) return;

  const thead = table.tHead || table.querySelector('thead');
  const tbody = table.tBodies[0];

  // Add arrow placeholders
  [...thead.querySelectorAll('th')].forEach(th => {
    const ind = document.createElement('span');
    ind.className = 'sort-indicator';
    th.appendChild(ind);
  });

  function parseDate(v) {
    const t = (v || '').trim();
    // Accept "YYYY-MM-DD" and "YYYY-MM-DD HH:MM[:SS]"
    if (/^\d{4}-\d{2}-\d{2}(?:\s+\d{2}:\d{2}(?::\d{2})?)?$/.test(t)) {
      return new Date(t.replace(' ', 'T'));
    }
    // Also try generic Date parsing for e.g. "Nov 01, 2025"
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

  function getCellValue(tr, idx, type) {
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

  function sortBy(colIdx, dir) {
    const type = detectType(colIdx);
    const rows = [...tbody.rows];

    rows.sort((a, b) => {
      const va = getCellValue(a, colIdx, type);
      const vb = getCellValue(b, colIdx, type);
      if (va < vb) return dir === 'asc' ? -1 : 1;
      if (va > vb) return dir === 'asc' ?  1 : -1;
      return 0;
    });

    const frag = document.createDocumentFragment();
    rows.forEach(r => frag.appendChild(r));
    tbody.appendChild(frag);
  }

  // Click to toggle asc/desc
  [...thead.querySelectorAll('th')].forEach((th, idx) => {
    th.addEventListener('click', () => {
      const isAsc = th.classList.contains('is-sorted-asc');
      const nextDir = isAsc ? 'desc' : 'asc';
      clearHeaderStates(idx);
      th.classList.toggle('is-sorted-asc',  nextDir === 'asc');
      th.classList.toggle('is-sorted-desc', nextDir === 'desc');
      sortBy(idx, nextDir);
    });
  });

  // Default: sort by "Date Given" (col 0) DESC on load
  const defaultCol = 0, defaultDir = 'desc';
  const defaultTh = thead.querySelectorAll('th')[defaultCol];
  if (defaultTh) {
    defaultTh.classList.add(defaultDir === 'asc' ? 'is-sorted-asc' : 'is-sorted-desc');
    sortBy(defaultCol, defaultDir);
  }
})();

//PRINT
function printDiv() {
  const headerEl = document.querySelector('.print-letterhead');
  const printHeader = headerEl ? headerEl.outerHTML : '';
  const originalArea = document.querySelector('.print-area');
  if (!originalArea) return;

  const clone = originalArea.cloneNode(true);

  const headerInClone = clone.querySelector('.print-letterhead');
  if (headerInClone) headerInClone.remove();
  const ruleInClone = clone.querySelector('.print-rule');
  if (ruleInClone) ruleInClone.remove();

  clone.querySelectorAll('.chart-controls-panel, .medicine-chart-controls, .chart-toggle-group').forEach(el => el.remove());

  // Remove charts that were not selected/viewed before printing.
  // Without this, hidden chart cards can appear in the print window as titles with broken/blank images.
  clone.querySelectorAll('.chart-card').forEach(cardClone => {
    const liveCard = cardClone.id ? document.getElementById(cardClone.id) : null;
    const liveCardHidden = liveCard && (
      liveCard.classList.contains('medicine-chart-hidden') ||
      window.getComputedStyle(liveCard).display === 'none' ||
      window.getComputedStyle(liveCard).visibility === 'hidden'
    );
    const cloneMarkedHidden = cardClone.classList.contains('medicine-chart-hidden');

    if (liveCardHidden || cloneMarkedHidden) {
      cardClone.remove();
    }
  });

  ['sexPieChart', 'ageGroupBarChart', 'barangayBarChart', 'medicineLineChart'].forEach(id => {
    const live = document.getElementById(id);
    const inClone = clone.querySelector('#' + id);
    const liveCard = live ? live.closest('.chart-card') : null;
    const liveCardHidden = liveCard && (
      liveCard.classList.contains('medicine-chart-hidden') ||
      window.getComputedStyle(liveCard).display === 'none' ||
      window.getComputedStyle(liveCard).visibility === 'hidden'
    );

    if (live && inClone && !liveCardHidden && typeof live.toDataURL === 'function') {
      const img = document.createElement('img');
      const dataUrl = live.toDataURL('image/png');
      if (dataUrl && dataUrl !== 'data:,') {
        img.src = dataUrl;
        img.style.cssText = 'max-width:100%;height:auto;';
        inClone.parentNode.replaceChild(img, inClone);
      } else {
        const card = inClone.closest('.chart-card');
        if (card) card.remove();
      }
    }
  });
  clone.querySelectorAll('canvas').forEach(c => {
    const card = c.closest('.chart-card');
    if (card) card.remove();
    else c.remove();
  });

  const w = window.open('', '', 'height=900,width=1100');
  if (!w) { alert('Please allow pop-ups to print this report.'); return; }
  w.document.write(`
    <html>
      <head>
        <title>Print Report</title>
        <meta charset="utf-8" />
        <style>
          body{font-family:'Plus Jakarta Sans',Arial,sans-serif;font-size:12px;color:#000;}
          table{width:100%;border-collapse:collapse;}
          th,td{border:1px solid #000;padding:4px 6px;text-align:left;}
          thead{background:#d8e4f0;print-color-adjust:exact;}
          img{display:block;margin:0 auto;max-width:100%;height:auto;}
          h3{margin:10px 0 6px;color:#000;}
          .print-letterhead{display:grid;grid-template-columns:64px auto 64px;align-items:center;justify-content:center;column-gap:60px;margin:0 auto 10px;text-align:center;width:fit-content;}
          .print-logo{width:64px;height:64px;object-fit:contain;}
          .print-heading{line-height:1.1;color:#000;}
          .print-heading .ph-line-1{font-size:12pt;font-weight:500;}
          .print-heading .ph-line-2{font-size:14pt;font-weight:800;}
          .print-heading .ph-line-3{font-size:12pt;font-weight:600;}
          .print-sub{font-size:11pt;margin-top:4px;}
          .print-rule{height:1px;border:0;background:#cfd8e3;margin:8px 0 12px;}
          .form-submit,.selected-filters,.chart-controls-panel,.medicine-chart-controls,.chart-toggle-group{display:none!important;}
          .medicine-chart-hidden{display:none!important;}
          .report-table-scroll{overflow:visible!important;max-height:none!important;}
          .summary-table,.summary-table2{box-shadow:none!important;border-radius:0!important;}
        </style>
      </head>
      <body>
        ${printHeader}
        <hr class="print-rule">
        ${clone.innerHTML}
      </body>
    </html>
  `);
  w.document.close();
  w.focus();
  setTimeout(() => { w.print(); w.close(); }, 500);
}



document.addEventListener('DOMContentLoaded', () => {
  fetch('../php/getUserName.php')
    .then(r => r.json())
    .then(data => {
      const fullName = (data && data.full_name) ? data.full_name : '';

      // Greeting (keep current behavior)
      const greetingEl = document.getElementById('userGreeting');
      if (greetingEl) {
        greetingEl.textContent = fullName ? `Hello, ${fullName}!` : 'Hello, User!';
      }

      // Build the signature block
      const gb = document.getElementById('generated_by');
      gb.innerHTML = `
        <div class="sig-label">Report Generated by:</div>
        <hr class="sig-line">
        <div class="sig-name"></div>
        <div class="sig-title">Nursing Attendant</div>
      `;
      gb.querySelector('.sig-name').textContent = fullName || '________________';
    })
    .catch(() => {
      const greetingEl = document.getElementById('userGreeting');
      if (greetingEl) {
        greetingEl.textContent = 'Hello, User!';
      }
      const gb = document.getElementById('generated_by');
      gb.innerHTML = `
        <div class="sig-label">Report Generated by:</div>
        <hr class="sig-line">
        <div class="sig-name">________________</div>
        <div class="sig-title">Nursing Attendant</div>
      `;
    });
});
// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('logoutModal');
    if (event.target == modal) {
        closeModal();
    }
}

    function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'block';
    return false; // Prevent the default link behavior
}

function closeModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

function proceedLogout() {
   window.location.href='../../ADMIN/php/logout'; 
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
            window.location.href='../auth/role';
        }
    })
    .catch(error => {
        console.error('Error checking session:', error);
        window.location.href='../auth/role';
    });

</script>





<script>
document.addEventListener("DOMContentLoaded", () => {
  const updateReferrals = document.getElementById("updateReferrals");
  const searchInput = document.getElementById("patientSearch");
  const searchButton = document.getElementById("searchButton");

  if (updateReferrals) {
    updateReferrals.addEventListener("click", function (event) {
      event.preventDefault();

      fetch("../php/update_referrals.php")
        .then(response => response.json())
        .then(data => {
          console.log(data.message);
          window.location.href = "../pending";
        })
        .catch(error => {
          console.error("Error updating referrals:", error);
          window.location.href = "../pending";
        });
    });
  }

  if (searchInput && searchButton) {
    searchInput.addEventListener("keypress", function (event) {
      if (event.key === "Enter") {
        event.preventDefault();
        searchButton.click();
      }
    });

    searchButton.addEventListener("click", function () {
      const searchQuery = searchInput.value.trim();
      if (searchQuery) {
        sessionStorage.setItem("searchQuery", searchQuery);
        window.location.href = "../searchPatient";
      } else {
        alert("Please enter a patient name to search.");
      }
    });
  }

  fetch('../php/getUserName.php')
    .then(response => response.json())
    .then(data => {
      const sidebarName = document.getElementById('sidebarUserName');
      if (sidebarName) {
        sidebarName.textContent = data.full_name || 'Nurse';
      }
    })
    .catch(error => {
      console.error('Error fetching sidebar user name:', error);
      const sidebarName = document.getElementById('sidebarUserName');
      if (sidebarName) sidebarName.textContent = 'Nurse';
    });
});
</script>
</body>
</html>