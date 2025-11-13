<?php
require 'config.php';
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    header("Location: ../../role.html");
    exit();
}

// Default to last 7 days if no date range is specified
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Fetch daily active users for the selected date range
$activeUsersQuery = "SELECT DATE(timestamp) AS log_date, COUNT(DISTINCT performed_by) AS active_users 
                     FROM logs 
                     WHERE action = 'Successful Login' AND DATE(timestamp) BETWEEN :start_date AND :end_date
                     GROUP BY log_date 
                     ORDER BY log_date ASC";

$activeUsersStmt = $pdo->prepare($activeUsersQuery);
$activeUsersStmt->bindParam(':start_date', $start_date);
$activeUsersStmt->bindParam(':end_date', $end_date); 
$activeUsersStmt->execute();
$activeUsers = $activeUsersStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch most common actions
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$commonActionsQuery = "SELECT action, COUNT(action) AS count
                       FROM logs
                       WHERE DATE(timestamp) BETWEEN :start_date AND :end_date
                       GROUP BY action
                       ORDER BY count DESC
                       LIMIT :limit OFFSET :offset";

$totalCommonActionsQuery = "SELECT COUNT(DISTINCT action) AS total
                            FROM logs
                            WHERE DATE(timestamp) BETWEEN :start_date AND :end_date";

$commonActionsStmt = $pdo->prepare($commonActionsQuery);
$commonActionsStmt->bindParam(':start_date', $start_date);
$commonActionsStmt->bindParam(':end_date', $end_date);
$commonActionsStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$commonActionsStmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$commonActionsStmt->execute();
$commonActions = $commonActionsStmt->fetchAll(PDO::FETCH_ASSOC);

$totalCommonActionsStmt = $pdo->prepare($totalCommonActionsQuery);
$totalCommonActionsStmt->bindParam(':start_date', $start_date);
$totalCommonActionsStmt->bindParam(':end_date', $end_date);
$totalCommonActionsStmt->execute();
$totalCommonActions = $totalCommonActionsStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalCommonActions / $limit);

// Today's Active Users
$todayUsersQuery = "SELECT COUNT(DISTINCT performed_by) AS today_users 
                    FROM logs 
                    WHERE action = 'Successful Login' AND DATE(timestamp) = CURDATE()";
$todayUsersStmt = $pdo->prepare($todayUsersQuery); 
$todayUsersStmt->execute();
$todayUsers = $todayUsersStmt->fetch(PDO::FETCH_ASSOC)['today_users'];

// FIXED: User Management Actions Report - removed performed_by_name
$userManagementQuery = "SELECT action, COUNT(*) as count, performed_by, u.full_name as admin_name
                        FROM logs l
                        LEFT JOIN users u ON u.user_id = l.performed_by
                        WHERE (action LIKE 'Added new user: %' OR action LIKE 'Terminated User %' OR action LIKE 'Change password for %')
                        AND DATE(l.timestamp) BETWEEN :start_date AND :end_date
                        GROUP BY action, performed_by
                        ORDER BY count DESC";

$userManagementStmt = $pdo->prepare($userManagementQuery);
$userManagementStmt->bindParam(':start_date', $start_date);
$userManagementStmt->bindParam(':end_date', $end_date);
$userManagementStmt->execute();
$userManagementActions = $userManagementStmt->fetchAll(PDO::FETCH_ASSOC);

// FIXED: Security Events Report - added full_name join
$securityPage = isset($_GET['security_page']) ? (int)$_GET['security_page'] : 1;
$securityLimit = 10;
$securityOffset = ($securityPage - 1) * $securityLimit;

$securityQuery = "SELECT l.action, u.full_name, l.timestamp
                  FROM logs l
                  LEFT JOIN users u ON u.user_id = l.performed_by
                  WHERE (l.action LIKE '%Failed%' OR l.action LIKE '%Unauthorized%' OR l.action LIKE '%Error%')
                  AND DATE(l.timestamp) BETWEEN :start_date AND :end_date
                  ORDER BY l.timestamp DESC
                  LIMIT :limit OFFSET :offset";

$totalSecurityQuery = "SELECT COUNT(*) AS total
                       FROM logs l
                       WHERE (l.action LIKE '%Failed%' OR l.action LIKE '%Unauthorized%' OR l.action LIKE '%Error%')
                       AND DATE(l.timestamp) BETWEEN :start_date AND :end_date";

$securityStmt = $pdo->prepare($securityQuery);
$securityStmt->bindParam(':start_date', $start_date);
$securityStmt->bindParam(':end_date', $end_date);
$securityStmt->bindParam(':limit', $securityLimit, PDO::PARAM_INT);
$securityStmt->bindParam(':offset', $securityOffset, PDO::PARAM_INT);
$securityStmt->execute();
$securityEvents = $securityStmt->fetchAll(PDO::FETCH_ASSOC);

$totalSecurityStmt = $pdo->prepare($totalSecurityQuery);
$totalSecurityStmt->bindParam(':start_date', $start_date);
$totalSecurityStmt->bindParam(':end_date', $end_date);
$totalSecurityStmt->execute();
$totalSecurityEvents = $totalSecurityStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalSecurityPages = ceil($totalSecurityEvents / $securityLimit);

