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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>

<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

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
            </div>
        </div>
    </div>
</section>

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
                    </div>
                </div>
            </div>

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
            }
        });
}

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

</body>
</html>