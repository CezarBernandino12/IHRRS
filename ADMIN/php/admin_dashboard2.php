<?php
require 'config.php';
require_once __DIR__ . '/session_config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    header("Location: ../../role");
    exit();
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function initials($name) {
    $name = trim((string)$name);
    if ($name === '') {
        return 'U';
    }

    $parts = preg_split('/\s+/', $name);
    $letters = '';

    foreach ($parts as $part) {
        if ($part !== '') {
            $letters .= strtoupper(substr($part, 0, 1));
        }
        if (strlen($letters) >= 2) {
            break;
        }
    }

    return $letters ?: 'U';
}

$stmt = $pdo->prepare("SELECT role, COUNT(*) AS count FROM users GROUP BY role");
$stmt->execute();
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$bhwMidwifeCount = 0;
$doctorCount = 0;
$adminCount = 0;
$totalUsers = 0;

foreach ($roles as $role) {
    $roleName = strtolower((string)$role['role']);
    $count = (int)$role['count'];
    $totalUsers += $count;

    if ($roleName === 'bhw' || $roleName === 'midwife' || $roleName === 'nursing_attendant') {
        $bhwMidwifeCount += $count;
    } elseif ($roleName === 'doctor') {
        $doctorCount += $count;
    } elseif ($roleName === 'admin') {
        $adminCount += $count;
    }
}

$stmt = $pdo->prepare("SELECT account_status, COUNT(*) AS count FROM users GROUP BY account_status");
$stmt->execute();
$statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$activeUsers = 0;
$inactiveUsers = 0;

foreach ($statusCounts as $status) {
    $statusName = strtolower((string)$status['account_status']);
    $count = (int)$status['count'];

    if ($statusName === 'active') {
        $activeUsers += $count;
    } elseif ($statusName === 'inactive' || $statusName === 'suspended' || $statusName === 'terminated') {
        $inactiveUsers += $count;
    }
}

$today = date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT ul.user_id) AS active_today
    FROM users u
    JOIN user_logs ul ON u.user_id = ul.user_id
    WHERE (u.role = 'bhw' OR u.role = 'doctor' OR u.role = 'nursing_attendant')
      AND ul.action = 'login'
      AND DATE(ul.log_time) = :today
      AND NOT EXISTS (
          SELECT 1
          FROM user_logs ul2
          WHERE ul2.user_id = ul.user_id
            AND ul2.action = 'logout'
            AND ul2.log_time > ul.log_time
      )