// FIXED: Data Modification Report - added full_name join, removed user_affected
$dataModPage = isset($_GET['data_page']) ? (int)$_GET['data_page'] : 1;
$dataModLimit = 10;
$dataModOffset = ($dataModPage - 1) * $dataModLimit;

$dataModQuery = "SELECT l.action, u.full_name, l.timestamp
                 FROM logs l
                 LEFT JOIN users u ON u.user_id = l.performed_by
                 WHERE l.action IN ('Updated Patient Information', 'Added Patient Assessment',
                                 'Added Diagnosis/Consultation Record', 'Dispensed Medicine to Patient',
                                 'Updated Patient Information', 'Added Referral', 'Cancelled Referral')
                 AND DATE(l.timestamp) BETWEEN :start_date AND :end_date
                 ORDER BY l.timestamp DESC
                 LIMIT :limit OFFSET :offset";

$totalDataModQuery = "SELECT COUNT(*) AS total
                      FROM logs l
                      WHERE l.action IN ('Updated Patient Information', 'Added Patient Assessment',
                                      'Added Diagnosis/Consultation Record', 'Dispensed Medicine to Patient',
                                      'Updated Patient Information', 'Added Referral', 'Cancelled Referral')
                      AND DATE(l.timestamp) BETWEEN :start_date AND :end_date";

$dataModStmt = $pdo->prepare($dataModQuery);
$dataModStmt->bindParam(':start_date', $start_date);
$dataModStmt->bindParam(':end_date', $end_date);
$dataModStmt->bindParam(':limit', $dataModLimit, PDO::PARAM_INT);
$dataModStmt->bindParam(':offset', $dataModOffset, PDO::PARAM_INT);
$dataModStmt->execute();
$dataModifications = $dataModStmt->fetchAll(PDO::FETCH_ASSOC);

$totalDataModStmt = $pdo->prepare($totalDataModQuery);
$totalDataModStmt->bindParam(':start_date', $start_date);
$totalDataModStmt->bindParam(':end_date', $end_date);
$totalDataModStmt->execute();
$totalDataModifications = $totalDataModStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalDataModPages = ceil($totalDataModifications / $dataModLimit);

// FIXED: Admin Activities Report - added full_name join, removed user_affected
$adminPage = isset($_GET['admin_page']) ? (int)$_GET['admin_page'] : 1;
$adminLimit = 10;
$adminOffset = ($adminPage - 1) * $adminLimit;

$adminActivitiesQuery = "SELECT l.action, u.full_name, l.timestamp
                         FROM logs l
                         LEFT JOIN users u ON u.user_id = l.performed_by
                         WHERE l.performed_by LIKE 'admin%' OR l.performed_by IN ('1', 'admin')
                         AND DATE(l.timestamp) BETWEEN :start_date AND :end_date
                         ORDER BY l.timestamp DESC
                         LIMIT :limit OFFSET :offset";

$totalAdminQuery = "SELECT COUNT(*) AS total
                    FROM logs l
                    WHERE l.performed_by LIKE 'admin%' OR l.performed_by IN ('1', 'admin')
                    AND DATE(l.timestamp) BETWEEN :start_date AND :end_date";
$adminActivitiesStmt = $pdo->prepare($adminActivitiesQuery);
$adminActivitiesStmt->bindParam(':start_date', $start_date);
$adminActivitiesStmt->bindParam(':end_date', $end_date);
$adminActivitiesStmt->bindParam(':limit', $adminLimit, PDO::PARAM_INT);
$adminActivitiesStmt->bindParam(':offset', $adminOffset, PDO::PARAM_INT);
$adminActivitiesStmt->execute();
$adminActivities = $adminActivitiesStmt->fetchAll(PDO::FETCH_ASSOC);

$totalAdminStmt = $pdo->prepare($totalAdminQuery);
$totalAdminStmt->bindParam(':start_date', $start_date);
$totalAdminStmt->bindParam(':end_date', $end_date);
$totalAdminStmt->execute();
$totalAdminActivities = $totalAdminStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalAdminPages = ceil($totalAdminActivities / $adminLimit);

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
        
        <main>
            <div class="head-title">
                <div class="left">
                  <h1>System Activity Logs Report</h1>
                  <ul class="breadcrumb">
                    <li><a href="#">System Activity Logs Report</a></li>
                    <li><i class="bx bx-chevron-right"></i></li>
                    <li><a class="active" href="#" onclick="history.back(); return false;">Go back</a></li>
                  </ul>
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
                    
                    
                    <div class="card">
                        <div class="card-header">
                            <h3>All Action</h3>
                        </div>
                        <table class="data-table" id="actions-table">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>Count</th>
                                    <th>Percentage</th>
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
                                        <td><?php echo round($percentage, 1); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&page=<?php echo $page - 1; ?>" class="page-link">Back</a>
                            <?php endif; ?>
                            <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                            <?php if ($page < $totalPages): ?>
                                <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&page=<?php echo $page + 1; ?>" class="page-link">Next</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </div>

                     <!-- Buttons Section -->
