<?php
// Connect to DB
require '../../php/db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../role");
    exit;
}

$userId = $_SESSION['user_id'];// or however you store the logged-in user's ID

// Fetch user info
$stmt = $pdo->prepare("SELECT full_name, rhu FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$rhu = $user ? $user['rhu'] : 'N/A';
$username = $user ? $user['full_name'] : 'N/A';




// Initialize filters
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$sex = $_GET['sex'] ?? '';
$age_group = $_GET['age_group'] ?? '';
$purok = $_GET['purok'] ?? ''; // Use 'purok' for address filtering
$bmi = $_GET['bmi'] ?? '';
$medication = $_GET['medication'] ?? '';


// Build query with filters
$sql = "SELECT v.*, p.first_name, p.last_name, p.age, p.sex, p.address FROM patient_assessment v 
        JOIN patients p ON v.patient_id = p.patient_id 
        JOIN users u_rec ON v.recorded_by = u_rec.user_id
        WHERE u_rec.rhu = :rhu";  // Only show consultations from same RHU

$params['rhu'] = $rhu; // First parameter is the current user's RHU



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

// Sort visits from latest to oldest by visit_date
usort($visits, function($a, $b) {
    return strtotime($b['visit_date']) - strtotime($a['visit_date']);
});

// Calculate summary data
$total_patients = count(array_unique(array_column($visits, 'patient_id')));

// Count unique patients by sex for the current filtered visits
$sex_counts = ['Male' => 0, 'Female' => 0];
$unique_patients = [];
foreach ($visits as $visit) {
    $pid = $visit['patient_id'];
    if (!isset($unique_patients[$pid])) {
        $unique_patients[$pid] = $visit['sex'];
    }
}
foreach ($unique_patients as $sex_value) {
    if (isset($sex_counts[$sex_value])) $sex_counts[$sex_value]++;
}

// Prepare age group counts based on filtered visits (unique patients)
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

// Prepare BMI category counts for unique patients (latest visit per patient)
$bmi_categories = [
    'Underweight' => 0,
    'Normal' => 0,
    'Overweight' => 0,
    'Class 1' => 0,
    'Class 2' => 0,
    'Class 3' => 0
];
$latest_bmi_per_patient = [];
foreach ($visits as $visit) {
    $pid = $visit['patient_id'];
    // Only count the latest visit per patient for BMI
    if (!isset($latest_bmi_per_patient[$pid]) || strtotime($visit['visit_date']) > strtotime($latest_bmi_per_patient[$pid]['visit_date'])) {
        $latest_bmi_per_patient[$pid] = $visit;
    }
}
foreach ($latest_bmi_per_patient as $visit) {
    // Skip if BMI is empty, null, or not numeric
    if (!isset($visit['bmi']) || $visit['bmi'] === '' || !is_numeric($visit['bmi'])) {
        continue;
    }
    $bmi_value = floatval($visit['bmi']);
    if ($bmi_value < 18.5) {
        $bmi_categories['Underweight']++;
    } elseif ($bmi_value >= 18.5 && $bmi_value <= 24.9) {
        $bmi_categories['Normal']++;
    } elseif ($bmi_value >= 25 && $bmi_value <= 29.9) {
        $bmi_categories['Overweight']++;
    } elseif ($bmi_value >= 30 && $bmi_value <= 34.9) {
        $bmi_categories['Class 1']++;
    } elseif ($bmi_value >= 35 && $bmi_value <= 39.9) {
        $bmi_categories['Class 2']++;
    } elseif ($bmi_value >= 40) {
        $bmi_categories['Class 3']++;
    }
}

// Prepare barangay counts based on filtered visits (unique patients)
$barangay_counts = [];
$unique_patients_address = [];
foreach ($visits as $visit) {
    $pid = $visit['patient_id'];
    if (!isset($unique_patients_address[$pid])) {
        // Extract barangay from the address
        $address_parts = explode(' - ', $visit['address']);
        if (isset($address_parts[1])) {
            $barangay_words = explode(' ', $address_parts[1]);
            $barangay = $barangay_words[1] ?? $address_parts[1];
        } else {
            $barangay = 'Others';
        }
        $barangay_counts[$barangay] = ($barangay_counts[$barangay] ?? 0) + 1;
        $unique_patients_address[$pid] = true;
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
            ':action' => "Generated RHU Patient Visit Summary Report",
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
    <title>Patient Summary Report</title>

    <style>
        /* Page-level UI consistency with the updated report design */
        .patient-report-page .filter-form {
            background: var(--surface, #ffffff);
            border: 1px solid var(--border, #dde4ef);
            border-radius: var(--r-lg, 16px);
            padding: 28px 32px 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm, 0 2px 8px rgba(13,45,82,.09));
        }

        .patient-report-page .filter-form h2 {
            font-size: 17px;
            font-weight: 700;
            color: var(--navy, #0d2d52);
            letter-spacing: -.2px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .patient-report-page .filter-form h2::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 18px;
            background: var(--blue, #1c6fba);
            border-radius: 2px;
            flex-shrink: 0;
        }

        .patient-report-page .form-submit {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            margin-top: 0;
        }

        .patient-report-page .selected-filters {
            margin-top: 20px;
        }

        .patient-report-page .selected-filters h3 {
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

        .patient-report-page .filter-tag {
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
            transition: background .15s !important;
        }

        .patient-report-page .filter-tag:hover {
            background: #dceeff !important;
        }

        .patient-report-page .filter-tag a {
            color: var(--grey-500, #8c96aa) !important;
            font-weight: 700 !important;
            font-size: 14px !important;
            line-height: 1;
            transition: color .15s !important;
            text-decoration: none !important;
        }

        .patient-report-page .filter-tag a:hover {
            color: var(--red, #e53e3e) !important;
        }

        .patient-report-page .print-area {
            background: var(--white, #ffffff);
            border: 1px solid var(--border, #dde4ef);
            border-radius: var(--r-lg, 16px);
            padding: 28px 32px;
            box-shadow: var(--shadow-sm, 0 2px 8px rgba(13,45,82,.09));
        }

        .patient-report-page .chart-controls-panel {
            background: var(--grey-100, #f8f9fc);
            border: 1px solid var(--border-soft, #edf0f7);
            border-radius: var(--r-md, 10px);
            padding: 16px 20px;
            margin-bottom: 24px;
        }

        .patient-report-page .chart-controls-panel h3 {
            font-size: 13px;
            font-weight: 700;
            color: var(--grey-700, #4a5568);
            text-transform: uppercase;
            letter-spacing: .07em;
            margin-bottom: 12px;
        }

        .patient-report-page .chart-toggle-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .patient-report-page .chart-toggle-group label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 14px;
            background: var(--white, #ffffff);
            border: 1.5px solid var(--border, #dde4ef);
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            color: var(--grey-700, #4a5568);
            cursor: pointer;
            transition: border-color .18s, background .18s, color .18s;
            user-select: none;
        }

        .patient-report-page .chart-toggle-group label:has(input:checked) {
            background: var(--navy, #0d2d52);
            border-color: var(--navy, #0d2d52);
            color: var(--white, #ffffff);
        }

        .patient-report-page .chart-toggle-group input[type="checkbox"] {
            width: 14px;
            height: 14px;
            accent-color: var(--white, #ffffff);
            cursor: pointer;
        }

        .patient-report-page .patient-chart-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(280px, 1fr));
            gap: 24px;
            align-items: stretch;
            margin: 24px 0 28px;
            transition: grid-template-columns .28s ease;
        }

        .patient-report-page .patient-chart-grid.single-chart {
            grid-template-columns: minmax(280px, 680px);
            justify-content: center;
        }

        .patient-report-page .chart-card {
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 20px;
            text-align: center;
            background: var(--white, #ffffff);
            border: 1px solid var(--border-soft, #edf0f7);
            border-radius: var(--r-md, 10px);
            box-shadow: var(--shadow-xs, 0 1px 3px rgba(13,45,82,.07));
            min-height: 340px;
            opacity: 0;
            transform: translateY(10px) scale(.985);
            transition: opacity .26s ease, transform .26s ease, box-shadow .18s ease;
            will-change: opacity, transform;
        }

        .patient-report-page .chart-card.is-visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .patient-report-page .chart-card:hover {
            box-shadow: var(--shadow-sm, 0 2px 8px rgba(13,45,82,.09));
        }

        .patient-report-page .chart-card canvas {
            width: 100% !important;
            height: 260px !important;
            max-height: 260px;
        }

        .patient-report-page .chart-card.chart-card-sm canvas {
            height: 250px !important;
            max-height: 250px;
        }

        @media (max-width: 980px) {
            .patient-report-page .patient-chart-grid,
            .patient-report-page .patient-chart-grid.single-chart {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .patient-report-page .chart-card {
                min-height: 300px;
                padding: 16px;
            }

            .patient-report-page .chart-card canvas,
            .patient-report-page .chart-card.chart-card-sm canvas {
                height: 230px !important;
                max-height: 230px;
            }
        }

        .patient-report-page .chart-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--navy, #0d2d52);
            margin-bottom: 12px;
        }

        .patient-report-page .report-table-container {
            width: 100%;
            border-radius: var(--r-lg, 16px);
            border: 1px solid var(--border, #dde4ef);
            overflow: hidden;
            box-shadow: var(--shadow-md, 0 4px 20px rgba(13,45,82,.10));
            background: var(--white, #ffffff);
            margin-top: 24px;
        }

        .patient-report-page .report-table-scroll {
            width: 100%;
            overflow-x: auto;
            max-height: 560px;
            overflow-y: auto;
        }

        .patient-report-page #reportTable {
            width: 100%;
            min-width: 960px;
            border-collapse: collapse;
            font-size: 13.5px;
        }

        .patient-report-page #reportTable thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .patient-report-page #reportTable thead tr {
            background: linear-gradient(135deg, var(--navy, #0d2d52) 0%, var(--navy-mid, #1a4477) 100%);
        }

        .patient-report-page #reportTable th {
            padding: 13px 14px;
            text-align: left;
            font-size: 11.5px;
            font-weight: 700;
            color: rgba(255,255,255,.92);
            text-transform: uppercase;
            letter-spacing: .07em;
            white-space: nowrap;
            cursor: pointer;
            user-select: none;
            border-right: 1px solid rgba(255,255,255,.08);
            transition: background .15s;
            position: relative;
        }

        .patient-report-page #reportTable th:last-child {
            border-right: none;
        }

        .patient-report-page #reportTable th:hover {
            background: rgba(255,255,255,.08);
        }

        .patient-report-page #reportTable th .sort-indicator {
            margin-left: 5px;
            font-size: 10px;
            opacity: .65;
            display: inline-block;
            transition: opacity .15s;
        }

        .patient-report-page #reportTable th:hover .sort-indicator {
            opacity: 1;
        }

        .patient-report-page #reportTable th.is-sorted-asc,
        .patient-report-page #reportTable th.is-sorted-desc {
            background: rgba(255,255,255,.12);
            color: #fff;
        }

        .patient-report-page #reportTable th.is-sorted-asc .sort-indicator::after { content: "▲"; }
        .patient-report-page #reportTable th.is-sorted-desc .sort-indicator::after { content: "▼"; }

        .patient-report-page #reportTable tbody tr {
            border-bottom: 1px solid var(--border-soft, #edf0f7);
            transition: background .15s;
        }

        .patient-report-page #reportTable tbody tr:nth-child(odd) {
            background: var(--white, #ffffff);
        }

        .patient-report-page #reportTable tbody tr:nth-child(even) {
            background: var(--grey-100, #f8f9fc);
        }

        .patient-report-page #reportTable tbody tr:hover {
            background: var(--blue-pale, #f0f6ff);
        }

        .patient-report-page #reportTable td {
            padding: 11px 14px;
            color: var(--grey-700, #4a5568);
            font-size: 13.5px;
            vertical-align: middle;
            border-right: 1px solid var(--border-soft, #edf0f7);
            white-space: nowrap;
        }

        .patient-report-page #reportTable td:last-child {
            border-right: none;
        }

        .patient-report-page #reportTable td:nth-child(1) {
            font-family: var(--font-mono, "DM Mono", monospace);
            font-size: 12.5px;
            color: var(--grey-500, #8c96aa);
        }

        .patient-report-page #reportTable td:nth-child(2) {
            font-weight: 600;
            color: var(--navy, #0d2d52);
        }

        .patient-report-page #reportTable td:nth-child(8) {
            white-space: normal;
            word-break: break-word;
            min-width: 180px;
        }

        .patient-report-page .no-records {
            text-align: center;
            padding: 48px 24px;
            color: var(--grey-500, #8c96aa);
            font-size: 15px;
        }

        .patient-report-page .summary-container {
            margin-top: 32px;
        }

        .patient-report-page .summary-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--navy, #0d2d52);
            letter-spacing: -.1px;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .patient-report-page .summary-title i {
            font-size: 18px;
            color: var(--blue, #1c6fba);
        }

        .patient-report-page .summary-table,
        .patient-report-page .summary-table2 {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid var(--border, #dde4ef);
            border-radius: var(--r-lg, 16px);
            overflow: hidden;
            box-shadow: var(--shadow-sm, 0 2px 8px rgba(13,45,82,.09));
            font-size: 14px;
            background: var(--white, #ffffff);
        }

        .patient-report-page .summary-table2 {
            margin-top: 8px;
            border-radius: var(--r-md, 10px);
        }

        .patient-report-page .summary-table th,
        .patient-report-page .summary-table2 th {
            background: var(--grey-100, #f8f9fc);
            font-weight: 600;
            color: var(--navy, #0d2d52);
            padding: 14px 18px;
            text-align: left;
            border-bottom: 1px solid var(--border-soft, #edf0f7);
            border-right: 1px solid var(--border-soft, #edf0f7);
            font-size: 13.5px;
            vertical-align: top;
        }

        .patient-report-page .summary-table th {
            width: 260px;
        }

        .patient-report-page .summary-table td,
        .patient-report-page .summary-table2 td {
            color: var(--grey-700, #4a5568);
            padding: 14px 18px;
            border-bottom: 1px solid var(--border-soft, #edf0f7);
            font-size: 13.5px;
            vertical-align: top;
        }

        .patient-report-page .summary-table2 td {
            border-right: 1px solid var(--border-soft, #edf0f7);
        }

        .patient-report-page .summary-table tr:last-child th,
        .patient-report-page .summary-table tr:last-child td,
        .patient-report-page .summary-table2 tr:last-child td {
            border-bottom: none;
        }

        .patient-report-page .summary-subtitle {
            display: block;
            font-size: 12pt;
            margin: 18px 0 8px;
            color: var(--navy, #0d2d52);
        }

        .patient-report-page #generated_by {
            display: block;
            margin: 32px 0 0 4px;
            color: var(--dark, #0f1d31);
        }

        .patient-report-page #generated_by .sig-label {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--grey-500, #8c96aa);
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 20px;
        }

        .patient-report-page #generated_by .sig-line {
            display: none;
            width: 200px;
            border: 0;
            border-top: 1.5px solid var(--dark, #0f1d31);
            margin: 26px 0 6px;
        }

        .patient-report-page #generated_by .sig-name {
            font-weight: 700;
            font-size: 15px;
            color: var(--navy, #0d2d52);
            margin-top: 4px;
        }

        .patient-report-page #generated_by .sig-title {
            font-size: 12.5px;
            color: var(--grey-500, #8c96aa);
            margin-top: 2px;
        }

        .print-letterhead { display: none; }
        .title { text-align: center; display: none; }

        @media print {
            @page { size: landscape; margin: 1cm; }

            body * { visibility: hidden; }
            .print-area, .print-area * { visibility: visible; }

            .print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                border-radius: 0 !important;
            }

            nav,
            #sidebar,
            .sidebar-overlay,
            .form-submit,
            .btn-print,
            .btn-export,
            .chart-controls-panel,
            .selected-filters {
                display: none !important;
            }

            .title { display: block !important; }
            .print-letterhead { display: grid !important; }

            .print-letterhead {
                grid-template-columns: 72px auto 72px;
                align-items: center;
                justify-content: center;
                column-gap: 60px;
                margin: 0 auto 18px;
                text-align: center;
                width: fit-content;
            }

            .print-logo { width:64px; height:64px; object-fit:contain; }
            .print-heading { line-height:1.1; color:#000; }
            .print-heading .ph-line-1 { font-size:12pt; font-weight:500; margin-bottom:3px; }
            .print-heading .ph-line-2 { font-size:14pt; font-weight:500; margin-bottom:3px; }
            .print-heading .ph-line-3 { font-size:11pt; font-weight:500; margin-bottom:3px; }
            .print-sub { font-size:11pt; margin-top:4px; }
            .print-rule { height:1px; border:0; background:#cfd8e3; margin:8px 0 12px; }

            .patient-report-page .report-table-container {
                box-shadow: none !important;
                border: 1px solid #000 !important;
                border-radius: 0 !important;
                max-height: none !important;
                overflow: visible !important;
                margin-top: 18px !important;
            }

            .patient-report-page .report-table-scroll {
                overflow: visible !important;
                max-height: none !important;
            }

            .patient-report-page #reportTable {
                min-width: unset !important;
                font-size: 11pt !important;
            }

            .patient-report-page #reportTable thead tr {
                background: #e0e8f5 !important;
                print-color-adjust: exact;
            }

            .patient-report-page #reportTable th {
                color: #000 !important;
                border: 1px solid #ccc !important;
                padding: 7px 10px !important;
                font-size: 9pt !important;
                background: #d8e4f0 !important;
            }

            .patient-report-page #reportTable td {
                border: 1px solid #ddd !important;
                padding: 7px 10px !important;
                font-size: 10pt !important;
                color: #000 !important;
                background: transparent !important;
            }

            .patient-report-page .summary > h3 { display: none !important; }

            .patient-report-page .summary-table,
            .patient-report-page .summary-table2 {
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            .patient-report-page .summary-table th,
            .patient-report-page .summary-table td,
            .patient-report-page .summary-table2 th,
            .patient-report-page .summary-table2 td {
                border: 1px solid #000 !important;
                font-size: 11pt !important;
                background: transparent !important;
                color: #000 !important;
            }

            .patient-report-page #generated_by { margin: 60mm 0 0 10mm; }
            .patient-report-page #generated_by .sig-label { font-size: 12pt; }
            .patient-report-page #generated_by .sig-name  { font-size: 12pt; }
            .patient-report-page #generated_by .sig-title { font-size: 11pt; }
            .patient-report-page #generated_by .sig-line  { display: block; width: 45mm; border-top-width: 1px; margin: 10mm 0 3mm; }
        }

        @media (max-width: 768px) {
            .patient-report-page .filter-form {
                padding: 20px 18px;
            }

            .patient-report-page .print-area {
                padding: 20px 18px;
            }

            .patient-report-page #reportTable thead {
                display: none;
            }

            .patient-report-page #reportTable,
            .patient-report-page #reportTable tbody,
            .patient-report-page #reportTable tr,
            .patient-report-page #reportTable td {
                display: block;
                width: 100%;
                min-width: 0;
            }

            .patient-report-page #reportTable tr {
                margin: 0 0 12px;
                padding: 14px 14px 8px;
                border: 1px solid var(--border, #dde4ef);
                border-radius: var(--r-md, 10px);
                background: var(--white, #ffffff);
                box-shadow: var(--shadow-xs, 0 1px 3px rgba(13,45,82,.07));
            }

            .patient-report-page #reportTable td {
                border: 0;
                border-bottom: 1px solid var(--border-soft, #edf0f7);
                padding: 9px 0;
                white-space: normal;
                font-size: 13px;
            }

            .patient-report-page #reportTable td:last-child {
                border-bottom: none;
            }

            .patient-report-page #reportTable td::before {
                content: attr(data-label);
                display: block;
                font-size: 10.5px;
                font-weight: 700;
                color: var(--grey-500, #8c96aa);
                text-transform: uppercase;
                letter-spacing: .07em;
                margin-bottom: 2px;
            }

            .patient-report-page .summary-table th {
                width: 140px;
            }
        }
    </style>
</head>
<body>

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

    <main class="patient-report-page">
        <div class="head-title">
            <div class="left">
                <h1>Patient Summary</h1>
            </div>
        </div>

        <br>

        <div class="history-container">

            <!-- Filter Form -->
            <div class="filter-form">
                <h2>Patient Summary Report - <?php echo htmlspecialchars($rhu); ?></h2>

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
                    <div id="filterTags" style="display:flex;flex-wrap:wrap;gap:8px;">
                        <?php
                        // Helper for tag rendering
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
                        if ($purok) renderTag('Barangay', 'purok', $purok);
                        if ($bmi) {
                            $bmi_labels = [
                                'underweight' => 'Underweight', 'normal' => 'Normal',
                                'overweight' => 'Overweight', 'class1' => 'Class 1',
                                'class2' => 'Class 2', 'class3' => 'Class 3'
                            ];
                            renderTag('BMI', 'bmi', $bmi_labels[$bmi] ?? $bmi);
                        }

                        // If no filters, show "All"
                        if (
                            !$from_date && !$to_date && !$sex && !$age_group &&
                            !$purok && !$bmi && !$medication
                        ) {
                            echo '<span style="color:var(--grey-500);font-size:13px;">None</span>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Filter Modal -->
                <div id="filterModal" class="modal" style="display:none;">
                    <div class="modal-content" style="max-width:600px;">
                        <div class="modal-header">
                            <h3><i class="bx bx-filter-alt" style="margin-right:8px;color:var(--blue);"></i>Apply Filters</h3>
                        </div>
                        <form method="GET" id="filterForm">
                            <div class="modal-body">
                                <div class="form-row">
                                    <!-- From Date -->
                                    <div class="form-item">
                                        <label for="from_date">From:</label>
                                        <input type="date" name="from_date" id="from_date" class="form-control" value="<?= $from_date ? htmlspecialchars($from_date) : '' ?>" placeholder="Select date">
                                    </div>
                                    <!-- To Date -->
                                    <div class="form-item">
                                        <label for="to_date">To:</label>
                                        <input type="date" name="to_date" id="to_date" class="form-control" value="<?= $to_date ? htmlspecialchars($to_date) : '' ?>" placeholder="Select date">
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
                                        </select>
                                    </div>

                                    <div class="form-item">
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
                                    </div> 

                                    <div class="form-item">
                                        <label for="bmi">BMI:</label>
                                        <select name="bmi" id="bmi" class="form-control">
                                            <option value="">All</option> 
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
                            <div class="modal-footer">
                                <button type="button" class="btn" id="closeFilterModal">Cancel</button>
                                <button type="submit" class="btn-submit">Apply Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
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
                            <h2>Patients Summary Report</h2>
                            <div class="print-sub">
                                (<?php
                                    $filters = [];
                                    if ($from_date || $to_date) {
                                        $readable_from = $from_date ? date("F j, Y", strtotime($from_date)) : '';
                                        $readable_to   = $to_date ? date("F j, Y", strtotime($to_date)) : '';

                                        // Combine them in a single display
                                        $filters[] = "<strong>" . trim($readable_from . ($readable_to ? " — " . $readable_to : '')) . "</strong>";
                                    } 
                                    if ($sex) $filters[] = "Sex: <strong>" . htmlspecialchars($sex) . "</strong>";
                                    if ($age_group) {
                                        $age_labels = [
                                            'child' => 'Child (0–12)',
                                            'teen' => 'Teen (13–19)',
                                            'adult' => 'Adult (20–59)',
                                            'senior' => 'Senior (60+)'
                                        ];
                                        $filters[] = "Age Group: <strong>" . ($age_labels[$age_group] ?? htmlspecialchars($age_group)) . "</strong>";
                                    }
                                    if ($purok) $filters[] = "Barangay: <strong>" . htmlspecialchars($purok) . "</strong>";
                                    if ($bmi) $filters[] = "BMI: <strong>" . htmlspecialchars($bmi) . "</strong>";
                                    echo $filters ? implode("&nbsp; | &nbsp;", $filters) : "All Records";
                                ?>)
                            </div>
                        </div>

                        <!-- Chart Visibility Controls -->
                        <div class="chart-controls-panel">
                            <h3>Charts:</h3>
                            <div class="chart-toggle-group">
                                <label><input type="checkbox" id="toggleSexChart"> Show Patients by Sex</label>
                                <label><input type="checkbox" id="toggleAgeGroupChart"> Show Age Group</label>
                                <label><input type="checkbox" id="toggleBMIChart"> Show Patients by BMI</label>
                            </div>
                        </div>

                        <!-- Charts Grid -->
                        <div id="patientChartGrid" class="patient-chart-grid single-chart">
                            <!-- Pie Chart Section -->
                            <div id="sexChart" class="chart-card chart-card-sm" style="display:none;">
                                <h3 class="chart-title">Patients by Sex</h3>
                                <canvas id="sexPieChart"></canvas>
                                <p id="noSexDataMessage" style="display:none;color:var(--grey-500);margin-top:10px;font-size:13px;">No data available</p>
                            </div>

                            <!-- Age Group Distribution Bar Chart -->
                            <div id="ageGroupChart" class="chart-card" style="display:none;">
                                <h3 class="chart-title">Age Groups</h3>
                                <canvas id="ageGroupBarChart"></canvas>
                                <p id="noAgeDataMessage" style="display:none;color:var(--grey-500);margin-top:10px;font-size:13px;">No data available</p>
                            </div>

                            <!-- BMI Category Pie Chart -->
                            <div id="bmiChart" class="chart-card chart-card-sm" style="display:none;">
                                <h3 class="chart-title">Patients by BMI Category</h3>
                                <canvas id="bmiPieChart"></canvas>
                                <p id="noBmiDataMessage" style="display:none;color:var(--grey-500);margin-top:10px;font-size:13px;">No data available</p>
                            </div>

                            <!-- Address Distribution Bar Chart -->
                            <div id="barangayChart" class="chart-card">
                                <h3 class="chart-title">Patients by Barangay</h3>
                                <canvas id="barangayBarChart"></canvas>
                                <p id="noBarangayDataMessage" style="display:none;color:var(--grey-500);margin-top:10px;font-size:13px;">No data available</p>
                            </div>
                        </div>

                        <!-- Table with Visit Details -->
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
                                                    <td data-label="Address"><?= htmlspecialchars($visit['address']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="no-records">No visits found for the selected filters.</p>
                        <?php endif; ?>

                        <!-- Summary Section -->
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
                                            <td><?= $total_patients ?></td>
                                        </tr>
                                        <tr>
                                            <th>By Sex</th>
                                            <td>
                                                Male — <?= $sex_counts['Male'] ?? 0 ?>,
                                                Female — <?= $sex_counts['Female'] ?? 0 ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>By Age Group</th>
                                            <td>
                                                0–5: <?= $age_group_counts['0–5'] ?? 0 ?>,
                                                6–17: <?= $age_group_counts['6–17'] ?? 0 ?>,
                                                18–59: <?= $age_group_counts['18–59'] ?? 0 ?>,
                                                60+: <?= $age_group_counts['60+'] ?? 0 ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>By BMI</th>
                                            <td>
                                                Underweight — <?= $bmi_categories['Underweight'] ?? 0 ?>,
                                                Normal — <?= $bmi_categories['Normal'] ?? 0 ?>,
                                                Overweight — <?= $bmi_categories['Overweight'] ?? 0 ?>,
                                                Obese — <?= ($bmi_categories['Class 1'] ?? 0) + ($bmi_categories['Class 2'] ?? 0) + ($bmi_categories['Class 3'] ?? 0) ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div>
                                    <strong class="summary-subtitle">Patient Counts per Barangay:</strong>
                                    <table class="summary-table2">
                                        <thead>
                                            <tr>
                                                <th>Barangay</th>
                                                <th>Patient Count</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // reuse the counts you already computed just above
                                            foreach ($barangay_counts as $barangay => $count) {
                                                echo "<tr>";
                                                echo "<td>" . htmlspecialchars($barangay) . "</td>";
                                                echo "<td>" . $count . "</td>";
                                                echo "</tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div id="generated_by"></div>
                    </div>
                </div>
            </div>

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
    </main>
</section>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const chartColors = ['#0d2d52', '#22a06b', '#e53e3e', '#1c6fba', '#d97706', '#9467bd'];

const sexLabels = <?= json_encode(array_keys($sex_counts), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const sexData = <?= json_encode(array_values($sex_counts), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const ageGroupLabels = <?= json_encode(array_keys($age_group_counts), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const ageGroupData = <?= json_encode(array_values($age_group_counts), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const bmiLabels = <?= json_encode(array_keys($bmi_categories), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const bmiData = <?= json_encode(array_values($bmi_categories), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const barangayLabels = <?= json_encode(array_keys($barangay_counts), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const barangayData = <?= json_encode(array_values($barangay_counts), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const reportCharts = {};

document.addEventListener('DOMContentLoaded', () => {
    function hasData(data) {
        return data.reduce((a, b) => a + Number(b || 0), 0) > 0;
    }

    if (hasData(sexData)) {
        const ctx = document.getElementById('sexPieChart');
        if (ctx) {
            reportCharts.sexPieChart = new Chart(ctx.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: sexLabels,
                    datasets: [{
                        data: sexData,
                        backgroundColor: ['#1c6fba', '#f26419'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: 6 },
                    plugins: {
                        legend: { position: 'bottom', labels: { font: { family: 'Plus Jakarta Sans', size: 12 } } },
                        title: { display: false }
                    }
                }
            });
        }
    } else {
        const chart = document.getElementById('sexPieChart');
        const message = document.getElementById('noSexDataMessage');
        if (chart) chart.style.display = 'none';
        if (message) message.style.display = 'block';
    }

    if (hasData(ageGroupData)) {
        const ctxBar = document.getElementById('ageGroupBarChart');
        if (ctxBar) {
            reportCharts.ageGroupBarChart = new Chart(ctxBar.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ageGroupLabels,
                    datasets: [{
                        label: 'Patient Count',
                        data: ageGroupData,
                        backgroundColor: '#1c6fba',
                        borderWidth: 0
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
                            title: { display: true, text: 'Patient Count', font: { family: 'Plus Jakarta Sans', size: 12 } },
                            grid: { color: 'rgba(0,0,0,.04)' },
                            ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } }
                        },
                        x: {
                            title: { display: true, text: 'Age Group', font: { family: 'Plus Jakarta Sans', size: 12 } },
                            grid: { display: false },
                            ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } }
                        }
                    }
                }
            });
        }
    } else {
        const chart = document.getElementById('ageGroupBarChart');
        const message = document.getElementById('noAgeDataMessage');
        if (chart) chart.style.display = 'none';
        if (message) message.style.display = 'block';
    }

    if (hasData(bmiData)) {
        const ctxBMI = document.getElementById('bmiPieChart');
        if (ctxBMI) {
            reportCharts.bmiPieChart = new Chart(ctxBMI.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: bmiLabels,
                    datasets: [{
                        data: bmiData,
                        backgroundColor: chartColors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: 6 },
                    plugins: {
                        legend: { position: 'bottom', labels: { font: { family: 'Plus Jakarta Sans', size: 12 } } },
                        title: { display: false }
                    }
                }
            });
        }
    } else {
        const chart = document.getElementById('bmiPieChart');
        const message = document.getElementById('noBmiDataMessage');
        if (chart) chart.style.display = 'none';
        if (message) message.style.display = 'block';
    }

    if (hasData(barangayData)) {
        const ctxBarangay = document.getElementById('barangayBarChart');
        if (ctxBarangay) {
            reportCharts.barangayBarChart = new Chart(ctxBarangay.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: barangayLabels,
                    datasets: [{
                        label: 'Patient Count',
                        data: barangayData,
                        backgroundColor: '#1c6fba',
                        borderWidth: 0
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
                            title: { display: true, text: 'Patient Count', font: { family: 'Plus Jakarta Sans', size: 12 } },
                            grid: { color: 'rgba(0,0,0,.04)' },
                            ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } }
                        },
                        x: {
                            title: { display: true, text: 'Barangay', font: { family: 'Plus Jakarta Sans', size: 12 } },
                            grid: { display: false },
                            ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } }
                        }
                    }
                }
            });
        }
    } else {
        const chart = document.getElementById('barangayBarChart');
        const message = document.getElementById('noBarangayDataMessage');
        if (chart) chart.style.display = 'none';
        if (message) message.style.display = 'block';
    }

    const chartMapping = {
        toggleSexChart: 'sexChart',
        toggleAgeGroupChart: 'ageGroupChart',
        toggleBMIChart: 'bmiChart',
    };

    const grid = document.getElementById('patientChartGrid');

    function resizeChartInside(card) {
        const canvas = card ? card.querySelector('canvas') : null;
        if (canvas && reportCharts[canvas.id]) {
            reportCharts[canvas.id].resize();
        }
    }

    function updatePatientChartGrid() {
        if (!grid) return;
        const visibleCards = Array.from(grid.querySelectorAll('.chart-card')).filter(card => {
            return card.style.display !== 'none';
        });
        grid.classList.toggle('single-chart', visibleCards.length <= 1);
        visibleCards.forEach(card => resizeChartInside(card));
    }

    function showChart(card) {
        if (!card) return;
        card.style.display = 'block';
        requestAnimationFrame(() => {
            card.classList.add('is-visible');
            updatePatientChartGrid();
            setTimeout(() => resizeChartInside(card), 280);
        });
    }

    function hideChart(card) {
        if (!card) return;
        card.classList.remove('is-visible');
        setTimeout(() => {
            card.style.display = 'none';
            updatePatientChartGrid();
        }, 240);
    }

    // Initial visible chart state
    document.querySelectorAll('.patient-chart-grid .chart-card').forEach(card => {
        if (card.style.display !== 'none') {
            card.classList.add('is-visible');
        }
    });
    updatePatientChartGrid();

    Object.keys(chartMapping).forEach(toggleId => {
        const checkbox = document.getElementById(toggleId);
        const chartElement = document.getElementById(chartMapping[toggleId]);

        if (checkbox && chartElement) {
            checkbox.addEventListener('change', () => {
                if (checkbox.checked) {
                    showChart(chartElement);
                } else {
                    hideChart(chartElement);
                }
            });

            if (checkbox.checked) {
                showChart(chartElement);
            } else {
                hideChart(chartElement);
            }
        }
    });
});
</script>

<script>
/* Filter Modal */
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
        flatpickr('#from_date', {
            dateFormat: 'Y-m-d',
            allowInput: true,
            disableMobile: true
        });

        flatpickr('#to_date', {
            dateFormat: 'Y-m-d',
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
    const filterModal = document.getElementById('filterModal');
    if (event.target == filterModal) {
        filterModal.style.display = 'none';
    }

    const logoutModal = document.getElementById('logoutModal');
    if (event.target == logoutModal) {
        closeModal();
    }
});
</script>

<script>
function exportTableToExcel(tableID, filename = 'Patient Summary Report') {
    try {
        // Create a temporary div with the same content as print
        const tempDiv = document.createElement('div');
        tempDiv.style.position = 'absolute';
        tempDiv.style.left = '-9999px';
        tempDiv.style.top = '-9999px';

        // Clone the summary section
        const summary = document.querySelector('.summary-container');
        if (summary) {
            const summaryClone = summary.cloneNode(true);
            const summaryScripts = summaryClone.querySelectorAll('script');
            summaryScripts.forEach(script => script.remove());
            tempDiv.appendChild(summaryClone);
        }

        // Clone the table
        const originalTable = document.getElementById(tableID);
        if (!originalTable) {
            alert('Table not found!');
            return;
        }

        const tableClone = originalTable.cloneNode(true);
        tempDiv.appendChild(tableClone);
        document.body.appendChild(tempDiv);

        // Create HTML content for Excel
        const htmlContent = `
            <html xmlns:o="urn:schemas-microsoft-com:office:office"
                  xmlns:x="urn:schemas-microsoft-com:office:excel"
                  xmlns="http://www.w3.org/TR/REC-html40">
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
            <body>${tempDiv.innerHTML}</body>
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
    const originalArea = document.querySelector('.print-area');
    const headerEl = document.querySelector('.print-letterhead');
    if (!originalArea || !headerEl) {
        alert('Error: Missing .print-area or .print-letterhead on page.');
        return;
    }

    const clone = originalArea.cloneNode(true);

    // Convert live charts to images before printing
    ['sexPieChart', 'ageGroupBarChart', 'bmiPieChart', 'barangayBarChart'].forEach(id => {
        const live = document.getElementById(id);
        const inClone = clone.querySelector('#' + id);
        if (live && inClone && typeof live.toDataURL === 'function') {
            const img = document.createElement('img');
            img.src = live.toDataURL('image/png');
            img.style.cssText = 'max-width:100%;height:auto;';
            inClone.parentNode.replaceChild(img, inClone);
        }
    });

    // Remove header duplication in the clone
    const headerInClone = clone.querySelector('.print-letterhead');
    if (headerInClone) headerInClone.remove();
    const ruleInClone = clone.querySelector('.print-rule');
    if (ruleInClone) ruleInClone.remove();

    // Remove controls in the clone
    clone.querySelectorAll('.chart-controls-panel').forEach(el => el.remove());

    const w = window.open('', '', 'height=900,width=1100');
    if (!w) {
        alert('Please allow pop-ups to print this report.');
        return;
    }

    w.document.write(`
        <html>
            <head>
                <title>Print Report</title>
                <meta charset="utf-8" />
                <style>
                    body{font-family:'Plus Jakarta Sans',Arial,sans-serif;font-size:12px;color:#000;}
                    table{width:100%;border-collapse:collapse;}
                    th,td{border:1px solid #000;padding:5px 8px;text-align:left;}
                    thead{background:#d8e4f0;print-color-adjust:exact;}
                    img{display:block;margin:0 auto;max-width:100%;height:auto;}

                    .print-letterhead{
                        display:grid;grid-template-columns:64px auto 64px;
                        align-items:center;justify-content:center;column-gap:60px;
                        margin:0 auto 10px;text-align:center;width:fit-content;
                    }
                    .print-logo{width:64px;height:64px;object-fit:contain;}
                    .print-heading{line-height:1.1;color:#000;}
                    .print-heading .ph-line-1{font-size:12pt;font-weight:500;}
                    .print-heading .ph-line-2{font-size:14pt;font-weight:800;}
                    .print-heading .ph-line-3{font-size:11pt;font-weight:500;}
                    .print-sub{font-size:11pt;margin-top:4px;}
                    .print-rule{height:1px;border:0;background:#cfd8e3;margin:8px 0 12px;}
                    .title{display:block;text-align:center;}
                    .chart-card{max-width:620px;margin:18px auto;text-align:center;page-break-inside:avoid;}
                    .chart-title{font-size:12pt;margin:8px 0;}
                    .summary-title{display:none;}
                    #generated_by{margin:60mm 0 0 10mm;}
                    #generated_by .sig-line{display:block;width:45mm;border:0;border-top:1px solid #000;margin:10mm 0 3mm;}
                </style>
            </head>
            <body>
                ${headerEl.outerHTML}
                <hr class="print-rule">
                ${clone.innerHTML}
            </body>
        </html>
    `);
    w.document.close();
    w.focus();
    setTimeout(() => { w.print(); w.close(); }, 500);
}
</script>

<script>
/* Table sort */
(function() {
    const table = document.getElementById('reportTable');
    if (!table) return;

    const thead = table.tHead || table.querySelector('thead');
    const tbody = table.tBodies[0];

    function parseDate(v) {
        const t = (v || '').trim();
        // ISO-like "YYYY-MM-DD" (with optional time)
        if (/^\d{4}-\d{2}-\d{2}(?:\s+\d{2}:\d{2}(?::\d{2})?)?$/.test(t)) {
            return new Date(t.replace(' ', 'T'));
        }
        const d = new Date(t); // fallback (e.g., "Nov 01, 2025")
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

    // Click handlers
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

    // Default: sort by Visit Date (col 0) descending on load
    const defaultCol = 0, defaultDir = 'desc';
    const defaultTh = thead.querySelectorAll('th')[defaultCol];
    if (defaultTh) {
        defaultTh.classList.add(defaultDir === 'asc' ? 'is-sorted-asc' : 'is-sorted-desc');
        sortBy(defaultCol, defaultDir);
    }
})();
</script>

<script>
/* Sidebar toggle */
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

/* Nav search & pending referrals */
document.addEventListener('DOMContentLoaded', () => {
    const updateReferrals = document.getElementById('updateReferrals');
    const searchInput = document.getElementById('patientSearch');
    const searchButton = document.getElementById('searchButton');

    if (updateReferrals) {
        updateReferrals.addEventListener('click', function (event) {
            event.preventDefault();

            fetch('../php/update_referrals.php')
                .then(response => response.json())
                .then(data => {
                    console.log(data.message);
                    window.location.href = '../pending';
                })
                .catch(error => {
                    console.error('Error updating referrals:', error);
                    window.location.href = '../pending';
                });
        });
    }

    if (searchInput && searchButton) {
        searchInput.addEventListener('keypress', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchButton.click();
            }
        });

        searchButton.addEventListener('click', function () {
            const searchQuery = searchInput.value.trim();
            if (searchQuery) {
                sessionStorage.setItem('searchQuery', searchQuery);
                window.location.href = '../searchPatient';
            } else {
                alert('Please enter a patient name to search.');
            }
        });
    }
});

/* Logout */
function confirmLogout() {
    document.getElementById('logoutModal').classList.add('open');
    document.getElementById('logoutModal').style.display = 'flex';
    return false; // Prevent the default link behavior
}

function closeModal() {
    document.getElementById('logoutModal').classList.remove('open');
    document.getElementById('logoutModal').style.display = 'none';
}

function proceedLogout() {
    window.location.href='../../ADMIN/php/logout'; 
}

/* User name */
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

            const sidebarNameEl = document.getElementById('sidebarUserName');
            if (sidebarNameEl) {
                sidebarNameEl.textContent = fullName || 'Nurse';
            }

            // Build the signature block
            const gb = document.getElementById('generated_by');
            if (gb) {
                gb.innerHTML = `
                    <div class="sig-label">Report Generated by:</div>
                    <hr class="sig-line">
                    <div class="sig-name"></div>
                    <div class="sig-title">Nursing Attendant</div>
                `;
                gb.querySelector('.sig-name').textContent = fullName || '________________';
            }
        })
        .catch(() => {
            const greetingEl = document.getElementById('userGreeting');
            if (greetingEl) {
                greetingEl.textContent = 'Hello, User!';
            }
            const sidebarNameEl = document.getElementById('sidebarUserName');
            if (sidebarNameEl) {
                sidebarNameEl.textContent = 'Nurse';
            }
            const gb = document.getElementById('generated_by');
            if (gb) {
                gb.innerHTML = `
                    <div class="sig-label">Report Generated by:</div>
                    <hr class="sig-line">
                    <div class="sig-name">________________</div>
                    <div class="sig-title">Nursing Attendant</div>
                `;
            }
        });
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

</body>
</html>
