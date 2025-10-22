<?php
require 'config.php';

// Fetch daily active users for the last 30 days
// Fetch daily active users for the last 7 days
$activeUsersQuery = "SELECT DATE(timestamp) AS log_date, COUNT(DISTINCT user_id) AS active_users 
                     FROM logs 
                     WHERE action = 'login' AND timestamp >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                     GROUP BY log_date 
                     ORDER BY log_date ASC";


$activeUsersStmt = $pdo->prepare($activeUsersQuery);
$activeUsersStmt->execute();
$activeUsers = $activeUsersStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch most common actions
$commonActionsQuery = "SELECT action, COUNT(action) AS count 
                       FROM logs 
                       WHERE timestamp >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                       GROUP BY action 
                       ORDER BY count DESC 
                       LIMIT 5";

$commonActionsStmt = $pdo->prepare($commonActionsQuery);
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
    <link rel="stylesheet" href="../css/user_activity_dashboard.css">
    <link rel="stylesheet" href="../css/user.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

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
                <a href="admin_approval.php">
                    <i class="bx bxs-user"></i>
                    <span class="text">Approval & Logs</span>
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
                <a href="../../role.html" class="logout" onclick="return confirmLogout()">
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
                <h1>User Activity Report</h1>
                <p class="subtitle">Analyze user behavior and track activities in real-time</p>
                   
                </div>
            </div>


            
<body>

       
<main id="print-section">


    <section id="contents">
    <div class="metrics-container">
    <div class="metrics">
        <div class="metric">
        <div class="metric-label">Today's Active Users</div>
            <div class="metric-value"><?php echo $totalUsers; ?></div>
        </div>
        <div class="metric">
        <div class="metric-label">Average Daily Users</div>
            <div class="metric-value"><?php echo $avgUsers; ?></div>
        </div>
        <div class="metric">
        <div class="metric-label">Total Actions </div>
            <div class="metric-value"><?php echo number_format($totalActions); ?></div>
            <div class="metric-subheader">(7 Days)</div> 
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header">
        <h3>Most Common Actions</h3>
    </div>

    <table class="data-table">
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
        <div class="progress-label"><?php echo round($percentage); ?>%</div>
    </div>
</td>


                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

        </section>
    </main>

        <div class="report-actions">
    <button onclick="printReport()" class="action-button">
        <i class="fas fa-print"></i> Print Report
    </button>
</div>



    
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
    </script>

    <script>

const activeUsersData = <?php echo json_encode($activeUsers); ?>;

const labels = activeUsersData.map(entry => entry.log_date);
const data = activeUsersData.map(entry => entry.active_users);

const ctx = document.getElementById('activeUsersChart').getContext('2d');
const activeUsersChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Daily Active Users',
            data: data,
            fill: false,
            borderColor: 'rgba(75, 192, 192, 1)',
            tension: 0.3,
            pointBackgroundColor: 'rgba(75, 192, 192, 1)',
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: (context) => `${context.parsed.y} users`
                }
            }
        },
        scales: {
            x: {
                title: { display: true, text: 'Date' }
            },
            y: {
                beginAtZero: true,
                title: { display: true, text: 'Users' }
            }
        }
    }
});
function printReport() {
    const content = document.getElementById('print-section').innerHTML;

    const printWindow = window.open('', '', 'width=800,height=600');

    const companyName = "Daet Rural Health Unit";
    const reportTitle = "Reports";
    const subTitle = "User Activity Report";
    const now = new Date();

// Format options
const monthYearFormatter = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' });
const fullDateFormatter = new Intl.DateTimeFormat('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
const shortMonthFormatter = new Intl.DateTimeFormat('en-US', { month: 'short' });

// Get current month and year
const reportMonth = monthYearFormatter.format(now); // e.g. "April 2025"
const generatedDate = fullDateFormatter.format(now); // e.g. "April 13, 2025"

// Calculate start and end of the month
const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);

const periodStart = `${shortMonthFormatter.format(firstDay)} 1`;
const periodEnd = `${shortMonthFormatter.format(lastDay)} ${lastDay.getDate()}, ${lastDay.getFullYear()}`;
const reportPeriod = `${periodStart} - ${periodEnd}`; // e.g. "Apr 1 - Apr 30, 2025"

    printWindow.document.open();
    printWindow.document.write(`
        <html>
        <head>
            <title></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 40px;
                }
                .header {
                    text-align: center;
                    border-bottom: 2px solid #000;
                    margin-bottom: 20px;
                    padding-bottom: 10px;
                }
                .header h1, .header h2 {
                    margin: 0;
                }
                .sub-header {
                    text-align: center;
                    margin-bottom: 30px;
                }
                .sub-header h3 {
                    margin: 5px 0;
                }
                .meta {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 20px;
                    font-size: 14px;
                }
                .report-content {
                    margin-top: 20px;
                }
                .box-info {
                    display: flex;
                    gap: 20px;
                    margin-bottom: 20px;
                }
                .box-info li {
                    list-style: none;
                    padding: 10px;
                    border: 1px solid #ccc;
                    border-radius: 10px;
                    flex: 1;
                    text-align: center;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                table, th, td {
                    border: 1px solid black;
                }
                th, td {
                    padding: 10px;
                    text-align: left;
                }
            </style>
        </head>
        <body onload="window.print(); window.close();">
            <div class="header">
                <h1>${companyName}</h1>
                <h2>${reportTitle}</h2>
            </div>

            <div class="sub-header">
                <h3>${subTitle}</h3>
                <h3>${reportMonth}</h3>
            </div>

            <div class="meta">
                <div><strong>Generated:</strong> ${generatedDate}</div>
                <div><strong>Period:</strong> ${reportPeriod}</div>
            </div>

            <div class="report-content">
                ${content}
            </div>
        </body>
        </html>
    `);

    printWindow.document.close();
}

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
</script>
</body>
</html>