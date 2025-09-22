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
// Default to last 7 days if no date range is specified
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Fetch daily active users for the selected date range
$activeUsersQuery = "SELECT DATE(timestamp) AS log_date, COUNT(DISTINCT user_id) AS active_users 
                     FROM logs 
                     WHERE action = 'login' AND DATE(timestamp) BETWEEN :start_date AND :end_date
                     GROUP BY log_date 
                     ORDER BY log_date ASC";

$activeUsersStmt = $pdo->prepare($activeUsersQuery);
$activeUsersStmt->bindParam(':start_date', $start_date);
$activeUsersStmt->bindParam(':end_date', $end_date); 
$activeUsersStmt->execute();
$activeUsers = $activeUsersStmt->fetchAll(PDO::FETCH_ASSOC);

// Debugging: Log the query results
error_log(print_r($activeUsers, true));

// Fetch most common actions
$commonActionsQuery = "SELECT action, COUNT(action) AS count 
                       FROM logs 
                       WHERE DATE(timestamp) BETWEEN :start_date AND :end_date
                       GROUP BY action 
                       ORDER BY count DESC 
                       LIMIT 5";

$commonActionsStmt = $pdo->prepare($commonActionsQuery);
$commonActionsStmt->bindParam(':start_date', $start_date);
$commonActionsStmt->bindParam(':end_date', $end_date);
$commonActionsStmt->execute();
$commonActions = $commonActionsStmt->fetchAll(PDO::FETCH_ASSOC);

// Today's Active Users
$todayUsersQuery = "SELECT COUNT(DISTINCT user_id) AS today_users 
                    FROM logs 
                    WHERE action = 'login' AND DATE(timestamp) = CURDATE()";
$todayUsersStmt = $pdo->prepare($todayUsersQuery); 
$todayUsersStmt->execute();
$todayUsers = $todayUsersStmt->fetch(PDO::FETCH_ASSOC)['today_users'];

// Calculate summary metrics
$totalUsers = $todayUsers;
$avgUsers = !empty($activeUsers) ? round(array_sum(array_column($activeUsers, 'active_users')) / count($activeUsers)) : 0;
$totalActions = array_sum(array_column($commonActions, 'count'));
?> 


 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../img/logo.png">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/user.css">
    <link rel="stylesheet" href="../css/logout.css">
    
    <!-- NEW: DatePicker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <!-- JS Dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <title>Reports</title>
    
</head>
<body>
    <!-- Sidebar Section -->
    <section id="sidebar">
        <a href="#" class="brand">
            <img src="../../img/logo.png" alt="RHULogo" class="logo">
            <span class="text">Hello Admin</span>
        </a>
        <ul class="side-menu top">
            <li>
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
            <li class="active">
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

        <div class="head-title">
        <h2 class="management-title">User Activity Report</h2>
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

        <main>
            <!-- NEW: Date Range Filter -->
            <div class="filter-container">
    <div class="date-filters">
        <span>Date Range:</span>
        <input type="text" id="start-date" name="start_date" class="date-input" placeholder="Start Date" value="<?php echo $start_date; ?>">
        <span>to</span>
        <input type="text" id="end-date" name="end_date" class="date-input" placeholder="End Date" value="<?php echo $end_date; ?>">
        <button id="apply-filter" class="filter-button">Apply Filter</button>
    </div>
    
            <div id="print-section">
                <section id="contents">
                    <div class="metrics">
                        <div class="metric">
                            <div class="metric-value"><?php echo $totalUsers; ?></div>
                            <div class="metric-label">Today's Active Users</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value"><?php echo $avgUsers; ?></div>
                            <div class="metric-label">Average Daily Users</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value"><?php echo number_format($totalActions); ?></div>
                            <div class="metric-label">Total Actions (Selected Period)</div>
                        </div>
                    </div>
                    
                    <!-- NEW: Charts Container -->
                    <div class="charts-container">
                        
                        <div class="chart-card">
                            <div class="chart-title">Login Action</div>
                            <canvas id="commonActionsChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3>All Action</h3>
                        </div>
                        <table class="data-table" id="actions-table">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>Count</th>
                                    <th>Distribution</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $maxCount = !empty($commonActions) ? max(array_column($commonActions, 'count')) : 0;
                                
                                foreach ($commonActions as $action): 
                                    $percentage = $maxCount > 0 ? ($action['count'] / $maxCount) * 100 : 0;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($action['action']); ?></td>
                                        <td><?php echo number_format($action['count']); ?></td>
                                        <td>
                                            <div class="progress-bar">
                                                <div class="progress" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

                     <!-- Buttons Section -->
</div> 


        </main>
    </section>

<script>

        // Initialize date pickers
        flatpickr("#start-date", {
        dateFormat: "Y-m-d",
        maxDate: "today"
    });
    
    flatpickr("#end-date", {
        dateFormat: "Y-m-d",
        maxDate: "today"
    });
    
    // Apply date filter
    document.getElementById('apply-filter').addEventListener('click', function() {
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    
    if (startDate && endDate) {
        // Redirect to the correct file (admin_reports.php)
        window.location.href = `admin_reports.php?start_date=${startDate}&end_date=${endDate}`;
    } else {
        alert('Please select both start and end dates');
    }
});    

    // Common Actions Chart
    const commonActionsData = <?php echo json_encode($commonActions); ?>;
    
    const actionLabels = commonActionsData.map(entry => entry.action);
    const actionCounts = commonActionsData.map(entry => entry.count);
    
    const actionsCtx = document.getElementById('commonActionsChart').getContext('2d');
    const commonActionsChart = new Chart(actionsCtx, {
        type: 'bar',
        data: {
            labels: actionLabels,
            datasets: [{
                label: 'Action Count',
                data: actionCounts,
                backgroundColor: [
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 99, 132, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => `Count: ${context.parsed.x}`
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    title: { display: true, text: 'Count' }
                }
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
    window.location.href = 'logout.php'; // Adjust path if needed
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
 