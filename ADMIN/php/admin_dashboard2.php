<?php
require 'config.php'; // Ensure your database connection is correctly set up

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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Admin Dashboard</title>
</head>
<body>
    <!-- Sidebar Section --> 
    <section id="sidebar">
        <a href="#" class="brand">
            <img src="../../img/logo.png" alt="RHULogo" class="logo"> 
           <span class="text" id="userGreeting">Hello Admin</span>

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
            <div class="welcome-message">
                <h2>Welcome, Admin!</h2>
                <p>Here's an overview of the system's activity.</p>
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
                <a href="dash_newuser.php" style="text-decoration: none; color: inherit;">
                    <div class="card hoverable">
                        <p class="value"><?php echo $newUsersThisWeek; ?></p>
                        <h3>New Users</h3>
                        <div class="subheader">Registered This Week</div>
                        <div class="progress-bar new-users-progress">
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
            <!-- Daily Logins Chart -->
            <div class="chart-container">
                <canvas id="dailyLoginsChart"></canvas>
            </div>

            <div id="logoutModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirm Logout</h3>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to logout?</p>
        </div>
        <div class="modal-footer">
            <button onclick="closeModal()" class="btn yes">Cancel</button>
            <button onclick="proceedLogout()" class="btn no">Yes, Logout</button>
        </div>
    </div>
</div>

        </main>
    </section>

    <script src="../js/notif.js"></script>
    <script>

        // Chart for daily logins
        const ctx = document.getElementById('dailyLoginsChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(28, 83, 138, 0.8)');
        gradient.addColorStop(1, 'rgba(28, 83, 138, 0.1)');

        const dailyLoginsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                datasets: [{
                    label: 'Daily Logins',
                    data: [12, 19, 3, 5, 2, 3, 7],
                    borderColor: '#1c538a',
                    backgroundColor: gradient,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#1c538a',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                family: 'Poppins',
                                size: 14,
                                weight: '500'
                            },
                            color: '#333'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#1c538a',
                        bodyColor: '#333',
                        borderColor: '#1c538a',
                        borderWidth: 1,
                        padding: 12,
                        boxPadding: 6,
                        usePointStyle: true,
                        callbacks: {
                            label: function(context) {
                                return `Logins: ${context.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Poppins',
                                size: 12
                            },
                            color: '#666'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                family: 'Poppins',
                                size: 12
                            },
                            color: '#666',
                            callback: function(value) {
                                return value + ' users';
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                }
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
</body>
</html>