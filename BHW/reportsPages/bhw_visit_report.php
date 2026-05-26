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

$from_date  = $_GET['from_date']  ?? '';
$to_date    = $_GET['to_date']    ?? '';
$sex        = $_GET['sex']        ?? '';
$age_group  = $_GET['age_group']  ?? '';
$purok      = $_GET['purok']      ?? '';
$bmi        = $_GET['bmi']        ?? '';
$treatment  = $_GET['treatment']  ?? '';

$sql = "SELECT v.*, p.first_name, p.last_name, p.age, p.sex, p.address FROM patient_assessment v 
        JOIN patients p ON v.patient_id = p.patient_id 
        WHERE p.address LIKE :barangay";

$params = [];
$params['barangay'] = '%' . $barangayName . '%';

if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND DATE(v.visit_date) BETWEEN :from_date AND :to_date";
    $params['from_date'] = $from_date;
    $params['to_date']   = $to_date;
}

if (!empty($sex)) {
    $sql .= " AND p.sex = :sex";
    $params['sex'] = $sex;
}

if (!empty($age_group)) {
    switch ($age_group) {
        case 'child':  $sql .= " AND p.age < 13"; break;
        case 'teen':   $sql .= " AND p.age BETWEEN 13 AND 19"; break;
        case 'adult':  $sql .= " AND p.age BETWEEN 20 AND 59"; break;
        case 'senior': $sql .= " AND p.age >= 60"; break;
    }
}

if (!empty($purok)) {
    $sql .= " AND p.address LIKE :purok";
    $params['purok'] = '%' . $purok . '%';
}

if (!empty($bmi)) {
    switch ($bmi) {
        case 'underweight': $sql .= " AND v.bmi < 18.5"; break;
        case 'normal':      $sql .= " AND v.bmi >= 18.5 AND v.bmi <= 24.9"; break;
        case 'overweight':  $sql .= " AND v.bmi >= 25 AND v.bmi <= 29.9"; break;
        case 'class1':      $sql .= " AND v.bmi >= 30 AND v.bmi <= 34.9"; break;
        case 'class2':      $sql .= " AND v.bmi >= 35 AND v.bmi <= 39.9"; break;
        case 'class3':      $sql .= " AND v.bmi >= 40"; break;
    }
}

if (!empty($treatment)) {
    switch ($treatment) {
        case 'weighing':     $sql .= " AND v.treatment LIKE '%weighing%'"; break;
        case 'immunization': $sql .= " AND v.treatment LIKE '%immunization%'"; break;
        case 'bp':           $sql .= " AND v.treatment LIKE '%bp%'"; break;
        case 'prenatal':     $sql .= " AND v.treatment LIKE '%prenatal%'"; break;
        case 'referred':     $sql .= " AND v.treatment LIKE '%referred%'"; break;
    }
}

$sql .= " ORDER BY v.visit_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$visits = $stmt->fetchAll();

/* ---------- Summary computations ---------- */
$total_patients = count(array_unique(array_column($visits, 'patient_id')));
$total_medicines_dispensed = 0;
$medicine_counts = [];

