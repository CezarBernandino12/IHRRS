<?php
// Connect to DB
require '../../php/db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../role");
    exit;
}


$userId = $_SESSION['user_id']; 

$stmt = $pdo->prepare("SELECT full_name, rhu FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$rhu = $user ? $user['rhu'] : 'N/A';
$username = $user ? $user['full_name'] : 'N/A';

$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$sex = $_GET['sex'] ?? '';
$age_group = $_GET['age_group'] ?? '';
$purok = $_GET['purok'] ?? '';
$diagnosis = isset($_GET['diagnosis']) ? (array)$_GET['diagnosis'] : [];
$diagnosis_status = $_GET['diagnosis_status'] ?? '';
$barangay = $_GET['barangay'] ?? '';

$sql = "SELECT r.*, p.first_name, p.last_name, p.age, p.sex, p.address
        FROM rhu_consultations r
        JOIN patients p ON r.patient_id = p.patient_id
        JOIN users u_rec ON r.recorded_by = u_rec.user_id
        WHERE u_rec.rhu = :rhu"; 

$params['rhu'] = $rhu;


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
        case 'child':  $sql .= " AND p.age < 13"; break;
        case 'teen':   $sql .= " AND p.age BETWEEN 13 AND 19"; break;
        case 'adult':  $sql .= " AND p.age BETWEEN 20 AND 59"; break;
        case 'senior': $sql .= " AND p.age >= 60"; break;
    }
}

if (!empty($diagnosis)) {
    $placeholders = [];
    foreach ($diagnosis as $i => $diag) {
        $ph = ":diagnosis_$i";
        $placeholders[] = "r.diagnosis LIKE $ph";
        $params["diagnosis_$i"] = '%' . $diag . '%';
    }
    $sql .= " AND (" . implode(" OR ", $placeholders) . ")";
}
if (!empty($diagnosis_status)) {
    $sql .= " AND r.diagnosis_status = :diagnosis_status";
    $params['diagnosis_status'] = $diagnosis_status;
}
if (!empty($purok)) {
    $sql .= " AND p.address LIKE :purok";
    $params['purok'] = '%' . $purok . '%';
}

if (!empty($barangay) && $barangay !== 'N/A') {
    $sql .= " AND p.address LIKE :barangay";
    $params['barangay'] = '%' . $barangay . '%';
}

$sql .= " ORDER BY r.consultation_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$visits = $stmt->fetchAll();

        $stmt_log = $pdo->prepare("INSERT INTO logs (
            user_id, action, performed_by
        ) VALUES (
            :user_id, :action, :performed_by
        )");
        $stmt_log->execute([
            ':user_id' => $_SESSION['user_id'],
            ':action' => "Generated RHU Medical Cases Report",
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

	<title>Medical Cases Report</title>
</head>
<body>

<style>
/* UI consistency layer for Medical Cases Report */
#reportTable th {
  cursor: pointer;
  position: relative;
  user-select: none;
}
#reportTable th .sort-indicator {
  margin-left: 5px;
  font-size: 10px;
  opacity: .65;
  display: inline-block;
  transition: opacity .15s;
}
#reportTable th:hover .sort-indicator { opacity: 1; }
#reportTable th.is-sorted-asc .sort-indicator::after { content: "▲"; }
#reportTable th.is-sorted-desc .sort-indicator::after { content: "▼"; }
#reportTable th.is-sorted-asc,
#reportTable th.is-sorted-desc {
  background: rgba(255,255,255,.12);
  color: #fff;
}

#content main {
  width: 100%;
  padding: 32px 28px;
  max-height: calc(100vh - 56px);
  overflow-y: auto;
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
  border-radius: 2px;
  background: var(--blue, #1c6fba);
  flex-shrink: 0;
}

.form-submit {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  align-items: center;
  margin-top: 0 !important;
}

