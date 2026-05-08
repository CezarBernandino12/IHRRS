<?php
require 'config.php'; // Ensure your database connection is correctly set up
session_start();

// Check if user is logged in and has BHW role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Destroy any existing session data
    session_destroy();
    // Redirect to BHW login page
    header("Location: ../../role");
    exit();
}


// Query to count users by role
$stmt = $pdo->prepare("SELECT role, COUNT(*) AS count FROM users GROUP BY role");
$stmt->execute();
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize variables
$bhwMidwifeCount = 0;
$doctorCount = 0;
$adminCount = 0;

// Assign count values based on roles
foreach ($roles as $role) {
    if ($role['role'] == 'bhw' || $role['role'] == 'midwife') {
        $bhwMidwifeCount += $role['count'];
    } elseif ($role['role'] == 'doctor') {
        $doctorCount = $role['count'];
    } elseif ($role['role'] == 'Admin') {
        $adminCount = $role['count'];
    }
}

// Query to count active and inactive users
$stmt = $pdo->prepare("SELECT account_status, COUNT(*) AS count FROM users GROUP BY account_status");
$stmt->execute();
$statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize variables
$activeUsers = 0;
$inactiveUsers = 0;

// Assign values based on account_status
foreach ($statusCounts as $status) {
    if ($status['account_status'] == 'active') {
        $activeUsers = $status['count'];
    } elseif ($status['account_status'] == 'inactive' || $status['account_status'] == 'suspended') {
        $inactiveUsers += $status['count'];
    }
}

// Count users (bhw and doctor) who logged in today
$today = date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT ul.user_id) AS active_today
    FROM users u
    JOIN user_logs ul ON u.user_id = ul.user_id
    WHERE (u.role = 'bhw' OR u.role = 'doctor' OR u.role = 'nursing_attendant') -- Include nursing_attendant role
      AND ul.action='login'
      AND DATE(ul.log_time) = :today
      AND NOT EXISTS (
          SELECT 1
          FROM user_logs ul2
          WHERE ul2.user_id = ul.user_id
            AND ul2.action='logout'
            AND ul2.log_time > ul.log_time
      )