</div> 

<!-- FIXED: User Management Actions Report -->
<div class="card">
    <div class="card-header">
        <h3>User Management Actions</h3>
        <small>Track all user account modifications</small>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Action</th>
                <th>Performed By</th>
                <th>Count</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($userManagementActions as $action): ?>
                <tr>
                    <td><?php echo htmlspecialchars($action['action']); ?></td>
                    <td><?php echo htmlspecialchars($action['admin_name'] ?? 'N/A'); ?></td>
                    <td><?php echo number_format($action['count']); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($userManagementActions)): ?>
                <tr>
                    <td colspan="3" style="text-align: center;">No user management actions in selected period</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- FIXED: Security Events Report -->
<div class="card">
    <div class="card-header">
        <h3>Security Events</h3>
        <small>Failed attempts and security-related events</small>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Action</th>
                <th>User</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($securityEvents as $event): ?>
                <tr>
                    <td><?php echo htmlspecialchars($event['action']); ?></td>
                    <td><?php echo htmlspecialchars($event['full_name'] ?? 'N/A'); ?></td>
                    <td><?php echo date('M j, Y g:i A', strtotime($event['timestamp'])); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($securityEvents)): ?>
                <tr>
                    <td colspan="3" style="text-align: center;">No security events in selected period</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="pagination">
        <?php if ($securityPage > 1): ?>
            <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&page=<?php echo $page; ?>&security_page=<?php echo $securityPage - 1; ?>" class="page-link">Back</a>
        <?php endif; ?>
        <span>Page <?php echo $securityPage; ?> of <?php echo $totalSecurityPages; ?></span>
        <?php if ($securityPage < $totalSecurityPages): ?>
            <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&page=<?php echo $page; ?>&security_page=<?php echo $securityPage + 1; ?>" class="page-link">Next</a>
        <?php endif; ?>
    </div>
</div>

<!-- FIXED: Data Modifications Report -->
<div class="card">
    <div class="card-header">
        <h3>Data Modifications</h3>
        <small>Patient records and medical data changes</small>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Action</th>
                <th>Performed By</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dataModifications as $mod): ?>
                <tr>
                    <td><?php echo htmlspecialchars($mod['action']); ?></td>
                    <td><?php echo htmlspecialchars($mod['full_name'] ?? 'N/A'); ?></td>
                    <td><?php echo date('M j, Y g:i A', strtotime($mod['timestamp'])); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($dataModifications)): ?>
                <tr>
                    <td colspan="3" style="text-align: center;">No data modifications in selected period</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="pagination">
        <?php if ($dataModPage > 1): ?>
            <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&page=<?php echo $page; ?>&security_page=<?php echo $securityPage; ?>&data_page=<?php echo $dataModPage - 1; ?>" class="page-link">Back</a>
        <?php endif; ?>
        <span>Page <?php echo $dataModPage; ?> of <?php echo $totalDataModPages; ?></span>
        <?php if ($dataModPage < $totalDataModPages): ?>
            <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&page=<?php echo $page; ?>&security_page=<?php echo $securityPage; ?>&data_page=<?php echo $dataModPage + 1; ?>" class="page-link">Next</a>
        <?php endif; ?>
    </div>
</div>

<!-- FIXED: Admin Activities Report -->
<div class="card">
    <div class="card-header">
        <h3>Admin Activities</h3>
        <small>All actions performed by administrators</small>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Action</th>
                <th>Performed By</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($adminActivities as $activity): ?>
                <tr>
                    <td><?php echo htmlspecialchars($activity['action']); ?></td>
                    <td><?php echo htmlspecialchars($activity['full_name'] ?? 'N/A'); ?></td>
                    <td><?php echo date('M j, Y g:i A', strtotime($activity['timestamp'])); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($adminActivities)): ?>
                <tr>
                    <td colspan="3" style="text-align: center;">No admin activities in selected period</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="pagination">
        <?php if ($adminPage > 1): ?>
            <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&page=<?php echo $page; ?>&security_page=<?php echo $securityPage; ?>&data_page=<?php echo $dataModPage; ?>&admin_page=<?php echo $adminPage - 1; ?>" class="page-link">Back</a>
        <?php endif; ?>
        <span>Page <?php echo $adminPage; ?> of <?php echo $totalAdminPages; ?></span>
        <?php if ($adminPage < $totalAdminPages): ?>
            <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&page=<?php echo $page; ?>&security_page=<?php echo $securityPage; ?>&data_page=<?php echo $dataModPage; ?>&admin_page=<?php echo $adminPage + 1; ?>" class="page-link">Next</a>
        <?php endif; ?>
    </div>
</div>

        </main>
    </section>

<script>
    // Fetch and display user name
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

        // Initialize date pickers
        flatpickr("#start-date", {
        dateFormat: "Y-m-d"
    });

    flatpickr("#end-date", {
        dateFormat: "Y-m-d"
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
    
    const actionsCtx = document.getElementById('commonActionsChart');
    if (actionsCtx) {
        const commonActionsChart = new Chart(actionsCtx.getContext('2d'), {
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
    }


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
}
  
</script>

</body>
</html>