<?php
require '../php/config.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    header("Location: ../../role");
    exit();
}

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$securityPage = isset($_GET['security_page']) ? max(1, (int)$_GET['security_page']) : 1;
$securityLimit = 10;
$securityOffset = ($securityPage - 1) * $securityLimit;

$dataModPage = isset($_GET['data_page']) ? max(1, (int)$_GET['data_page']) : 1;
$dataModLimit = 10;
$dataModOffset = ($dataModPage - 1) * $dataModLimit;

$adminPage = isset($_GET['admin_page']) ? max(1, (int)$_GET['admin_page']) : 1;
$adminLimit = 10;
$adminOffset = ($adminPage - 1) * $adminLimit;

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function reportUrl($params = [], $fragment = '') {
    $base = [
        'start_date' => $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days')),
        'end_date' => $_GET['end_date'] ?? date('Y-m-d'),
        'page' => $_GET['page'] ?? 1,
        'security_page' => $_GET['security_page'] ?? 1,
        'data_page' => $_GET['data_page'] ?? 1,
        'admin_page' => $_GET['admin_page'] ?? 1
    ];

    $url = '?' . http_build_query(array_merge($base, $params));

    if ($fragment !== '') {
        $url .= '#' . ltrim($fragment, '#');
    }

    return $url;
}

/* Daily active users */
$activeUsersQuery = "SELECT DATE(timestamp) AS log_date, COUNT(DISTINCT performed_by) AS active_users 
                     FROM logs 
                     WHERE action = 'Successful Login' 
                     AND DATE(timestamp) BETWEEN :start_date AND :end_date
                     GROUP BY log_date 
                     ORDER BY log_date ASC";

$activeUsersStmt = $pdo->prepare($activeUsersQuery);
$activeUsersStmt->bindParam(':start_date', $start_date);
$activeUsersStmt->bindParam(':end_date', $end_date);
$activeUsersStmt->execute();
$activeUsers = $activeUsersStmt->fetchAll(PDO::FETCH_ASSOC);

/* Common actions */
$commonActionsQuery = "SELECT action, COUNT(action) AS count
                       FROM logs
                       WHERE DATE(timestamp) BETWEEN :start_date AND :end_date
                       GROUP BY action
                       ORDER BY count DESC
                       LIMIT :limit OFFSET :offset";

$totalCommonActionsQuery = "SELECT COUNT(DISTINCT action) AS total
                            FROM logs
                            WHERE DATE(timestamp) BETWEEN :start_date AND :end_date";

$commonActionsStmt = $pdo->prepare($commonActionsQuery);
$commonActionsStmt->bindParam(':start_date', $start_date);
$commonActionsStmt->bindParam(':end_date', $end_date);
$commonActionsStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$commonActionsStmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$commonActionsStmt->execute();
$commonActions = $commonActionsStmt->fetchAll(PDO::FETCH_ASSOC);

$totalCommonActionsStmt = $pdo->prepare($totalCommonActionsQuery);
$totalCommonActionsStmt->bindParam(':start_date', $start_date);
$totalCommonActionsStmt->bindParam(':end_date', $end_date);
$totalCommonActionsStmt->execute();
$totalCommonActions = (int)$totalCommonActionsStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = max(1, (int)ceil($totalCommonActions / $limit));

/* Today's active users */
$todayUsersQuery = "SELECT COUNT(DISTINCT performed_by) AS today_users 
                    FROM logs 
                    WHERE action = 'Successful Login' 
                    AND DATE(timestamp) = CURDATE()";

$todayUsersStmt = $pdo->prepare($todayUsersQuery);
$todayUsersStmt->execute();
$todayUsers = (int)$todayUsersStmt->fetch(PDO::FETCH_ASSOC)['today_users'];

/* User management actions */
$userManagementQuery = "SELECT action, COUNT(*) AS count, performed_by, u.full_name AS admin_name
                        FROM logs l
                        LEFT JOIN users u ON u.user_id = l.performed_by
                        WHERE (
                            action LIKE 'Added new user: %' 
                            OR action LIKE 'Terminated User %' 
                            OR action LIKE 'Change password for %'
                        )
                        AND DATE(l.timestamp) BETWEEN :start_date AND :end_date
                        GROUP BY action, performed_by, u.full_name
                        ORDER BY count DESC";

$userManagementStmt = $pdo->prepare($userManagementQuery);
$userManagementStmt->bindParam(':start_date', $start_date);
$userManagementStmt->bindParam(':end_date', $end_date);
$userManagementStmt->execute();
$userManagementActions = $userManagementStmt->fetchAll(PDO::FETCH_ASSOC);

/* Security events */
$securityQuery = "SELECT l.action, u.full_name, l.timestamp
                  FROM logs l
                  LEFT JOIN users u ON u.user_id = l.performed_by
                  WHERE (
                    l.action LIKE '%Failed%' 
                    OR l.action LIKE '%Unauthorized%' 
                    OR l.action LIKE '%Error%'
                  )
                  AND DATE(l.timestamp) BETWEEN :start_date AND :end_date
                  ORDER BY l.timestamp DESC
                  LIMIT :limit OFFSET :offset";

$totalSecurityQuery = "SELECT COUNT(*) AS total
                       FROM logs l
                       WHERE (
                        l.action LIKE '%Failed%' 
                        OR l.action LIKE '%Unauthorized%' 
                        OR l.action LIKE '%Error%'
                       )
                       AND DATE(l.timestamp) BETWEEN :start_date AND :end_date";

$securityStmt = $pdo->prepare($securityQuery);
$securityStmt->bindParam(':start_date', $start_date);
$securityStmt->bindParam(':end_date', $end_date);
$securityStmt->bindParam(':limit', $securityLimit, PDO::PARAM_INT);
$securityStmt->bindParam(':offset', $securityOffset, PDO::PARAM_INT);
$securityStmt->execute();
$securityEvents = $securityStmt->fetchAll(PDO::FETCH_ASSOC);

$totalSecurityStmt = $pdo->prepare($totalSecurityQuery);
$totalSecurityStmt->bindParam(':start_date', $start_date);
$totalSecurityStmt->bindParam(':end_date', $end_date);
$totalSecurityStmt->execute();
$totalSecurityEvents = (int)$totalSecurityStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalSecurityPages = max(1, (int)ceil($totalSecurityEvents / $securityLimit));

/* Data modifications */
$dataModQuery = "SELECT l.action, u.full_name, l.timestamp
                 FROM logs l
                 LEFT JOIN users u ON u.user_id = l.performed_by
                 WHERE l.action IN (
                    'Updated Patient Information',
                    'Added Patient Assessment',
                    'Added Diagnosis/Consultation Record',
                    'Dispensed Medicine to Patient',
                    'Added Referral',
                    'Cancelled Referral'
                 )
                 AND DATE(l.timestamp) BETWEEN :start_date AND :end_date
                 ORDER BY l.timestamp DESC
                 LIMIT :limit OFFSET :offset";

$totalDataModQuery = "SELECT COUNT(*) AS total
                      FROM logs l
                      WHERE l.action IN (
                        'Updated Patient Information',
                        'Added Patient Assessment',
                        'Added Diagnosis/Consultation Record',
                        'Dispensed Medicine to Patient',
                        'Added Referral',
                        'Cancelled Referral'
                      )
                      AND DATE(l.timestamp) BETWEEN :start_date AND :end_date";

