<?php
require '../../php/db_connect.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../role");
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT barangay FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$barangayName = $user ? $user['barangay'] : 'N/A';

/* ── Filter values ── */
$from_date       = $_GET['from_date']  ?? '';
$to_date         = $_GET['to_date']    ?? '';
$referral_status = $_GET['status']     ?? '';
$bhw_id          = $_GET['bhw']        ?? '';
$sex             = $_GET['sex']        ?? '';
$age_group       = $_GET['age_group']  ?? '';
$status          = $_GET['status']     ?? '';

function prettyDate($dateStr, $withTime = false) {
    $ts = strtotime($dateStr);
    if (!$ts) return htmlspecialchars($dateStr);
    return $withTime ? date('F d, Y H:i:s', $ts) : date('M d, Y', $ts);
}

/* ── BHW dropdown ── */
$bhw_stmt = $pdo->query("SELECT user_id, full_name FROM users WHERE role = 'BHW'");
$bhws = $bhw_stmt->fetchAll();

/* ── Build main SQL ── */
$sql = "SELECT r.*, p.first_name, p.last_name, p.sex, p.age, v.visit_date, v.chief_complaints, u.full_name AS bhw_name
        FROM referrals r
        JOIN patient_assessment v ON r.visit_id = v.visit_id
        JOIN patients p ON r.patient_id = p.patient_id
        JOIN users u ON r.referred_by = u.user_id
        WHERE p.address LIKE :barangay";

$params = [];
$params['barangay'] = '%' . $barangayName . '%';