");
$stmt->execute(['today' => $today]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$loggedInToday = $row['active_today'];


// NEW FEATURE 1: Count new users registered this week
$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekEnd = date('Y-m-d', strtotime('sunday this week'));

$stmt = $pdo->prepare("
    SELECT COUNT(*) AS new_users
    FROM users
    WHERE DATE(registration_date) BETWEEN :weekStart AND :weekEnd
");
$stmt->execute(['weekStart' => $weekStart, 'weekEnd' => $weekEnd]);
$newUsersRow = $stmt->fetch(PDO::FETCH_ASSOC);
$newUsersThisWeek = $newUsersRow['new_users'];

// NEW FEATURE 2: Get the most active users (based on login counts)
$stmt = $pdo->prepare("
    SELECT u.username, u.role, COUNT(ul.log_id) AS login_count
    FROM users u
    JOIN user_logs ul ON u.user_id = ul.user_id
    WHERE ul.action='login'
    AND DATE(ul.log_time) >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY) -- Only last 7 days
    GROUP BY u.user_id
    ORDER BY login_count DESC
    LIMIT 5
");
$stmt->execute();
$mostActiveUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unreadCount = 0;
?>

<!DOCTYPE html>
<html lang="en"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../img/logo.png">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashstyle.css"> 
    <link rel="stylesheet" href="../css/dashboard2.css">
    <link rel="stylesheet" href="../css/sidebar.css">
     <link rel="stylesheet" href="../css/logout.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Admin Dashboard</title>
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
 
    <!-- Main Content Section -->
    <section id="content">
        <nav>
            <button class="nav-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="bx bx-menu"></i>
            </button>

            <div class="nav-search" style="position: relative;">
                <input type="search" id="patientSearch" placeholder="Search dashboard..." name="search" autocomplete="off">
                <button type="button" id="searchButton" aria-label="Search">
                    <i class="bx bx-search"></i>
                </button>
                <div id="resultDropdown" class="dropdown-content"></div>
            </div>
        </nav>

        <main>
            <?php if (isset($_SESSION['pending_reset']) && $_SESSION['pending_reset']): ?>
            <div class="info-message" style="background:#fff3cd; border:1px solid #ffeaa7; padding:15px; margin-bottom:20px; border-radius:5px; display:flex; align-items:center; gap:10px;">
                <i class="bx bx-info-circle" style="color:#856404; font-size:24px;"></i>
                <div>
                    <strong style="color:#856404;">Password Reset Request Pending</strong>
                    <p style="color:#856404; margin:5px 0 0 0;">Your current password still works. An admin will contact you soon to complete the reset.</p>
                </div>
                <button onclick="this.parentElement.style.display='none';" style="background:none; border:none; font-size:20px; color:#856404; cursor:pointer; margin-left:auto;">&times;</button>
            </div>
            <?php unset($_SESSION['pending_reset']); ?>
            <?php endif; ?>

                        <div class="head-title">
                <div class="left">
                  <h1>Welcome, Admin!</h1>
                  <ul class="breadcrumb">
                    <li><a href="#">Here's an overview of the system's activity.</a></li>
                    </ul>
                </div>
              </div>

            <div class="dashboard-container">
                <a href="today_logins" style="text-decoration: none; color: inherit;">
                    <div class="card hoverable">
                        <p class="value"><?php echo $loggedInToday; ?></p>
                        <h3>Today's Logins</h3>
                        <div class="subheader">BHW & Doctor Logins Today</div>
                        <div class="progress-bar invoices-progress">
                            <div class="progress"></div>
                        </div>
                    </div>
                </a>

                <a href="inactive_users" style="text-decoration: none; color: inherit;">
                    <div class="card hoverable">
                        <p class="value"><?php echo $inactiveUsers; ?></p>
                        <h3>Terminated Accounts</h3>
                        <div class="subheader">Terminated Accounts</div>
                        <div class="progress-bar leads-progress">
                            <div class="progress"></div>
                        </div>
                    </div>
                </a>
         
                <div class="most-active-users">
    <div class="section-header">
        <i class='bx bxs-user-check'></i>
        <h3>Most Active Users</h3>
        <span class="time-period">Last 7 Days</span>
    </div>
    <div class="users-list">
        <?php if(empty($mostActiveUsers)): ?>
            <div class="no-active-users">
                <i class='bx bx-user-x'></i>
                <p>No active users found.</p>
            </div>
        <?php else: ?>
            <?php foreach($mostActiveUsers as $user): ?>
                <div class="user-card">
                    <div class="user-info">
                        <div class="role-badge <?php echo strtolower($user['role']); ?><?php echo strtolower($user['role']) == 'nursing_attendant' ? ' nursing_attendant' : ''; ?>">
                            <?php if (strtolower($user['role']) == 'bhw'): ?>
                                <i class='bx bxs-user'></i>
                            <?php elseif (strtolower($user['role']) == 'doctor'): ?>
                                <i class='bx bxs-user-voice'></i>
                            <?php elseif (strtolower($user['role']) == 'admin'): ?>
                                <i class='bx bxs-crown'></i>
                            <?php elseif (strtolower($user['role']) == 'nursing_attendant'): ?>
                                <i class='bx bxs-band-aid'></i>
                            <?php else: ?>
                                <i class='bx bxs-user'></i>
                            <?php endif; ?>
                        </div>
                        <div class="user-details">
                            <span class="user-name"><?php echo htmlspecialchars($user['username']); ?></span>
                            <span class="user-role"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span>
                        </div>
                    </div>
                    <div class="activity-info">
                        <span class="login-count">
                            <i class='bx bx-log-in'></i>
                            <?php echo $user['login_count']; ?> logins
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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

    <script src="../js/notif.js"></script>
    <script>
        fetch('getUserName.php')
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

// Keep the BHW-style navbar search local to this dashboard so content stays intact.
document.addEventListener("DOMContentLoaded", () => {
  const searchInput = document.getElementById("patientSearch");
  const searchButton = document.getElementById("searchButton");
  const cards = () => Array.from(document.querySelectorAll(".card, .user-card"));

  function filterDashboardCards() {
    const term = (searchInput?.value || "").toLowerCase().trim();
    cards().forEach(card => {
      card.style.display = card.textContent.toLowerCase().includes(term) ? "" : "none";
    });
  }

  if (searchInput && searchButton) {
    searchInput.addEventListener("input", filterDashboardCards);
    searchInput.addEventListener("keypress", function (event) {
      if (event.key === "Enter") {
        event.preventDefault();
        filterDashboardCards();
      }
    });
    searchButton.addEventListener("click", filterDashboardCards);
  }
});
</script>
</body>
</html>