$dataModStmt = $pdo->prepare($dataModQuery);
$dataModStmt->bindParam(':start_date', $start_date);
$dataModStmt->bindParam(':end_date', $end_date);
$dataModStmt->bindParam(':limit', $dataModLimit, PDO::PARAM_INT);
$dataModStmt->bindParam(':offset', $dataModOffset, PDO::PARAM_INT);
$dataModStmt->execute();
$dataModifications = $dataModStmt->fetchAll(PDO::FETCH_ASSOC);

$totalDataModStmt = $pdo->prepare($totalDataModQuery);
$totalDataModStmt->bindParam(':start_date', $start_date);
$totalDataModStmt->bindParam(':end_date', $end_date);
$totalDataModStmt->execute();
$totalDataModifications = (int)$totalDataModStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalDataModPages = max(1, (int)ceil($totalDataModifications / $dataModLimit));

/* Admin activities */
$adminActivitiesQuery = "SELECT l.action, u.full_name, l.timestamp
                         FROM logs l
                         LEFT JOIN users u ON u.user_id = l.performed_by
                         WHERE (
                            l.performed_by LIKE 'admin%' 
                            OR l.performed_by IN ('1', 'admin')
                         )
                         AND DATE(l.timestamp) BETWEEN :start_date AND :end_date
                         ORDER BY l.timestamp DESC
                         LIMIT :limit OFFSET :offset";

$totalAdminQuery = "SELECT COUNT(*) AS total
                    FROM logs l
                    WHERE (
                        l.performed_by LIKE 'admin%' 
                        OR l.performed_by IN ('1', 'admin')
                    )
                    AND DATE(l.timestamp) BETWEEN :start_date AND :end_date";

$adminActivitiesStmt = $pdo->prepare($adminActivitiesQuery);
$adminActivitiesStmt->bindParam(':start_date', $start_date);
$adminActivitiesStmt->bindParam(':end_date', $end_date);
$adminActivitiesStmt->bindParam(':limit', $adminLimit, PDO::PARAM_INT);
$adminActivitiesStmt->bindParam(':offset', $adminOffset, PDO::PARAM_INT);
$adminActivitiesStmt->execute();
$adminActivities = $adminActivitiesStmt->fetchAll(PDO::FETCH_ASSOC);

$totalAdminStmt = $pdo->prepare($totalAdminQuery);
$totalAdminStmt->bindParam(':start_date', $start_date);
$totalAdminStmt->bindParam(':end_date', $end_date);
$totalAdminStmt->execute();
$totalAdminActivities = (int)$totalAdminStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalAdminPages = max(1, (int)ceil($totalAdminActivities / $adminLimit));

$avgUsers = !empty($activeUsers) ? round(array_sum(array_column($activeUsers, 'active_users')) / count($activeUsers)) : 0;
$totalActions = array_sum(array_column($commonActions, 'count'));
$adminName = htmlspecialchars($_SESSION['full_name'] ?? 'Admin User', ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log Reports</title>

    <link rel="icon" href="../../img/logo.png">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/logout.css">
    <link rel="stylesheet" href="../css/reportsdesign.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<<<<<<< HEAD
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
=======
    <title>Audit Log Reports</title>

<style>
/* ── Screen-only elements hidden on print ── */
.print-letterhead { display: none; }
.print-title      { display: none; }
.print-rule       { display: none; }

/* ────────────────────────────────────────────
   @page — Remove ALL browser-generated margin
   space where URL / date / title appear.
   Setting margin to 0 eliminates the browser
   header and footer completely across Chrome,
   Edge, Firefox, and Safari.
   ──────────────────────────────────────────── */
@page {
  size: A4 portrait;
  margin: 0;
}

@media print {
  /* ── Restore safe printable area via body padding ── */
  body {
    padding: 14mm 12mm !important;
    margin: 0 !important;
    background: #fff !important;
    font-family: Arial, sans-serif !important;
    font-size: 10pt !important;
    color: #000 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  /* Hide everything that should NOT print */
  .no-print,
  #sidebar,
  nav,
  .sidebar-overlay,
  .ar-filter-bar,
  .ar-stats,
  .ar-actions,
  #logoutModal,
  #printModal,
  .ar-pagination,
  .ar-card-header i,
  .ar-card-header small {
    display: none !important;
  }

  /* Layout reset */
  #content {
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
  }

  main {
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
  }

  .head-title { display: none !important; }

  /* ── Letterhead ── */
  .print-letterhead {
    display: flex !important;
    align-items: center;
    justify-content: center;
    gap: 24px;
    margin: 0 0 6px 0;
    text-align: center;
  }
  .print-logo {
    width: 60px;
    height: 60px;
    object-fit: contain;
    flex-shrink: 0;
  }
  .print-heading {
    line-height: 1.35;
    color: #000;
    text-align: center;
  }
  .ph-line-1 { font-size: 10pt; font-weight: 400; display: block; }
  .ph-line-2 { font-size: 13pt; font-weight: 700; display: block; margin-top: 2px; }
  .ph-line-3 { font-size: 10pt; font-weight: 400; display: block; }

  .print-rule {
    display: block !important;
    height: 0;
    border: 0;
    border-top: 1.5px solid #555;
    margin: 8px 0 10px;
  }

  /* ── Report title block ── */
  .print-title {
    display: block !important;
    text-align: center;
    margin-bottom: 14px;
  }
  .print-title h2 {
    font-size: 13pt;
    font-weight: 700;
    margin: 0 0 2px;
    color: #000;
  }
  .print-title p {
    font-size: 10pt;
    color: #333;
    margin: 0;
  }

  /* ── Cards ── */
  .ar-card {
    box-shadow: none !important;
    border: 1px solid #ccc !important;
    border-radius: 0 !important;
    margin-bottom: 14px !important;
    /* Allow cards to break across pages — required for tables with many rows.
       page-break-inside: avoid clips content when card is taller than one page. */
    page-break-inside: auto;
    break-inside: auto;
    overflow: visible !important;
  }
  /* Keep the section heading attached to the first content row below it */
  .ar-card-header {
    background: #eef0f8 !important;
    padding: 8px 12px !important;
    border-bottom: 1px solid #bbb !important;
    page-break-after: avoid;
    break-after: avoid;
  }
  .ar-card-header h3 {
    font-size: 11pt !important;
    font-weight: 700 !important;
    color: #000 !important;
    margin: 0 !important;
  }

  /* ── Chart card: small enough to keep together ── */
  .ar-chart-wrap {
    padding: 10px !important;
    page-break-inside: avoid;
    break-inside: avoid;
  }
  .ar-chart-wrap canvas {
    max-height: 180px !important;
    width: 100% !important;
  }

  /* ── Tables ── */
  .ar-table-wrap {
    overflow: visible !important;
    padding: 0 !important;
  }
  .ar-table {
    width: 100% !important;
    border-collapse: collapse !important;
    font-size: 9pt !important;
    /* Allow table itself to span pages */
    page-break-inside: auto;
    break-inside: auto;
  }
  /* thead repeats on every printed page */
  .ar-table thead {
    display: table-header-group;
  }
  .ar-table tfoot {
    display: table-footer-group;
  }
  /* Each row stays whole; never split a single row across pages */
  .ar-table tr {
    page-break-inside: avoid;
    break-inside: avoid;
    orphans: 3;
    widows: 3;
  }
  .ar-table th {
    background: #eef0f8 !important;
    color: #000 !important;
    font-size: 8.5pt !important;
    font-weight: 700 !important;
    padding: 6px 8px !important;
    border: 1px solid #bbb !important;
    text-transform: uppercase;
  }
  .ar-table td {
    padding: 5px 8px !important;
    border: 1px solid #ddd !important;
    color: #000 !important;
    font-size: 9pt !important;
    vertical-align: top !important;
    word-break: break-word;
  }
  .ar-table tbody tr:hover { background: transparent !important; }

  /* ── Badges ── */
  .ar-badge {
    border-radius: 0 !important;
    padding: 1px 4px !important;
    font-size: 8.5pt !important;
    font-weight: 600 !important;
    border: 1px solid #999 !important;
    background: transparent !important;
    color: #000 !important;
  }

  /* ── Generated-by signature ── */
  #generated_by {
    margin-top: 30px !important;
    page-break-inside: avoid;
  }
  #generated_by .sig-label { font-size: 10pt; color: #000; margin-bottom: 4px; }
  #generated_by .sig-line  {
    display: block !important;
    width: 200px;
    border: 0;
    border-top: 1px solid #000;
    margin: 28px 0 4px;
  }
  #generated_by .sig-name  { font-size: 11pt; font-weight: 700; color: #000; }
  #generated_by .sig-title { font-size: 9.5pt; color: #333; }
}