");
$stmt->execute(['today' => $today]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$loggedInToday = (int)($row['active_today'] ?? 0);

$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekEnd = date('Y-m-d', strtotime('sunday this week'));

$stmt = $pdo->prepare("
    SELECT COUNT(*) AS new_users
    FROM users
    WHERE DATE(registration_date) BETWEEN :weekStart AND :weekEnd
");
$stmt->execute(['weekStart' => $weekStart, 'weekEnd' => $weekEnd]);
$newUsersRow = $stmt->fetch(PDO::FETCH_ASSOC);
$newUsersThisWeek = (int)($newUsersRow['new_users'] ?? 0);

$stmt = $pdo->prepare("
    SELECT u.username, u.role, COUNT(ul.log_id) AS login_count
    FROM users u
    JOIN user_logs ul ON u.user_id = ul.user_id
    WHERE ul.action = 'login'
      AND DATE(ul.log_time) >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
    GROUP BY u.user_id, u.username, u.role
    ORDER BY login_count DESC
    LIMIT 5
");
$stmt->execute();
$mostActiveUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$maxLoginCount = !empty($mostActiveUsers) ? max(array_map('intval', array_column($mostActiveUsers, 'login_count'))) : 0;
$activeRate = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100) : 0;
$unreadCount = 0;
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
    <link rel="stylesheet" href="../css/dashstyle.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Admin Dashboard</title>
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
            <li class="active">
                <a href="admin_dashboard2" data-tooltip="Dashboard">
                    <i class="bx bxs-dashboard nav-icon"></i>
                    <span class="nav-label">Dashboard</span>
                </a>
            </li>
            <li>
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
        <button class="nav-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="bx bx-menu"></i>
        </button>

        <div class="nav-search">
            <input type="search" id="patientSearch" placeholder="Search dashboard..." name="search" autocomplete="off">
            <button type="button" id="searchButton" aria-label="Search">
                <i class="bx bx-search"></i>
            </button>
            <div id="resultDropdown" class="dropdown-content"></div>
        </div>
    </nav>

    <main>
        <?php if (isset($_SESSION['pending_reset']) && $_SESSION['pending_reset']): ?>
            <div class="info-message dashboard-search-item">
                <span class="info-icon"><i class="bx bx-info-circle"></i></span>
                <div>
                    <strong>Password Reset Request Pending</strong>
                    <p>Your current password still works. An admin will contact you soon to complete the reset.</p>
                </div>
                <button type="button" onclick="this.parentElement.style.display='none';" aria-label="Dismiss message">&times;</button>
            </div>
            <?php unset($_SESSION['pending_reset']); ?>
        <?php endif; ?>

        <div class="dashboard-page">
            <header class="dashboard-hero dashboard-search-item">
                <div class="hero-copy">
                    <span class="eyebrow"><i class="bx bxs-dashboard"></i> Admin Overview</span>
                    <h1>Dashboard</h1>
                    <p>Monitor user activity, account status, and the most active healthcare system users in one clean view.</p>
                </div>

                <div class="hero-actions">
                    <a href="activity_logs" class="hero-btn secondary">
                        <i class="bx bx-history"></i>
                        View Logs
                    </a>
                    <a href="admin_user" class="hero-btn primary">
                        <i class="bx bx-user-plus"></i>
                        Manage Users
                    </a>
                </div>
            </header>

            <section class="stats-grid" aria-label="Dashboard statistics">
                <a href="today_logins" class="stat-card stat-link dashboard-search-item">
                    <span class="stat-icon blue"><i class="bx bx-log-in-circle"></i></span>
                    <div class="stat-copy">
                        <p>Today's Logins</p>
                        <h3><?php echo number_format($loggedInToday); ?></h3>
                        <small>BHW, doctors, and nursing attendants currently active today</small>
                    </div>
                </a>

                <a href="inactive_users" class="stat-card stat-link dashboard-search-item">
                    <span class="stat-icon red"><i class="bx bx-user-x"></i></span>
                    <div class="stat-copy">
                        <p>Terminated Accounts</p>
                        <h3><?php echo number_format($inactiveUsers); ?></h3>
                        <small>Inactive, suspended, or terminated user accounts</small>
                    </div>
                </a>

                <div class="stat-card dashboard-search-item">
                    <span class="stat-icon green"><i class="bx bx-user-plus"></i></span>
                    <div class="stat-copy">
                        <p>New Users This Week</p>
                        <h3><?php echo number_format($newUsersThisWeek); ?></h3>
                        <small><?php echo h(date('M j', strtotime($weekStart)) . ' - ' . date('M j', strtotime($weekEnd))); ?></small>
                    </div>
                </div>

                <div class="stat-card dashboard-search-item">
                    <span class="stat-icon violet"><i class="bx bx-group"></i></span>
                    <div class="stat-copy">
                        <p>Total Users</p>
                        <h3><?php echo number_format($totalUsers); ?></h3>
                        <small><?php echo number_format($activeUsers); ?> active accounts · <?php echo $activeRate; ?>% active rate</small>
                    </div>
                </div>
            </section>

            <section class="dashboard-content">
                <div class="panel system-snapshot dashboard-search-item">
                    <div class="panel-header">
                        <div>
                            <h2>System Snapshot</h2>
                            <p>Quick breakdown of active accounts and user roles.</p>
                        </div>
                        <span class="panel-badge">Live Summary</span>
                    </div>

                    <div class="snapshot-grid">
                        <div class="snapshot-item">
                            <span class="snapshot-label">Active Accounts</span>
                            <strong><?php echo number_format($activeUsers); ?></strong>
                            <div class="snapshot-bar"><span style="width: <?php echo min(100, $activeRate); ?>%;"></span></div>
                        </div>

                        <div class="snapshot-item">
                            <span class="snapshot-label">BHW / Midwife / Nursing</span>
                            <strong><?php echo number_format($bhwMidwifeCount); ?></strong>
                            <div class="snapshot-bar"><span style="width: <?php echo $totalUsers > 0 ? min(100, round(($bhwMidwifeCount / $totalUsers) * 100)) : 0; ?>%;"></span></div>
                        </div>

                        <div class="snapshot-item">
                            <span class="snapshot-label">Doctors</span>
                            <strong><?php echo number_format($doctorCount); ?></strong>
                            <div class="snapshot-bar"><span style="width: <?php echo $totalUsers > 0 ? min(100, round(($doctorCount / $totalUsers) * 100)) : 0; ?>%;"></span></div>
                        </div>

                        <div class="snapshot-item">
                            <span class="snapshot-label">Administrators</span>
                            <strong><?php echo number_format($adminCount); ?></strong>
                            <div class="snapshot-bar"><span style="width: <?php echo $totalUsers > 0 ? min(100, round(($adminCount / $totalUsers) * 100)) : 0; ?>%;"></span></div>
                        </div>
                    </div>
                </div>

                <div class="panel most-active-users dashboard-search-item">
                    <div class="panel-header">
                        <div>
                            <h2>Most Active Users</h2>
                            <p>Top users based on login activity.</p>
                        </div>
                        <span class="panel-badge"><i class="bx bx-calendar"></i> Last 7 Days</span>
                    </div>

                    <div class="users-list">
                        <?php if (empty($mostActiveUsers)): ?>
                            <div class="empty-state">
                                <i class="bx bx-user-x"></i>
                                <h3>No active users found</h3>
                                <p>No login activity has been recorded in the last 7 days.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($mostActiveUsers as $index => $user): ?>
                                <?php
                                    $roleSlug = strtolower((string)$user['role']);
                                    $loginCount = (int)$user['login_count'];
                                    $barWidth = $maxLoginCount > 0 ? max(8, round(($loginCount / $maxLoginCount) * 100)) : 0;
                                ?>
                                <div class="user-card dashboard-search-item">
                                    <div class="rank-badge">#<?php echo $index + 1; ?></div>
                                    <div class="dashboard-user-info">
                                        <strong class="dashboard-user-name"><?php echo h($user['username']); ?></strong>
                                        <span class="role-pill <?php echo h($roleSlug); ?>">
                                            <?php if ($roleSlug === 'doctor'): ?>
                                                <i class="bx bxs-user-voice"></i>
                                            <?php elseif ($roleSlug === 'admin'): ?>
                                                <i class="bx bxs-crown"></i>
                                            <?php elseif ($roleSlug === 'nursing_attendant'): ?>
                                                <i class="bx bxs-band-aid"></i>
                                            <?php else: ?>
                                                <i class="bx bxs-user"></i>
                                            <?php endif; ?>
                                            <?php echo h(ucwords(str_replace('_', ' ', $user['role']))); ?>
                                        </span>
                                        <div class="activity-meter"><span style="width: <?php echo $barWidth; ?>%;"></span></div>
                                    </div>
                                    <div class="login-count">
                                        <strong><?php echo number_format($loginCount); ?></strong>
                                        <span>logins</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
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
    </main>
</section>

<script src="../js/notif.js"></script>
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
    if (modal) {
        modal.style.display = 'grid';
    }
    return false;
}

function closeModal() {
    const modal = document.getElementById('logoutModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function proceedLogout() {
    window.location.href = 'logout';
}

window.addEventListener('click', function(event) {
    const logoutModal = document.getElementById('logoutModal');
    if (event.target === logoutModal) {
        closeModal();
    }
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

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('patientSearch');
    const searchButton = document.getElementById('searchButton');
    const items = () => Array.from(document.querySelectorAll('.dashboard-search-item'));

    function filterDashboardItems() {
        const term = (searchInput?.value || '').toLowerCase().trim();
        items().forEach(item => {
            item.style.display = item.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    }

    if (searchInput && searchButton) {
        searchInput.addEventListener('input', filterDashboardItems);
        searchInput.addEventListener('keypress', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                filterDashboardItems();
            }
        });
        searchButton.addEventListener('click', filterDashboardItems);
    }
});
</script>
</body>
</html>
