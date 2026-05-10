<?php
require 'config.php';
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    header("Location: ../../role");
    exit();
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$today = date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT
        u.full_name,
        u.role,
        u.barangay,
        latest_login.login_time,
        'Online' as status
    FROM users u
    INNER JOIN (
        SELECT user_id, MAX(log_time) as login_time
        FROM user_logs
        WHERE action = 'login' AND DATE(log_time) = :today
        GROUP BY user_id
    ) latest_login ON u.user_id = latest_login.user_id
    WHERE u.role IN ('bhw', 'doctor', 'nursing_attendant')
    AND NOT EXISTS (
        SELECT 1 FROM user_logs ul2
        WHERE ul2.user_id = latest_login.user_id
        AND ul2.action = 'logout'
        AND ul2.log_time > latest_login.login_time
        AND DATE(ul2.log_time) = :today
    )
    ORDER BY latest_login.login_time DESC
");

$stmt->execute(['today' => $today]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$roleDisplay = [
    'bhw' => 'Barangay Health Worker',
    'doctor' => 'Physician',
    'nursing_attendant' => 'Nursing Attendant'
];

$onlineCount = count($users);
$bhwCount = 0;
$doctorCount = 0;
$nursingCount = 0;
$latestLogin = null;

foreach ($users as &$user) {
    $login_dt = new DateTime($user['login_time']);
    $end_dt = new DateTime();
    $interval = $login_dt->diff($end_dt);
    $hours = $interval->h + ($interval->days * 24);
    $minutes = $interval->i;

    $user['duration_formatted'] = "Currently " . $hours . 'h ' . $minutes . 'm';
    $user['status_badge'] = 'Online';
    $user['role_display'] = $roleDisplay[$user['role']] ?? ucfirst((string)$user['role']);

    if ($user['role'] === 'bhw') {
        $bhwCount++;
    } elseif ($user['role'] === 'doctor') {
        $doctorCount++;
    } elseif ($user['role'] === 'nursing_attendant') {
        $nursingCount++;
    }

    if ($latestLogin === null || strtotime($user['login_time']) > strtotime($latestLogin)) {
        $latestLogin = $user['login_time'];
    }
}
unset($user);

$latestLoginText = $latestLogin ? date('h:i A', strtotime($latestLogin)) : 'N/A';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../img/logo.png">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/today.css">
    <link rel="stylesheet" href="../css/logout.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Logged In Today</title>
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
                    <span class="nav-label">User management</span>
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

        <div class="nav-search" style="position: relative;">
            <input type="search" id="patientSearch" placeholder="Search logged in users..." name="search" autocomplete="off">
            <button type="button" id="searchButton" aria-label="Search">
                <i class="bx bx-search"></i>
            </button>
            <div id="resultDropdown" class="dropdown-content"></div>
        </div>
    </nav>

    <main>
        <div class="pending-approvals-container">
            <div class="head-title today-page-header">
                <div class="left">
                    <span class="page-kicker"><i class="bx bx-wifi"></i> Live Login Monitoring</span>
                    <h1>Users Logged In Today</h1>
                    <ul class="breadcrumb">
                        <li><a href="admin_dashboard2">Dashboard</a></li>
                        <li><i class="bx bx-chevron-right"></i></li>
                        <li><a class="active" href="#">Today's Logins</a></li>
                    </ul>
                    <p>Monitor BHW, physician, and nursing attendant accounts that are currently online today.</p>
                </div>

                <div class="today-actions">
                    <a href="admin_dashboard2" class="today-action-btn secondary">
                        <i class="bx bx-arrow-back"></i>
                        Back to Dashboard
                    </a>
                    <a href="activity_logs" class="today-action-btn primary">
                        <i class="bx bx-history"></i>
                        View Activity Logs
                    </a>
                </div>
            </div>

            <div class="today-summary-grid">
                <div class="today-summary-card">
                    <span class="today-summary-icon online"><i class="bx bx-user-check"></i></span>
                    <div>
                        <p>Currently Online</p>
                        <h3><?php echo number_format($onlineCount); ?></h3>
                    </div>
                </div>

                <div class="today-summary-card">
                    <span class="today-summary-icon bhw"><i class="bx bx-health"></i></span>
                    <div>
                        <p>BHW Online</p>
                        <h3><?php echo number_format($bhwCount); ?></h3>
                    </div>
                </div>

                <div class="today-summary-card">
                    <span class="today-summary-icon doctor"><i class="bx bx-plus-medical"></i></span>
                    <div>
                        <p>Physicians Online</p>
                        <h3><?php echo number_format($doctorCount); ?></h3>
                    </div>
                </div>

                <div class="today-summary-card">
                    <span class="today-summary-icon nursing"><i class="bx bx-band-aid"></i></span>
                    <div>
                        <p>Latest Login</p>
                        <h3><?php echo e($latestLoginText); ?></h3>
                    </div>
                </div>
            </div>

            <div class="today-filter-panel">
                <div class="today-filter-heading">
                    <div>
                        <h2><i class="bx bx-filter-alt"></i> Filter Online Users</h2>
                        <p>Use search and role filter to quickly find currently logged-in users.</p>
                    </div>
                    <span><?php echo date('F j, Y', strtotime($today)); ?></span>
                </div>

                <div class="today-filter-controls">
                    <div class="today-filter-field">
                        <label for="tableSearchInput">Search</label>
                        <input type="text" id="tableSearchInput" placeholder="Search by name, role, barangay, or time...">
                    </div>

                    <div class="today-filter-field">
                        <label for="roleFilterToday">Role</label>
                        <select id="roleFilterToday">
                            <option value="">All Roles</option>
                            <option value="bhw">Barangay Health Worker</option>
                            <option value="doctor">Physician</option>
                            <option value="nursing_attendant">Nursing Attendant</option>
                        </select>
                    </div>

                    <button type="button" id="resetFilterBtn" class="today-reset-btn">
                        <i class="bx bx-reset"></i>
                        Reset
                    </button>
                </div>
            </div>

            <div class="logs-table-card">
                <div class="logs-table-card-header">
                    <div class="logs-table-title">
                        <i class="bx bx-log-in"></i>
                        <div>
                            <h3>Online Users List</h3>
                            <small>Users who logged in today and have not logged out yet.</small>
                        </div>
                    </div>
                    <span class="logs-count"><span id="visibleUsersCount"><?php echo number_format($onlineCount); ?></span> online</span>
                </div>

                <div class="today-table-wrap">
                    <table class="logs-table" id="todayLoginsTable">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Role</th>
                                <th>Designated Barangay</th>
                                <th>Login Time</th>
                                <th>Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr data-role="<?php echo e($user['role']); ?>">
                                    <td>
                                        <div class="today-user-name">
                                            <strong><?php echo e($user['full_name']); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="today-role-pill <?php echo e($user['role']); ?>">
                                            <?php echo e($user['role_display']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo !empty($user['barangay']) ? e($user['barangay']) : 'N/A'; ?></td>
                                    <td>
                                        <span class="today-time-pill">
                                            <i class="bx bx-time-five"></i>
                                            <?php echo date('h:i A', strtotime($user['login_time'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo e($user['duration_formatted']); ?></td>
                                    <td><span class="tl-online"><i class="bx bx-radio-circle-marked"></i> Online</span></td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($users)): ?>
                                <tr class="today-empty-row">
                                    <td colspan="6">
                                        <div class="today-empty-state">
                                            <i class="bx bx-user-x"></i>
                                            <h3>No users currently online</h3>
                                            <p>There are no BHW, physician, or nursing attendant accounts currently logged in today.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div id="noFilterResults" class="today-empty-state filter-empty" style="display:none;">
                    <i class="bx bx-search-alt"></i>
                    <h3>No matching users found</h3>
                    <p>Try adjusting your search keyword or role filter.</p>
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
    </main>
</section>

<script>
function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'block';
    return false;
}

function closeModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

function proceedLogout() {
    window.location.href = 'logout';
}

window.addEventListener('click', function (event) {
    const modal = document.getElementById('logoutModal');
    if (event.target === modal) {
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

document.addEventListener("DOMContentLoaded", () => {
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

  const navbarSearch = document.getElementById('patientSearch');
  const searchButton = document.getElementById('searchButton');
  const tableSearchInput = document.getElementById('tableSearchInput');
  const roleFilter = document.getElementById('roleFilterToday');
  const resetBtn = document.getElementById('resetFilterBtn');
  const countEl = document.getElementById('visibleUsersCount');
  const noFilterResults = document.getElementById('noFilterResults');
  const rows = Array.from(document.querySelectorAll('#todayLoginsTable tbody tr')).filter(row => !row.classList.contains('today-empty-row'));

  function filterLoggedInUsers() {
    const navTerm = (navbarSearch?.value || '').toLowerCase().trim();
    const tableTerm = (tableSearchInput?.value || '').toLowerCase().trim();
    const searchTerm = `${navTerm} ${tableTerm}`.trim();
    const selectedRole = (roleFilter?.value || '').toLowerCase().trim();
    let visibleCount = 0;

    rows.forEach(row => {
      const role = (row.dataset.role || '').toLowerCase();
      const text = row.textContent.toLowerCase();
      const matchesSearch = !searchTerm || text.includes(searchTerm);
      const matchesRole = !selectedRole || role === selectedRole;
      const shouldShow = matchesSearch && matchesRole;

      row.style.display = shouldShow ? '' : 'none';
      if (shouldShow) visibleCount++;
    });

    if (countEl) {
      countEl.textContent = visibleCount.toLocaleString();
    }

    if (noFilterResults) {
      noFilterResults.style.display = rows.length > 0 && visibleCount === 0 ? 'block' : 'none';
    }
  }

  [navbarSearch, tableSearchInput].forEach(input => {
    if (!input) return;
    input.addEventListener('input', filterLoggedInUsers);
    input.addEventListener('keypress', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        filterLoggedInUsers();
      }
    });
  });

  if (searchButton) {
    searchButton.addEventListener('click', filterLoggedInUsers);
  }

  if (roleFilter) {
    roleFilter.addEventListener('change', filterLoggedInUsers);
  }

  if (resetBtn) {
    resetBtn.addEventListener('click', function () {
      if (navbarSearch) navbarSearch.value = '';
      if (tableSearchInput) tableSearchInput.value = '';
      if (roleFilter) roleFilter.value = '';
      filterLoggedInUsers();
    });
  }
});
</script>
</body>
</html>