/* ── Screen layout ── */
.ar-filter-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  padding: 14px 20px;
  margin-bottom: 24px;
  box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.ar-filter-bar label {
  font-size: 13px;
  font-weight: 600;
  color: #555;
  white-space: nowrap;
}
.ar-date-input {
  padding: 8px 12px;
  border: 1px solid #d0d5dd;
  border-radius: 6px;
  font-size: 14px;
  font-family: 'Poppins', sans-serif;
  color: #333;
  transition: border-color .2s;
}
.ar-date-input:focus { outline: none; border-color: #3f51b5; }
.ar-apply-btn {
  padding: 8px 20px;
  background: #3f51b5;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  font-family: 'Poppins', sans-serif;
  cursor: pointer;
  transition: background .2s;
}
.ar-apply-btn:hover { background: #303f9f; }

/* Summary stat strip */
.ar-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}
.ar-stat {
  background: #fff;
  border-radius: 10px;
  padding: 18px 20px;
  border-left: 4px solid #3f51b5;
  box-shadow: 0 1px 6px rgba(0,0,0,.07);
}
.ar-stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #1e2a5e;
  line-height: 1;
}
.ar-stat-label {
  font-size: 12px;
  color: #777;
  margin-top: 4px;
  font-weight: 500;
}

/* Section cards */
.ar-card {
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0,0,0,.08);
  margin-bottom: 24px;
  /* Use clip instead of hidden — same visual rounding without creating a scroll
     container, which can interfere with print page-break rendering. */
  overflow: clip;
}
.ar-card-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 16px 20px;
  border-bottom: 1px solid #f0f0f0;
  background: #fafbff;
}
.ar-card-header i { font-size: 20px; color: #3f51b5; }
.ar-card-header h3 {
  font-size: 15px;
  font-weight: 600;
  color: #1e2a5e;
  margin: 0;
}
.ar-card-header small {
  font-size: 12px;
  color: #999;
  margin-left: auto;
  white-space: nowrap;
}

/* Chart wrapper */
.ar-chart-wrap {
  padding: 20px;
}
.ar-chart-wrap canvas { max-height: 280px; }

/* Tables */
.ar-table-wrap { padding: 0 4px; overflow-x: auto; }
.ar-table {
  width: 100%;
  border-collapse: collapse;
  font-family: 'Poppins', sans-serif;
  font-size: 13.5px;
}
.ar-table thead tr { background: #f5f7ff; }
.ar-table th {
  padding: 11px 16px;
  text-align: left;
  font-size: 12px;
  font-weight: 600;
  color: #3f51b5;
  text-transform: uppercase;
  letter-spacing: .4px;
  border-bottom: 2px solid #e8eaf6;
}
.ar-table td {
  padding: 11px 16px;
  color: #444;
  border-bottom: 1px solid #f0f0f0;
  vertical-align: middle;
}
.ar-table tbody tr:hover { background: #f8f9ff; }
.ar-table .empty-row td {
  text-align: center;
  color: #aaa;
  padding: 24px;
  font-style: italic;
}
.ar-badge {
  display: inline-block;
  padding: 2px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
}
.ar-badge-blue  { background: #e8eaf6; color: #3f51b5; }
.ar-badge-green { background: #e8f5e9; color: #2e7d32; }
.ar-badge-red   { background: #fdecea; color: #c62828; }

/* Pagination strip */
.ar-pagination {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  padding: 12px 16px;
  border-top: 1px solid #f0f0f0;
  font-size: 13px;
  color: #666;
}
.ar-page-link {
  padding: 5px 12px;
  border: 1px solid #d0d5dd;
  border-radius: 5px;
  color: #3f51b5;
  text-decoration: none;
  font-weight: 600;
  transition: all .15s;
}
.ar-page-link:hover { background: #e8eaf6; }

/* Actions bar */
.ar-actions {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 20px;
}
.ar-print-btn {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 10px 22px;
  background: #1e2a5e;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  font-family: 'Poppins', sans-serif;
  cursor: pointer;
  transition: background .2s;
}
.ar-print-btn i { font-size: 18px; }
.ar-print-btn:hover { background: #2d3a80; }

/* Generated-by block */
#generated_by {
  display: block;
  margin: 28px 0 0 4px;
  color: #000;
}
#generated_by .sig-label { font-size: 13px; margin-bottom: 6px; color: #444; }
#generated_by .sig-line  { display: none; }
#generated_by .sig-name  { font-weight: 700; font-size: 15px; margin-top: 2px; }
#generated_by .sig-title { font-size: 12px; color: #666; }
</style>
>>>>>>> 23984592a94087055b071541e42a022dc90209a3
</head>

<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<<<<<<< HEAD
<section id="sidebar">
    <a href="#" class="sidebar-brand">
        <img src="../../img/logo.png" alt="Admin Logo" class="brand-logo">

        <div class="brand-text">
            <span class="brand-name">Hello Admin</span>
        </div>
    </a>

    <div class="sidebar-scroll">
        <div class="sidebar-section-label">Main Menu</div>

        <ul class="side-menu top">
            <li>
                <a href="../php/admin_dashboard2" data-tooltip="Dashboard">
                    <i class="bx bxs-dashboard nav-icon"></i>
                    <span class="nav-label">Dashboard</span>
                </a>
            </li>

            <li>
                <a href="../php/activity_logs" data-tooltip="Activity Logs">
                    <i class="bx bxs-user nav-icon"></i>
                    <span class="nav-label">Activity Logs</span>
                </a>
            </li>

            <li>
                <a href="../php/admin_user" data-tooltip="User Management">
                    <i class="bx bxs-notepad nav-icon"></i>
                    <span class="nav-label">User Management</span>
                </a>
            </li>

            <li class="active">
                <a href="../reports" data-tooltip="Reports">
                    <i class="bx bxs-report nav-icon"></i>
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
            <img src="../../img/admin.png" alt="Admin User">

            <div class="sidebar-user-info">
                <div class="user-name" id="sidebarUserName"><?php echo $adminName; ?></div>
                <div class="user-role">Administrator</div>
=======
    <!-- Sidebar -->
    <section id="sidebar">
        <a href="#" class="sidebar-brand">
            <img src="../../img/logo.png" alt="Admin Logo" class="brand-logo">
            <div class="brand-text">
                <span class="brand-name">Hello Admin</span>
            </div>
        </a>
        <div class="sidebar-scroll">
            <div class="sidebar-section-label">Main Menu</div>
            <ul class="side-menu top">
                <li>
                    <a href="../php/admin_dashboard2" data-tooltip="Dashboard">
                        <i class="bx bxs-dashboard nav-icon"></i>
                        <span class="nav-label">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="../php/activity_logs" data-tooltip="Activity Logs">
                        <i class="bx bxs-user nav-icon"></i>
                        <span class="nav-label">Activity Logs</span>
                    </a>
                </li>
                <li>
                    <a href="../php/admin_user" data-tooltip="User Management">
                        <i class="bx bxs-notepad nav-icon"></i>
                        <span class="nav-label">User management</span>
                    </a>
                </li>
                <li class="active">
                    <a href="../reports" data-tooltip="Reports">
                        <i class="bx bxs-report nav-icon"></i>
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
                <img src="../../img/admin.png" alt="Admin User">
                <div class="sidebar-user-info">
                    <div class="user-name" id="sidebarUserName">Admin User</div>
                    <div class="user-role">Administrator</div>
                </div>
>>>>>>> 23984592a94087055b071541e42a022dc90209a3
            </div>
        </div>
    </div>
</section>

<<<<<<< HEAD
<section id="content">
    <nav>
        <button type="button" class="nav-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="bx bx-menu"></i>
        </button>

        <div class="nav-search">
            <input 
                type="search" 
                id="patientSearch" 
                placeholder="Search audit reports..." 
                name="search" 
                autocomplete="off"
            >

            <button type="button" id="searchButton" aria-label="Search">
                <i class="bx bx-search"></i>
            </button>

            <div id="resultDropdown" class="dropdown-content"></div>
        </div>
    </nav>

    <main>
        <div class="audit-report-page">
            <div class="page-title">
                <div>
                    <h1>Audit Log Reports</h1>
                    <p>Review system activity, security events, user management changes, and administrator actions.</p>
                </div>
            </div>

            <section class="filter-panel">
                <div class="section-heading">
                    <div class="section-heading-left">
                        <span class="section-icon">
                            <i class="bx bx-filter-alt"></i>
                        </span>

                        <div>
                            <h2>Report Filters</h2>
                            <p>Select a date range to update the audit report data.</p>
                        </div>
                    </div>
                </div>

                <div class="date-filters">
                    <div class="date-field">
                        <label for="start-date">Start Date</label>
                        <input type="text" id="start-date" name="start_date" class="date-input" placeholder="Start Date" value="<?php echo e($start_date); ?>">
                    </div>

                    <div class="date-field">
                        <label for="end-date">End Date</label>
                        <input type="text" id="end-date" name="end_date" class="date-input" placeholder="End Date" value="<?php echo e($end_date); ?>">
                    </div>

                    <button type="button" id="apply-filter" class="filter-button">
                        <i class="bx bx-check"></i>
                        Apply Filter
                    </button>
                </div>
            </section>

            <div class="summary-grid no-print">
                <div class="summary-card">
                    <span class="summary-icon">
                        <i class="bx bx-user-check"></i>
                    </span>

                    <div>
                        <p>Today's Active Users</p>
                        <h3><?php echo number_format($todayUsers); ?></h3>
                    </div>
                </div>

                <div class="summary-card">
                    <span class="summary-icon">
                        <i class="bx bx-line-chart"></i>
                    </span>

                    <div>
                        <p>Average Active Users</p>
                        <h3><?php echo number_format($avgUsers); ?></h3>
                    </div>
                </div>

                <div class="summary-card">
                    <span class="summary-icon">
                        <i class="bx bx-list-check"></i>
                    </span>

                    <div>
                        <p>Total Listed Actions</p>
                        <h3><?php echo number_format($totalActions); ?></h3>
                    </div>
                </div>

                <div class="summary-card">
                    <span class="summary-icon">
                        <i class="bx bx-calendar"></i>
                    </span>

                    <div>
                        <p>Date Range</p>
                        <h3><?php echo e($start_date); ?> to <?php echo e($end_date); ?></h3>
=======
    <!-- Main Content -->
    <section id="content">
        <nav>
            <button class="nav-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="bx bx-menu"></i>
            </button>
            <div class="nav-search" style="position: relative;">
                <input type="search" id="patientSearch" placeholder="Search audit reports..." name="search" autocomplete="off">
                <button type="button" id="searchButton" aria-label="Search">
                    <i class="bx bx-search"></i>
                </button>
                <div id="resultDropdown" class="dropdown-content"></div>
            </div>
        </nav>

        <main>
            <div class="head-title">
                <div class="left">
                    <h1>Audit Log Reports</h1>
                    <ul class="breadcrumb">
                        <li><a href="#">Audit Log Reports</a></li>
                        <li><i class="bx bx-chevron-right"></i></li>
                        <li><a class="active" href="../reports">Go back</a></li>
                    </ul>
                </div>
            </div>

            <!-- Logout Modal -->
            <div id="logoutModal" class="logout-modal">
                <div class="logout-modal-content">
                    <div class="logout-modal-header"><h3>Confirm Logout</h3></div>
                    <div class="logout-modal-body"><p>Are you sure you want to logout?</p></div>
                    <div class="logout-modal-footer">
                        <button onclick="closeModal()" class="logout-cancel-btn">Cancel</button>
                        <button onclick="proceedLogout()" class="logout-confirm-btn">Yes, Logout</button>
>>>>>>> 23984592a94087055b071541e42a022dc90209a3
                    </div>
                </div>
            </div>

<<<<<<< HEAD
            <div class="print-area">
                <div class="print-letterhead">
                    <img src="../../img/daet_logo.png" alt="Left Logo" class="print-logo">

                    <div class="print-heading">
                        <div class="ph-line-1">Republic of the Philippines</div>
                        <div class="ph-line-1">Department of Health</div>
                        <div class="ph-line-1">Province of Camarines Norte</div>
                        <div class="ph-line-2">Municipality of Daet</div>
                        <div class="ph-line-3">Admin Reports</div>
                    </div>

                    <img src="../../img/mho_logo.png" alt="Right Logo" class="print-logo">
                </div>

                <hr class="print-rule">

                <div class="title">
                    <h2>System Activity Logs Report</h2>
                    <div class="print-sub">
                        (<?php echo e($start_date . ' to ' . $end_date); ?>)
                    </div>
                </div>

                <div class="report-content">
                    <section class="report-section-card" id="all-actions">
                        <div class="card-header">
                            <div>
                                <h3>All Actions</h3>
                                <small>Most common system actions within the selected date range.</small>
                            </div>
                        </div>

                        <div class="table-wrap">
                            <table class="data-table" id="actions-table">
                                <thead>
                                    <tr>
                                        <th>Action</th>
                                        <th>Count</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php
                                    $maxCount = !empty($commonActions) ? max(array_column($commonActions, 'count')) : 0;

                                    foreach ($commonActions as $action):
                                        $percentage = $maxCount > 0 ? ($action['count'] / $maxCount) * 100 : 0;
                                    ?>
                                        <tr>
                                            <td><?php echo e($action['action']); ?></td>
                                            <td><?php echo number_format($action['count']); ?></td>
                                            <td><?php echo round($percentage, 1); ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($commonActions)): ?>
                                        <tr>
                                            <td colspan="3" class="empty-table">No actions found in selected period.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="pagination" aria-label="All actions pagination">
                            <div class="pagination-info">
                                <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                            </div>

                            <div class="pagination-controls">
                                <?php if ($page > 1): ?>
                                    <a href="<?php echo reportUrl(['page' => $page - 1], 'all-actions'); ?>" class="page-link" data-preserve-scroll>Back</a>
                                <?php else: ?>
                                    <span class="page-link disabled" aria-disabled="true">Back</span>
                                <?php endif; ?>

                                <?php if ($page < $totalPages): ?>
                                    <a href="<?php echo reportUrl(['page' => $page + 1], 'all-actions'); ?>" class="page-link" data-preserve-scroll>Next</a>
                                <?php else: ?>
                                    <span class="page-link disabled" aria-disabled="true">Next</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <section class="report-section-card" id="user-management-actions">
                        <div class="card-header">
                            <div>
                                <h3>User Management Actions</h3>
                                <small>Track all user account modifications.</small>
                            </div>
                        </div>

                        <div class="table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Action</th>
                                        <th>Performed By</th>
                                        <th>Count</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($userManagementActions as $action): ?>
                                        <tr>
                                            <td><?php echo e($action['action']); ?></td>
                                            <td><?php echo e($action['admin_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo number_format($action['count']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($userManagementActions)): ?>
                                        <tr>
                                            <td colspan="3" class="empty-table">No user management actions in selected period.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="report-section-card" id="security-events">
                        <div class="card-header">
                            <div>
                                <h3>Security Events</h3>
                                <small>Failed attempts and security-related events.</small>
                            </div>
                        </div>

                        <div class="table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Action</th>
                                        <th>User</th>
                                        <th>Timestamp</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($securityEvents as $event): ?>
                                        <tr>
                                            <td><?php echo e($event['action']); ?></td>
                                            <td><?php echo e($event['full_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($event['timestamp'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($securityEvents)): ?>
                                        <tr>
                                            <td colspan="3" class="empty-table">No security events in selected period.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="pagination" aria-label="Security events pagination">
                            <div class="pagination-info">
                                <span>Page <?php echo $securityPage; ?> of <?php echo $totalSecurityPages; ?></span>
                            </div>

                            <div class="pagination-controls">
                                <?php if ($securityPage > 1): ?>
                                    <a href="<?php echo reportUrl(['security_page' => $securityPage - 1], 'security-events'); ?>" class="page-link" data-preserve-scroll>Back</a>
                                <?php else: ?>
                                    <span class="page-link disabled" aria-disabled="true">Back</span>
                                <?php endif; ?>

                                <?php if ($securityPage < $totalSecurityPages): ?>
                                    <a href="<?php echo reportUrl(['security_page' => $securityPage + 1], 'security-events'); ?>" class="page-link" data-preserve-scroll>Next</a>
                                <?php else: ?>
                                    <span class="page-link disabled" aria-disabled="true">Next</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <section class="report-section-card" id="data-modifications">
                        <div class="card-header">
                            <div>
                                <h3>Data Modifications</h3>
                                <small>Patient records and medical data changes.</small>
                            </div>
                        </div>

                        <div class="table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Action</th>
                                        <th>Performed By</th>
                                        <th>Timestamp</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($dataModifications as $mod): ?>
                                        <tr>
                                            <td><?php echo e($mod['action']); ?></td>
                                            <td><?php echo e($mod['full_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($mod['timestamp'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($dataModifications)): ?>
                                        <tr>
                                            <td colspan="3" class="empty-table">No data modifications in selected period.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="pagination" aria-label="Data modifications pagination">
                            <div class="pagination-info">
                                <span>Page <?php echo $dataModPage; ?> of <?php echo $totalDataModPages; ?></span>
                            </div>

                            <div class="pagination-controls">
                                <?php if ($dataModPage > 1): ?>
                                    <a href="<?php echo reportUrl(['data_page' => $dataModPage - 1], 'data-modifications'); ?>" class="page-link" data-preserve-scroll>Back</a>
                                <?php else: ?>
                                    <span class="page-link disabled" aria-disabled="true">Back</span>
                                <?php endif; ?>

                                <?php if ($dataModPage < $totalDataModPages): ?>
                                    <a href="<?php echo reportUrl(['data_page' => $dataModPage + 1], 'data-modifications'); ?>" class="page-link" data-preserve-scroll>Next</a>
                                <?php else: ?>
                                    <span class="page-link disabled" aria-disabled="true">Next</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <section class="report-section-card" id="admin-activities">
                        <div class="card-header">
                            <div>
                                <h3>Admin Activities</h3>
                                <small>All actions performed by administrators.</small>
                            </div>
                        </div>

                        <div class="table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Action</th>
                                        <th>Performed By</th>
                                        <th>Timestamp</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($adminActivities as $activity): ?>
                                        <tr>
                                            <td><?php echo e($activity['action']); ?></td>
                                            <td><?php echo e($activity['full_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($activity['timestamp'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($adminActivities)): ?>
                                        <tr>
                                            <td colspan="3" class="empty-table">No admin activities in selected period.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="pagination" aria-label="Admin activities pagination">
                            <div class="pagination-info">
                                <span>Page <?php echo $adminPage; ?> of <?php echo $totalAdminPages; ?></span>
                            </div>

                            <div class="pagination-controls">
                                <?php if ($adminPage > 1): ?>
                                    <a href="<?php echo reportUrl(['admin_page' => $adminPage - 1], 'admin-activities'); ?>" class="page-link" data-preserve-scroll>Back</a>
                                <?php else: ?>
                                    <span class="page-link disabled" aria-disabled="true">Back</span>
                                <?php endif; ?>

                                <?php if ($adminPage < $totalAdminPages): ?>
                                    <a href="<?php echo reportUrl(['admin_page' => $adminPage + 1], 'admin-activities'); ?>" class="page-link" data-preserve-scroll>Next</a>
                                <?php else: ?>
                                    <span class="page-link disabled" aria-disabled="true">Next</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                </div>

                <div id="generated_by"></div>
            </div>

            <div class="form-submit no-print">
                <button type="button" class="btn-export" onclick="exportToExcel()">
                    <i class="bx bx-file"></i>
                    Export to Excel
                </button>

                <button type="button" class="btn-export" onclick="exportToPDF()">
                    <i class="bx bx-file-pdf"></i>
                    Export to PDF
                </button>

                <button type="button" class="btn-print" onclick="confirmPrint()">
                    <i class="bx bx-printer"></i>
                    Print Report
                </button>
            </div>
        </div>
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
            <button type="button" class="logout-cancel-btn" onclick="closeModal()">Cancel</button>
            <button type="button" class="logout-confirm-btn" onclick="proceedLogout()">Yes, Logout</button>
        </div>
    </div>
</div>

<div id="printModal" class="logout-modal">
    <div class="logout-modal-content">
        <div class="logout-modal-header">
            <h3>Confirm Print</h3>
        </div>

        <div class="logout-modal-body">
            <p>Are you sure you want to print this report?</p>
        </div>

        <div class="logout-modal-footer">
            <button type="button" class="logout-cancel-btn" onclick="closePrintModal()">Cancel</button>
            <button type="button" class="logout-confirm-btn" onclick="proceedPrint()">Yes, Print</button>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    setupAdminName();
    setupDatePickers();
    setupDateFilter();
    setupSignatureBlock();
    setupSidebar();
    setupAuditSearch();
    setupPaginationScrollMemory();
});

function setupAdminName() {
    fetch("../php/getUserName")
        .then(response => response.json())
        .then(data => {
            const sidebarNameEl = document.getElementById("sidebarUserName");

            if (sidebarNameEl) {
                sidebarNameEl.textContent = data.full_name || "Admin User";
            }
        })
        .catch(error => {
            console.error("Error fetching user name:", error);

            const sidebarNameEl = document.getElementById("sidebarUserName");

            if (sidebarNameEl) {
                sidebarNameEl.textContent = "Admin User";
=======
            <!-- Print confirm Modal -->
            <div id="printModal" class="logout-modal">
                <div class="logout-modal-content">
                    <div class="logout-modal-header"><h3>Confirm Print</h3></div>
                    <div class="logout-modal-body"><p>Are you sure you want to print this report?</p></div>
                    <div class="logout-modal-footer">
                        <button onclick="closePrintModal()" class="logout-cancel-btn">Cancel</button>
                        <button onclick="proceedPrint()" class="logout-confirm-btn">Yes, Print</button>
                    </div>
                </div>
            </div>

            <!-- Date filter -->
            <div class="ar-filter-bar no-print">
                <label>Date Range:</label>
                <input type="text" id="start-date" class="ar-date-input" placeholder="Start Date" value="<?php echo $start_date; ?>">
                <span style="color:#888;">to</span>
                <input type="text" id="end-date" class="ar-date-input" placeholder="End Date" value="<?php echo $end_date; ?>">
                <button id="apply-filter" class="ar-apply-btn">Apply Filter</button>
            </div>

            <!-- Summary stats -->
            <div class="ar-stats no-print">
                <div class="ar-stat">
                    <div class="ar-stat-value"><?php echo $todayUsers; ?></div>
                    <div class="ar-stat-label">Logins Today</div>
                </div>
                <div class="ar-stat">
                    <div class="ar-stat-value"><?php echo $avgUsers; ?></div>
                    <div class="ar-stat-label">Avg. Daily Active Users</div>
                </div>
                <div class="ar-stat">
                    <div class="ar-stat-value"><?php echo number_format($totalActions); ?></div>
                    <div class="ar-stat-label">Total Actions in Range</div>
                </div>
                <div class="ar-stat">
                    <div class="ar-stat-value"><?php echo $totalCommonActions; ?></div>
                    <div class="ar-stat-label">Distinct Action Types</div>
                </div>
            </div>

            <!-- Print button -->
            <div class="ar-actions no-print">
                <button class="ar-print-btn" onclick="confirmPrint()">
                    <i class="bx bx-printer"></i> Print Report
                </button>
            </div>

            <!-- Print-only letterhead -->
            <div class="print-letterhead">
                <img src="../../img/daet_logo.png" alt="Left Logo" class="print-logo">
                <div class="print-heading">
                    <div class="ph-line-1">Republic of the Philippines</div>
                    <div class="ph-line-1">Department of Health</div>
                    <div class="ph-line-1">Province of Camarines Norte</div>
                    <div class="ph-line-2">Municipality of Daet</div>
                    <div class="ph-line-3">Rural Health Unit</div>
                </div>
                <img src="../../img/mho_logo.png" alt="Right Logo" class="print-logo">
            </div>
            <hr class="print-rule">
            <div class="print-title">
                <h2>System Activity Logs Report</h2>
                <p><?php echo htmlspecialchars($start_date . ' to ' . $end_date); ?></p>
            </div>

            <!-- 1. Action Frequency Chart -->
            <div class="ar-card">
                <div class="ar-card-header">
                    <i class="bx bx-bar-chart-alt-2"></i>
                    <h3>Action Frequency</h3>
                    <small><?php echo htmlspecialchars($start_date); ?> &ndash; <?php echo htmlspecialchars($end_date); ?></small>
                </div>
                <div class="ar-chart-wrap">
                    <canvas id="commonActionsChart"></canvas>
                </div>
            </div>

            <!-- 2. All Actions Table -->
            <div class="ar-card">
                <div class="ar-card-header">
                    <i class="bx bx-list-ul"></i>
                    <h3>All Actions</h3>
                    <small>By frequency</small>
                </div>
                <div class="ar-table-wrap">
                    <table class="ar-table" id="actions-table">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Count</th>
                                <th>% of Most Common</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $maxCount = !empty($commonActions) ? max(array_column($commonActions, 'count')) : 0;
                            foreach ($commonActions as $action):
                                $pct = $maxCount > 0 ? round(($action['count'] / $maxCount) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($action['action']); ?></td>
                                <td><span class="ar-badge ar-badge-blue"><?php echo number_format($action['count']); ?></span></td>
                                <td><?php echo $pct; ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($commonActions)): ?>
                            <tr class="empty-row"><td colspan="3">No actions recorded in selected period</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="ar-pagination">
                    <?php if ($page > 1): ?>
                        <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&page=<?php echo $page - 1; ?>" class="ar-page-link">&lsaquo; Previous</a>
                    <?php endif; ?>
                    <span>Page <?php echo $page; ?> of <?php echo max(1, $totalPages); ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&page=<?php echo $page + 1; ?>" class="ar-page-link">Next &rsaquo;</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 3. User Management Actions -->
            <div class="ar-card">
                <div class="ar-card-header">
                    <i class="bx bx-user-check"></i>
                    <h3>User Management Actions</h3>
                    <small>Account additions, terminations &amp; password changes</small>
                </div>
                <div class="ar-table-wrap">
                    <table class="ar-table">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Performed By</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userManagementActions as $action): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($action['action']); ?></td>
                                <td><?php echo htmlspecialchars($action['admin_name'] ?? 'N/A'); ?></td>
                                <td><span class="ar-badge ar-badge-green"><?php echo number_format($action['count']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($userManagementActions)): ?>
                            <tr class="empty-row"><td colspan="3">No user management actions in selected period</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. Security Events -->
            <div class="ar-card">
                <div class="ar-card-header">
                    <i class="bx bx-shield-x"></i>
                    <h3>Security Events</h3>
                    <small>Failed attempts &amp; security-related events</small>
                </div>
                <div class="ar-table-wrap">
                    <table class="ar-table">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>User</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($securityEvents as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['action']); ?></td>
                                <td><?php echo htmlspecialchars($event['full_name'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($event['timestamp'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($securityEvents)): ?>
                            <tr class="empty-row"><td colspan="3">No security events in selected period</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="ar-pagination">
                    <?php if ($securityPage > 1): ?>
                        <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&page=<?php echo $page; ?>&security_page=<?php echo $securityPage - 1; ?>" class="ar-page-link">&lsaquo; Previous</a>
                    <?php endif; ?>
                    <span>Page <?php echo $securityPage; ?> of <?php echo max(1, $totalSecurityPages); ?></span>
                    <?php if ($securityPage < $totalSecurityPages): ?>
                        <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&page=<?php echo $page; ?>&security_page=<?php echo $securityPage + 1; ?>" class="ar-page-link">Next &rsaquo;</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 5. Data Modifications -->
            <div class="ar-card">
                <div class="ar-card-header">
                    <i class="bx bx-edit"></i>
                    <h3>Data Modifications</h3>
                    <small>Patient records &amp; medical data changes</small>
                </div>
                <div class="ar-table-wrap">
                    <table class="ar-table">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Performed By</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dataModifications as $mod): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($mod['action']); ?></td>
                                <td><?php echo htmlspecialchars($mod['full_name'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($mod['timestamp'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($dataModifications)): ?>
                            <tr class="empty-row"><td colspan="3">No data modifications in selected period</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="ar-pagination">
                    <?php if ($dataModPage > 1): ?>
                        <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&page=<?php echo $page; ?>&security_page=<?php echo $securityPage; ?>&data_page=<?php echo $dataModPage - 1; ?>" class="ar-page-link">&lsaquo; Previous</a>
                    <?php endif; ?>
                    <span>Page <?php echo $dataModPage; ?> of <?php echo max(1, $totalDataModPages); ?></span>
                    <?php if ($dataModPage < $totalDataModPages): ?>
                        <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&page=<?php echo $page; ?>&security_page=<?php echo $securityPage; ?>&data_page=<?php echo $dataModPage + 1; ?>" class="ar-page-link">Next &rsaquo;</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 6. Admin Activities -->
            <div class="ar-card">
                <div class="ar-card-header">
                    <i class="bx bx-cog"></i>
                    <h3>Admin Activities</h3>
                    <small>All actions performed by administrators</small>
                </div>
                <div class="ar-table-wrap">
                    <table class="ar-table">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Performed By</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($adminActivities as $activity): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                <td><?php echo htmlspecialchars($activity['full_name'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($activity['timestamp'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($adminActivities)): ?>
                            <tr class="empty-row"><td colspan="3">No admin activities in selected period</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="ar-pagination">
                    <?php if ($adminPage > 1): ?>
                        <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&page=<?php echo $page; ?>&security_page=<?php echo $securityPage; ?>&data_page=<?php echo $dataModPage; ?>&admin_page=<?php echo $adminPage - 1; ?>" class="ar-page-link">&lsaquo; Previous</a>
                    <?php endif; ?>
                    <span>Page <?php echo $adminPage; ?> of <?php echo max(1, $totalAdminPages); ?></span>
                    <?php if ($adminPage < $totalAdminPages): ?>
                        <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&page=<?php echo $page; ?>&security_page=<?php echo $securityPage; ?>&data_page=<?php echo $dataModPage; ?>&admin_page=<?php echo $adminPage + 1; ?>" class="ar-page-link">Next &rsaquo;</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Generated-by signature (visible on print) -->
            <div id="generated_by"></div>

        </main>
    </section>

<script>
    // Fetch and populate sidebar username + signature block
    fetch('../php/getUserName')
        .then(r => r.json())
        .then(data => {
            const fullName = (data && data.full_name) ? data.full_name : 'Admin User';
            const sidebarNameEl = document.getElementById('sidebarUserName');
            if (sidebarNameEl) sidebarNameEl.textContent = fullName;

            const gb = document.getElementById('generated_by');
            if (gb) {
                gb.innerHTML = `
                    <div class="sig-label">Report Generated by:</div>
                    <hr class="sig-line">
                    <div class="sig-name">${fullName}</div>
                    <div class="sig-title">Administrator</div>
                `;
            }
        })
        .catch(() => {
            const gb = document.getElementById('generated_by');
            if (gb) {
                gb.innerHTML = `
                    <div class="sig-label">Report Generated by:</div>
                    <hr class="sig-line">
                    <div class="sig-name">________________</div>
                    <div class="sig-title">Administrator</div>
                `;
>>>>>>> 23984592a94087055b071541e42a022dc90209a3
            }
        });
}

<<<<<<< HEAD
function setupDatePickers() {
    flatpickr("#start-date", {
        dateFormat: "Y-m-d"
    });

    flatpickr("#end-date", {
        dateFormat: "Y-m-d"
    });
}

function setupDateFilter() {
    const filterButton = document.getElementById("apply-filter");

    if (!filterButton) {
        return;
    }

    filterButton.addEventListener("click", function () {
        const startDate = document.getElementById("start-date").value;
        const endDate = document.getElementById("end-date").value;

        if (startDate && endDate) {
            window.location.href = `admin_reports?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;
        } else {
            alert("Please select both start and end dates.");
        }
    });
}

function setupSignatureBlock() {
    fetch("../php/getUserName")
        .then(response => response.json())
        .then(data => {
            const fullName = data && data.full_name ? data.full_name : "";
            const generatedBy = document.getElementById("generated_by");

            if (!generatedBy) {
                return;
            }

            generatedBy.innerHTML = `
                <div class="sig-label">Report Generated by:</div>
                <hr class="sig-line">
                <div class="sig-name"></div>
                <div class="sig-title">Administrator</div>
            `;

            generatedBy.querySelector(".sig-name").textContent = fullName || "________________";
        })
        .catch(() => {
            const generatedBy = document.getElementById("generated_by");

            if (!generatedBy) {
                return;
            }

            generatedBy.innerHTML = `
                <div class="sig-label">Report Generated by:</div>
                <hr class="sig-line">
                <div class="sig-name">________________</div>
                <div class="sig-title">Administrator</div>
            `;
        });
}

function setupSidebar() {
    const sidebar = document.getElementById("sidebar");
    const toggle = document.getElementById("sidebarToggle");
    const overlay = document.getElementById("sidebarOverlay");
    const MOBILE_BP = 768;

    if (!sidebar || !toggle || !overlay) {
        return;
    }

    function isMobile() {
        return window.innerWidth <= MOBILE_BP;
    }

    function closeMobileSidebar() {
        sidebar.classList.remove("mobile-open");
        overlay.classList.remove("active");
        document.body.style.overflow = "";
    }

    toggle.addEventListener("click", function () {
        if (isMobile()) {
            const open = sidebar.classList.toggle("mobile-open");

            overlay.classList.toggle("active", open);
            document.body.style.overflow = open ? "hidden" : "";
        } else {
            sidebar.classList.toggle("collapsed");
        }
    });

    overlay.addEventListener("click", closeMobileSidebar);

    window.addEventListener("resize", function () {
        if (!isMobile()) {
            closeMobileSidebar();
        }
    });
}

function setupAuditSearch() {
    const searchInput = document.getElementById("patientSearch");
    const searchButton = document.getElementById("searchButton");

    function filterAuditCards() {
        const searchTerm = (searchInput?.value || "").toLowerCase().trim();

        document.querySelectorAll(".report-section-card").forEach(card => {
            card.style.display = card.textContent.toLowerCase().includes(searchTerm) ? "" : "none";
        });
    }

    if (searchInput) {
        searchInput.addEventListener("input", filterAuditCards);

        searchInput.addEventListener("keypress", function (event) {
            if (event.key === "Enter") {
                event.preventDefault();
                filterAuditCards();
            }
        });
    }

    if (searchButton) {
        searchButton.addEventListener("click", filterAuditCards);
    }
}


function setupPaginationScrollMemory() {
    const storageKey = "auditReportScrollY";

    document.querySelectorAll("[data-preserve-scroll]").forEach(link => {
        link.addEventListener("click", function () {
            sessionStorage.setItem(storageKey, String(window.scrollY));
        });
    });

    const savedScrollY = sessionStorage.getItem(storageKey);

    if (savedScrollY !== null) {
        sessionStorage.removeItem(storageKey);

        requestAnimationFrame(() => {
            window.scrollTo({
                top: Number(savedScrollY),
                left: 0,
                behavior: "auto"
            });
        });

        return;
    }

    if (window.location.hash) {
        const target = document.querySelector(window.location.hash);

        if (target) {
            requestAnimationFrame(() => {
                target.scrollIntoView({
                    behavior: "auto",
                    block: "start"
                });
            });
        }
    }
}

function confirmLogout() {
    const modal = document.getElementById("logoutModal");

    if (modal) {
        modal.style.display = "grid";
    }

    return false;
}

function closeModal() {
    const modal = document.getElementById("logoutModal");

    if (modal) {
        modal.style.display = "none";
    }
}

function proceedLogout() {
    window.location.href = "../php/logout";
}

function confirmPrint() {
    const modal = document.getElementById("printModal");

    if (modal) {
        modal.style.display = "grid";
    }
}

function closePrintModal() {
    const modal = document.getElementById("printModal");

    if (modal) {
        modal.style.display = "none";
    }
}

function proceedPrint() {
    closePrintModal();

    setTimeout(function () {
        window.print();
    }, 120);
}

window.addEventListener("click", function (event) {
    const logoutModal = document.getElementById("logoutModal");
    const printModal = document.getElementById("printModal");

    if (event.target === logoutModal) {
        closeModal();
    }

    if (event.target === printModal) {
        closePrintModal();
    }
});

function exportToExcel() {
    const tables = document.querySelectorAll(".data-table");
    const workbook = XLSX.utils.book_new();

    tables.forEach((table, index) => {
        const worksheet = XLSX.utils.table_to_sheet(table);
        const section = table.closest(".report-section-card");
        const title = section?.querySelector(".card-header h3")?.textContent.trim() || `Sheet${index + 1}`;

        XLSX.utils.book_append_sheet(workbook, worksheet, title.substring(0, 31));
    });

    XLSX.writeFile(workbook, "admin_audit_reports.xlsx");
}

function exportToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    doc.setFontSize(16);
    doc.text("System Activity Logs Report", 20, 20);

    doc.setFontSize(11);
    doc.text("Date Range: <?php echo e($start_date . ' to ' . $end_date); ?>", 20, 30);

    let yPosition = 46;

    document.querySelectorAll(".report-section-card").forEach(section => {
        const header = section.querySelector(".card-header h3");

        if (header) {
            doc.setFontSize(13);
            doc.text(header.textContent.trim(), 20, yPosition);
            yPosition += 8;
        }

        const rows = section.querySelectorAll("table tr");

        rows.forEach(row => {
            const cells = row.querySelectorAll("th, td");
            let xPosition = 20;

            cells.forEach(cell => {
                const text = cell.textContent.trim().substring(0, 28);
                doc.setFontSize(8);
                doc.text(text, xPosition, yPosition);
                xPosition += 58;
            });

            yPosition += 6;

            if (yPosition > 280) {
                doc.addPage();
                yPosition = 20;
            }
        });

        yPosition += 10;

        if (yPosition > 280) {
            doc.addPage();
            yPosition = 20;
        }
    });

    doc.save("admin_audit_reports.pdf");
}
</script>

=======
    // Date pickers
    flatpickr("#start-date", { dateFormat: "Y-m-d" });
    flatpickr("#end-date",   { dateFormat: "Y-m-d" });

    // Apply filter
    document.getElementById('apply-filter').addEventListener('click', function () {
        const startDate = document.getElementById('start-date').value;
        const endDate   = document.getElementById('end-date').value;
        if (startDate && endDate) {
            window.location.href = `admin_reports?start_date=${startDate}&end_date=${endDate}`;
        } else {
            alert('Please select both start and end dates.');
        }
    });

    // Action Frequency Chart
    const commonActionsData = <?php echo json_encode($commonActions); ?>;
    const actionLabels = commonActionsData.map(e => e.action);
    const actionCounts = commonActionsData.map(e => parseInt(e.count));

    const actionsCtx = document.getElementById('commonActionsChart');
    if (actionsCtx && actionLabels.length > 0) {
        new Chart(actionsCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: actionLabels,
                datasets: [{
                    label: 'Count',
                    data: actionCounts,
                    backgroundColor: 'rgba(63, 81, 181, 0.75)',
                    borderColor: 'rgba(63, 81, 181, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.x} occurrences` } }
                },
                scales: {
                    x: { beginAtZero: true, grid: { color: '#f0f0f0' }, title: { display: true, text: 'Count' } },
                    y: { grid: { display: false } }
                }
            }
        });
    }

    // Logout / print modals
    function confirmLogout() { document.getElementById('logoutModal').style.display = 'block'; return false; }
    function closeModal()     { document.getElementById('logoutModal').style.display = 'none'; }
    function proceedLogout()  { window.location.href = '../php/logout'; }

    function confirmPrint()   { document.getElementById('printModal').style.display = 'block'; }
    function closePrintModal(){ document.getElementById('printModal').style.display = 'none'; }
    function proceedPrint()   {
        closePrintModal();
        // Temporarily blank the document title so browsers don't print
        // "Audit Log Reports" in the header/footer area.
        const _title = document.title;
        document.title = '';
        window.print();
        document.title = _title;
    }

    // Belt-and-suspenders: also handle the native beforeprint event
    // (covers Ctrl+P and right-click > Print paths)
    let _savedTitle = '';
    window.addEventListener('beforeprint', function () {
        _savedTitle = document.title;
        document.title = '';
    });
    window.addEventListener('afterprint', function () {
        document.title = _savedTitle;
    });

    window.onclick = function (event) {
        const lm = document.getElementById('logoutModal');
        const pm = document.getElementById('printModal');
        if (event.target == lm) closeModal();
        if (event.target == pm) closePrintModal();
    };

    // Navbar search – filter visible cards by header text
    document.addEventListener("DOMContentLoaded", () => {
        const searchInput  = document.getElementById("patientSearch");
        const searchButton = document.getElementById("searchButton");
        function filterCards() {
            const term = (searchInput?.value || "").toLowerCase().trim();
            document.querySelectorAll(".ar-card").forEach(card => {
                card.style.display = card.textContent.toLowerCase().includes(term) ? "" : "none";
            });
        }
        if (searchInput && searchButton) {
            searchInput.addEventListener("input", filterCards);
            searchInput.addEventListener("keypress", e => { if (e.key === "Enter") { e.preventDefault(); filterCards(); } });
            searchButton.addEventListener("click", filterCards);
        }
    });
</script>

<script>
(function () {
  const sidebar = document.getElementById('sidebar');
  const toggle  = document.getElementById('sidebarToggle');
  const overlay = document.getElementById('sidebarOverlay');
  const MOBILE_BP = 768;
  if (!sidebar || !toggle || !overlay) return;
  function isMobile() { return window.innerWidth <= MOBILE_BP; }
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
  window.addEventListener('resize', function () { if (!isMobile()) closeMobileSidebar(); });
})();
</script>

>>>>>>> 23984592a94087055b071541e42a022dc90209a3
</body>
</html>