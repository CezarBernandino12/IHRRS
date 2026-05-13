<?php
require 'config.php';
require_once __DIR__ . '/session_config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    header("Location: ../../role");
    exit();
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function isValidDateYmd($date) {
    $dt = DateTime::createFromFormat('Y-m-d', (string)$date);
    return $dt && $dt->format('Y-m-d') === $date;
}

function getInitials($name) {
    $name = trim((string)$name);

    if ($name === '') {
        return 'U';
    }

    $parts = preg_split('/\s+/', $name);
    $first = substr($parts[0] ?? 'U', 0, 1);
    $last = count($parts) > 1 ? substr(end($parts), 0, 1) : '';

    return strtoupper($first . $last);
}

function actionTone($action) {
    $action = strtolower((string)$action);

    if (strpos($action, 'failed') !== false || strpos($action, 'error') !== false || strpos($action, 'unauthorized') !== false || strpos($action, 'cancelled') !== false || strpos($action, 'terminated') !== false) {
        return 'tone-danger';
    }

    if (strpos($action, 'successful login') !== false || strpos($action, 'added') !== false || strpos($action, 'dispensed') !== false || strpos($action, 'forwarded') !== false) {
        return 'tone-success';
    }

    if (strpos($action, 'updated') !== false || strpos($action, 'change') !== false || strpos($action, 'reset') !== false) {
        return 'tone-warning';
    }

    if (strpos($action, 'generated') !== false || strpos($action, 'report') !== false || strpos($action, 'certificate') !== false || strpos($action, 'prescription') !== false) {
        return 'tone-info';
    }

    if (strpos($action, 'logged out') !== false || strpos($action, 'logout') !== false) {
        return 'tone-muted';
    }

    return 'tone-default';
}

/* Summary statistics */
$totalLogsStmt = $pdo->query("SELECT COUNT(*) AS total FROM logs");
$totalLogs = (int)$totalLogsStmt->fetch(PDO::FETCH_ASSOC)['total'];

$todayActivitiesStmt = $pdo->query("SELECT COUNT(*) AS today FROM logs WHERE DATE(timestamp) = CURDATE()");
$todayActivities = (int)$todayActivitiesStmt->fetch(PDO::FETCH_ASSOC)['today'];

/* Filters */
$userFilter = $_GET['user'] ?? '';
$actionFilter = $_GET['action'] ?? '';
$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';

if ($fromDate !== '' && !isValidDateYmd($fromDate)) {
    $fromDate = '';
}

if ($toDate !== '' && !isValidDateYmd($toDate)) {
    $toDate = '';
}

if ($fromDate !== '' && $toDate !== '' && $fromDate > $toDate) {
    [$fromDate, $toDate] = [$toDate, $fromDate];
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$where = ["1 = 1"];
$params = [];

if ($userFilter !== '') {
    $where[] = "logs.performed_by = :user";
    $params[':user'] = $userFilter;
}

if ($actionFilter !== '') {
    $where[] = "logs.action LIKE :action";
    $params[':action'] = '%' . $actionFilter . '%';
}

if ($fromDate !== '') {
    $where[] = "logs.timestamp >= :from_date";
    $params[':from_date'] = $fromDate . ' 00:00:00';
}

if ($toDate !== '') {
    $where[] = "logs.timestamp < :to_date";
    $params[':to_date'] = date('Y-m-d', strtotime($toDate . ' +1 day')) . ' 00:00:00';
}

$whereSql = implode(' AND ', $where);

function activityLogPageUrl($targetPage) {
    $params = $_GET;
    $params['page'] = max(1, (int)$targetPage);

    return '?' . http_build_query($params) . '#activity-table-section';
}

$countQuery = "SELECT COUNT(*) AS total
               FROM logs
               LEFT JOIN users ON logs.performed_by = users.user_id
               WHERE {$whereSql}";

$countStmt = $pdo->prepare($countQuery);

foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}

