<?php
require 'config.php'; // Ensure your database connection is correctly set up
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Destroy any existing session data
    session_destroy();
    // Redirect to login page
    header("Location: ../../role");
    exit();
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
        WHERE action='login' AND DATE(log_time) = :today
        GROUP BY user_id
    ) latest_login ON u.user_id = latest_login.user_id
    WHERE u.role IN ('bhw', 'doctor', 'nursing_attendant')
    AND NOT EXISTS (
        SELECT 1 FROM user_logs ul2
        WHERE ul2.user_id = latest_login.user_id
        AND ul2.action='logout'
        AND ul2.log_time > latest_login.login_time
        AND DATE(ul2.log_time) = :today
    )
    ORDER BY latest_login.login_time DESC
");

$stmt->execute(['today' => $today]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Role mapping for display
$roleDisplay = [
    'bhw' => 'Barangay Health Worker',
    'doctor' => 'Physician', 
    'nursing_attendant' => 'Nursing Attendant'
];

// Process duration and status badges
foreach ($users as &$user) {
    $login_dt = new DateTime($user['login_time']);
    $end_dt = new DateTime();
    $interval = $login_dt->diff($end_dt);
    $hours = $interval->h + ($interval->days * 24);
    $minutes = $interval->i;
    
    $user['duration_formatted'] = "Currently " . $hours . 'h ' . $minutes . 'm';
    $user['status_badge'] = 'Online';
    $user['role_display'] = $roleDisplay[$user['role']] ?? ucfirst($user['role']);
}
?>

<!DOCTYPE html>
<html lang="en">  
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../img/logo.png">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashstyle.css">
    <link rel="stylesheet" href="../css/approval.css">
    <link rel="stylesheet" href="../css/logout.css">
    <link rel="stylesheet" href="../css/sidebar.css">   
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Log In Today</title>
</head>

<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar Section -->
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

    <!-- Main Content Section -->
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

            <div class="logs-table-card">
                <div class="logs-table-card-header">
                    <i class="bx bx-log-in"></i>
                    <h3>Users Logged In Today</h3>
                    <span class="logs-count"><?php echo date('F j, Y', strtotime($today)); ?> &mdash; <?php echo count($users); ?> online</span>
                </div>

                <table class="logs-table">
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
                            <tr>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo $user['role_display']; ?></td>
                                <td><?php echo !empty($user['barangay']) ? htmlspecialchars($user['barangay']) : 'N/A'; ?></td>
                                <td><?php echo date('h:i A', strtotime($user['login_time'])); ?></td>
                                <td><?php echo $user['duration_formatted']; ?></td>
                                <td><span class=\"tl-online\">Online</span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="6" style="text-align:center;color:#999;">No users currently online.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div><!-- /.logs-table-card -->


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

    <script>
        function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'block';
    return false; // Prevent the default link behavior
}

function closeModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

function proceedLogout() {
    window.location.href='logout';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('logoutModal');
    if (event.target == modal) {
        closeModal();
    }
};
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
  const rows = document.querySelectorAll('main table tbody tr');

  function filterLoggedInUsers() {
    const term = (navbarSearch?.value || '').toLowerCase().trim();
    rows.forEach(row => {
      row.style.display = !term || row.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
  }

  if (navbarSearch && searchButton) {
    navbarSearch.addEventListener('input', filterLoggedInUsers);
    navbarSearch.addEventListener('keypress', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        filterLoggedInUsers();
      }
    });
    searchButton.addEventListener('click', filterLoggedInUsers);
  }
});
</script>
</body>
</html>
