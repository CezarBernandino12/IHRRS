<?php
require 'config.php'; // Ensure your database connection is correctly set up
session_start();

// Check if user is logged in and has BHW role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Destroy any existing session data
    session_destroy();
    // Redirect to BHW login page
    header("Location: ../../role.html");
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
    WHERE ul.action = 'login'
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
     <link rel="stylesheet" href="../css/logout.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Admin Dashboard</title>
</head> 
<body>
    <!-- Sidebar Section --> 
    <section id="sidebar">
        <a href="#" class="brand">
            <img src="../../img/logo.png" alt="RHULogo" class="logo">
                        <span class="text">Hello Admin</span>


        </a> 
        <ul class="side-menu top">
            <li class="active">
                <a href="admin_dashboard2.php">
                    <i class="bx bxs-dashboard"></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li> 
                <a href="activity_logs.php">
                    <i class="bx bxs-user"></i>
                    <span class="text">Activity Logs</span>
                </a>
            </li>
            <li>
                <a href="admin_user.php">
                    <i class="bx bxs-notepad"></i>
                    <span class="text">User management</span>
                </a>
            </li>
            <li>
                <a href="admin_reports.php">
                    <i class="bx bxs-report"></i>
                    <span class="text">Reports</span>
                </a>
            </li>
        </ul>

        <ul class="side-menu">
            <li>
                <a href="#" class="logout" onclick="return confirmLogout()">
               <i class="bx bxs-log-out-circle"></i>
                <span class="text">Logout</span>
                </a>
            </li>
        </ul>
    </section>
 
    <!-- Main Content Section -->
    <section id="content">
        <nav>
            <form action="#">
            </form>
            <div class="greeting">
                <span id="userGreeting">Hello Admin!</span>
            </div>
            <a href="#" class="profile">
                <img src="../../img/admin.png">
            </a>
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
                <a href="today_logins.php" style="text-decoration: none; color: inherit;">
                    <div class="card hoverable">
                        <p class="value"><?php echo $loggedInToday; ?></p>
                        <h3>Today's Logins</h3>
                        <div class="subheader">BHW & Doctor Logins Today</div>
                        <div class="progress-bar invoices-progress">
                            <div class="progress"></div>
                        </div>
                    </div>
                </a>

                <a href="inactive_users.php" style="text-decoration: none; color: inherit;">
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
                if (data.full_name) {
                    document.getElementById('userGreeting').textContent = `Hello, ${data.full_name}!`;
                } else {
                    document.getElementById('userGreeting').textContent = 'Hello, Admin!';
                }
            })
            .catch(error => {
                console.error('Error fetching user name:', error);
                document.getElementById('userGreeting').textContent = 'Hello, Admin!';
            });


        function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'block';
    return false; // Prevent the default link behavior
}
 
function closeModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

function proceedLogout() {
    window.location.href = 'logout.php';
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
document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.getElementById("sidebar");

  function applyResponsiveSidebar() {
    if (window.innerWidth <= 1024) {
      sidebar.classList.add("hide");   // collapsed on small screens
    } else {
      sidebar.classList.remove("hide"); // expanded on larger screens
    }
  }

  applyResponsiveSidebar();
  window.addEventListener("resize", applyResponsiveSidebar);

  // keep the rest of your existing code (auth, stats, modals, etc.)
});
</script>
</body>
</html>