.btn-export,
.btn-print,
.form-submit button {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 9px 18px;
  font-family: var(--font-body, 'Plus Jakarta Sans', sans-serif);
  font-size: 13.5px;
  font-weight: 600;
  border: none;
  border-radius: var(--r-sm, 6px);
  cursor: pointer;
  transition: background .18s, box-shadow .18s, transform .12s;
  white-space: nowrap;
  letter-spacing: .01em;
}
.btn-export {
  background: var(--navy, #0d2d52);
  color: var(--white, #fff);
  box-shadow: var(--shadow-xs, 0 1px 3px rgba(13,45,82,.07));
}
.btn-export:hover {
  background: var(--navy-mid, #1a4477);
  box-shadow: var(--shadow-sm, 0 2px 8px rgba(13,45,82,.09));
  transform: translateY(-1px);
}
.btn-print {
  background: var(--blue, #1c6fba);
  color: var(--white, #fff);
  box-shadow: var(--shadow-xs, 0 1px 3px rgba(13,45,82,.07));
}
.btn-print:hover {
  background: var(--navy-mid, #1a4477);
  box-shadow: var(--shadow-sm, 0 2px 8px rgba(13,45,82,.09));
  transform: translateY(-1px);
}

.selected-filters { margin-top: 20px; }
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
  transition: background .15s !important;
}
.filter-tag:hover { background: #dceeff !important; }
.filter-tag a {
  color: var(--grey-500, #8c96aa) !important;
  font-weight: 700 !important;
  font-size: 14px !important;
  line-height: 1;
  transition: color .15s !important;
  text-decoration: none !important;
  margin-left: 2px !important;
}
.filter-tag a:hover { color: var(--red, #e53e3e) !important; }
.no-filter-text {
  color: var(--grey-500, #8c96aa);
  font-size: 13px;
}

.modal {
  display: none;
  position: fixed;
  z-index: 9999;
  inset: 0;
  background: rgba(10,20,40,.45);
  backdrop-filter: blur(4px);
  overflow-y: auto;
}
.modal-content {
  background: var(--white, #fff);
  margin: 6% auto;
  padding: 32px 36px 28px;
  border-radius: var(--r-xl, 20px);
  width: 90%;
  max-width: 640px !important;
  box-shadow: var(--shadow-lg, 0 8px 32px rgba(13,45,82,.13));
}
.modal-header {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--border-soft, #edf0f7);
}
.modal-header h3 {
  font-size: 20px;
  font-weight: 700;
  color: var(--navy, #0d2d52);
  letter-spacing: -.2px;
}
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px 20px;
  margin-top: 4px;
}
.form-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.form-item label {
  font-size: 12.5px;
  font-weight: 600;
  color: var(--grey-700, #4a5568);
  text-transform: uppercase;
  letter-spacing: .05em;
}
.form-control {
  padding: 9px 12px;
  font-family: var(--font-body, 'Plus Jakarta Sans', sans-serif);
  font-size: 14px;
  color: var(--dark, #0f1d31);
  background: var(--grey-100, #f8f9fc);
  border: 1.5px solid var(--border, #dde4ef);
  border-radius: var(--r-sm, 6px);
  outline: none;
  transition: border-color .18s, box-shadow .18s, background .18s;
  width: 100%;
}
.form-control:focus {
  border-color: var(--accent, #2196f3);
  background: var(--white, #fff);
  box-shadow: 0 0 0 3px var(--accent-glow, rgba(33,150,243,.15));
}
.checkbox-list {
  max-height: 170px;
  overflow-y: auto;
  border: 1.5px solid var(--border, #dde4ef);
  background: var(--grey-100, #f8f9fc);
  padding: 10px;
  border-radius: var(--r-sm, 6px);
}
.checkbox-list label,
.checkbox-option {
  display: flex !important;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px !important;
  text-align: left !important;
  font-weight: 500 !important;
  color: var(--grey-700, #4a5568) !important;
  text-transform: none !important;
  letter-spacing: 0 !important;
  font-size: 13px !important;
}
.checkbox-help {
  color: var(--grey-500, #8c96aa);
  font-size: 12.5px;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  margin-top: 28px;
  gap: 12px;
  padding-top: 20px;
  border-top: 1px solid var(--border-soft, #edf0f7);
}
.modal-footer .btn,
.modal-footer .btn-submit {
  min-width: 110px;
  padding: 10px 20px;
  font-family: var(--font-body, 'Plus Jakarta Sans', sans-serif);
  font-size: 14px;
  font-weight: 600;
  border-radius: var(--r-sm, 6px);
  cursor: pointer;
  transition: background .18s, transform .12s, box-shadow .18s;
}
.modal-footer .btn {
  border: 1.5px solid var(--border, #dde4ef);
  background: var(--grey-100, #f8f9fc);
  color: var(--grey-700, #4a5568);
}
.modal-footer .btn:hover { background: var(--grey-200, #eef1f7); transform: translateY(-1px); }
.modal-footer .btn-submit {
  border: none;
  background: var(--blue, #1c6fba);
  color: var(--white, #fff);
  font-weight: 700;
  box-shadow: 0 2px 8px rgba(28,111,186,.25);
}
.modal-footer .btn-submit:hover {
  background: var(--navy, #0d2d52);
  box-shadow: var(--shadow-md, 0 4px 20px rgba(13,45,82,.10));
  transform: translateY(-1px);
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
  margin-bottom: 24px;
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
  transition: border-color .18s, background .18s, color .18s;
  user-select: none;
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
.chart-card {
  max-width: 520px;
  margin: 24px auto 0;
  text-align: center;
  background: var(--white, #fff);
  border: 1px solid var(--border-soft, #edf0f7);
  border-radius: var(--r-md, 10px);
  padding: 20px;
}
.chart-card-wide { max-width: 800px; }
.chart-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--navy, #0d2d52);
  margin-bottom: 12px;
}

.report-table-container {
  width: 100%;
  border-radius: var(--r-lg, 16px);
  border: 1px solid var(--border, #dde4ef);
  overflow: hidden;
  box-shadow: var(--shadow-md, 0 4px 20px rgba(13,45,82,.10));
  background: var(--white, #fff);
  margin-top: 24px !important;
  margin-bottom: 40px !important;
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
#reportTable thead tr,
.case-table thead tr,
.summary-table thead tr {
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
  transition: background .15s;
}
#reportTable th:hover { background: rgba(255,255,255,.08); }
#reportTable td {
  padding: 11px 14px;
  color: var(--grey-700, #4a5568);
  font-size: 13.5px;
  vertical-align: middle;
  border-right: 1px solid var(--border-soft, #edf0f7);
  border-bottom: 1px solid var(--border-soft, #edf0f7);
}
#reportTable tbody tr:nth-child(odd) { background: var(--white, #fff); }
#reportTable tbody tr:nth-child(even) { background: var(--grey-100, #f8f9fc); }
#reportTable tbody tr:hover { background: var(--blue-pale, #f0f6ff); }
#reportTable td:nth-child(2),
#reportTable td:nth-child(4),
#reportTable td:nth-child(7) {
  white-space: normal;
  word-break: break-word;
  min-width: 130px;
}
.no-records {
  text-align: center;
  padding: 48px 24px;
  color: var(--grey-500, #8c96aa);
  font-size: 15px;
}

.summary-container { margin-top: 32px; }
.summary-title,
.summary h3 {
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
.summary h3 i {
  font-size: 18px;
  color: var(--blue, #1c6fba);
}
.summary-list,
.summary-list li {
  list-style: none;
  padding-left: 0;
  margin-left: 0;
}
.case-table,
.summary-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  border: 1px solid var(--border, #dde4ef);
  border-radius: var(--r-lg, 16px);
  overflow: hidden;
  box-shadow: var(--shadow-sm, 0 2px 8px rgba(13,45,82,.09));
  font-size: 14px;
  table-layout: fixed;
  background: var(--white, #fff);
}
.case-table { margin-top: 10px; margin-bottom: 20px; }
.case-table th,
.summary-table thead th {
  padding: 12px 14px;
  color: rgba(255,255,255,.9);
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .07em;
  text-align: center;
  border-bottom: 1px solid var(--border-soft, #edf0f7);
}
.case-table td,
.summary-table td {
  color: var(--grey-700, #4a5568);
  padding: 12px 14px;
  border-bottom: 1px solid var(--border-soft, #edf0f7);
  border-right: 1px solid var(--border-soft, #edf0f7);
  font-size: 13.5px;
  vertical-align: top;
  word-wrap: break-word;
}
.case-table td { text-align: center; }
.case-table th.tl,
.case-table td:nth-child(1) { text-align: left; }
.summary-table th {
  background: var(--grey-100, #f8f9fc);
  font-weight: 600;
  color: var(--navy, #0d2d52);
  padding: 14px 18px;
  width: 260px;
  text-align: left;
  border-bottom: 1px solid var(--border-soft, #edf0f7);
  border-right: 1px solid var(--border-soft, #edf0f7);
  font-size: 13.5px;
}
.summary-table tbody tr:hover td,
.summary-table tbody tr:hover th,
.case-table tbody tr:hover td { background: var(--blue-pale, #f0f6ff); }

#generated_by {
  display: block;
  margin: 48px 0 0 4px;
  color: var(--dark, #0f1d31);
}
#generated_by .sig-label {
  font-size: 12.5px;
  font-weight: 600;
  color: var(--grey-500, #8c96aa);
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
  font-size: 15px;
  color: var(--navy, #0d2d52);
  white-space: nowrap;
}
#generated_by .sig-title {
  font-size: 12px;
  color: var(--grey-500, #8c96aa);
  margin-top: 2px;
}

@media print {
  @page { size: landscape; margin: 1cm; }
  body * { visibility: hidden; }
  .print-area, .print-area * { visibility: visible; }
  .print-area {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    box-shadow: none;
    border: none;
    padding: 0;
    border-radius: 0;
  }
  .chart-controls-panel,
  .form-submit,
  .btn-print,
  .btn-export,
  .selected-filters,
  nav,
  #sidebar,
  .sidebar-overlay { display: none !important; }

  .chart-is-hidden,
  .chart-card[aria-hidden="true"] {
    display: none !important;
    visibility: hidden !important;
  }

  .chart-card {
    display: block !important;
    box-shadow: none !important;
    border: 1px solid #000 !important;
    border-radius: 0 !important;
    margin: 10px 0 16px !important;
    page-break-inside: avoid;
    break-inside: avoid;
  }

  .chart-title {
    display: block !important;
    color: #000 !important;
    font-size: 11pt !important;
    margin: 6px 0 8px !important;
  }
  .title { display: block !important; text-align: center; }
  .ph-line-4 { text-align: center; }
  .print-sub { text-align: center; }
  /* Consistent font */
  body, table, th, td, #generated_by, .sig-label, .sig-name, .sig-title,
  .ph-line-4, .print-sub { font-family: Arial, sans-serif !important; }
  /* Hide charts */
  .medical-chart-grid, .chart-is-hidden, canvas { display: none !important; }
  .print-letterhead { display: grid !important; }
  .print-rule { display: block !important; }
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
  .print-heading .ph-line-1 { font-size: 12pt; font-weight: 500; margin-bottom: 4px; }
  .print-heading .ph-line-2 { font-size: 14pt; font-weight: 800; margin-bottom: 4px; }
  .print-heading .ph-line-3 { font-size: 11pt; font-weight: 500; margin-bottom: 4px; }
  .ph-line-4 { font-size: 12pt; font-weight: 800; margin-top: 4px; letter-spacing: .3px; }
  .print-sub { font-size: 10.5pt; margin-top: 4px; }
  .print-rule { height: 1px; border: 0; background: #cfd8e3; margin: 8px 0 12px; }
  .report-table-container {
    box-shadow: none;
    border: 1px solid #000;
    border-radius: 0;
    max-height: none !important;
    overflow: visible !important;
    margin-bottom: 40px !important;
  }
  .report-table-scroll {
    overflow: visible !important;
    max-height: none !important;
  }
  #reportTable { min-width: unset; font-size: 10pt; }
  #reportTable thead tr,
  .case-table thead tr,
  .summary-table thead tr { background: #fff !important; }
  #reportTable th,
  .case-table th {
    color: #000 !important;
    border: 1px solid #ccc;
    padding: 7px 10px;
    font-size: 9pt;
    background: #fff !important;
  }
  #reportTable td,
  .case-table td,
  .summary-table th,
  .summary-table td {
    border: 1px solid #000 !important;
    padding: 7px 10px;
    font-size: 10pt;
    color: #000;
    background: transparent !important;
  }
  .summary > h3 { display: none !important; }
  .case-table thead { display: table-header-group; }
  .case-table tr,
  .summary-table tr,
  #reportTable tr {
    page-break-inside: avoid;
    break-inside: avoid;
  }
  #generated_by { margin: 50mm 0 0 10mm !important; }
  #generated_by .sig-label { font-size: 11px; margin-bottom: 60px; display: block; }
  #generated_by .sig-block { display: inline-block; text-align: center; }
  #generated_by .sig-line { display: block; border: none; border-top: 1.5px solid #000; width: 100%; margin: 0 0 4px; }
  #generated_by .sig-name { font-weight: 700; font-size: 12pt; white-space: nowrap; }
  #generated_by .sig-title { font-size: 11pt; }
}

@media (max-width: 768px) {
  #content main { padding: 20px 14px; }
  .filter-form { padding: 20px 18px; }
  .form-row { grid-template-columns: 1fr; }
  .modal-content { padding: 24px 20px 20px; }
  .chart-toggle-group { gap: 8px; }
  #reportTable thead { display: none; }
  #reportTable,
  #reportTable tbody,
  #reportTable tr,
  #reportTable td { display: block; width: 100%; }
  #reportTable {
    min-width: unset;
  }
  #reportTable tr {
    margin: 0 0 12px;
    padding: 14px 14px 8px;
    border: 1px solid var(--border, #dde4ef);
    border-radius: var(--r-md, 10px);
    background: var(--white, #fff);
    box-shadow: var(--shadow-xs, 0 1px 3px rgba(13,45,82,.07));
  }
  #reportTable td {
    border: 0;
    border-bottom: 1px solid var(--border-soft, #edf0f7);
    padding: 9px 0;
    white-space: normal;
    font-size: 13px;
  }
  #reportTable td:last-child { border-bottom: none; }
  #reportTable td::before {
    content: attr(data-label);
    display: block;
    font-size: 10.5px;
    font-weight: 700;
    color: var(--grey-500, #8c96aa);
    text-transform: uppercase;
    letter-spacing: .07em;
    margin-bottom: 2px;
  }
  .summary-table { font-size: 13px; }
  .summary-table th { width: 140px; }
}

@media (max-width: 480px) {
  #content main .head-title .left h1 { font-size: 22px; }
  .print-area { padding: 16px 14px; }
}


/* Balanced chart layout update */
.medical-chart-grid {
  display: grid;
  gap: 24px;
  width: 100%;
  margin: 24px 0 28px;
  align-items: stretch;
}

.medical-chart-grid.line-chart-grid {
  grid-template-columns: 1fr;
}

.medical-chart-grid.secondary-chart-grid {
  grid-template-columns: repeat(2, minmax(280px, 1fr));
}

.medical-chart-grid.secondary-chart-grid.single-chart {
  grid-template-columns: minmax(280px, 520px);
  justify-content: center;
}

.medical-chart-grid .chart-card,
.chart-card.chart-card-wide {
  width: 100%;
  max-width: none !important;
  margin: 0 !important;
}

.chart-card {
  min-height: 330px;
  opacity: 1;
  transform: translateY(0);
  transition: opacity .28s ease, transform .28s ease, box-shadow .18s ease;
}

.chart-card.chart-card-sm {
  min-height: 330px;
}

.chart-card.chart-card-wide {
  min-height: 390px;
}

.chart-card canvas {
  width: 100% !important;
  height: 270px !important;
  max-height: 270px;
}

.chart-card.chart-card-wide canvas {
  height: 330px !important;
  max-height: 330px;
}

.chart-is-hidden {
  display: none !important;
  opacity: 0;
  transform: translateY(12px);
}

.chart-is-visible {
  display: block !important;
  animation: chartSoftEnter .34s ease both;
}

@keyframes chartSoftEnter {
  from { opacity: 0; transform: translateY(12px) scale(.985); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

@media (max-width: 900px) {
  .medical-chart-grid.secondary-chart-grid,
  .medical-chart-grid.secondary-chart-grid.single-chart {
    grid-template-columns: 1fr;
  }

  .medical-chart-grid .chart-card {
    max-width: 560px !important;
    margin: 0 auto !important;
  }

  .chart-card.chart-card-wide {
    max-width: none !important;
  }
}

@media (max-width: 480px) {
  .chart-card,
  .chart-card.chart-card-sm {
    min-height: 300px;
  }

  .chart-card.chart-card-wide {
    min-height: 330px;
  }

  .chart-card canvas {
    height: 235px !important;
    max-height: 235px;
  }

  .chart-card.chart-card-wide canvas {
    height: 270px !important;
    max-height: 270px;
  }
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
                  <h1>Medical Cases</h1>
                  <ul class="breadcrumb">
                    <li><a href="#">MC Report</a></li>
                    <li><i class="bx bx-chevron-right"></i></li>
                    <li><a class="active" href="#" onclick="history.back(); return false;">Go back</a></li>
                  </ul>
                </div>
              </div>

<div class="history-container">

    
	

<!-- Filter Form -->
<div class="filter-form">
    <h2>Medical Cases Monitoring Report - <?php echo htmlspecialchars($rhu); ?>   </h2> <br>

    
    <!-- Filter Modal Trigger -->
   
        <div class="form-submit">
               <button type="button" class="btn-export" id="openFilterModal"><i class="bx bx-filter-alt"></i> Select Filters</button>
                         <button type="button" class="btn-export" onclick="exportTableToExcel('reportTable')"><i class="bx bx-spreadsheet"></i> Export to Excel</button>
                   <button type="button" class="btn-print" onclick="printDiv()">
        <i class='bx bx-printer'></i>
        Print Report
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

                if (substr($param, -2) === '[]') {
                    $base = substr($param, 0, -2);
                    if (isset($url[$base])) {
                        if (is_array($url[$base])) {
                            $url[$base] = array_values(array_diff($url[$base], [$value]));
                            if (empty($url[$base])) unset($url[$base]);
                        } else {
                            unset($url[$base]);
                        }
                    }
                    $query = http_build_query($url);
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

            if ($from_date) renderTag('From', 'from_date', $from_date);
            if ($to_date) renderTag('To', 'to_date', $to_date);
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
                echo '<span class="no-filter-text">None</span>';
            }
            ?>
        </div>
    </div>


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
                            <input type="text" name="from_date" id="from_date" class="form-control" value="<?= $from_date ? htmlspecialchars($from_date) : '' ?>"  placeholder="Select date">
                        </div>
                        <!-- To Date -->
                        <div class="form-item">
                            <label for="to_date">To:</label>
                            <input type="text" name="to_date" id="to_date" class="form-control" value="<?= $to_date ? htmlspecialchars($to_date) : '' ?>"  placeholder="Select date">
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
                            <label for="purok">Barangay:</label>
                            <select name="purok" id="purok" class="form-control">
                                <option value="">All</option>
                                <?php
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
                            <label for="diagnosis_status">Status:</label>
                            <select name="diagnosis_status" id="diagnosis_status" class="form-control">
                                <option value="">All</option> 
                                <option value="Ongoing" <?= $diagnosis_status == 'Ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                <option value="Treated" <?= $diagnosis_status == 'Treated' ? 'selected' : '' ?>>Treated</option>
                                <option value="Deceased" <?= $diagnosis_status == 'Deceased' ? 'selected' : '' ?>>Deceased</option>
                           
                            </select> </div>

                                  <div class="form-item">
                        <label for="diagnosis">Diagnoses:</label>
                        <div class="checkbox-list">
                            <?php
                            // Fetch medicines for checkboxes
                            $diagnosis_stmt = $pdo->prepare("SELECT value FROM custom_options WHERE category = 'diagnosis' ");
                            $diagnosis_stmt->execute();
                            // Support multiple selection from GET
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
                        <small class="checkbox-help">You may select multiple diagnoses.</small>
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

    <script>
/* Filter modal */
document.getElementById('openFilterModal').onclick = () => {
    const modal = document.getElementById('filterModal');
    modal.style.display = 'block';
    setTimeout(() => {
        ['from_date', 'to_date'].forEach(id => {
            const el = document.getElementById(id);
            if (el && el._flatpickr) el._flatpickr.destroy();
            if (el) flatpickr('#' + id, { dateFormat: 'Y-m-d', allowInput: true, disableMobile: true });
        });
    }, 100);
};

document.getElementById('closeFilterModal').onclick = () => {
    document.getElementById('filterModal').style.display = 'none';
};

document.getElementById('filterForm').onsubmit = () => {
    document.getElementById('filterModal').style.display = 'none';
    return true;
};

window.addEventListener('click', event => {
    const modal = document.getElementById('filterModal');
    if (event.target === modal) modal.style.display = 'none';
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
    <div class="ph-line-1">Province of Camarines Norte</div>
    <div class="ph-line-2">Municipality of Daet</div>
    <div class="ph-line-3"><?php echo htmlspecialchars($rhu); ?></div>
 
  </div>
  <img src="../../img/mho_logo.png" alt="Right Logo" class="print-logo">
</div>
<hr class="print-rule">


<div class="report-content">

<div class="title">
   <div class="ph-line-4">MEDICAL CASE MONITORING REPORT</div>
    <div class="print-sub">
      (<?php
        $filters = [];
                   if ($from_date || $to_date) {
    $readable_from = $from_date ? date("F j, Y", strtotime($from_date)) : '';
    $readable_to   = $to_date ? date("F j, Y", strtotime($to_date)) : '';

    // Combine them in a single display
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
            'child' => 'Child (0–12)', 'teen' => 'Teen (13–19)',
            'adult' => 'Adult (20–59)', 'senior' => 'Senior (60+)'
          ];
          $filters[] = "Age Group: <strong>" . ($age_labels[$age_group] ?? htmlspecialchars($age_group)) . "</strong>";
        }
        if ($purok) $filters[] = "Barangay: <strong>" . htmlspecialchars($purok) . "</strong>";
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
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const chartMapping = {
        toggleSexChart: "sexChart",
        toggleAgeGroupChart: "ageGroupChart"
    };

    const optionalGrid = document.getElementById("secondaryChartsGrid");

    function refreshOptionalGrid() {
        if (!optionalGrid) return;
        const visibleCards = optionalGrid.querySelectorAll(".chart-is-visible").length;
        optionalGrid.classList.toggle("single-chart", visibleCards <= 1);
    }

    function resizeChartInside(card) {
        if (!window.Chart || !card) return;
        const canvas = card.querySelector("canvas");
        if (!canvas) return;
        const chart = Chart.getChart(canvas);
        if (chart) setTimeout(() => chart.resize(), 80);
    }

    function setChartVisibility(chartElement, shouldShow) {
        if (!chartElement) return;
        chartElement.classList.toggle("chart-is-hidden", !shouldShow);
        chartElement.classList.toggle("chart-is-visible", shouldShow);
        chartElement.setAttribute("aria-hidden", shouldShow ? "false" : "true");
        if (shouldShow) resizeChartInside(chartElement);
        refreshOptionalGrid();
    }

    Object.keys(chartMapping).forEach(toggleId => {
        const checkbox = document.getElementById(toggleId);
        const chartElement = document.getElementById(chartMapping[toggleId]);

        if (checkbox && chartElement) {
            checkbox.addEventListener("change", () => setChartVisibility(chartElement, checkbox.checked));
            setChartVisibility(chartElement, checkbox.checked);
        }
    });
});
</script>


<!-- Disease Frequency Over Time Line Chart -->
<div class="medical-chart-grid line-chart-grid">
    <div class="chart chart-card chart-card-wide">
        <h3 class="chart-title">Medical Cases</h3>
        <canvas id="casesLineChart"></canvas>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    <?php
    // Prepare disease frequency with unique patients across all dates
    $disease_dates = [];
    $seen = []; // Track diagnosis+patient globally

    foreach ($visits as $visit) {
        $diag = $visit['diagnosis'] ?? '';
        $date = isset($visit['consultation_date']) ? date('Y-m-d', strtotime($visit['consultation_date'])) : '';
        $patient = $visit['patient_id'] ?? null;

        if ($diag && $date && $patient) {
            // Create unique key for this diagnosis+patient
            $key = $diag . '_' . $patient;

            // Skip if already counted (ensures deduplication across all dates)
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;

            // Ensure structure
            if (!isset($disease_dates[$diag])) $disease_dates[$diag] = [];
            if (!isset($disease_dates[$diag][$date])) $disease_dates[$diag][$date] = 0;

            // Count unique patient on their first appearance only
            $disease_dates[$diag][$date]++;
        }
    }

    // Collect all unique dates
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


    <div id="secondaryChartsGrid" class="medical-chart-grid secondary-chart-grid single-chart">
    <!-- Pie Chart Section: Sex Distribution -->
    <div id="sexChart" class="chart chart-card chart-card-sm chart-is-hidden" aria-hidden="true">
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
    <div id="ageGroupChart" class="chart chart-card chart-card-sm chart-is-hidden" aria-hidden="true">
         <h3 class="chart-title">Age Groups</h3>
        <canvas id="ageGroupBarChart"></canvas>
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
    </div>

<br>

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
<?php
// Sort visits from latest to oldest by consultation_date
usort($visits, function($a, $b) {
    return strtotime($b['consultation_date']) - strtotime($a['consultation_date']);
});

// Track unique combinations of patient name + diagnosis
$seen = [];
$unique_visits = [];

foreach ($visits as $visit) {
    $key = strtolower(trim($visit['first_name'] . ' ' . $visit['last_name'] . '|' . $visit['diagnosis']));
    if (!isset($seen[$key])) {
        $seen[$key] = true;
        $unique_visits[] = $visit;
    }
}
?>
<tbody>
    <?php if (!empty($unique_visits)): ?>
        <?php foreach ($unique_visits as $visit): ?>
            <tr>
                <td data-label="Date Diagnosed"><?= date('Y-m-d', strtotime($visit['consultation_date'])) ?></td>
                <td data-label="Diagnosis"><?= htmlspecialchars($visit['diagnosis']) ?></td>
                <td data-label="Status"><?= htmlspecialchars($visit['diagnosis_status']) ?></td>
                <td data-label="Patient Name"><?= htmlspecialchars($visit['first_name'] . ' ' . $visit['last_name']) ?></td>
                <td data-label="Sex"><?= htmlspecialchars($visit['sex']) ?></td>
                <td data-label="Age"><?= htmlspecialchars($visit['age']) ?></td>
                <td data-label="Address"><?= htmlspecialchars($visit['address']) ?></td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="7" class="no-records">No unique records found</td></tr>
    <?php endif; ?>
</tbody>

    </table>
</div>
</div>
<?php else: ?>
    <p class="no-records">No visits found for the selected filters.</p>
<?php endif; ?>
           
  
</div>


<!-- Summary Section -->
<?php
// Calculate total unique patients
$unique_patient_ids = [];
foreach ($visits as $visit) {
    $unique_patient_ids[$visit['patient_id']] = true;
}
$total_patients = count($unique_patient_ids);
?>
<div class="summary-container">
    <div class="summary">
        <h3 class="summary-title"><i class="bx bx-file"></i> Report Details</h3>
        <ul class="summary-list">
            <li>
    <strong>Case Counts:</strong>
    <?php
    // Prepare disease breakdown by sex and age group (unique patients only)
    $disease_summary = [];
    $seen = []; // track patient+diagnosis globally

    foreach ($visits as $visit) {
        $d = $visit['diagnosis'] ?? 'Unknown';
        $s = $visit['sex'] ?? 'Unknown';
        $a = (int)($visit['age'] ?? 0);
        $p = $visit['patient_id'] ?? null;

        if (!$p) continue;

        $key = $d . '_' . $p;

        // Skip if this patient already counted for this disease
        if (isset($seen[$key])) continue;
        $seen[$key] = true;

        if (!isset($disease_summary[$d])) {
            $disease_summary[$d] = [
                'total' => 0,
                'sex' => ['Male' => 0, 'Female' => 0],
                'age' => ['0–5' => 0, '6–17' => 0, '18–59' => 0, '60+' => 0]
            ];
        }

        // Count this unique patient once
        $disease_summary[$d]['total']++;

        // Count sex
        if (isset($disease_summary[$d]['sex'][$s])) {
            $disease_summary[$d]['sex'][$s]++;
        }

        // Count age group
        if ($a >= 0 && $a <= 5) {
            $disease_summary[$d]['age']['0–5']++;
        } elseif ($a >= 6 && $a <= 17) {
            $disease_summary[$d]['age']['6–17']++;
        } elseif ($a >= 18 && $a <= 59) {
            $disease_summary[$d]['age']['18–59']++;
        } elseif ($a >= 60) {
            $disease_summary[$d]['age']['60+']++;
        }
    }

    if (count($disease_summary) === 0) {
        echo "<p style='color:#888;'>No disease cases found for the selected filters.</p>";
    } else {
echo "<table class='case-table'>
  <colgroup>
    <col style='width:28%'>    <!-- Case -->
    <col style='width:12%'>    <!-- Total -->
    <col style='width:10%'>    <!-- Male -->
    <col style='width:10%'>    <!-- Female -->
    <col style='width:10%'>    <!-- 0–5 -->
    <col style='width:10%'>    <!-- 6–17 -->
    <col style='width:10%'>    <!-- 18–59 -->
    <col style='width:10%'>    <!-- 60+ -->
  </colgroup>
  <thead>
    <tr>
      <th class='tl'>Case</th>
      <th>Total</th>
      <th>Male</th>
      <th>Female</th>
      <th>0–5</th>
      <th>6–17</th>
      <th>18–59</th>
      <th>60+</th>
    </tr>
  </thead><tbody>";

        foreach ($disease_summary as $disease => $info) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($disease) . "</td>";
            echo "<td>{$info['total']}</td>";
            echo "<td>{$info['sex']['Male']}</td>";
            echo "<td>{$info['sex']['Female']}</td>";
            echo "<td>{$info['age']['0–5']}</td>";
            echo "<td>{$info['age']['6–17']}</td>";
            echo "<td>{$info['age']['18–59']}</td>";
            echo "<td>{$info['age']['60+']}</td>";
            echo "</tr>";
        }

        echo "</tbody></table>";
    }
    ?>
</li>
<br>
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
      <th>Total Unique Patients</th>
      <td><?= $total_patients ?? 0 ?></td>
    </tr>
    <tr>
      <th>By Sex</th>
      <td>Male — <?= $sex_counts['Male'] ?? 0 ?>, Female — <?= $sex_counts['Female'] ?? 0 ?></td>
    </tr>
    <tr>
      <th>By Age Group</th>
      <td>
        Young Children: <?= $age_group_counts['0–5'] ?? 0 ?>,
        Children: <?= $age_group_counts['6–17'] ?? 0 ?>,
        Adults: <?= $age_group_counts['18–59'] ?? 0 ?>,
        Seniors: <?= $age_group_counts['60+'] ?? 0 ?>
      </td>
    </tr>
  </tbody>
</table>


        </ul>
    </div>

<span id="generated_by"></span>

</div>



</div> 


</div><!-- /main-content -->

</div><!-- /history-container -->
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
<script src="../js/reports.js"></script>
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
  const headerEl = document.querySelector('.print-letterhead');
  const printHeader = headerEl ? headerEl.outerHTML : '';

  const area = document.querySelector('.print-area');
  if (!area) return;
  const clone = area.cloneNode(true);

  const headerInClone = clone.querySelector('.print-letterhead');
  if (headerInClone) headerInClone.remove();

  const ruleInClone = clone.querySelector('.print-rule');
  if (ruleInClone) ruleInClone.remove();

  /* Remove all chart/canvas elements from the clone */
  clone.querySelectorAll('.chart-controls-panel, .chart-toggle-group, .medical-chart-grid, canvas').forEach(el => el.remove());

  const w = window.open('', '', 'height=900,width=1100');
  if (!w) { alert('Please allow pop-ups to print this report.'); return; }
  w.document.write(`
    <html>
      <head>
        <title>Print Report</title>
        <meta charset="utf-8" />
        <style>
          body{font-family:Arial,sans-serif;font-size:13px;color:#000;}
          table{width:100%;border-collapse:collapse;font-family:Arial,sans-serif;font-size:12px;}
          th,td{border:1px solid #000;padding:4px 6px;text-align:left;font-family:Arial,sans-serif;}
          thead tr{background:#fff!important;}
          thead th{background:#fff!important;color:#000!important;font-weight:700;}
          img{display:block;margin:0 auto;max-width:100%;height:auto;}
          h3{font-family:Arial,sans-serif;}
          .print-letterhead{display:grid;grid-template-columns:64px auto 64px;align-items:center;justify-content:center;column-gap:14px;margin:0 auto 10px;text-align:center;width:fit-content;}
          .print-logo{width:64px;height:64px;object-fit:contain;}
          .print-heading{line-height:1.1;color:#000;font-family:Arial,sans-serif;}
          .print-heading .ph-line-1{font-size:12pt;font-weight:500;}
          .print-heading .ph-line-2{font-size:14pt;font-weight:800;}
          .print-heading .ph-line-3{font-size:11pt;font-weight:500;}
          .title{text-align:center;margin:8px 0;font-family:Arial,sans-serif;}
          .ph-line-4{font-size:12pt;font-weight:800;margin-top:4px;text-align:center;font-family:Arial,sans-serif;}
          .print-sub{font-size:10.5pt;margin-top:4px;text-align:center;font-family:Arial,sans-serif;}
          .print-rule{height:1px;border:0;background:#cfd8e3;margin:8px 0 12px;}
          .form-submit,.selected-filters,.chart-controls-panel,.chart-toggle-group,.btn-export,.btn-print,
          .medical-chart-grid,canvas{display:none!important;}
          .report-table-scroll{overflow:visible!important;max-height:none!important;}
          .report-table-container{box-shadow:none!important;border-radius:0!important;}
          .summary-table,.case-table{box-shadow:none!important;border-radius:0!important;}
          #generated_by{margin-top:48px;font-family:Arial,sans-serif;}
          .sig-label{font-size:11px;text-transform:uppercase;letter-spacing:.07em;color:#666;margin-bottom:60px;display:block;}
          .sig-block{display:inline-block;text-align:center;}
          .sig-line{display:block;border:none;border-top:1.5px solid #000;margin:0 0 4px;}
          .sig-name{font-weight:700;font-size:13px;white-space:nowrap;}
          .sig-title{font-size:11px;color:#666;}
        </style>
      </head>
      <body>
        ${printHeader}
        ${clone.innerHTML}
      </body>
    </html>
  `);
  w.document.close();
  w.focus();
  setTimeout(() => { w.print(); w.close(); }, 300);
}
document.addEventListener('DOMContentLoaded', () => {
  fetch('../php/getUserName.php')
    .then(r => r.json())
    .then(data => {
      const fullName = (data && data.full_name) ? data.full_name : '';

      // Greeting (keep current behavior)
      const greetingEl = document.getElementById('userGreeting');
      if (greetingEl) greetingEl.textContent = fullName ? `Hello, ${fullName}!` : 'Hello, User!';

      // Build the signature block
      const gb = document.getElementById('generated_by');
      const name = fullName || '________________';
      gb.innerHTML = `<div class="sig-label">Report Generated by:</div><div class="sig-block"><span class="sig-line"></span><div class="sig-name"></div><div class="sig-title">Nursing Attendant</div></div>`;
      gb.querySelector('.sig-name').textContent = name;
      const nameEl = gb.querySelector('.sig-name');
      const lineEl = gb.querySelector('.sig-line');
      requestAnimationFrame(() => { lineEl.style.width = nameEl.offsetWidth + 'px'; });
    })
    .catch(() => {
      const greetingEl = document.getElementById('userGreeting');
      if (greetingEl) greetingEl.textContent = 'Hello, User!';
      const gb = document.getElementById('generated_by');
      gb.innerHTML = `<div class="sig-label">Report Generated by:</div><div class="sig-block"><span class="sig-line" style="width:180px;"></span><div class="sig-name">________________</div><div class="sig-title">Nursing Attendant</div></div>`;
    });
});

(function() {
  const table = document.getElementById('reportTable');
  if (!table) return;

  const thead = table.tHead || table.querySelector('thead');
  const tbody = table.tBodies[0];

  // Add arrow placeholders to headers
  [...thead.querySelectorAll('th')].forEach(th => {
    const ind = document.createElement('span');
    ind.className = 'sort-indicator';
    th.appendChild(ind);
  });

  function parseDate(v) {
    const t = (v || '').trim();
    // Support "YYYY-MM-DD" and "YYYY-MM-DD HH:MM[:SS]"
    if (/^\d{4}-\d{2}-\d{2}(?:\s+\d{2}:\d{2}(?::\d{2})?)?$/.test(t)) {
      return new Date(t.replace(' ', 'T'));
    }
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

  // Default sort: Date Diagnosed (col 0) DESC
  const defaultCol = 0, defaultDir = 'desc';
  const defaultTh = thead.querySelectorAll('th')[defaultCol];
  if (defaultTh) {
    defaultTh.classList.add(defaultDir === 'asc' ? 'is-sorted-asc' : 'is-sorted-desc');
    sortBy(defaultCol, defaultDir);
  }
})();

    function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'block';
    return false; // Prevent the default link behavior
}

function closeModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

function proceedLogout() {
    window.location.href='../../role';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('logoutModal');
    if (event.target == modal) {
        closeModal();
    }
}

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
    if (!isMobile()) closeMobileSidebar();
  });
})();

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