foreach ($visits as $visit) {
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

arsort($medicine_counts);
$most_dispensed_medicine = key($medicine_counts);
$most_dispensed_quantity  = current($medicine_counts);

/* ---------- Chart and summary datasets ---------- */
$sex_counts = ['Male' => 0, 'Female' => 0];
$unique_patients = [];
foreach ($visits as $visit) {
    $pid = $visit['patient_id'];
    if (!isset($unique_patients[$pid])) {
        $unique_patients[$pid] = $visit['sex'];
    }
}
foreach ($unique_patients as $s) {
    if (isset($sex_counts[$s])) $sex_counts[$s]++;
}

$age_group_counts = ['0–5' => 0, '6–17' => 0, '18–59' => 0, '60+' => 0];
$unique_patients_age = [];
foreach ($visits as $visit) {
    $pid = $visit['patient_id'];
    if (!isset($unique_patients_age[$pid])) {
        $age = (int)$visit['age'];
        if ($age <= 5) $age_group_counts['0–5']++;
        elseif ($age <= 17) $age_group_counts['6–17']++;
        elseif ($age <= 59) $age_group_counts['18–59']++;
        else $age_group_counts['60+']++;
        $unique_patients_age[$pid] = true;
    }
}

$bmi_categories = ['Underweight'=>0,'Normal'=>0,'Overweight'=>0,'Class 1'=>0,'Class 2'=>0,'Class 3'=>0];
$latest_bmi_per_patient = [];
foreach ($visits as $visit) {
    $pid = $visit['patient_id'];
    if (!isset($latest_bmi_per_patient[$pid]) || strtotime($visit['visit_date']) > strtotime($latest_bmi_per_patient[$pid]['visit_date'])) {
        $latest_bmi_per_patient[$pid] = $visit;
    }
}
foreach ($latest_bmi_per_patient as $visit) {
    if (!isset($visit['bmi']) || $visit['bmi'] === '' || !is_numeric($visit['bmi'])) continue;
    $b = floatval($visit['bmi']);
    if ($b < 18.5) $bmi_categories['Underweight']++;
    elseif ($b <= 24.9) $bmi_categories['Normal']++;
    elseif ($b <= 29.9) $bmi_categories['Overweight']++;
    elseif ($b <= 34.9) $bmi_categories['Class 1']++;
    elseif ($b <= 39.9) $bmi_categories['Class 2']++;
    else $bmi_categories['Class 3']++;
}

$treatment_types = ['Weighing'=>0,'Immunization'=>0,'Blood Pressure Reading'=>0,'Prenatal Check-up'=>0,'Referred'=>0];
foreach ($visits as $visit) {
    $t = strtolower($visit['treatment'] ?? '');
    if (strpos($t,'weighing')     !== false) $treatment_types['Weighing']++;
    if (strpos($t,'immunization') !== false) $treatment_types['Immunization']++;
    if (strpos($t,'bp')           !== false || strpos($t,'blood pressure') !== false) $treatment_types['Blood Pressure Reading']++;
    if (strpos($t,'prenatal')     !== false) $treatment_types['Prenatal Check-up']++;
    if (strpos($t,'referred')     !== false) $treatment_types['Referred']++;
}

$max_treatment = '';
$max_treatment_count = 0;
foreach ($treatment_types as $treat => $count) {
    if ($count > $max_treatment_count) {
        $max_treatment = $treat;
        $max_treatment_count = $count;
    }
}

$unique_patient_addresses = [];
foreach ($visits as $visit) {
    $unique_patient_addresses[$visit['patient_id']] = $visit['address'] ?? 'Unknown';
}

$barangay_patient_counts = [];
foreach ($unique_patient_addresses as $address) {
    $parts = explode('-', $address, 2);
    $purok_key = trim($parts[0]);
    $barangay_patient_counts[$purok_key] = ($barangay_patient_counts[$purok_key] ?? 0) + 1;
}
uksort($barangay_patient_counts, function($a, $b) {
    preg_match('/\d+/', $a, $ma);
    preg_match('/\d+/', $b, $mb);
    return (int)($ma[0] ?? 0) <=> (int)($mb[0] ?? 0);
});

/* ---------- Audit log ---------- */
$stmt_log = $pdo->prepare("INSERT INTO logs (user_id, action, performed_by) VALUES (:user_id, :action, :performed_by)");
$stmt_log->execute([
    ':user_id'      => $_SESSION['user_id'],
    ':action'       => "Generated BHS Patient Visit Summary Report",
    ':performed_by' => $_SESSION['user_id'],
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
    <title>Patient Visits Summary Report</title>


    <style>
    /* Page-level layout polish for BHW Patient Visits Summary report */
    #content main {
        width: 100%;
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

    .history-container,
    .main-content { width: 100%; }

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
        width: 4px;
        height: 18px;
        background: var(--blue, #1c6fba);
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

    .selected-filters { margin-top: 20px !important; }
    .selected-filters h3 {
        font-size: 13px;
        font-weight: 600;
        color: var(--grey-700, #4a5568);
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
        text-decoration: none !important;
    }
    .filter-tag a:hover { color: var(--red, #e53e3e) !important; }

    .modal-content { max-width: 620px !important; }
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px 20px;
    }
    .form-item label {
        font-size: 12.5px;
        font-weight: 600;
        color: var(--grey-700, #4a5568);
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .print-area {
        background: var(--white, #fff);
        border: 1px solid var(--border, #dde4ef);
        border-radius: var(--r-lg, 16px);
        padding: 28px 32px;
        box-shadow: var(--shadow-sm, 0 2px 8px rgba(13,45,82,.09));
    }

    .print-letterhead,
    .print-rule,
    .title { display: none; }
    .title { text-align: center; }

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
        user-select: none;
        transition: border-color .18s, background .18s, color .18s;
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

    .patient-chart-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(280px, 1fr));
        gap: 24px;
        align-items: stretch;
        margin: 24px 0 28px;
        transition: grid-template-columns .35s ease, gap .35s ease;
    }
    .patient-chart-grid.single-chart {
        grid-template-columns: minmax(0, 1fr);
        justify-content: stretch;
    }
    .patient-chart-grid .chart-card {
        width: 100%;
        margin: 0 !important;
        padding: 20px;
        min-height: 340px;
        background: var(--white, #fff);
        border: 1px solid var(--border-soft, #edf0f7);
        border-radius: var(--r-lg, 16px);
        box-shadow: var(--shadow-xs, 0 1px 3px rgba(13,45,82,.07));
        text-align: center;
        transform-origin: center top;
        will-change: opacity, transform;
    }
    .patient-chart-grid .chart-card canvas {
        width: 100% !important;
        height: 280px !important;
        max-height: 280px;
    }

    /* Keep the Address/Purok chart at its original full-width size. */
    #addressChart {
        grid-column: 1 / -1;
        width: 100%;
        max-width: 100% !important;
        margin: 0 !important;
    }

    #addressChart canvas {
        height: 430px !important;
        max-height: 430px;
        min-height: 360px;
    }

    /* Keep Chart.js labels readable inside all report cards. */
    .patient-chart-grid canvas {
        display: block;
    }
    .patient-chart-grid .chart-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--navy, #0d2d52);
        margin-bottom: 12px;
    }
    .patient-chart-grid .chart-entered {
        animation: chartFadeUp .46s cubic-bezier(.22, 1, .36, 1) both;
    }
    .chart-animated {
        display: block;
        overflow: hidden;
        opacity: 1;
        transform: translateY(0) scale(1);
        max-height: 390px;
        transition:
            opacity .34s ease,
            transform .34s cubic-bezier(.22, 1, .36, 1),
            max-height .38s ease,
            padding .28s ease;
    }
    .chart-animated.is-hidden {
        opacity: 0;
        transform: translateY(14px) scale(.985);
        max-height: 0;
        padding-top: 0;
        padding-bottom: 0;
        pointer-events: none;
    }
    @keyframes chartFadeUp {
        from { opacity: 0; transform: translateY(16px) scale(.985); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .report-table-container {
        width: 100%;
        border-radius: var(--r-lg, 16px);
        border: 1px solid var(--border, #dde4ef);
        overflow: hidden;
        box-shadow: var(--shadow-md, 0 4px 20px rgba(13,45,82,.10));
        background: var(--white, #fff);
        margin-top: 24px;
    }
    .report-table-scroll {
        width: 100%;
        overflow-x: auto;
        max-height: 620px;
        overflow-y: auto;
    }
    #reportTable {
        width: 100%;
        min-width: 1500px;
        border-collapse: collapse;
        font-size: 13.5px;
    }
    #reportTable thead {
        position: sticky;
        top: 0;
        z-index: 10;
    }
    #reportTable thead tr {
        background: linear-gradient(135deg, var(--navy, #0d2d52) 0%, var(--navy-mid, #1a4477) 100%);
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
        cursor: pointer;
        user-select: none;
    }
    #reportTable th .sort-indicator { margin-left: 5px; font-size: 10px; opacity: .65; }
    #reportTable th.is-sorted-asc .sort-indicator::after { content: "▲"; }
    #reportTable th.is-sorted-desc .sort-indicator::after { content: "▼"; }
    #reportTable td {
        padding: 11px 14px;
        color: var(--grey-700, #4a5568);
        font-size: 13.5px;
        vertical-align: middle;
        border-right: 1px solid var(--border-soft, #edf0f7);
        border-bottom: 1px solid var(--border-soft, #edf0f7);
        white-space: nowrap;
    }
    #reportTable tbody tr:nth-child(odd) { background: var(--white, #fff); }
    #reportTable tbody tr:nth-child(even) { background: var(--grey-100, #f8f9fc); }
    #reportTable tbody tr:hover { background: var(--blue-pale, #f0f6ff); }
    #reportTable td:nth-child(2),
    #reportTable td:nth-child(12),
    #reportTable td:nth-child(13),
    #reportTable td:nth-child(14) {
        white-space: normal;
        word-break: break-word;
        min-width: 160px;
    }

    .summary-container { margin-top: 32px; }
    .summary-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--navy, #0d2d52);
        letter-spacing: -.1px;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 16px;
    }
    .summary-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid var(--border, #dde4ef);
        border-radius: var(--r-lg, 16px);
        overflow: hidden;
        box-shadow: var(--shadow-sm, 0 2px 8px rgba(13,45,82,.09));
        font-size: 14px;
        background: var(--white, #fff);
    }
    .summary-table th {
        width: 260px;
        background: var(--grey-100, #f8f9fc);
        color: var(--navy, #0d2d52);
        font-weight: 700;
        padding: 14px 18px;
        text-align: left;
        border-bottom: 1px solid var(--border-soft, #edf0f7);
        border-right: 1px solid var(--border-soft, #edf0f7);
        vertical-align: top;
    }
    .summary-table td {
        color: var(--grey-700, #4a5568);
        padding: 14px 18px;
        border-bottom: 1px solid var(--border-soft, #edf0f7);
        vertical-align: top;
    }
    .summary-table tr:last-child th,
    .summary-table tr:last-child td { border-bottom: none; }
    .summary-sublist {
        margin: 0;
        padding-left: 18px;
        columns: 2;
        column-gap: 28px;
    }

    #generated_by {
        display: block;
        margin: 48px 0 0 4px !important;
        color: var(--dark, #0f1d31) !important;
    }
    #generated_by .sig-label {
        font-size: 12.5px;
        font-weight: 600;
        color: var(--grey-500, #8c96aa);
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 60px;
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
        font-size: 15px;
        color: var(--navy, #0d2d52);
        white-space: nowrap;
    }
    #generated_by .sig-title {
        font-size: 12px;
        color: var(--grey-500, #8c96aa);
    }

    @media (max-width: 980px) {
        .patient-chart-grid,
        .patient-chart-grid.single-chart { grid-template-columns: 1fr; }
        .patient-chart-grid .chart-card { max-width: 760px; margin: 0 auto !important; }
    }

    @media (max-width: 768px) {
        #content main { padding: 20px 14px; }
        .filter-form,
        .print-area { padding: 20px 18px; }
        .form-row { grid-template-columns: 1fr; }
        .summary-table th { width: 140px; }
        .summary-sublist { columns: 1; }
    }

    @media (max-width: 520px) {
        .patient-chart-grid .chart-card { min-height: 300px; padding: 16px 12px; }
        .patient-chart-grid .chart-card canvas { height: 240px !important; max-height: 240px; }
        #addressChart canvas { height: 360px !important; max-height: 360px; min-height: 320px; }
    }

@media print {

  .title,
  .print-letterhead {
    display: block;
  }

  .title {
    text-align: center;
  }

  .ph-line-4,
  .print-sub {
    text-align: center;
  }

  /* Hide all charts and graph elements */
  .chart,
  .patient-chart-grid,
  #patientChartGrid,
  #addressChart,
  .chart-card,
  canvas,
  .chart-controls-panel,
  .form-submit,
  .summary-container .summary h3 {
    display: none !important;
  }

  /* Remove color from table headers */
  #reportTable thead tr {
    background: #fff !important;
  }
  #reportTable th {
    background: #fff !important;
    color: #000 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  /* Consistent font throughout (except letterhead) */
  body, table, th, td,
  .summary-table, .summary-table th, .summary-table td,
  #generated_by, .sig-label, .sig-name, .sig-title,
  .print-sub, .ph-line-4 {
    font-family: Arial, sans-serif !important;
  }

  .summary-list,
  #generated_by {
    font-size: 13px;
  }

  .summary-container {
    margin-top: 64px;
  }

  .summary-container .kv-table {
    margin-top: 0 !important;
  }

  .report-table-container {
    margin-top: 20px !important;
    margin-bottom: 40px !important;
  }

  /* Letterhead */

  .print-letterhead {
    display: grid;
    grid-template-columns: 72px auto 72px;
    align-items: center;
    justify-content: center;
    column-gap: 60px;
    margin: 0 auto 18px;
    text-align: center;
    width: fit-content;
  }

  .print-logo {
    width: 64px;
    height: 64px;
    object-fit: contain;
  }

  .print-heading {
    line-height: 1.1;
    color: #000;
  }

  .print-heading .ph-line-1 {
    font-size: 12pt;
    font-weight: 500;
  }

  .print-heading .ph-line-2 {
    font-size: 14pt;
    font-weight: 500;
  }

  .print-heading .ph-line-3 {
    font-size: 12pt;
    font-weight: 500;
  }

  .print-heading .ph-line-4 {
    font-size: 12pt;
    font-weight: 600;
    margin-top: 15px;
    letter-spacing: .3px;
  }

  .print-sub {
    font-size: 12pt;
    margin-top: 4px;
  }

  .print-rule {
    height: 1px;
    border: 0;
    background: #cfd8e3;
    margin: 8px 0 12px;
  }

    @media (prefers-reduced-motion: reduce) {
        .patient-chart-grid,
        .patient-chart-grid .chart-card,
        .chart-animated { animation: none !important; transition: none !important; }
    }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ═══ SIDEBAR ═══ -->
<section id="sidebar">
    <a href="#" class="sidebar-brand">
        <img src="../../img/logo.png" alt="RHU Logo" class="brand-logo">
        <div class="brand-text">
            <span class="brand-name">IHRRS</span>
        </div>
    </a>

    <div class="sidebar-scroll">
        <p class="sidebar-section-label">Main Menu</p>
        <ul class="side-menu top">
            <li><a href="../dashboard" data-tooltip="Dashboard"><i class="bx bxs-dashboard nav-icon"></i><span class="nav-label">Dashboard</span></a></li>
            <li><a href="../ITR" data-tooltip="Add New ITR"><i class="bx bxs-notepad nav-icon"></i><span class="nav-label">Add New ITR</span></a></li>
            <li><a href="../searchPatient" data-tooltip="Patient Records"><i class="bx bxs-search nav-icon"></i><span class="nav-label">Patient Records</span></a></li>
            <li><a href="../History" data-tooltip="Referral History"><i class="bx bx-history nav-icon"></i><span class="nav-label">Referral History</span></a></li>
            <li class="active"><a href="../reports" data-tooltip="Reports"><i class="bx bx-notepad nav-icon"></i><span class="nav-label">Reports</span></a></li>
        </ul>

        <div class="sidebar-divider"></div>

        <ul class="side-menu">
            <li><a href="#" class="logout" data-tooltip="Logout" onclick="return confirmLogout()"><i class="bx bxs-log-out-circle nav-icon"></i><span class="nav-label">Logout</span></a></li>
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

<!-- ═══ MAIN CONTENT ═══ -->
<section id="content">
    <nav>
        <button class="nav-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="bx bx-menu"></i>
        </button>
    </nav>

    <main>

        <div class="history-container">

            <!-- ─── Filter Form Card ─── -->
            <div class="filter-form">

                <h2>Patient Summary Report — BHS <?php echo htmlspecialchars($barangayName); ?></h2>

                <!-- Action Buttons -->
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

                <!-- Active Filter Tags -->
                <div class="selected-filters" style="margin-top:20px;">
                    <h3><i class="bx bx-filter-alt"></i> Active Filters</h3>
                    <div id="filterTags" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;">
                        <?php
                        function renderTag($label, $param, $value) {
                            $display = htmlspecialchars($label . ': ' . $value);
                            $url = $_GET;
                            unset($url[$param]);
                            $query = http_build_query($url);
                            echo '<span class="filter-tag">';
                            echo $display;
                            echo ' <a href="?' . $query . '" title="Remove filter">&times;</a>';
                            echo '</span>';
                        }

                        if ($from_date) renderTag('From', 'from_date', $from_date);
                        if ($to_date)   renderTag('To',   'to_date',   $to_date);
                        if ($sex)        renderTag('Sex',  'sex',       $sex);
                        if ($age_group) {
                            $age_labels = ['child'=>'Child (0–12)','teen'=>'Teen (13–19)','adult'=>'Adult (20–59)','senior'=>'Senior (60+)'];
                            renderTag('Age Group', 'age_group', $age_labels[$age_group] ?? ucfirst($age_group));
                        }
                        if ($purok) renderTag('Address', 'purok', $purok);
                        if ($bmi) {
                            $bmi_labels = ['underweight'=>'Underweight','normal'=>'Normal','overweight'=>'Overweight','class1'=>'Class 1','class2'=>'Class 2','class3'=>'Class 3'];
                            renderTag('BMI', 'bmi', $bmi_labels[$bmi] ?? $bmi);
                        }
                        if ($treatment) {
                            $treat_labels = ['weighing'=>'Weighing','immunization'=>'Immunization','bp'=>'Blood Pressure','prenatal'=>'Prenatal','referred'=>'Referred'];
                            renderTag('Treatment', 'treatment', $treat_labels[$treatment] ?? $treatment);
                        }

                        if (!$from_date && !$to_date && !$sex && !$age_group && !$purok && !$bmi && !$treatment) {
                            echo '<span style="color:var(--grey-500);font-size:13px;">All records — no filters applied</span>';
                        }
                        ?>
                    </div>
                </div>

                <!-- ─── Filter Modal ─── -->
                <div id="filterModal" class="modal" style="display:none;">
                    <div class="modal-content" style="max-width:560px;">
                        <div class="modal-header">
                            <h3><i class="bx bx-filter-alt" style="margin-right:8px;color:var(--blue);"></i>Apply Filters</h3>
                        </div>
                        <form method="GET" id="filterForm">
                            <div class="modal-body">
                                <div class="form-row">
                                    <div class="form-item">
                                        <label for="from_date">From Date</label>
                                        <input type="date" name="from_date" id="from_date" class="form-control"
                                               value="<?= $from_date ? htmlspecialchars($from_date) : '' ?>">
                                    </div>
                                    <div class="form-item">
                                        <label for="to_date">To Date</label>
                                        <input type="date" name="to_date" id="to_date" class="form-control"
                                               value="<?= $to_date ? htmlspecialchars($to_date) : '' ?>">
                                    </div>
                                    <div class="form-item">
                                        <label for="sex">Sex</label>
                                        <select name="sex" id="sex" class="form-control">
                                            <option value="" <?= $sex=='' ? 'selected':'' ?>>All</option>
                                            <option value="Male"   <?= $sex=='Male'   ? 'selected':'' ?>>Male</option>
                                            <option value="Female" <?= $sex=='Female' ? 'selected':'' ?>>Female</option>
                                        </select>
                                    </div>
                                    <div class="form-item">
                                        <label for="age_group">Age Group</label>
                                        <select name="age_group" id="age_group" class="form-control">
                                            <option value="">All</option>
                                            <option value="child"  <?= $age_group=='child'  ? 'selected':'' ?>>Child (0–12)</option>
                                            <option value="teen"   <?= $age_group=='teen'   ? 'selected':'' ?>>Teen (13–19)</option>
                                            <option value="adult"  <?= $age_group=='adult'  ? 'selected':'' ?>>Adult (20–59)</option>
                                            <option value="senior" <?= $age_group=='senior' ? 'selected':'' ?>>Senior (60+)</option>
                                        </select>
                                    </div>
                                    <div class="form-item">
                                        <label for="purok">Address</label>
                                        <select name="purok" id="purok" class="form-control">
                                            <option value="">All</option>
                                            <?php
                                            $barangay_stmt = $pdo->prepare("SELECT DISTINCT address FROM patients WHERE address LIKE :barangayName ORDER BY address");
                                            $barangay_stmt->execute([':barangayName' => "%$barangayName%"]);
                                            $selected_purok = $_GET['purok'] ?? '';
                                            while ($row = $barangay_stmt->fetch(PDO::FETCH_ASSOC)) {
                                                $value    = $row['address'];
                                                $selected = ($selected_purok === $value) ? 'selected' : '';
                                                echo "<option value=\"" . htmlspecialchars($value) . "\" $selected>" . htmlspecialchars($value) . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-item">
                                        <label for="treatment">Treatment</label>
                                        <select name="treatment" id="treatment" class="form-control">
                                            <option value="">All</option>
                                            <option value="weighing"     <?= $treatment=='weighing'     ? 'selected':'' ?>>Weighing</option>
                                            <option value="immunization" <?= $treatment=='immunization' ? 'selected':'' ?>>Immunization</option>
                                            <option value="bp"           <?= $treatment=='bp'           ? 'selected':'' ?>>Blood Pressure Reading</option>
                                            <option value="prenatal"     <?= $treatment=='prenatal'     ? 'selected':'' ?>>Prenatal Check-up</option>
                                            <option value="referred"     <?= $treatment=='referred'     ? 'selected':'' ?>>Referred</option>
                                        </select>
                                    </div>
                                    <div class="form-item" style="grid-column:1/-1;">
                                        <label for="bmi">BMI Category</label>
                                        <select name="bmi" id="bmi" class="form-control">
                                            <option value="">All</option>
                                            <option value="underweight" <?= $bmi=='underweight' ? 'selected':'' ?>>Underweight (&lt; 18.5)</option>
                                            <option value="normal"      <?= $bmi=='normal'      ? 'selected':'' ?>>Normal (18.5 – 24.9)</option>
                                            <option value="overweight"  <?= $bmi=='overweight'  ? 'selected':'' ?>>Overweight (25 – 29.9)</option>
                                            <option value="class1"      <?= $bmi=='class1'      ? 'selected':'' ?>>Class 1 — Moderate Obesity (30 – 34.9)</option>
                                            <option value="class2"      <?= $bmi=='class2'      ? 'selected':'' ?>>Class 2 — Severe Obesity (35 – 39.9)</option>
                                            <option value="class3"      <?= $bmi=='class3'      ? 'selected':'' ?>>Class 3 — Morbid Obesity (≥ 40)</option>
                                        </select>
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

            </div><!-- /filter-form -->

            <!-- ═══ PRINT AREA ═══ -->
            <div class="main-content">
                <div class="print-area">

                    <!-- Letterhead (print only) -->
                    <div class="print-letterhead">
                        <img src="../../img/daet_logo.png" alt="Left Logo" class="print-logo">
                        <div class="print-heading">
                            <div class="ph-line-1">Republic of the Philippines</div>
                            <div class="ph-line-1">Province of Camarines Norte</div>
                            <div class="ph-line-2">Municipality of Daet</div>
                            <div class="ph-line-3"><?= htmlspecialchars($barangayName, ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <img src="../../img/mho_logo.png" alt="Right Logo" class="print-logo">
                    </div>
                    <hr class="print-rule">

                    <!-- Report title (print only) -->
                    <div class="title">
                        <div class="ph-line-4">BHS PATIENT VISIT SUMMARY REPORT</div>
                        <div class="print-sub">
                            (<?php
                            $filters = [];
                            if ($from_date || $to_date) {
                                $readable_from = $from_date ? date("F j, Y", strtotime($from_date)) : '';
                                $readable_to   = $to_date   ? date("F j, Y", strtotime($to_date))   : '';
                                $filters[] = "<strong>" . trim($readable_from . ($readable_to ? " — " . $readable_to : '')) . "</strong>";
                            }
                            if ($sex)       $filters[] = "Sex: <strong>" . htmlspecialchars($sex, ENT_QUOTES, 'UTF-8') . "</strong>";
                            if ($age_group) {
                                $age_labels = ['child'=>'Child (0–12)','teen'=>'Teen (13–19)','adult'=>'Adult (20–59)','senior'=>'Senior (60+)'];
                                $filters[] = "Age Group: <strong>" . ($age_labels[$age_group] ?? htmlspecialchars($age_group, ENT_QUOTES,'UTF-8')) . "</strong>";
                            }
                            if ($purok)     $filters[] = "Address: <strong>" . htmlspecialchars($purok, ENT_QUOTES, 'UTF-8') . "</strong>";
                            if ($bmi) {
                                $bmi_labels = ['underweight'=>'Underweight','normal'=>'Normal','overweight'=>'Overweight','class1'=>'Class 1','class2'=>'Class 2','class3'=>'Class 3'];
                                $filters[] = "BMI: <strong>" . ($bmi_labels[$bmi] ?? htmlspecialchars($bmi, ENT_QUOTES,'UTF-8')) . "</strong>";
                            }
                            if ($treatment) {
                                $treat_labels = ['weighing'=>'Weighing','immunization'=>'Immunization','bp'=>'Blood Pressure','prenatal'=>'Prenatal','referred'=>'Referred'];
                                $filters[] = "Treatment: <strong>" . ($treat_labels[$treatment] ?? htmlspecialchars($treatment, ENT_QUOTES,'UTF-8')) . "</strong>";
                            }
                            echo $filters ? implode(" &nbsp;|&nbsp; ", $filters) : "All Records";
                            ?>)
                        </div>
                    </div>

                    <!-- ─── Chart Controls ─── -->
                    <div class="chart chart-controls-panel">
                        <h3>Charts</h3>
                        <div class="chart-toggle-group">
                            <label><input type="checkbox" id="toggleSexChart"> Patients by Sex</label>
                            <label><input type="checkbox" id="toggleAgeGroupChart"> Age Group</label>
                            <label><input type="checkbox" id="toggleBMIChart"> Patients by BMI</label>
                            <label><input type="checkbox" id="toggleTreatmentChart"> Treatments</label>
                        </div>
                    </div>

                    <!-- ─── Charts ─── -->
                    <div id="patientChartGrid" class="patient-chart-grid single-chart">
                        <div class="chart chart-card chart-animated is-hidden" id="sexChart" style="display:none;">
                            <h3 class="chart-title">Patients by Sex</h3>
                            <canvas id="sexPieChart"></canvas>
                        </div>

                        <div class="chart chart-card chart-animated is-hidden" id="ageGroupChart" style="display:none;">
                            <h3 class="chart-title">Age Group Distribution</h3>
                            <canvas id="ageGroupBarChart"></canvas>
                        </div>

                        <div class="chart chart-card chart-animated is-hidden" id="bmiChart" style="display:none;">
                            <h3 class="chart-title">Patients by BMI Category</h3>
                            <canvas id="bmiPieChart"></canvas>
                        </div>

                        <div class="chart chart-card chart-animated is-hidden" id="treatmentChart" style="display:none;">
                            <h3 class="chart-title">Treatment Distribution</h3>
                            <canvas id="treatmentBarChart"></canvas>
                        </div>

                        <div class="chart chart-card" id="addressChart">
                            <h3 class="chart-title">Patient Count by Address / Purok</h3>
                            <canvas id="addressBarChart"></canvas>
                        </div>
                    </div>

                    <!-- ─── Report Table ─── -->
                    <?php if ($visits): ?>
                    <div class="report-table-container">
                        <div class="report-table-scroll">
                            <table id="reportTable">
                                <thead>
                                    <tr>
                                        <th data-type="date">Visit Date<span class="sort-indicator"></span></th>
                                        <th data-type="string">Patient Name<span class="sort-indicator"></span></th>
                                        <th data-type="string">Sex<span class="sort-indicator"></span></th>
                                        <th data-type="number">Age<span class="sort-indicator"></span></th>
                                        <th data-type="number">BMI<span class="sort-indicator"></span></th>
                                        <th data-type="number">Weight<span class="sort-indicator"></span></th>
                                        <th data-type="number">Height<span class="sort-indicator"></span></th>
                                        <th data-type="string">Blood Pressure<span class="sort-indicator"></span></th>
                                        <th data-type="number">Temp<span class="sort-indicator"></span></th>
                                        <th data-type="number">Chest Rate<span class="sort-indicator"></span></th>
                                        <th data-type="number">Resp. Rate<span class="sort-indicator"></span></th>
                                        <th data-type="string">Chief Complaints<span class="sort-indicator"></span></th>
                                        <th data-type="string">Treatment<span class="sort-indicator"></span></th>
                                        <th data-type="string">Address<span class="sort-indicator"></span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($visits as $visit): ?>
                                    <tr>
                                        <td data-label="Visit Date"><?= date('Y-m-d', strtotime($visit['visit_date'])) ?></td>
                                        <td data-label="Patient Name"><?= htmlspecialchars($visit['first_name'] . ' ' . $visit['last_name']) ?></td>
                                        <td data-label="Sex"><?= htmlspecialchars($visit['sex']) ?></td>
                                        <td data-label="Age"><?= htmlspecialchars($visit['age']) ?></td>
                                        <td data-label="BMI"><?= htmlspecialchars($visit['bmi']) ?></td>
                                        <td data-label="Weight"><?= htmlspecialchars($visit['weight']) ?></td>
                                        <td data-label="Height"><?= htmlspecialchars($visit['height']) ?></td>
                                        <td data-label="Blood Pressure"><?= htmlspecialchars($visit['blood_pressure']) ?></td>
                                        <td data-label="Temperature"><?= htmlspecialchars($visit['temperature']) ?></td>
                                        <td data-label="Chest Rate"><?= htmlspecialchars($visit['chest_rate']) ?></td>
                                        <td data-label="Respiratory Rate"><?= htmlspecialchars($visit['respiratory_rate']) ?></td>
                                        <td data-label="Chief Complaints"><?= htmlspecialchars($visit['chief_complaints']) ?></td>
                                        <td data-label="Treatment"><?= htmlspecialchars($visit['treatment']) ?></td>
                                        <td data-label="Address"><?= htmlspecialchars($visit['address']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <br>

                    <!-- ─── Summary Section ─── -->
                    <?php
                    /* Sex counts */
                    $sex_counts = ['Male' => 0, 'Female' => 0];
                    $unique_patients = [];
                    foreach ($visits as $visit) {
                        $pid = $visit['patient_id'];
                        if (!isset($unique_patients[$pid])) $unique_patients[$pid] = $visit['sex'];
                    }
                    foreach ($unique_patients as $s) {
                        if (isset($sex_counts[$s])) $sex_counts[$s]++;
                    }

                    /* Age groups */
                    $age_group_counts = ['0–5' => 0, '6–17' => 0, '18–59' => 0, '60+' => 0];
                    $unique_patients_age = [];
                    foreach ($visits as $visit) {
                        $pid = $visit['patient_id'];
                        if (!isset($unique_patients_age[$pid])) {
                            $age = (int)$visit['age'];
                            if ($age <= 5) $age_group_counts['0–5']++;
                            elseif ($age <= 17) $age_group_counts['6–17']++;
                            elseif ($age <= 59) $age_group_counts['18–59']++;
                            else $age_group_counts['60+']++;
                            $unique_patients_age[$pid] = true;
                        }
                    }

                    /* BMI */
                    $bmi_categories = ['Underweight'=>0,'Normal'=>0,'Overweight'=>0,'Class 1'=>0,'Class 2'=>0,'Class 3'=>0];
                    $latest_bmi_per_patient = [];
                    foreach ($visits as $visit) {
                        $pid = $visit['patient_id'];
                        if (!isset($latest_bmi_per_patient[$pid]) || strtotime($visit['visit_date']) > strtotime($latest_bmi_per_patient[$pid]['visit_date'])) {
                            $latest_bmi_per_patient[$pid] = $visit;
                        }
                    }
                    foreach ($latest_bmi_per_patient as $visit) {
                        if (!isset($visit['bmi']) || $visit['bmi'] === '' || !is_numeric($visit['bmi'])) continue;
                        $b = floatval($visit['bmi']);
                        if ($b < 18.5) $bmi_categories['Underweight']++;
                        elseif ($b <= 24.9) $bmi_categories['Normal']++;
                        elseif ($b <= 29.9) $bmi_categories['Overweight']++;
                        elseif ($b <= 34.9) $bmi_categories['Class 1']++;
                        elseif ($b <= 39.9) $bmi_categories['Class 2']++;
                        else $bmi_categories['Class 3']++;
                    }

                    /* Treatments */
                    $treatment_types = ['Weighing'=>0,'Immunization'=>0,'Blood Pressure Reading'=>0,'Prenatal Check-up'=>0,'Referred'=>0];
                    foreach ($visits as $visit) {
                        $t = strtolower($visit['treatment'] ?? '');
                        if (strpos($t,'weighing')     !== false) $treatment_types['Weighing']++;
                        if (strpos($t,'immunization') !== false) $treatment_types['Immunization']++;
                        if (strpos($t,'bp')           !== false || strpos($t,'blood pressure') !== false) $treatment_types['Blood Pressure Reading']++;
                        if (strpos($t,'prenatal')     !== false) $treatment_types['Prenatal Check-up']++;
                        if (strpos($t,'referred')     !== false) $treatment_types['Referred']++;
                    }

                    /* Most common treatment */
                    $max_treatment = ''; $max_treatment_count = 0;
                    foreach ($treatment_types as $treat => $count) {
                        if ($count > $max_treatment_count) { $max_treatment = $treat; $max_treatment_count = $count; }
                    }

                    /* Address / purok counts */
                    $unique_patient_addresses = [];
                    foreach ($visits as $visit) $unique_patient_addresses[$visit['patient_id']] = $visit['address'] ?? 'Unknown';

                    $barangay_patient_counts = [];
                    foreach ($unique_patient_addresses as $address) {
                        $parts = explode('-', $address, 2);
                        $purok_key = trim($parts[0]);
                        $barangay_patient_counts[$purok_key] = ($barangay_patient_counts[$purok_key] ?? 0) + 1;
                    }
                    uksort($barangay_patient_counts, function($a,$b) {
                        preg_match('/\d+/', $a, $ma); preg_match('/\d+/', $b, $mb);
                        return (int)($ma[0] ?? 0) <=> (int)($mb[0] ?? 0);
                    });
                    ?>

                    <div class="summary-container">
                        <div class="summary">
                            <h3 class="summary-title"><i class="bx bx-file"></i> Report Details</h3>

                            <table class="summary-table">
                                <tbody>
                                    <tr>
                                        <th>Report Generated On</th>
                                        <td class="summary-mono"><?= htmlspecialchars(date('F jS, Y \a\t g:i A'), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Total Patients in Report</th>
                                        <td><strong><?= (int)$total_patients ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>By Sex</th>
                                        <td>Male – <?= (int)($sex_counts['Male']??0) ?>, Female – <?= (int)($sex_counts['Female']??0) ?></td>
                                    </tr>
                                    <tr>
                                        <th>By Age Group</th>
                                        <td>
                                            Children – <?= (int)(($age_group_counts['0–5']??0)+($age_group_counts['6–17']??0)) ?>,
                                            Adults – <?= (int)($age_group_counts['18–59']??0) ?>,
                                            Seniors – <?= (int)($age_group_counts['60+']??0) ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>By BMI</th>
                                        <td>
                                            Underweight – <?= (int)($bmi_categories['Underweight']??0) ?>,
                                            Normal – <?= (int)($bmi_categories['Normal']??0) ?>,
                                            Overweight – <?= (int)($bmi_categories['Overweight']??0) ?>,
                                            Obese – <?= (int)(($bmi_categories['Class 1']??0)+($bmi_categories['Class 2']??0)+($bmi_categories['Class 3']??0)) ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Most Common Treatment</th>
                                        <td><?= htmlspecialchars($max_treatment ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Patient Count per Purok</th>
                                        <td>
                                            <ul class="summary-sublist">
                                                <?php foreach ($barangay_patient_counts as $p => $c): ?>
                                                <li><?= htmlspecialchars($p, ENT_QUOTES,'UTF-8') ?> – <?= (int)$c ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php else: ?>
                    <div class="no-records">
                        <i class="bx bx-file-blank" style="font-size:48px;color:var(--grey-500);display:block;margin-bottom:12px;"></i>
                        No visits found for the selected filters.
                    </div>
                    <?php endif; ?>

                    <br><br>
                    <span id="generated_by" style="font-size:16px;"></span>

                </div><!-- /print-area -->
            </div><!-- /main-content -->

        </div><!-- /history-container -->
    </main>
</section>

<!-- ═══ LOGOUT MODAL ═══ -->
<div id="logoutModal" class="logout-modal">
    <div class="logout-modal-content">
        <div class="logout-modal-header"><h3>Confirm Logout</h3></div>
        <div class="logout-modal-body"><p>Are you sure you want to logout?</p></div>
        <div class="logout-modal-footer">
            <button onclick="closeModal()" class="logout-cancel-btn">Cancel</button>
            <button onclick="proceedLogout()" class="logout-confirm-btn">Yes, Logout</button>
        </div>
    </div>
</div>

<!-- ═══ SCRIPTS ═══ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
/* ─── Chart data from PHP ─── */
<?php
/* Sex */
$sex_counts_js  = ['Male' => 0, 'Female' => 0];
foreach ($unique_patients as $s) { if (isset($sex_counts_js[$s])) $sex_counts_js[$s]++; }

/* Address */
$address_labels = array_keys($barangay_patient_counts);
$address_data   = array_values($barangay_patient_counts);
?>
const sexLabels       = <?= json_encode(array_keys($sex_counts)) ?>;
const sexData         = <?= json_encode(array_values($sex_counts)) ?>;
const ageGroupLabels  = <?= json_encode(array_keys($age_group_counts)) ?>;
const ageGroupData    = <?= json_encode(array_values($age_group_counts)) ?>;
const bmiLabels       = <?= json_encode(array_keys($bmi_categories)) ?>;
const bmiData         = <?= json_encode(array_values($bmi_categories)) ?>;
const treatmentLabels = <?= json_encode(array_keys($treatment_types)) ?>;
const treatmentData   = <?= json_encode(array_values($treatment_types)) ?>;
const addressLabels   = <?= json_encode($address_labels) ?>;
const addressData     = <?= json_encode($address_data) ?>;

/* ─── Chart palette ─── */
const palette = ['#0d2d52','#1c6fba','#2196f3','#64b5f6','#bbdefb','#4caf50'];
const paletteWarm = ['#0d2d52','#1c6fba','#2196f3','#22a06b','#d97706'];

const chartInstances = {};
const chartFontFamily = 'Plus Jakarta Sans, Arial, sans-serif';
const chartTextColor = '#4a5568';
const chartTitleColor = '#0d2d52';

if (window.Chart) {
    Chart.defaults.color = chartTextColor;
    Chart.defaults.font.family = chartFontFamily;
    Chart.defaults.font.size = 12;
}

function sumChartData(data) {
    return data.reduce((a, b) => a + Number(b || 0), 0);
}

function compactAddressLabel(label) {
    const raw = String(label || '').replace(/\s+/g, ' ').trim();
    if (!raw) return 'Unknown';

    const purokMatch = raw.match(/\bPurok\s*([A-Za-z0-9\-]+)/i);
    if (purokMatch) return 'Purok ' + purokMatch[1];

    const firstPart = raw.split('-')[0].trim();
    if (firstPart && firstPart.length <= 28) return firstPart;

    return raw.length > 30 ? raw.slice(0, 27) + '...' : raw;
}

function wrapChartLabel(label, maxChars = 14, maxLines = 3) {
    const text = String(label || '').replace(/\s+/g, ' ').trim();
    if (!text) return '';

    const words = text.split(/([\s,/()-]+)/).filter(Boolean);
    const lines = [];
    let current = '';

    words.forEach(word => {
        const candidate = (current + word).trim();
        if (candidate.length > maxChars && current) {
            lines.push(current.trim());
            current = word.trim();
        } else {
            current = candidate;
        }
    });

    if (current) lines.push(current.trim());
    if (lines.length > maxLines) {
        const clipped = lines.slice(0, maxLines);
        clipped[maxLines - 1] = clipped[maxLines - 1].replace(/\.{3}$/, '') + '...';
        return clipped;
    }
    return lines;
}

function getCategoryLabel(scaleContext, value) {
    return scaleContext && typeof scaleContext.getLabelForValue === 'function'
        ? scaleContext.getLabelForValue(value)
        : value;
}

function buildReadableLabels(labels, mode) {
    if (mode === 'address') return labels.map(compactAddressLabel);
    return labels;
}

function makeChart(id, type, labels, data, colors, config = {}) {
    const ctx = document.getElementById(id);
    if (!ctx) return null;
    if (sumChartData(data) === 0) return null;

    const displayLabels = buildReadableLabels(labels, config.labelMode);
    const isBar = type === 'bar';
    const isHorizontal = Boolean(config.horizontal);

    const axisText = {
        color: chartTextColor,
        font: { family: chartFontFamily, size: config.tickSize || 11 },
        padding: 6
    };

    const scales = isBar ? (isHorizontal ? {
        x: {
            beginAtZero: true,
            title: {
                display: true,
                text: config.xTitle || 'Count',
                color: chartTitleColor,
                font: { family: chartFontFamily, size: 12, weight: '600' }
            },
            grid: { color: 'rgba(0,0,0,.05)' },
            ticks: axisText
        },
        y: {
            title: {
                display: Boolean(config.yTitle),
                text: config.yTitle || '',
                color: chartTitleColor,
                font: { family: chartFontFamily, size: 12, weight: '600' }
            },
            grid: { display: false },
            ticks: {
                ...axisText,
                autoSkip: false,
                callback: function(value) {
                    return wrapChartLabel(getCategoryLabel(this, value), config.wrapChars || 16, config.maxLines || 2);
                }
            }
        }
    } : {
        y: {
            beginAtZero: true,
            title: {
                display: Boolean(config.yTitle),
                text: config.yTitle || 'Count',
                color: chartTitleColor,
                font: { family: chartFontFamily, size: 12, weight: '600' }
            },
            grid: { color: 'rgba(0,0,0,.05)' },
            ticks: axisText
        },
        x: {
            title: {
                display: Boolean(config.xTitle),
                text: config.xTitle || '',
                color: chartTitleColor,
                font: { family: chartFontFamily, size: 12, weight: '600' }
            },
            grid: { display: false },
            ticks: {
                ...axisText,
                autoSkip: config.autoSkip ?? false,
                maxRotation: config.maxRotation ?? 0,
                minRotation: config.minRotation ?? 0,
                callback: function(value) {
                    return wrapChartLabel(getCategoryLabel(this, value), config.wrapChars || 12, config.maxLines || 3);
                }
            }
        }
    }) : {};

    chartInstances[id] = new Chart(ctx.getContext('2d'), {
        type,
        data: {
            labels: displayLabels,
            datasets: [{
                data,
                label: config.datasetLabel || 'Count',
                backgroundColor: colors,
                borderWidth: type === 'pie' ? 2 : 0,
                borderColor: '#fff',
                maxBarThickness: config.maxBarThickness || 44
            }]
        },
        options: {
            indexAxis: isHorizontal ? 'y' : 'x',
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 850, easing: 'easeOutQuart' },
            layout: { padding: { top: 8, right: 12, bottom: 16, left: 12 } },
            plugins: {
                legend: {
                    display: type === 'pie' || !config.hideLegend,
                    position: type === 'pie' ? 'bottom' : 'top',
                    labels: {
                        color: chartTextColor,
                        font: { family: chartFontFamily, size: 12, weight: '500' },
                        boxWidth: 14,
                        boxHeight: 14,
                        padding: 14
                    }
                },
                tooltip: {
                    callbacks: {
                        title: function(items) {
                            if (!items.length) return '';
                            return labels[items[0].dataIndex] || items[0].label;
                        }
                    },
                    bodyFont: { family: chartFontFamily },
                    titleFont: { family: chartFontFamily, weight: '700' }
                }
            },
            scales
        }
    });
    return chartInstances[id];
}

function replayChartEntrance(target) {
    target.classList.remove('chart-entered');
    void target.offsetWidth;
    target.classList.add('chart-entered');
}

function updatePatientChartGrid(animate = true) {
    const grid = document.getElementById('patientChartGrid');
    if (!grid) return;

    const optionalCharts = ['sexChart', 'ageGroupChart', 'bmiChart', 'treatmentChart']
        .map(id => document.getElementById(id))
        .filter(Boolean);

    const visibleOptional = optionalCharts.filter(el => !el.classList.contains('is-hidden') && el.style.display !== 'none');
    grid.classList.toggle('single-chart', visibleOptional.length === 0);

    const visibleCharts = [...visibleOptional, document.getElementById('addressChart')].filter(Boolean);
    if (animate) visibleCharts.forEach(replayChartEntrance);

    setTimeout(() => {
        Object.values(chartInstances).forEach(chart => chart && chart.resize());
    }, 360);
}

document.addEventListener('DOMContentLoaded', () => {
    makeChart('sexPieChart', 'pie', sexLabels, sexData, palette, {
        datasetLabel: 'Patients'
    });
    makeChart('ageGroupBarChart', 'bar', ageGroupLabels, ageGroupData, palette, {
        xTitle: 'Age Group',
        yTitle: 'Patient Count',
        datasetLabel: 'Patient Count',
        hideLegend: true,
        wrapChars: 12,
        maxLines: 2
    });
    makeChart('bmiPieChart', 'pie', bmiLabels, bmiData, palette, {
        datasetLabel: 'Patients'
    });
    makeChart('treatmentBarChart', 'bar', treatmentLabels, treatmentData, paletteWarm, {
        xTitle: 'Treatment',
        yTitle: 'Count',
        datasetLabel: 'Treatment Count',
        hideLegend: true,
        wrapChars: 14,
        maxLines: 2
    });
    makeChart('addressBarChart', 'bar', addressLabels, addressData, ['#1c6fba'], {
        horizontal: true,
        labelMode: 'address',
        xTitle: 'Patient Count',
        yTitle: 'Address / Purok',
        datasetLabel: 'Patient Count',
        hideLegend: true,
        wrapChars: 18,
        maxLines: 2,
        tickSize: 10,
        maxBarThickness: 28
    });

    const chartMapping = {
        toggleSexChart:       { element: 'sexChart',       canvas: 'sexPieChart' },
        toggleAgeGroupChart:  { element: 'ageGroupChart',  canvas: 'ageGroupBarChart' },
        toggleBMIChart:       { element: 'bmiChart',       canvas: 'bmiPieChart' },
        toggleTreatmentChart: { element: 'treatmentChart', canvas: 'treatmentBarChart' }
    };

    Object.keys(chartMapping).forEach(id => {
        const cb = document.getElementById(id);
        const config = chartMapping[id];
        const el = document.getElementById(config.element);
        if (!cb || !el) return;

        cb.addEventListener('change', () => {
            if (cb.checked) {
                el.style.display = 'block';
                requestAnimationFrame(() => {
                    el.classList.remove('is-hidden');
                    updatePatientChartGrid(true);
                    if (chartInstances[config.canvas]) chartInstances[config.canvas].resize();
                });
            } else {
                el.classList.add('is-hidden');
                setTimeout(() => {
                    el.style.display = 'none';
                    updatePatientChartGrid(true);
                }, 320);
            }
        });

        el.style.display = cb.checked ? 'block' : 'none';
        el.classList.toggle('is-hidden', !cb.checked);
    });

    updatePatientChartGrid(false);
});</script>

<script>
/* ─── Table sort ─── */
(function() {
    const table = document.getElementById('reportTable');
    if (!table) return;
    const thead = table.tHead;
    const tbody = table.tBodies[0];

    function parseDate(v) {
        if (/^\d{4}-\d{2}-\d{2}/.test(v)) return new Date(v + 'T00:00:00');
        const d = new Date(v); return isNaN(d.getTime()) ? null : d;
    }
    function detectType(idx) {
        const th = thead.querySelectorAll('th')[idx];
        if (th && th.dataset.type) return th.dataset.type;
        for (const tr of tbody.rows) {
            const t = (tr.cells[idx]?.textContent || '').trim();
            if (!t) continue;
            if (parseDate(t)) return 'date';
            if (!isNaN(t.replace(/,/g,''))) return 'number';
            return 'string';
        }
        return 'string';
    }
    function getCellValue(tr, idx, type) {
        const raw = (tr.cells[idx]?.textContent || '').trim();
        if (type === 'number') { const n = parseFloat(raw.replace(/,/g,'')); return isNaN(n) ? -Infinity : n; }
        if (type === 'date')   { const d = parseDate(raw); return d ? d.getTime() : -Infinity; }
        return raw.toLowerCase();
    }
    function sortBy(idx, dir) {
        const type = detectType(idx);
        const rows = [...tbody.rows].sort((a,b) => {
            const va = getCellValue(a,idx,type), vb = getCellValue(b,idx,type);
            return va < vb ? (dir==='asc'?-1:1) : va > vb ? (dir==='asc'?1:-1) : 0;
        });
        const frag = document.createDocumentFragment();
        rows.forEach(r => frag.appendChild(r));
        tbody.appendChild(frag);
    }

    [...thead.querySelectorAll('th')].forEach((th, idx) => {
        th.addEventListener('click', () => {
            const nextDir = th.classList.contains('is-sorted-asc') ? 'desc' : 'asc';
            [...thead.querySelectorAll('th')].forEach(h => h.classList.remove('is-sorted-asc','is-sorted-desc'));
            th.classList.add(nextDir === 'asc' ? 'is-sorted-asc' : 'is-sorted-desc');
            sortBy(idx, nextDir);
        });
    });

    /* Default sort: Visit Date desc */
    const def = thead.querySelectorAll('th')[0];
    if (def) { def.classList.add('is-sorted-desc'); sortBy(0, 'desc'); }
})();
</script>

<script>
/* ─── Filter Modal ─── */
document.getElementById('openFilterModal').onclick  = () => document.getElementById('filterModal').style.display = 'block';
document.getElementById('closeFilterModal').onclick = () => document.getElementById('filterModal').style.display = 'none';
document.getElementById('filterForm')?.addEventListener('submit', () => {
    document.getElementById('filterModal').style.display = 'none';
});

/* Init Flatpickr after modal opens */
document.getElementById('openFilterModal').addEventListener('click', () => {
    setTimeout(() => {
        ['from_date','to_date'].forEach(id => {
            const el = document.getElementById(id);
            if (el._flatpickr) el._flatpickr.destroy();
            flatpickr('#' + id, { dateFormat:'Y-m-d', allowInput:true, disableMobile:true });
        });
    }, 100);
});

window.addEventListener('click', e => {
    const modal = document.getElementById('filterModal');
    if (e.target === modal) modal.style.display = 'none';
    const logout = document.getElementById('logoutModal');
    if (e.target === logout) closeModal();
});
</script>

<script>
/* ─── Excel Export ─── */
function exportTableToExcel(tableID, filename = 'Patient Summary Report') {
    try {
        const tempDiv = document.createElement('div');
        tempDiv.style.cssText = 'position:absolute;left:-9999px;top:-9999px;';

        const summary = document.querySelector('.summary-container');
        if (summary) { const cl = summary.cloneNode(true); cl.querySelectorAll('script').forEach(s=>s.remove()); tempDiv.appendChild(cl); }

        const orig = document.getElementById(tableID);
        if (!orig) { alert('Table not found!'); return; }
        const tc = orig.cloneNode(true);
        tempDiv.appendChild(tc);
        document.body.appendChild(tempDiv);

        const html = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
            <head><meta charset="UTF-8"></head><body>${tempDiv.innerHTML}</body></html>`;

        const blob = new Blob([html], { type:'application/vnd.ms-excel' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = filename + '.xls';
        document.body.appendChild(a); a.click();
        setTimeout(() => { document.body.removeChild(a); document.body.removeChild(tempDiv); URL.revokeObjectURL(a.href); }, 100);
    } catch (err) { console.error(err); alert('Export error: ' + err.message); }
}

/* ─── Print ─── */
function printDiv() {
    const headerEl = document.querySelector('.print-letterhead, .print-header');
    const printHeader = headerEl ? headerEl.outerHTML : '';
    const area = document.querySelector('.print-area');
    if (!area) return;
    const clone = area.cloneNode(true);

    /* Remove all chart/canvas elements from the clone */
    clone.querySelectorAll('canvas, .chart, .chart-card, .patient-chart-grid, #patientChartGrid, #addressChart, .chart-controls-panel').forEach(el => el.remove());

    const headerInClone = clone.querySelector('.print-letterhead, .print-header');
    if (headerInClone) headerInClone.remove();

    const w = window.open('', '', 'height=900,width=1100');
    if (!w) { alert('Please allow pop-ups to print this report.'); return; }
    w.document.write(`<html><head><title>Print Report</title><meta charset="utf-8"/>
    <style>
      body{font-family:Arial,sans-serif;font-size:13px;color:#000;}
      table{width:100%;border-collapse:collapse;font-family:Arial,sans-serif;font-size:12px;}
      th,td{border:1px solid #000;padding:5px 8px;text-align:left;font-family:Arial,sans-serif;}
      thead{background:#fff;}
      thead th{background:#fff;color:#000;font-weight:700;}
      .print-letterhead{display:grid;grid-template-columns:64px auto 64px;align-items:center;column-gap:60px;margin:0 auto 10px;text-align:center;width:fit-content;}
      .print-logo{width:64px;height:64px;object-fit:contain;}
      .print-heading{font-family:Arial,sans-serif;}
      .print-heading .ph-line-1{font-size:12pt;font-weight:500;}
      .print-heading .ph-line-2{font-size:14pt;font-weight:800;}
      .print-heading .ph-line-3{font-size:12pt;font-weight:500;}
      .title{text-align:center;margin:10px 0;}
      .ph-line-4{font-size:12pt;font-weight:800;margin-top:4px;text-align:center;font-family:Arial,sans-serif;}
      .print-sub{font-size:11pt;margin-top:4px;text-align:center;font-family:Arial,sans-serif;}
      .print-rule{height:1px;border:0;background:#ccc;margin:8px 0 12px;}
      .chart-controls-panel,.btn-export,.btn-print,.selected-filters,
      .chart,.patient-chart-grid,#patientChartGrid,#addressChart,
      .chart-card,canvas,img[src^="data:"]{display:none!important;}
      .summary-table th{font-family:Arial,sans-serif;font-weight:700;}
      .summary-table td{font-family:Arial,sans-serif;}
      #generated_by,.sig-label,.sig-name,.sig-title{font-family:Arial,sans-serif;}
      #generated_by{margin-top:48px;}
      .sig-label{font-size:11px;text-transform:uppercase;letter-spacing:.07em;color:#666;margin-bottom:60px;display:block;}
      .sig-block{display:inline-block;text-align:center;}
      .sig-line{display:block;border:none;border-top:1.5px solid #000;margin:0 0 4px;}
      .sig-name{font-weight:700;font-size:13px;white-space:nowrap;}
      .sig-title{font-size:11px;color:#666;}
    </style></head>
    <body>${printHeader}${clone.innerHTML}</body></html>`);
    w.document.close(); w.focus();
    setTimeout(() => { w.print(); w.close(); }, 350);
}

/* ─── User name ─── */
fetch('../php/getUserName.php')
    .then(r => r.json())
    .then(data => {
        const fullName = (data && data.full_name) ? data.full_name : '';
        const sn = document.getElementById('sidebarUserName');
        if (sn) sn.textContent = fullName || 'BHW User';
        const gb = document.getElementById('generated_by');
        if (gb) {
            const name = fullName || '________________';
            gb.innerHTML = `<div class="sig-label">Report Generated by:</div>
                <div class="sig-block">
                    <span class="sig-line"></span>
                    <div class="sig-name"></div>
                    <div class="sig-title">Barangay Health Worker</div>
                </div>`;
            gb.querySelector('.sig-name').textContent = name;
            /* Make the line match the name width */
            const nameEl = gb.querySelector('.sig-name');
            const lineEl = gb.querySelector('.sig-line');
            requestAnimationFrame(() => { lineEl.style.width = nameEl.offsetWidth + 'px'; });
        }
    })
    .catch(() => {
        const sn = document.getElementById('sidebarUserName');
        if (sn) sn.textContent = 'BHW User';
        const gb = document.getElementById('generated_by');
        if (gb) {
            gb.innerHTML = `<div class="sig-label">Report Generated by:</div>
                <div class="sig-block">
                    <span class="sig-line" style="width:180px;"></span>
                    <div class="sig-name">________________</div>
                    <div class="sig-title">Barangay Health Worker</div>
                </div>`;
        }
    });

/* ─── Logout ─── */
function confirmLogout() { document.getElementById('logoutModal').classList.add('open'); return false; }
function closeModal()    { document.getElementById('logoutModal').classList.remove('open'); }
function proceedLogout() { window.location.href='../../ADMIN/php/logout'; }
</script>

<script>
/* ─── Sidebar toggle ─── */
(function() {
    const sidebar = document.getElementById('sidebar');
    const toggle  = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');
    if (!sidebar || !toggle || !overlay) return;

    function isMobile() { return window.innerWidth <= 768; }
    function closeMobile() { sidebar.classList.remove('mobile-open'); overlay.classList.remove('active'); document.body.style.overflow = ''; }

    toggle.addEventListener('click', () => {
        if (isMobile()) {
            const open = sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active', open);
            document.body.style.overflow = open ? 'hidden' : '';
        } else {
            sidebar.classList.toggle('collapsed');
        }
    });
    overlay.addEventListener('click', closeMobile);
    window.addEventListener('resize', () => { if (!isMobile()) closeMobile(); });
})();
</script>

</body>
</html>