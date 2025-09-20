<?php
require 'config.php';
session_start();

// Check if user is logged in and has BHW role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Destroy any existing session data
    session_destroy();
    // Redirect to BHW login page
    header("Location: ../../role.html");
    exit();
}

// Get the current week dates
$weekStart = date('Y-m-d', strtotime('-2 weeks'));
$weekEnd = date('Y-m-d', strtotime('today'));

// Query new users this week with detailed information
$stmt = $pdo->prepare("
    SELECT u.user_id, u.full_name AS name, u.role, u.contact_number, u.registration_date
    FROM users u
    WHERE DATE(u.registration_date) BETWEEN :weekStart AND :weekEnd
    ORDER BY u.registration_date DESC
");
$stmt->execute(['weekStart' => $weekStart, 'weekEnd' => $weekEnd]);
$newUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get daily new user counts for the current week
$stmt = $pdo->prepare("
    SELECT DATE(registration_date) AS date, COUNT(*) AS count
    FROM users 
    WHERE DATE(registration_date) BETWEEN :weekStart AND :weekEnd
    GROUP BY DATE(registration_date)
    ORDER BY DATE(registration_date)
");
$stmt->execute(['weekStart' => $weekStart, 'weekEnd' => $weekEnd]);
$dailyNewUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for chart
$dates = [];
$counts = [];
foreach ($dailyNewUsers as $day) {
    $dates[] = date('D, M d', strtotime($day['date']));
    $counts[] = $day['count'];
}

$chartDates = json_encode($dates);
$chartCounts = json_encode($counts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../img/logo.png">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard3.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>New Users</title>

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
                <a href="logout.php" class="logout" onclick="return confirmLogout()">
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
            
            <a href="profile.php" class="profile">
            </a>
        </nav>

        <main>
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <a href="admin_dashboard2.php" class="back-btn">
                <i class="bx bx-undo"></i>
            </a>
            <h1>New Users This Week</h1>
            <p>User registrations from <?php echo date('F j, Y', strtotime($weekStart)); ?> to <?php echo date('F j, Y', strtotime($weekEnd)); ?></p>
        </div>
        <div class="header-actions">
            <button class="export-btn" onclick="exportToExcel()">
                <i class='bx bx-export'></i> Export to Excel
            </button>
            <div class="date-filter">
                <select id="dateRange" onchange="updateDateRange()">
                    <option value="7">Last 7 Days</option>
                    <option value="14">Last 14 Days</option>
                    <option value="30">Last 30 Days</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <div class="card-icon">
                <i class='bx bx-user-plus'></i>
            </div>
            <div class="card-content">
                <h3>Total New Users</h3>
                <p class="value"><?php echo count($newUsers); ?></p>
                <p class="trend <?php echo count($newUsers) > 0 ? 'positive' : 'neutral'; ?>">
                    <i class='bx <?php echo count($newUsers) > 0 ? 'bx-up-arrow-alt' : 'bx-minus'; ?>'></i>
                    <?php echo count($newUsers); ?> this period
                </p>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="card-icon">
                <i class='bx bx-user-check'></i>
            </div>
            <div class="card-content">
                <h3>Most Common Role</h3>
                <?php
                $roles = array_count_values(array_column($newUsers, 'role'));
                arsort($roles);
                $mostCommonRole = key($roles);
                $roleCount = current($roles);
                ?>
                <p class="value"><?php echo ucfirst($mostCommonRole); ?></p>
                <p class="trend">
                    <i class='bx bx-user'></i>
                    <?php echo $roleCount; ?> users
                </p>
            </div>
        </div>

        <div class="summary-card">
            <div class="card-icon">
                <i class='bx bx-calendar-check'></i>
            </div>
            <div class="card-content">
                <h3>Peak Registration Day</h3>
                <?php
                $dates = array_count_values(array_map(function($user) {
                    return date('Y-m-d', strtotime($user['registration_date']));
                }, $newUsers));
                arsort($dates);
                $peakDate = key($dates);
                $peakCount = current($dates);
                ?>
                <p class="value"><?php echo date('D, M d', strtotime($peakDate)); ?></p>
                <p class="trend">
                    <i class='bx bx-user'></i>
                    <?php echo $peakCount; ?> registrations
                </p>
            </div>
        </div>
    </div>

    <!-- New Users Chart -->
    <div class="chart-container">
        <div class="chart-header">
            <h2>New User Registrations</h2>
            <div class="chart-actions">
                <button class="chart-btn" onclick="toggleChartType()">
                    <i class='bx bx-bar-chart-alt-2'></i> Toggle View
                </button>
            </div>
        </div>
        <div class="chart-wrapper">
            <canvas id="newUsersChart"></canvas>
        </div>
    </div>

    <!-- New Users Table -->
    <div class="table-container">
        <div class="table-header">
            <h2>Detailed User List</h2>
            <div class="table-actions">
                <div class="search-box">
                    <i class='bx bx-search'></i>
                    <input type="text" id="userSearch" placeholder="Search users..." onkeyup="filterUsers()">
                </div>
                <select id="roleFilter" onchange="filterUsers()">
                    <option value="">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="doctor">Doctor</option>
                    <option value="bhw">BHW/Midwife</option>
                    <option value="nursing_attendant">Nursing Attendant</option>
                </select>
            </div>
        </div>
        <?php if (empty($newUsers)): ?>
            <div class="no-users">
                <i class='bx bx-user-x'></i>
                <p>No new users registered this week.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Contact Number</th>
                            <th>Registration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($newUsers as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td>
                                    <?php 
                                        $roleClass = 'role-' . strtolower($user['role']);
                                        echo '<span class="role-badge ' . $roleClass . '">' . 
                                            htmlspecialchars(ucfirst($user['role'])) . '</span>';
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['contact_number']); ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($user['registration_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
    </section>

    <script>
        function confirmLogout() {
            return confirm("Are you sure you want to logout?");
        }

        let chartType = 'bar';
        const ctx = document.getElementById('newUsersChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(28, 83, 138, 0.8)');
        gradient.addColorStop(1, 'rgba(28, 83, 138, 0.1)');

        const newUsersChart = new Chart(ctx, {
            type: chartType,
            data: {
                labels: <?php echo $chartDates; ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?php echo $chartCounts; ?>,
                    backgroundColor: gradient,
                    borderColor: 'rgba(28, 83, 138, 1)',
                    borderWidth: 2,
                    borderRadius: 8,
                    minBarLength: 5,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#1c538a',
                        bodyColor: '#666',
                        borderColor: '#1c538a',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return `New Users: ${context.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            precision: 0,
                            font: {
                                size: 12,
                                weight: '500'
                            },
                            color: '#666',
                            maxTicksLimit: 5
                        },
                        title: {
                            display: true,
                            text: 'Number of New Users',
                            font: {
                                size: 14,
                                weight: '600'
                            },
                            color: '#1c538a',
                            padding: {top: 10, bottom: 10}
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12,
                                weight: '500'
                            },
                            color: '#666',
                            maxRotation: 45,
                            minRotation: 45
                        },
                        title: {
                            display: true,
                            text: 'Date',
                            font: {
                                size: 14,
                                weight: '600'
                            },
                            color: '#1c538a',
                            padding: {top: 10, bottom: 10}
                        }
                    }
                }
            }
        });

        function toggleChartType() {
            chartType = chartType === 'bar' ? 'line' : 'bar';
            newUsersChart.config.type = chartType;
            newUsersChart.update();
        }

        function filterUsers() {
            const searchText = document.getElementById('userSearch').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value.toLowerCase();
            const rows = document.querySelectorAll('.users-table tbody tr');

            rows.forEach(row => {
                const name = row.cells[0].textContent.toLowerCase();
                const role = row.cells[1].textContent.toLowerCase();
                const matchesSearch = name.includes(searchText);
                const matchesRole = !roleFilter || role.includes(roleFilter);
                row.style.display = matchesSearch && matchesRole ? '' : 'none';
            });
        }

        function updateDateRange() {
            const days = document.getElementById('dateRange').value;
            window.location.href = `dash_newuser.php?days=${days}`;
        }

        function exportToExcel() {
            const table = document.querySelector('.users-table');
            const html = table.outerHTML;
            const url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
            const link = document.createElement('a');
            link.download = 'new_users_report.xls';
            link.href = url;
            link.click();
        }

        function viewUserDetails(userId) {
            // Implement view user details functionality
            window.location.href = `user_details.php?id=${userId}`;
        }

        function editUser(userId) {
            // Implement edit user functionality
            window.location.href = `edit_user.php?id=${userId}`;
        }
    </script>
</body>
</html>