$countStmt->execute();
$filteredLogs = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = max(1, (int)ceil($filteredLogs / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

$query = "SELECT logs.*, users.full_name AS performed_by_name,
          DATE_FORMAT(logs.timestamp, '%M %e, %Y %l:%i %p') AS formatted_timestamp
          FROM logs
          LEFT JOIN users ON logs.performed_by = users.user_id
          WHERE {$whereSql}
          ORDER BY logs.timestamp DESC
          LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$startRecord = $filteredLogs > 0 ? $offset + 1 : 0;
$endRecord = $filteredLogs > 0 ? min($offset + count($logs), $filteredLogs) : 0;

$userStmt = $pdo->query("SELECT user_id, full_name FROM users ORDER BY full_name ASC");

$actionOptions = [
    'Successful Login' => 'User Login',
    'User Logged Out' => 'User Logout',
    'Added New User' => 'Added New User',
    'Terminated User' => 'Terminated User',
    'Reset Password' => 'Password Change',
    'Added New Patient' => 'Added New Patient',
    'Added New Patient and Referred to RHU' => 'Added New Patient and Referred to RHU',
    'Updated Patient Information' => 'Updated Patient Information',
    'Dispensed Medicine to Patient' => 'Dispensed Medicine to Patient',
    'Added Referral' => 'Added Referral',
    'Cancelled Referral' => 'Cancelled Referral',
    'Generated BHS Patient Visit Summary Report' => 'Generated BHS Patient Visit Summary Report',
    'Generated BHS Referral Summary Report' => 'Generated BHS Referral Summary Report',
    'Generated BHS Medicine Dispensation Report' => 'Generated BHS Medicine Dispensation Report',
    'Generated BHS Medical Cases Report' => 'Generated BHS Medical Cases Report',
    'Forwarded Referral to Physician' => 'Forwarded Referral to Physician',
    'Added Patient Assessment Record' => 'Added Patient Assessment Record',
    'Added Diagnosis/Consultation Record' => 'Added Diagnosis/Consultation Record',
    'Dispensed Medicine to Patient (RHU)' => 'Dispensed Medicine to Patient (RHU)',
    'Generated Prescription' => 'Generated Prescription',
    'Generated Medical Certificate' => 'Generated Medical Certificate',
];

$hasFilters = $userFilter !== '' || $actionFilter !== '' || $fromDate !== '' || $toDate !== '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs</title>

    <link rel="icon" href="../../img/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/logout.css">
    <link rel="stylesheet" href="../css/logs.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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
                <a href="admin_dashboard2" data-tooltip="Dashboard">
                    <i class="bx bxs-dashboard nav-icon"></i>
                    <span class="nav-label">Dashboard</span>
                </a>
            </li>

            <li class="active">
                <a href="activity_logs" data-tooltip="Activity Logs">
                    <i class="bx bxs-user nav-icon"></i>
                    <span class="nav-label">Activity Logs</span>
                </a>
            </li>

            <li>
                <a href="admin_user" data-tooltip="User Management">
                    <i class="bx bxs-notepad nav-icon"></i>
                    <span class="nav-label">User Management</span>
                </a>
            </li>

            <li>
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
        </div>
    </div>
</section>

<section id="content">
    <nav>
        <button type="button" class="nav-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="bx bx-menu"></i>
        </button>

        <div class="nav-search">
            <input type="search" id="patientSearch" placeholder="Search activity logs..." name="search" autocomplete="off">
            <button type="button" id="searchButton" aria-label="Search">
                <i class="bx bx-search"></i>
            </button>
            <div id="resultDropdown" class="dropdown-content"></div>
        </div>
    </nav>

    <main>
        <div class="activity-page">
            <header class="activity-hero">
                <div>
                    <div class="eyebrow">Administrator Monitoring</div>
                    <h1>System Activity Logs</h1>
                    <p>Review user actions, system activity, security-related events, and record changes in one clean dashboard.</p>
                </div>

                <a href="../reports" class="hero-action">
                    <i class="bx bxs-report"></i>
                    View Reports
                </a>
            </header>

            <section class="stats-grid">
                <article class="stat-card">
                    <div class="stat-icon">
                        <i class="bx bx-list-ul"></i>
                    </div>

                    <div>
                        <span>Total Logs</span>
                        <strong><?= number_format($totalLogs); ?></strong>
                    </div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">
                        <i class="bx bx-calendar-check"></i>
                    </div>

                    <div>
                        <span>Today's Activities</span>
                        <strong><?= number_format($todayActivities); ?></strong>
                    </div>
                </article>
            </section>

            <section class="logs-panel filter-panel">
                <div class="panel-heading">
                    <div class="panel-heading-left">
                        <span class="panel-icon">
                            <i class="bx bx-slider-alt"></i>
                        </span>

                        <div>
                            <h2>Filter Activity Logs</h2>
                            <p>Choose a user, action type, and date range to narrow the audit trail.</p>
                        </div>
                    </div>

                    <?php if ($hasFilters): ?>
                        <a href="activity_logs" class="clear-filter-top">
                            <i class="bx bx-refresh"></i>
                            Reset filters
                        </a>
                    <?php endif; ?>
                </div>

                <form method="GET" action="" class="logs-filter-grid" id="logFilterForm">
                    <div class="form-group">
                        <label for="userFilter">Select User</label>
                        <select name="user" id="userFilter" class="auto-submit">
                            <option value="">All Users</option>
                            <?php while ($user = $userStmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?= e($user['user_id']); ?>" <?= $userFilter == $user['user_id'] ? 'selected' : ''; ?>>
                                    <?= e($user['full_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group action-group">
                        <label for="action">Action Type</label>
                        <select name="action" id="action" class="auto-submit">
                            <option value="">All Actions</option>
                            <?php foreach ($actionOptions as $value => $label): ?>
                                <option value="<?= e($value); ?>" <?= $actionFilter === $value ? 'selected' : ''; ?>>
                                    <?= e($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group date-group">
                        <label for="from_date">From</label>
                        <input type="text" id="from_date" name="from_date" class="flatpickr" placeholder="Start date" value="<?= e($fromDate); ?>">
                    </div>

                    <div class="form-group date-group">
                        <label for="to_date">To</label>
                        <input type="text" id="to_date" name="to_date" class="flatpickr" placeholder="End date" value="<?= e($toDate); ?>">
                    </div>


                </form>
            </section>

            <section class="logs-panel table-panel" id="activity-table-section">
                <div class="panel-heading table-heading">
                    <div class="panel-heading-left">
                        <span class="panel-icon">
                            <i class="bx bx-history"></i>
                        </span>

                        <div>
                            <h2>Recent Activity</h2>
                            <p>
                                Showing <?= number_format($startRecord); ?>–<?= number_format($endRecord); ?> of <?= number_format($filteredLogs); ?> matching log<?= $filteredLogs === 1 ? '' : 's'; ?>.
                            </p>
                        </div>
                    </div>

                    <span class="table-count">
                        Latest first
                    </span>
                </div>

                <div class="table-shell">
                    <table class="logs-table" id="logTable">
                        <thead>
                            <tr>
                                <th>Summary</th>
                                <th>User</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <?php
                                    $displayName = $log['performed_by_name'] ?? 'Unknown User';
                                    $tone = actionTone($log['action'] ?? '');
                                ?>
                                <tr>
                                    <td>
                                        <span class="action-pill <?= e($tone); ?>">
                                            <?= e($log['action'] ?? 'No action recorded'); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <a href="#" class="user-link" data-userid="<?= e($log['performed_by'] ?? ''); ?>" data-fullname="<?= e($displayName); ?>" data-username="<?= e($log['performed_by'] ?? 'N/A'); ?>" data-role="N/A" data-action="<?= e($log['action'] ?? ''); ?>" data-timestamp="<?= e($log['formatted_timestamp'] ?? 'N/A'); ?>">
                                            
                                            <span class="user-name-text"><?= e($displayName); ?></span>
                                        </a>
                                    </td>

                                    <td>
                                        <span class="timestamp-text">
                                            <i class="bx bx-time-five"></i>
                                            <?= e($log['formatted_timestamp'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="3">
                                        <div class="empty-state">
                                            <i class="bx bx-search-alt"></i>
                                            <strong>No activity logs found</strong>
                                            <span>Try changing the selected user, action type, or date range.</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-container">
                    <div class="pagination-info">
                        <span>Showing <?= number_format($startRecord); ?>–<?= number_format($endRecord); ?> of <?= number_format($filteredLogs); ?> record<?= $filteredLogs === 1 ? '' : 's'; ?></span>
                    </div>

                    <nav class="pagination-nav" aria-label="Activity logs pagination">
                        <?php if ($page > 1): ?>
                            <a class="pagination-control" data-preserve-scroll href="<?= e(activityLogPageUrl($page - 1)); ?>">
                                <i class="bx bx-chevron-left"></i>
                                Previous
                            </a>
                        <?php else: ?>
                            <span class="pagination-control disabled">
                                <i class="bx bx-chevron-left"></i>
                                Previous
                            </span>
                        <?php endif; ?>

                        <div class="pagination-pages">
                            <?php
                            $maxVisiblePages = 3;
                            $pageStart = max(1, $page - 1);
                            $pageEnd = min($totalPages, $pageStart + $maxVisiblePages - 1);

                            if (($pageEnd - $pageStart + 1) < $maxVisiblePages) {
                                $pageStart = max(1, $pageEnd - $maxVisiblePages + 1);
                            }

                            for ($pageNumber = $pageStart; $pageNumber <= $pageEnd; $pageNumber++):
                                if ($pageNumber === $page):
                            ?>
                                    <span class="pagination-page active" aria-current="page"><?= $pageNumber; ?></span>
                            <?php else: ?>
                                    <a class="pagination-page" data-preserve-scroll href="<?= e(activityLogPageUrl($pageNumber)); ?>"><?= $pageNumber; ?></a>
                            <?php
                                endif;
                            endfor;
                            ?>
                        </div>

                        <?php if ($page < $totalPages): ?>
                            <a class="pagination-control" data-preserve-scroll href="<?= e(activityLogPageUrl($page + 1)); ?>">
                                Next
                                <i class="bx bx-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="pagination-control disabled">
                                Next
                                <i class="bx bx-chevron-right"></i>
                            </span>
                        <?php endif; ?>
                    </nav>
                </div>
            </section>

            <div class="logs-summary">
                <p>Total Logs: <strong><?= number_format($totalLogs); ?></strong></p>
            </div>
        </div>
    </main>

    <div id="modalOverlay" class="modal-overlay"></div>

    <div id="userModal" class="modal-box">
        <div class="modal-header">
            <div>
                <span>User Activity Details</span>
                <h2>User Information</h2>
            </div>

            <button type="button" class="close-btn" onclick="closeModal()" aria-label="Close modal">
                &times;
            </button>
        </div>

        <div class="modal-contents">
            <p><strong>Full Name:</strong> <span id="logUserFullName"></span></p>
            <p><strong>User Name:</strong> <span id="logUserName"></span></p>
            <p><strong>Role:</strong> <span id="logUserRole"></span></p>
            <p><strong>Action:</strong> <span id="logUserAction"></span></p>
            <p><strong>Timestamp:</strong> <span id="logUserTimestamp"></span></p>
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
                <button type="button" onclick="closeModal()" class="logout-cancel-btn">Cancel</button>
                <button type="button" onclick="proceedLogout()" class="logout-confirm-btn">Yes, Logout</button>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function () {
    fetch("getUserName")
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

    if (window.flatpickr) {
        flatpickr("#from_date", {
            dateFormat: "Y-m-d",
            allowInput: true,
            onClose: function (selectedDates, dateStr) {
                if (dateStr) {
                    const form = document.getElementById("logFilterForm");
                    if (form) form.submit();
                }
            }
        });

        flatpickr("#to_date", {
            dateFormat: "Y-m-d",
            allowInput: true,
            onClose: function (selectedDates, dateStr) {
                if (dateStr) {
                    const form = document.getElementById("logFilterForm");
                    if (form) form.submit();
                }
            }
        });
    }

    document.querySelectorAll(".auto-submit").forEach(function (el) {
        el.addEventListener("change", function () {
            const form = document.getElementById("logFilterForm");
            if (form) form.submit();
        });
    });

    setupSidebar();
    setupActivitySearch();
    setupPaginationScroll();
    setupUserModal();
});

function confirmLogout() {
    const modal = document.getElementById("logoutModal");

    if (modal) {
        modal.style.display = "grid";
    }

    return false;
}

function closeModal() {
    const logoutModal = document.getElementById("logoutModal");
    const userModal = document.getElementById("userModal");
    const modalOverlay = document.getElementById("modalOverlay");

    if (logoutModal) {
        logoutModal.style.display = "none";
    }

    if (userModal) {
        userModal.style.display = "none";
    }

    if (modalOverlay) {
        modalOverlay.style.display = "none";
    }
}

function proceedLogout() {
    window.location.href = "logout";
}

window.addEventListener("click", function (event) {
    const logoutModal = document.getElementById("logoutModal");
    const modalOverlay = document.getElementById("modalOverlay");

    if (event.target === logoutModal || event.target === modalOverlay) {
        closeModal();
    }
});

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

function setupActivitySearch() {
    const searchInput = document.getElementById("patientSearch");
    const searchButton = document.getElementById("searchButton");

    function rows() {
        return Array.from(document.querySelectorAll("#logTable tbody tr"));
    }

    function filterActivityLogs() {
        const term = (searchInput?.value || "").toLowerCase().trim();

        rows().forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(term) ? "" : "none";
        });
    }

    if (searchInput) {
        searchInput.addEventListener("input", filterActivityLogs);

        searchInput.addEventListener("keypress", function (event) {
            if (event.key === "Enter") {
                event.preventDefault();
                filterActivityLogs();
            }
        });
    }

    if (searchButton) {
        searchButton.addEventListener("click", filterActivityLogs);
    }
}

function setupPaginationScroll() {
    const savedScroll = sessionStorage.getItem("activityLogsScrollY");

    if (savedScroll !== null) {
        requestAnimationFrame(() => {
            window.scrollTo({ top: Number(savedScroll), behavior: "auto" });
            sessionStorage.removeItem("activityLogsScrollY");
        });
    }

    document.querySelectorAll("[data-preserve-scroll]").forEach(link => {
        link.addEventListener("click", function () {
            sessionStorage.setItem("activityLogsScrollY", String(window.scrollY));
        });
    });
}

function setupUserModal() {
    document.querySelectorAll(".user-link").forEach(link => {
        link.addEventListener("click", function (event) {
            event.preventDefault();

            const overlay = document.getElementById("modalOverlay");
            const modal = document.getElementById("userModal");

            const fullName = document.getElementById("logUserFullName");
            const userName = document.getElementById("logUserName");
            const role = document.getElementById("logUserRole");
            const action = document.getElementById("logUserAction");
            const timestamp = document.getElementById("logUserTimestamp");

            if (fullName) fullName.textContent = this.dataset.fullname || "N/A";
            if (userName) userName.textContent = this.dataset.username || "N/A";
            if (role) role.textContent = this.dataset.role || "N/A";
            if (action) action.textContent = this.dataset.action || "N/A";
            if (timestamp) timestamp.textContent = this.dataset.timestamp || "N/A";

            if (overlay) overlay.style.display = "block";
            if (modal) modal.style.display = "block";
        });
    });
}
</script>
</body>
</html>
