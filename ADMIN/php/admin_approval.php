<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    header("Location: ../../role");
    exit();
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function buildUrl($params = []) {
    $base = $_GET;
    return '?' . http_build_query(array_merge($base, $params));
}

function selected($name, $value) {
    return ($_GET[$name] ?? '') === $value ? 'selected' : '';
}

// Handle user approval/rejection AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action'];
    $admin_id = $_SESSION['user_id'] ?? 0;

    $stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    $admin_name = $admin ? $admin['full_name'] : 'Unknown Admin';

    if ($action === 'approve') {
        $status = 'approved';
        $actionLog = "Approved user";

        if (function_exists('notifyAdminOfUserApproval')) {
            notifyAdminOfUserApproval($user['username'], $admin_name);
        }
    } elseif ($action === 'reject') {
        $status = 'rejected';
        $actionLog = "Rejected user";

        if (function_exists('notifyAdminOfUserRejection')) {
            notifyAdminOfUserRejection($user['username'], $admin_name);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
    $success = $stmt->execute([$status, $user_id]);

    if ($success) {
        $logStmt = $pdo->prepare("INSERT INTO logs (performed_by, action, timestamp) VALUES (?, ?, NOW())");
        $logStmt->execute([$admin_id, "$actionLog {$user['username']}"]);

        echo json_encode(['success' => true, 'message' => 'User ' . $status . ' successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating user status']);
    }

    exit;
}

// Fetch pending users
$stmt = $pdo->query("SELECT *, DATE_FORMAT(registration_date, '%Y-%m-%d %h:%i %p') AS formatted_date FROM users WHERE status = 'pending' ORDER BY registration_date DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$pendingCount = count($users);

$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$approvedUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'approved'")->fetchColumn();
$totalLogs = (int)$pdo->query("SELECT COUNT(*) FROM logs")->fetchColumn();

// Fetch activity logs with filtering and pagination
$where = " FROM logs JOIN users ON logs.performed_by = users.user_id WHERE 1";
$params = [];

if (!empty($_GET['user'])) {
    $where .= " AND logs.performed_by = :user";
    $params[':user'] = $_GET['user'];
}

if (!empty($_GET['action'])) {
    $where .= " AND logs.action LIKE :action";
    $params[':action'] = '%' . $_GET['action'] . '%';
}

if (!empty($_GET['from_date'])) {
    $where .= " AND DATE(logs.timestamp) >= :from_date";
    $params[':from_date'] = $_GET['from_date'];
}

if (!empty($_GET['to_date'])) {
    $where .= " AND DATE(logs.timestamp) <= :to_date";
    $params[':to_date'] = $_GET['to_date'];
}

$countStmt = $pdo->prepare("SELECT COUNT(*)" . $where);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$filteredLogs = (int)$countStmt->fetchColumn();

$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$totalPages = max(1, (int)ceil($filteredLogs / $limit));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $limit;

$query = "SELECT logs.*, users.full_name AS performed_by_name,
          DATE_FORMAT(logs.timestamp, '%M %e, %Y %l:%i %p') AS formatted_timestamp" .
          $where . " ORDER BY logs.timestamp DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hasFilters = !empty($_GET['user']) || !empty($_GET['action']) || !empty($_GET['from_date']) || !empty($_GET['to_date']);
$showingFrom = $filteredLogs > 0 ? $offset + 1 : 0;
$showingTo = min($offset + $limit, $filteredLogs);

$startPage = max(1, $page - 1);
$endPage = min($totalPages, $startPage + 2);
$startPage = max(1, $endPage - 2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../img/logo.png">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/logout.css">
    <link rel="stylesheet" href="../css/approval.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Approvals</title>
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
                <a href="admin_approval" data-tooltip="Approval & Logs">
                    <i class="bx bxs-user nav-icon"></i>
                    <span class="nav-label">Approval & Logs</span>
                </a>
            </li>
            <li>
                <a href="admin_user" data-tooltip="User Management">
                    <i class="bx bxs-notepad nav-icon"></i>
                    <span class="nav-label">User Management</span>
                </a>
            </li>
            <li>
                <a href="admin_reports" data-tooltip="Reports">
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
        <button class="nav-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="bx bx-menu"></i>
        </button>

        <div class="nav-search">
            <input type="search" id="patientSearch" placeholder="Search approvals or logs..." name="search" autocomplete="off">
            <button type="button" id="searchButton" aria-label="Search">
                <i class="bx bx-search"></i>
            </button>
            <div id="resultDropdown" class="dropdown-content"></div>
        </div>
    </nav>

    <main>
        <div class="approval-page">
            <section class="page-hero">
                <div>
                    <span class="eyebrow">Admin Workspace</span>
                    <h1>Approval & Activity Logs</h1>
                    <p>Review pending account requests and monitor recent system actions in one clean dashboard.</p>

                </div>

                <a href="admin_reports" class="hero-action">
                    <i class="bx bxs-report"></i>
                    View Reports
                </a>
            </section>

            <section class="summary-grid">
                <article class="summary-card highlight">
                    <span class="summary-icon"><i class="bx bx-user-plus"></i></span>
                    <div>
                        <p>Pending Approvals</p>
                        <h3><?php echo number_format($pendingCount); ?></h3>
                    </div>
                </article>

                <article class="summary-card">
                    <span class="summary-icon"><i class="bx bx-list-ul"></i></span>
                    <div>
                        <p>Filtered Logs</p>
                        <h3><?php echo number_format($filteredLogs); ?></h3>
                    </div>
                </article>

                <article class="summary-card">
                    <span class="summary-icon"><i class="bx bx-user-check"></i></span>
                    <div>
                        <p>Approved Users</p>
                        <h3><?php echo number_format($approvedUsers); ?></h3>
                    </div>
                </article>

                <article class="summary-card">
                    <span class="summary-icon"><i class="bx bx-data"></i></span>
                    <div>
                        <p>Total Logs</p>
                        <h3><?php echo number_format($totalLogs); ?></h3>
                    </div>
                </article>
            </section>

            <section class="dashboard-card pending-approvals-container" id="pending-approvals">
                <div class="card-heading">
                    <div class="card-heading-left">
                        <span class="card-icon"><i class="bx bx-user-pin"></i></span>
                        <div>
                            <h2>Pending User Approvals</h2>
                            <p>Approve, reject, or review account requests waiting for admin action.</p>
                        </div>
                    </div>
                    <span class="count-pill"><?php echo number_format($pendingCount); ?> pending</span>
                </div>

                <div class="table-shell">
                    <table class="logs-table approval-table">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr id="row-<?php echo e($user['user_id']); ?>">
                                    <td><span class="mono-id">#<?php echo e($user['user_id']); ?></span></td>
                                    <td>
                                        <div class="user-cell">
                                            <span class="avatar-chip"><?php echo e(mb_strtoupper(mb_substr($user['full_name'] ?? 'U', 0, 1))); ?></span>
                                            <strong><?php echo e($user['full_name']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo e($user['username']); ?></td>
                                    <td><span class="status-badge pending"><?php echo e($user['status']); ?></span></td>
                                    <td>
                                        <div class="action-group">
                                            <button type="button" class="icon-btn approve" title="Approve user" onclick="updateUser(<?php echo e($user['user_id']); ?>, 'approve')">
                                                <i class="bx bx-check"></i>
                                            </button>
                                            <button type="button" class="icon-btn reject" title="Reject user" onclick="updateUser(<?php echo e($user['user_id']); ?>, 'reject')">
                                                <i class="bx bx-x"></i>
                                            </button>
                                            <button type="button" class="icon-btn view" title="View user" onclick="viewUser(<?php echo e($user['user_id']); ?>)">
                                                <i class="bx bx-show"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <i class="bx bx-check-shield"></i>
                                        <strong>No pending approvals</strong>
                                        <span>All account requests are currently reviewed.</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="dashboard-card logs-container" id="activity-logs">
                <div class="card-heading">
                    <div class="card-heading-left">
                        <span class="card-icon"><i class="bx bx-history"></i></span>
                        <div>
                            <h2>System Activity Logs</h2>
                            <p>Use the filters below to review specific users, actions, or date ranges.</p>
                        </div>
                    </div>
                    <?php if ($hasFilters): ?>
                        <a href="admin_approval#activity-logs" class="reset-link"><i class="bx bx-refresh"></i> Reset</a>
                    <?php endif; ?>
                </div>

                <form method="GET" action="#activity-logs" class="logs-filter-grid" id="logFilterForm">
                    <div class="form-group">
                        <label for="userFilter">Select User</label>
                        <select name="user" id="userFilter">
                            <option value="">All Users</option>
                            <?php
                            $userStmt = $pdo->query("SELECT user_id, full_name FROM users ORDER BY full_name ASC");
                            while ($user = $userStmt->fetch(PDO::FETCH_ASSOC)) {
                                $userSelected = ($_GET['user'] ?? '') == $user['user_id'] ? 'selected' : '';
                                echo '<option value="' . e($user['user_id']) . '" ' . $userSelected . '>' . e($user['full_name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="action">Action Type</label>
                        <select name="action" id="action">
                            <option value="">All Actions</option>
                            <option value="User Login" <?php echo selected('action', 'User Login'); ?>>User Login</option>
                            <option value="User Logout" <?php echo selected('action', 'User Logout'); ?>>User Logout</option>
                            <option value="Added New User" <?php echo selected('action', 'Added New User'); ?>>Added New User</option>
                            <option value="Deactivated User" <?php echo selected('action', 'Deactivated User'); ?>>Deactivated User</option>
                            <option value="Reset Password" <?php echo selected('action', 'Reset Password'); ?>>Reset Password</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="from_date">From</label>
                        <input type="date" id="from_date" name="from_date" value="<?php echo e($_GET['from_date'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="to_date">To</label>
                        <input type="date" id="to_date" name="to_date" value="<?php echo e($_GET['to_date'] ?? ''); ?>">
                    </div>

                    <div class="form-group filter-actions">
                        <button type="submit" class="filter-btn">
                            <i class="bx bx-filter-alt"></i>
                            Apply
                        </button>
                    </div>
                </form>

                <div class="table-meta">
                    <span>Showing <?php echo number_format($showingFrom); ?>-<?php echo number_format($showingTo); ?> of <?php echo number_format($filteredLogs); ?> logs</span>
                    <span>Page <?php echo number_format($page); ?> of <?php echo number_format($totalPages); ?></span>
                </div>

                <div class="table-shell">
                    <table class="logs-table activity-table" id="logTable">
                        <thead>
                            <tr>
                                <th>Summary</th>
                                <th>User</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><span class="action-badge"><?php echo e($log['action']); ?></span></td>
                                    <td>
                                        <a href="#" class="user-link" data-userid="<?php echo e($log['performed_by']); ?>" data-action="<?php echo e($log['action']); ?>">
                                            <span class="avatar-chip small"><?php echo e(mb_strtoupper(mb_substr($log['performed_by_name'] ?? 'U', 0, 1))); ?></span>
                                            <?php echo e($log['performed_by_name']); ?>
                                        </a>
                                    </td>
                                    <td><span class="timestamp-chip"><?php echo e($log['formatted_timestamp']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="3" class="empty-state">
                                        <i class="bx bx-search-alt"></i>
                                        <strong>No logs found</strong>
                                        <span>Try changing or clearing your filters.</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination-wrap" data-preserve-scroll>
                        <?php if ($page > 1): ?>
                            <a class="page-control" href="<?php echo e(buildUrl(['page' => $page - 1])); ?>#activity-logs"><i class="bx bx-chevron-left"></i> Previous</a>
                        <?php else: ?>
                            <span class="page-control disabled"><i class="bx bx-chevron-left"></i> Previous</span>
                        <?php endif; ?>

                        <div class="page-numbers">
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a class="page-number <?php echo $i === $page ? 'active' : ''; ?>" href="<?php echo e(buildUrl(['page' => $i])); ?>#activity-logs"><?php echo $i; ?></a>
                            <?php endfor; ?>
                        </div>

                        <?php if ($page < $totalPages): ?>
                            <a class="page-control" href="<?php echo e(buildUrl(['page' => $page + 1])); ?>#activity-logs">Next <i class="bx bx-chevron-right"></i></a>
                        <?php else: ?>
                            <span class="page-control disabled">Next <i class="bx bx-chevron-right"></i></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <div id="modalOverlay"></div>

        <div id="userModal" class="modal-box">
            <div class="modal-header">
                <h2>User Information and Activity</h2>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>

            <div class="modal-content">
                <div class="user-details">
                    <h3>User Details</h3>
                    <p><strong>Full Name:</strong> <span id="logUserFullName"></span></p>
                    <p><strong>User Name:</strong> <span id="logUserName"></span></p>
                    <p><strong>Status:</strong> <span id="logUserStatus"></span></p>
                    <p><strong>Barangay:</strong> <span id="logUserBarangay"></span></p>
                    <p><strong>Role:</strong> <span id="logUserRole"></span></p>
                </div>

                <div class="activity-details">
                    <h3>Activity Details</h3>
                    <p><strong>Action:</strong> <span id="logUserAction"></span></p>
                    <p><strong>Timestamp:</strong> <span id="logUserTimestamp"></span></p>
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
                    <button onclick="closeLogoutModal()" class="logout-cancel-btn">Cancel</button>
                    <button onclick="proceedLogout()" class="logout-confirm-btn">Yes, Logout</button>
                </div>
            </div>
        </div>
    </main>
</section>

<button id="loadMoreBtn" type="button" hidden>Load More</button>

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

document.addEventListener("DOMContentLoaded", () => {
  const searchInput = document.getElementById("patientSearch");
  const searchButton = document.getElementById("searchButton");
  const searchableRows = () => Array.from(document.querySelectorAll(".pending-approvals-container tbody tr, #logTable tbody tr"));

  function filterPageRows() {
    const term = (searchInput?.value || "").toLowerCase().trim();
    searchableRows().forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(term) ? "" : "none";
    });
  }

  if (searchInput && searchButton) {
    searchInput.addEventListener("input", filterPageRows);
    searchInput.addEventListener("keypress", function (event) {
      if (event.key === "Enter") {
        event.preventDefault();
        filterPageRows();
      }
    });
    searchButton.addEventListener("click", filterPageRows);
  }

  document.querySelectorAll(".pagination-wrap a").forEach(link => {
    link.addEventListener("click", () => {
      sessionStorage.setItem("approvalLogsScrollY", String(window.scrollY));
    });
  });

  const savedY = sessionStorage.getItem("approvalLogsScrollY");
  if (savedY !== null) {
    sessionStorage.removeItem("approvalLogsScrollY");
    window.scrollTo({ top: Number(savedY), behavior: "auto" });
  }
});
</script>

<script>
fetch('getUserName')
    .then(response => response.json())
    .then(data => {
        const sidebarNameEl = document.getElementById('sidebarUserName');
        if (sidebarNameEl) {
            sidebarNameEl.textContent = data.full_name || 'Admin User';
        }
    })
    .catch(error => {
        console.error('Error fetching user name:', error);
        const sidebarNameEl = document.getElementById('sidebarUserName');
        if (sidebarNameEl) {
            sidebarNameEl.textContent = 'Admin User';
        }
    });

function confirmLogout() {
    const modal = document.getElementById('logoutModal');
    if (modal) modal.style.display = 'grid';
    return false;
}

function closeLogoutModal() {
    const modal = document.getElementById('logoutModal');
    if (modal) modal.style.display = 'none';
}

function proceedLogout() {
    window.location.href='logout';
}

window.addEventListener('click', function(event) {
    const logoutModal = document.getElementById('logoutModal');
    if (event.target === logoutModal) {
        closeLogoutModal();
    }
});
</script>
<script src="../js/notif.js"></script>
<script src="../js/admin_approval.js"></script>

</body>
</html>