if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND r.referral_date >= :from_dt AND r.referral_date < :to_dt_plus1";
    $params['from_dt']      = $from_date . " 00:00:00";
    $params['to_dt_plus1']  = date('Y-m-d', strtotime($to_date . ' +1 day')) . " 00:00:00";
}
if (!empty($referral_status)) {
    $sql .= " AND r.referral_status = :status";
    $params['status'] = $referral_status;
}
if (!empty($bhw_id)) {
    $sql .= " AND r.referred_by = :bhw";
    $params['bhw'] = $bhw_id;
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

$sql .= " ORDER BY r.referral_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

/* ── Audit log ── */
$stmt_log = $pdo->prepare("INSERT INTO logs (user_id, action, performed_by) VALUES (:user_id, :action, :performed_by)");
$stmt_log->execute([
    ':user_id'      => $_SESSION['user_id'],
    ':action'       => "Generated BHS Referral Summary Report",
    ':performed_by' => $_SESSION['user_id'],
]);

/* ── Referral count & visits-without-referral ── */
$referral_count = count($rows);

$visit_sql = "SELECT COUNT(*) FROM patient_assessment v
    JOIN patients p ON v.patient_id = p.patient_id
    WHERE p.address LIKE :barangay
    AND v.visit_id NOT IN (SELECT visit_id FROM referrals)";
$visit_params = ['barangay' => '%' . $barangayName . '%'];

if (!empty($from_date) && !empty($to_date)) {
    $visit_sql .= " AND v.visit_date >= :from_dt AND v.visit_date < :to_dt_plus1";
    $visit_params['from_dt']     = $from_date . " 00:00:00";
    $visit_params['to_dt_plus1'] = date('Y-m-d', strtotime($to_date . ' +1 day')) . " 00:00:00";
}
if (!empty($sex)) {
    $visit_sql .= " AND p.sex = :sex";
    $visit_params['sex'] = $sex;
}
if (!empty($age_group)) {
    switch ($age_group) {
        case 'child':  $visit_sql .= " AND p.age < 13"; break;
        case 'teen':   $visit_sql .= " AND p.age BETWEEN 13 AND 19"; break;
        case 'adult':  $visit_sql .= " AND p.age BETWEEN 20 AND 59"; break;
        case 'senior': $visit_sql .= " AND p.age >= 60"; break;
    }
}
if (!empty($bhw_id)) {
    $visit_sql .= " AND v.recorded_by = :bhw";
    $visit_params['bhw'] = $bhw_id;
}

$visit_stmt = $pdo->prepare($visit_sql);
$visit_stmt->execute($visit_params);
$visits_without_referral = (int)$visit_stmt->fetchColumn();

/* ── Status counts ── */
$status_counts = ['Completed' => 0, 'Uncompleted' => 0, 'Pending' => 0, 'Canceled' => 0];
foreach ($rows as $row) {
    $sv = ucfirst(strtolower($row['referral_status']));
    if (isset($status_counts[$sv])) $status_counts[$sv]++;
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
    <link rel="stylesheet" href="../css/logout.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <title>Referrals Report</title>

    <style>
    /* ── Status badge styles (page-specific) ── */
    .referral-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px 3px 8px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .03em;
        white-space: nowrap;
    }
    .referral-status::before {
        content: '';
        display: inline-block;
        width: 6px; height: 6px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .status-pending     { background: #dbeafe; color: #1d4ed8; }
    .status-pending::before     { background: #1d4ed8; }
    .status-completed   { background: #dcfce7; color: #15803d; }
    .status-completed::before   { background: #15803d; }
    .status-uncompleted { background: #fee2e2; color: #b91c1c; }
    .status-uncompleted::before { background: #b91c1c; }
    .status-canceled    { background: #f3f4f6; color: #6b7280; }
    .status-canceled::before    { background: #6b7280; }

    /* ── Sort indicator (table-specific override) ── */
    #reportTable th { cursor: pointer; user-select: none; }
    #reportTable th .sort-indicator { margin-left: 5px; font-size: 10px; opacity: .65; }
    #reportTable th.is-sorted-asc  .sort-indicator::after { content: "▲"; }
    #reportTable th.is-sorted-desc .sort-indicator::after { content: "▼"; }


    /* ── Balanced side-by-side chart layout ── */
    .referral-charts-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(280px, 1fr));
        gap: 24px;
        align-items: start;
        margin: 24px 0 28px;
    }

    .referral-charts-grid.single-chart {
        grid-template-columns: minmax(300px, 520px);
        justify-content: center;
    }

    .referral-charts-grid .chart {
        width: 100%;
        max-width: none !important;
        margin: 0 !important;
        padding: 18px 18px 14px;
        text-align: center;
        min-height: 340px;
    }

    .referral-charts-grid canvas {
        width: 100% !important;
        height: 280px !important;
        max-height: 280px;
    }

    @media (max-width: 900px) {
        .referral-charts-grid,
        .referral-charts-grid.single-chart {
            grid-template-columns: 1fr;
        }

        .referral-charts-grid .chart {
            max-width: 480px !important;
            margin: 0 auto !important;
        }
    }

    @media (max-width: 480px) {
        .referral-charts-grid .chart { min-height: 300px; }
        .referral-charts-grid canvas { height: 240px !important; max-height: 240px; }
    }

    /* ── Smooth chart entrance/exit ── */
    .referral-charts-grid {
        transition: grid-template-columns .35s ease, gap .35s ease;
    }

    .referral-charts-grid .chart {
        transform-origin: center top;
        will-change: opacity, transform;
    }

    .referral-charts-grid .chart.chart-entered {
        animation: chartFadeUp .46s cubic-bezier(.22, 1, .36, 1) both;
    }

    #referralChart.chart-animated {
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

    #referralChart.chart-animated.is-hidden {
        opacity: 0;
        transform: translateY(14px) scale(.985);
        max-height: 0;
        padding-top: 0;
        padding-bottom: 0;
        pointer-events: none;
    }

    @keyframes chartFadeUp {
        from {
            opacity: 0;
            transform: translateY(16px) scale(.985);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .referral-charts-grid,
        .referral-charts-grid .chart,
        #referralChart.chart-animated {
            animation: none !important;
            transition: none !important;
        }
    }

    /* ── Summary tables ── */
    .kv-table, .status-breakdown-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid var(--border);
        border-radius: var(--r-lg);
        overflow: hidden;
        font-size: 14px;
        margin-bottom: 20px;
        box-shadow: var(--shadow-sm);
    }
    .kv-table th,
    .status-breakdown-table th {
        background: var(--grey-100);
        font-weight: 600;
        color: var(--navy);
        padding: 13px 18px;
        text-align: left;
        border-bottom: 1px solid var(--border-soft);
        border-right: 1px solid var(--border-soft);
        width: 50%;
    }
    .kv-table td,
    .status-breakdown-table td {
        color: var(--grey-700);
        padding: 13px 18px;
        border-bottom: 1px solid var(--border-soft);
        font-size: 13.5px;
    }
    .kv-table tr:last-child th,
    .kv-table tr:last-child td,
    .status-breakdown-table tr:last-child th,
    .status-breakdown-table tr:last-child td { border-bottom: none; }

    .status-breakdown-table thead tr {
        background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
    }
    .status-breakdown-table thead th {
        color: rgba(255,255,255,.9);
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: .07em;
        background: transparent;
        border-color: rgba(255,255,255,.1);
    }
    .status-breakdown-table tbody tr:hover td,
    .status-breakdown-table tbody tr:hover th { background: var(--blue-pale); }
    .kv-table tbody tr:hover td,
    .kv-table tbody tr:hover th { background: var(--blue-pale); }



    /* ── Unified layout polish for this report page ── */
    .bhw-referral-layout-page {
        width: 100%;
        padding: 32px 28px;
        max-height: calc(100vh - 56px);
        overflow-y: auto;
    }

    .bhw-referral-layout-page .history-container,
    .bhw-referral-layout-page .main-content,
    .bhw-referral-layout-page .report-content {
        width: 100%;
    }

    .bhw-referral-layout-page .filter-form {
        background: var(--surface, #fff);
        border: 1px solid var(--border, #dde4ef);
        border-radius: var(--r-lg, 16px);
        padding: 28px 32px 24px;
        margin-bottom: 24px;
        box-shadow: var(--shadow-sm, 0 2px 8px rgba(13,45,82,.09));
    }

    .bhw-referral-layout-page .filter-form h2 {
        font-size: 17px;
        font-weight: 700;
        color: var(--navy, #0d2d52);
        letter-spacing: -.2px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .bhw-referral-layout-page .filter-form h2::before {
        content: '';
        display: inline-block;
        width: 4px;
        height: 18px;
        border-radius: 2px;
        background: var(--blue, #1c6fba);
        flex-shrink: 0;
    }

    .bhw-referral-layout-page .form-submit {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 0 !important;
    }

    .bhw-referral-layout-page .selected-filters h3 {
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

    .bhw-referral-layout-page .filter-tag {
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
        transition: background .15s ease;
    }

    .bhw-referral-layout-page .filter-tag:hover {
        background: #dceeff !important;
    }

    .bhw-referral-layout-page .filter-tag a {
        color: var(--grey-500, #8c96aa) !important;
        font-weight: 700 !important;
        font-size: 14px !important;
        line-height: 1;
        text-decoration: none !important;
        margin-left: 2px;
    }

    .bhw-referral-layout-page .filter-tag a:hover {
        color: var(--red, #e53e3e) !important;
    }

    .bhw-referral-layout-page .modal-content {
        width: 90%;
        max-width: 560px !important;
        border-radius: var(--r-xl, 20px);
        box-shadow: var(--shadow-lg, 0 8px 32px rgba(13,45,82,.13));
    }

    .bhw-referral-layout-page .form-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px 20px;
    }

    .bhw-referral-layout-page .form-item label {
        font-size: 12.5px;
        font-weight: 600;
        color: var(--grey-700, #4a5568);
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .bhw-referral-layout-page .print-area {
        background: var(--white, #fff);
        border: 1px solid var(--border, #dde4ef);
        border-radius: var(--r-lg, 16px);
        padding: 28px 32px;
        box-shadow: var(--shadow-sm, 0 2px 8px rgba(13,45,82,.09));
    }

    .bhw-referral-layout-page .chart-controls-panel {
        background: var(--grey-100, #f8f9fc);
        border: 1px solid var(--border-soft, #edf0f7);
        border-radius: var(--r-md, 10px);
        padding: 16px 20px;
        margin-bottom: 24px;
    }

    .bhw-referral-layout-page .chart-controls-panel h3 {
        font-size: 13px;
        font-weight: 700;
        color: var(--grey-700, #4a5568);
        text-transform: uppercase;
        letter-spacing: .07em;
        margin-bottom: 12px;
    }

    .bhw-referral-layout-page .chart-toggle-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .bhw-referral-layout-page .chart-toggle-group label {
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
        transition: background .18s ease, border-color .18s ease, color .18s ease;
    }

    .bhw-referral-layout-page .chart-toggle-group label:has(input:checked) {
        background: var(--navy, #0d2d52);
        border-color: var(--navy, #0d2d52);
        color: var(--white, #fff);
    }

    .bhw-referral-layout-page .referral-charts-grid .chart {
        background: var(--white, #fff);
        border: 1px solid var(--border-soft, #edf0f7);
        border-radius: var(--r-lg, 16px);
        box-shadow: var(--shadow-xs, 0 1px 3px rgba(13,45,82,.07));
    }

    .bhw-referral-layout-page .report-table-container {
        width: 100%;
        border-radius: var(--r-lg, 16px);
        border: 1px solid var(--border, #dde4ef);
        overflow: hidden;
        box-shadow: var(--shadow-md, 0 4px 20px rgba(13,45,82,.10));
        background: var(--white, #fff);
        margin: 24px 0 32px !important;
    }

    .bhw-referral-layout-page .report-table-scroll {
        width: 100%;
        overflow-x: auto;
        max-height: 560px;
        overflow-y: auto;
    }

    .bhw-referral-layout-page #reportTable {
        width: 100%;
        min-width: 980px;
        border-collapse: collapse;
        font-size: 13.5px;
    }

    .bhw-referral-layout-page #reportTable thead {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .bhw-referral-layout-page #reportTable thead tr {
        background: linear-gradient(135deg, var(--navy, #0d2d52) 0%, var(--navy-mid, #1a4477) 100%);
    }

    .bhw-referral-layout-page #reportTable th {
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

    .bhw-referral-layout-page #reportTable td {
        padding: 11px 14px;
        color: var(--grey-700, #4a5568);
        font-size: 13.5px;
        vertical-align: middle;
        border-right: 1px solid var(--border-soft, #edf0f7);
        border-bottom: 1px solid var(--border-soft, #edf0f7);
    }

    .bhw-referral-layout-page #reportTable tbody tr:nth-child(odd) { background: var(--white, #fff); }
    .bhw-referral-layout-page #reportTable tbody tr:nth-child(even) { background: var(--grey-100, #f8f9fc); }
    .bhw-referral-layout-page #reportTable tbody tr:hover { background: var(--blue-pale, #f0f6ff); }

    .bhw-referral-layout-page #generated_by {
        display: block;
        margin: 48px 0 0 4px;
        color: var(--dark, #0f1d31);
    }

    .bhw-referral-layout-page #generated_by .sig-label {
        font-size: 12.5px;
        font-weight: 600;
        color: var(--grey-500, #8c96aa);
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 60px;
        display: block;
    }

    .bhw-referral-layout-page #generated_by .sig-block {
        display: inline-block;
        text-align: center;
        min-width: 200px;
    }
    .bhw-referral-layout-page #generated_by .sig-line {
        display: block;
        border: none;
        border-top: 1.5px solid #000;
        width: 100%;
        margin: 0 0 4px;
    }
    .bhw-referral-layout-page #generated_by .sig-name {
        font-weight: 700;
        font-size: 15px;
        color: var(--navy, #0d2d52);
        white-space: nowrap;
    }
    .bhw-referral-layout-page #generated_by .sig-title {
        font-size: 12px;
        color: var(--grey-500, #8c96aa);
        margin-top: 2px;
    }

    @media (max-width: 768px) {
        .bhw-referral-layout-page { padding: 20px 14px; }
        .bhw-referral-layout-page .filter-form,
        .bhw-referral-layout-page .print-area { padding: 20px 18px; }
        .bhw-referral-layout-page .form-row { grid-template-columns: 1fr; }
        .bhw-referral-layout-page .modal-content { padding: 24px 20px 20px; }

        .bhw-referral-layout-page #reportTable { min-width: unset; }
        .bhw-referral-layout-page #reportTable thead { display: none; }
        .bhw-referral-layout-page #reportTable,
        .bhw-referral-layout-page #reportTable tbody,
        .bhw-referral-layout-page #reportTable tr,
        .bhw-referral-layout-page #reportTable td {
            display: block;
            width: 100%;
        }
        .bhw-referral-layout-page #reportTable tr {
            margin: 0 0 12px;
            padding: 14px 14px 8px;
            border: 1px solid var(--border, #dde4ef);
            border-radius: var(--r-md, 10px);
            background: var(--white, #fff);
            box-shadow: var(--shadow-xs, 0 1px 3px rgba(13,45,82,.07));
        }
        .bhw-referral-layout-page #reportTable td {
            border: 0;
            border-bottom: 1px solid var(--border-soft, #edf0f7);
            padding: 9px 0;
            white-space: normal;
            font-size: 13px;
        }
        .bhw-referral-layout-page #reportTable td:last-child { border-bottom: none; }
        .bhw-referral-layout-page #reportTable td::before {
            content: attr(data-label);
            display: block;
            font-size: 10.5px;
            font-weight: 700;
            color: var(--grey-500, #8c96aa);
            text-transform: uppercase;
            letter-spacing: .07em;
            margin-bottom: 2px;
        }
    }

    @media print {
        .bhw-referral-layout-page { padding: 0; max-height: none; overflow: visible; }
        .bhw-referral-layout-page .print-area {
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
            border-radius: 0 !important;
        }
        .bhw-referral-layout-page .report-table-container {
            box-shadow: none !important;
            border: 1px solid #000 !important;
            border-radius: 0 !important;
            margin: 18px 0 30px !important;
        }
        .bhw-referral-layout-page .report-table-scroll {
            overflow: visible !important;
            max-height: none !important;
        }
        .bhw-referral-layout-page #reportTable {
            min-width: unset !important;
            font-size: 10pt !important;
        }
        .bhw-referral-layout-page #reportTable th,
        .bhw-referral-layout-page #reportTable td {
            border: 1px solid #000 !important;
            color: #000 !important;
            background: transparent !important;
            padding: 6px 8px !important;
        }
        /* Title centered */
        .title { text-align: center !important; }
        .ph-line-4, .print-sub { text-align: center; }
        /* Hide charts */
        .chart-controls-panel, .referral-charts-grid, .chart,
        canvas { display: none !important; }
        /* Remove thead color */
        .bhw-referral-layout-page #reportTable thead tr { background: #fff !important; }
        .bhw-referral-layout-page #reportTable th { background: #fff !important; color: #000 !important; }
        .status-breakdown-table thead tr { background: #fff !important; }
        .status-breakdown-table thead th { background: #fff !important; color: #000 !important; }
        /* Consistent font */
        body, table, th, td, #generated_by, .sig-label, .sig-name, .sig-title,
        .ph-line-4, .print-sub { font-family: Arial, sans-serif !important; }
        /* Signature */
        .bhw-referral-layout-page #generated_by { margin: 50mm 0 0 10mm !important; }
        #generated_by .sig-label { font-size: 11px; margin-bottom: 60px; display: block; }
        #generated_by .sig-block { display: inline-block; text-align: center; }
        #generated_by .sig-line { display: block; border: none; border-top: 1.5px solid #000; width: 100%; margin: 0 0 4px; }
        #generated_by .sig-name { font-weight: 700; font-size: 12pt; white-space: nowrap; }
        #generated_by .sig-title { font-size: 11pt; }
    }

    @media print {
        .kv-table th, .kv-table td,
        .status-breakdown-table th, .status-breakdown-table td {
            border: 1px solid #000 !important;
            background: transparent !important;
        }
        .status-breakdown-table thead th { background: #fff !important; color: #000 !important; }
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
            <span class="brand-name">Hello BHW</span>
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

    <main class="bhw-referral-layout-page">
        <div class="head-title">
            <div class="left">
                <h1>Referrals</h1>
            </div>
        </div>

        <br>

        <div class="history-container">

            <!-- ─── Filter Form Card ─── -->
            <form method="GET" class="filter-form" id="filterForm">

                <h2>Referral Report — BHS <?php echo htmlspecialchars($barangayName); ?></h2>

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
                            $url = $_GET; unset($url[$param]);
                            $query = http_build_query($url);
                            echo '<span class="filter-tag">';
                            echo $display;
                            echo ' <a href="?' . $query . '" title="Remove filter">&times;</a>';
                            echo '</span>';
                        }

                        if ($from_date) renderTag('From', 'from_date', prettyDate($from_date));
                        if ($to_date)   renderTag('To',   'to_date',   prettyDate($to_date));
                        if ($sex)        renderTag('Sex',  'sex',       $sex);
                        if ($status)     renderTag('Status', 'status',  $status);
                        if ($bhw_id) {
                            $bhw_name = '';
                            foreach ($bhws as $bhw) { if ($bhw['user_id'] == $bhw_id) { $bhw_name = $bhw['full_name']; break; } }
                            renderTag('BHW', 'bhw', $bhw_name ?: $bhw_id);
                        }
                        if ($age_group) {
                            $age_labels = ['child'=>'Child (0–12)','teen'=>'Teen (13–19)','adult'=>'Adult (20–59)','senior'=>'Senior (60+)'];
                            renderTag('Age Group', 'age_group', $age_labels[$age_group] ?? ucfirst($age_group));
                        }

                        if (!$from_date && !$to_date && !$sex && !$age_group && !$status && !$bhw_id) {
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
                                    <label for="status">Referral Status</label>
                                    <select name="status" id="status" class="form-control">
                                        <option value="">All</option>
                                        <option value="Pending"     <?= $referral_status==='Pending'     ? 'selected':'' ?>>Pending</option>
                                        <option value="Completed"   <?= $referral_status==='Completed'   ? 'selected':'' ?>>Completed</option>
                                        <option value="Uncompleted" <?= $referral_status==='Uncompleted' ? 'selected':'' ?>>Uncompleted</option>
                                        <option value="Canceled"    <?= $referral_status==='Canceled'    ? 'selected':'' ?>>Canceled</option>
                                    </select>
                                </div>
                                <div class="form-item">
                                    <label for="bhw">Referred By</label>
                                    <select name="bhw" id="bhw" class="form-control">
                                        <option value="">All BHWs</option>
                                        <?php foreach ($bhws as $bhw): ?>
                                        <option value="<?= $bhw['user_id'] ?>" <?= $bhw['user_id']==$bhw_id ? 'selected':'' ?>>
                                            <?= htmlspecialchars($bhw['full_name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn" id="closeFilterModal">Cancel</button>
                            <button type="submit" class="btn-submit">Apply Filters</button>
                        </div>
                    </div>
                </div>

            </form><!-- /filter-form -->

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
                            <div class="ph-line-3"><?= htmlspecialchars($barangayName) ?></div>
                        </div>
                        <img src="../../img/mho_logo.png" alt="Right Logo" class="print-logo">
                    </div>
                    <hr class="print-rule">

                    <!-- Report title (print only) -->
                    <div class="title">
                        <div class="ph-line-4">BHS REFERRAL REPORT</div>
                        <div class="print-sub">
                            (<?php
                            $filters = [];
                            if ($from_date || $to_date) {
                                $rf = $from_date ? date("F j, Y", strtotime($from_date)) : '';
                                $rt = $to_date   ? date("F j, Y", strtotime($to_date))   : '';
                                $filters[] = "<strong>" . trim($rf . ($rt ? " — $rt" : '')) . "</strong>";
                            }
                            if ($referral_status) $filters[] = "Status: <strong>" . htmlspecialchars($referral_status) . "</strong>";
                            if ($bhw_id) {
                                $bn = '';
                                foreach ($bhws as $b) { if ($b['user_id'] == $bhw_id) { $bn = $b['full_name']; break; } }
                                $filters[] = "Referred by: <strong>" . htmlspecialchars($bn) . "</strong>";
                            }
                            if ($sex) $filters[] = "Sex: <strong>" . htmlspecialchars($sex) . "</strong>";
                            if ($age_group) {
                                $al = ['child'=>'Child (0–12)','teen'=>'Teen (13–19)','adult'=>'Adult (20–59)','senior'=>'Senior (60+)'];
                                $filters[] = "Age Group: <strong>" . ($al[$age_group] ?? htmlspecialchars($age_group)) . "</strong>";
                            }
                            echo $filters ? implode(" &nbsp;|&nbsp; ", $filters) : "All Records";
                            ?>)
                        </div>
                    </div>

                    <!-- ─── Chart Controls ─── -->
                    <div class="chart chart-controls-panel">
                        <h3>Charts</h3>
                        <div class="chart-toggle-group">
                            <label><input type="checkbox" id="toggleReferralChart"> Referrals vs. Visits Without Referral</label>
                        </div>
                    </div>

                    <!-- ─── Charts Row ─── -->
                    <div id="referralChartGrid" class="referral-charts-grid single-chart">
                        <!-- Referral vs No-Referral Pie (toggle) -->
                        <div id="referralChart" class="chart chart-animated is-hidden" style="display:none;">
                            <h3 style="font-size:15px;font-weight:700;color:var(--navy);margin-bottom:12px;">Referrals vs. Visits Without Referral</h3>
                            <canvas id="referralPieChart"></canvas>
                        </div>

                        <!-- Status Distribution Pie (always visible) -->
                        <div class="chart">
                            <h3 style="font-size:15px;font-weight:700;color:var(--navy);margin-bottom:12px;">Referral Status Distribution</h3>
                            <canvas id="statusPieChart"></canvas>
                        </div>
                    </div>

                    <!-- ─── Report Table ─── -->
                    <?php if ($rows): ?>
                    <div class="report-table-container">
                        <div class="report-table-scroll">
                            <table id="reportTable">
                                <thead>
                                    <tr>
                                        <th data-type="date">Referral Date<span class="sort-indicator"></span></th>
                                        <th data-type="string">Patient<span class="sort-indicator"></span></th>
                                        <th data-type="string">Sex<span class="sort-indicator"></span></th>
                                        <th data-type="number">Age<span class="sort-indicator"></span></th>
                                        <th data-type="date">Visit Date<span class="sort-indicator"></span></th>
                                        <th data-type="string">Chief Complaints<span class="sort-indicator"></span></th>
                                        <th data-type="string">Status<span class="sort-indicator"></span></th>
                                        <th data-type="string">Referred By<span class="sort-indicator"></span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row):
                                        $statusKey = strtolower($row['referral_status']);
                                        $statusClass = match($statusKey) {
                                            'pending'     => 'status-pending',
                                            'completed'   => 'status-completed',
                                            'uncompleted' => 'status-uncompleted',
                                            'canceled'    => 'status-canceled',
                                            default       => ''
                                        };
                                    ?>
                                    <tr>
                                        <td data-label="Referral Date"><?= prettyDate($row['referral_date']) ?></td>
                                        <td data-label="Patient"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                        <td data-label="Sex"><?= htmlspecialchars($row['sex']) ?></td>
                                        <td data-label="Age"><?= htmlspecialchars($row['age']) ?></td>
                                        <td data-label="Visit Date"><?= prettyDate($row['visit_date']) ?></td>
                                        <td data-label="Chief Complaints"><?= htmlspecialchars($row['chief_complaints']) ?></td>
                                        <td data-label="Status">
                                            <span class="referral-status <?= $statusClass ?>">
                                                <?= htmlspecialchars($row['referral_status']) ?>
                                            </span>
                                        </td>
                                        <td data-label="Referred By"><?= htmlspecialchars($row['bhw_name']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <br>

                    <!-- ─── Summary Section ─── -->
                    <div class="summary-container">
                        <div class="summary">
                            <h3 class="summary-title"><i class="bx bx-file"></i> Report Details</h3>

                            <table class="kv-table">
                                <tbody>
                                    <tr>
                                        <th>Report Generated On</th>
                                        <td class="summary-mono"><?= prettyDate(date('c'), true) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Total Referrals</th>
                                        <td><strong><?= $referral_count ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>Visits With Referral</th>
                                        <td><?= $referral_count ?></td>
                                    </tr>
                                    <tr>
                                        <th>Visits Without Referral</th>
                                        <td><?= $visits_without_referral ?></td>
                                    </tr>
                                </tbody>
                            </table>

                            <table class="status-breakdown-table">
                                <thead>
                                    <tr>
                                        <th>Referral Status</th>
                                        <th>Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="referral-status status-pending">Pending</span></td>
                                        <td><?= $status_counts['Pending'] ?></td>
                                    </tr>
                                    <tr>
                                        <td><span class="referral-status status-completed">Completed</span></td>
                                        <td><?= $status_counts['Completed'] ?></td>
                                    </tr>
                                    <tr>
                                        <td><span class="referral-status status-uncompleted">Uncompleted</span></td>
                                        <td><?= $status_counts['Uncompleted'] ?></td>
                                    </tr>
                                    <tr>
                                        <td><span class="referral-status status-canceled">Canceled</span></td>
                                        <td><?= $status_counts['Canceled'] ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php else: ?>
                    <div class="no-records">
                        <i class="bx bx-file-blank" style="font-size:48px;color:var(--grey-500);display:block;margin-bottom:12px;"></i>
                        No referrals found for the selected filters.
                    </div>
                    <?php endif; ?>

                    <br><br>
                    <span id="generated_by"></span>

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
/* ─── Chart data ─── */
const referralCount          = <?= $referral_count ?>;
const visitsWithoutReferral  = <?= $visits_without_referral ?>;
const statusCounts = {
    Completed:   <?= $status_counts['Completed']   ?>,
    Uncompleted: <?= $status_counts['Uncompleted'] ?>,
    Pending:     <?= $status_counts['Pending']     ?>,
    Canceled:    <?= $status_counts['Canceled']    ?>
};

const chartFont = { family: "'Plus Jakarta Sans', sans-serif" };

document.addEventListener('DOMContentLoaded', () => {

    let referralPieChartInstance = null;
    let statusPieChartInstance = null;

    /* Referral vs No-Referral */
    if (referralCount + visitsWithoutReferral > 0) {
        referralPieChartInstance = new Chart(document.getElementById('referralPieChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: ['With Referral', 'Without Referral'],
                datasets: [{
                    data: [referralCount, visitsWithoutReferral],
                    backgroundColor: ['#0d2d52', '#bfdbfe'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 850, easing: 'easeOutQuart' },
                layout: { padding: 6 },
                plugins: {
                    legend: { position: 'bottom', labels: { font: chartFont } },
                    title: { display: false }
                }
            }
        });
    }

    /* Status distribution */
    const totalStatus = Object.values(statusCounts).reduce((a,b) => a+b, 0);
    if (totalStatus > 0) {
        statusPieChartInstance = new Chart(document.getElementById('statusPieChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: ['Completed', 'Uncompleted', 'Pending', 'Canceled'],
                datasets: [{
                    data: [statusCounts.Completed, statusCounts.Uncompleted, statusCounts.Pending, statusCounts.Canceled],
                    backgroundColor: ['#15803d', '#b91c1c', '#1d4ed8', '#9ca3af'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 850, easing: 'easeOutQuart' },
                layout: { padding: 6 },
                plugins: {
                    legend: { position: 'bottom', labels: { font: chartFont } },
                    title: { display: false }
                }
            }
        });
    }

    /* Chart toggle */
    const cb = document.getElementById('toggleReferralChart');
    const el = document.getElementById('referralChart');
    const grid = document.getElementById('referralChartGrid');

    function replayChartEntrance(target) {
        target.classList.remove('chart-entered');
        void target.offsetWidth;
        target.classList.add('chart-entered');
    }

    function resizeVisibleCharts() {
        window.setTimeout(() => {
            if (referralPieChartInstance) referralPieChartInstance.resize();
            if (statusPieChartInstance) statusPieChartInstance.resize();
        }, 360);
    }

    function syncReferralChartLayout(animate = true) {
        if (!cb || !el) return;
        const showReferralChart = cb.checked;

        if (showReferralChart) {
            el.style.display = 'block';
            requestAnimationFrame(() => {
                if (grid) grid.classList.remove('single-chart');
                el.classList.remove('is-hidden');
                if (animate) {
                    replayChartEntrance(el);
                    const statusChart = grid ? grid.querySelector('.chart:not(#referralChart)') : null;
                    if (statusChart) replayChartEntrance(statusChart);
                }
                resizeVisibleCharts();
            });
        } else {
            el.classList.add('is-hidden');
            window.setTimeout(() => {
                el.style.display = 'none';
                if (grid) grid.classList.add('single-chart');
                const statusChart = grid ? grid.querySelector('.chart:not(#referralChart)') : null;
                if (animate && statusChart) replayChartEntrance(statusChart);
                resizeVisibleCharts();
            }, 320);
        }
    }

    if (cb && el) {
        cb.addEventListener('change', () => syncReferralChartLayout(true));
        syncReferralChartLayout(false);
    }
});
</script>

<script>
/* ─── Table sort ─── */
(function() {
    const table = document.getElementById('reportTable');
    if (!table) return;
    const thead = table.tHead;
    const tbody = table.tBodies[0];

    function parseDate(v) {
        const t = (v||'').trim();
        if (/^\d{4}-\d{2}-\d{2}/.test(t)) return new Date(t.replace(' ','T'));
        const d = new Date(t); return isNaN(d.getTime()) ? null : d;
    }
    function detectType(idx) {
        const th = thead.querySelectorAll('th')[idx];
        if (th?.dataset?.type) return th.dataset.type;
        for (const tr of tbody.rows) {
            const txt = (tr.cells[idx]?.textContent||'').trim();
            if (!txt) continue;
            if (parseDate(txt)) return 'date';
            if (!isNaN(txt.replace(/,/g,''))) return 'number';
            return 'string';
        }
        return 'string';
    }
    function getCellValue(tr, idx, type) {
        const raw = (tr.cells[idx]?.textContent||'').trim();
        if (type==='number') { const n=parseFloat(raw.replace(/,/g,'')); return isNaN(n)?-Infinity:n; }
        if (type==='date')   { const d=parseDate(raw); return d?d.getTime():-Infinity; }
        return raw.toLowerCase();
    }
    function sortBy(idx, dir) {
        const type = detectType(idx);
        const rows = [...tbody.rows].sort((a,b) => {
            const va=getCellValue(a,idx,type), vb=getCellValue(b,idx,type);
            return va<vb?(dir==='asc'?-1:1):va>vb?(dir==='asc'?1:-1):0;
        });
        const frag = document.createDocumentFragment();
        rows.forEach(r=>frag.appendChild(r));
        tbody.appendChild(frag);
    }

    [...thead.querySelectorAll('th')].forEach((th, idx) => {
        th.addEventListener('click', () => {
            const nextDir = th.classList.contains('is-sorted-asc') ? 'desc' : 'asc';
            [...thead.querySelectorAll('th')].forEach(h=>h.classList.remove('is-sorted-asc','is-sorted-desc'));
            th.classList.add(nextDir==='asc'?'is-sorted-asc':'is-sorted-desc');
            sortBy(idx, nextDir);
        });
    });

    /* Default: Referral Date desc */
    const def = thead.querySelectorAll('th')[0];
    if (def) { def.classList.add('is-sorted-desc'); sortBy(0,'desc'); }
})();
</script>

<script>
/* ─── Filter Modal ─── */
document.getElementById('openFilterModal').onclick  = () => document.getElementById('filterModal').style.display = 'block';
document.getElementById('closeFilterModal').onclick = () => document.getElementById('filterModal').style.display = 'none';

document.getElementById('openFilterModal').addEventListener('click', () => {
    setTimeout(() => {
        ['from_date','to_date'].forEach(id => {
            const el = document.getElementById(id);
            if (el._flatpickr) el._flatpickr.destroy();
            flatpickr('#' + id, { dateFormat:'Y-m-d', allowInput:true, disableMobile:true });
        });
    }, 100);
});

document.getElementById('filterForm').onsubmit = () => {
    document.getElementById('filterModal').style.display = 'none';
    return true;
};

window.addEventListener('click', e => {
    const m = document.getElementById('filterModal');
    if (e.target === m) m.style.display = 'none';
    const lm = document.getElementById('logoutModal');
    if (e.target === lm) closeModal();
});
</script>

<script>
/* ─── Excel Export ─── */
function exportTableToExcel(tableID, filename = 'Referral Summary Report') {
    try {
        const tempDiv = document.createElement('div');
        tempDiv.style.cssText = 'position:absolute;left:-9999px;top:-9999px;';

        const summary = document.querySelector('.summary-container');
        if (summary) { const cl=summary.cloneNode(true); cl.querySelectorAll('script').forEach(s=>s.remove()); tempDiv.appendChild(cl); }

        const orig = document.getElementById(tableID);
        if (!orig) { alert('Table not found!'); return; }
        tempDiv.appendChild(orig.cloneNode(true));
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
    clone.querySelectorAll('canvas, .chart-controls-panel, .chart, .referral-charts-grid').forEach(el => el.remove());
    const hInClone = clone.querySelector('.print-letterhead, .print-header');
    if (hInClone) hInClone.remove();

    const w = window.open('', '', 'height=900,width=1100');
    if (!w) { alert('Please allow pop-ups to print this report.'); return; }
    w.document.write(`<html><head><title>Print Report</title><meta charset="utf-8"/>
    <style>
      body{font-family:Arial,sans-serif;font-size:13px;color:#000;}
      table{width:100%;border-collapse:collapse;font-family:Arial,sans-serif;font-size:12px;}
      th,td{border:1px solid #000;padding:5px 8px;text-align:left;font-family:Arial,sans-serif;}
      thead tr{background:#fff!important;}
      thead th{background:#fff!important;color:#000!important;font-weight:700;}
      .print-letterhead{display:grid;grid-template-columns:64px auto 64px;align-items:center;column-gap:60px;margin:0 auto 10px;text-align:center;width:fit-content;}
      .print-logo{width:64px;height:64px;object-fit:contain;}
      .print-heading{font-family:Arial,sans-serif;}
      .print-heading .ph-line-1{font-size:12pt;font-weight:500;}
      .print-heading .ph-line-2{font-size:14pt;font-weight:800;}
      .print-heading .ph-line-3{font-size:12pt;font-weight:500;}
      .title{text-align:center;margin:8px 0;font-family:Arial,sans-serif;}
      .ph-line-4{font-size:12pt;font-weight:800;margin-top:4px;text-align:center;font-family:Arial,sans-serif;}
      .print-sub{font-size:11pt;margin-top:4px;text-align:center;font-family:Arial,sans-serif;}
      .print-rule{height:1px;border:0;background:#ccc;margin:8px 0 12px;}
      .chart-controls-panel,.btn-export,.btn-print,.selected-filters,
      .chart,.referral-charts-grid,canvas{display:none!important;}
      .referral-status{display:inline-block;padding:2px 8px;border-radius:10px;font-weight:700;font-size:11pt;}
      .status-pending{color:#1d4ed8;}.status-completed{color:#15803d;}.status-uncompleted{color:#b91c1c;}.status-canceled{color:#6b7280;}
      #generated_by{margin-top:48px;font-family:Arial,sans-serif;}
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
    .then(r=>r.json())
    .then(data => {
        const fullName = (data && data.full_name) ? data.full_name : '';
        const sn = document.getElementById('sidebarUserName');
        if (sn) sn.textContent = fullName || 'BHW User';
        const gb = document.getElementById('generated_by');
        if (gb) {
            const name = fullName || '________________';
            gb.innerHTML = `<div class="sig-label">Report Generated by:</div><div class="sig-block"><span class="sig-line"></span><div class="sig-name"></div><div class="sig-title">Barangay Health Worker</div></div>`;
            gb.querySelector('.sig-name').textContent = name;
            const nameEl = gb.querySelector('.sig-name');
            const lineEl = gb.querySelector('.sig-line');
            requestAnimationFrame(() => { lineEl.style.width = nameEl.offsetWidth + 'px'; });
        }
    })
    .catch(() => {
        const sn = document.getElementById('sidebarUserName');
        if (sn) sn.textContent = 'BHW User';
        const gb = document.getElementById('generated_by');
        if (gb) gb.innerHTML = `<div class="sig-label">Report Generated by:</div><div class="sig-block"><span class="sig-line" style="width:180px;"></span><div class="sig-name">________________</div><div class="sig-title">Barangay Health Worker</div></div>`;
    });

/* ─── Logout ─── */
function confirmLogout() { document.getElementById('logoutModal').classList.add('open'); return false; }
function closeModal()    { document.getElementById('logoutModal').classList.remove('open'); }
function proceedLogout() { window.location.href='../../ADMIN/php/logout'; }

/* ─── Session check ─── */
fetch('../php/getUserId.php')
    .then(r=>r.json())
    .then(data => { if (data.error) window.location.href='../auth/role'; })
    .catch(() => window.location.href='../auth/role');
</script>

<script>
/* ─── Sidebar toggle ─── */
(function() {
    const sidebar = document.getElementById('sidebar');
    const toggle  = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');
    if (!sidebar||!toggle||!overlay) return;

    function isMobile() { return window.innerWidth <= 768; }
    function closeMobile() { sidebar.classList.remove('mobile-open'); overlay.classList.remove('active'); document.body.style.overflow=''; }

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