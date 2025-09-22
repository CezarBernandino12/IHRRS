<?php
session_start();
require_once 'config.php';

// Function to calculate average processing time in hours
// Since we don't have an approval timestamp, we'll use a placeholder value or remove this metric
function calculateAverageProcessingTime($pdo) {
    // Since we don't have approval_date, we'll return a placeholder
    // In reality, you would need to track when status changes in your database
    return "N/A"; // Not available without timestamp data
}

// Function to calculate approval rates
function calculateApprovalRates($pdo) {
    // Get total count of processed users (approved or rejected)
    $totalQuery = "SELECT COUNT(*) as total FROM users WHERE status IN ('approved', 'rejected')";
    $totalStmt = $pdo->query($totalQuery);
    $totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
    $total = $totalResult['total'];
    
    // Get count of approved users
    $approvedQuery = "SELECT COUNT(*) as approved FROM users WHERE status = 'approved'";
    $approvedStmt = $pdo->query($approvedQuery);
    $approvedResult = $approvedStmt->fetch(PDO::FETCH_ASSOC);
    $approved = $approvedResult['approved'];
    
    // Calculate rates
    $approvalRate = $total > 0 ? round(($approved / $total) * 100, 1) : 0;
    $rejectionRate = $total > 0 ? round(100 - $approvalRate, 1) : 0;
    
    return [
        'approval_rate' => $approvalRate,
        'rejection_rate' => $rejectionRate,
        'total_processed' => $total,
        'total_approved' => $approved,
        'total_rejected' => $total - $approved
    ];
}

// Function to get approval data for trend chart (monthly)
function getMonthlyApprovalData($pdo, $months = 6) {
    try {
        // Since we don't have approval_date, we'll use registration_date as our time basis
        // This will show registration date trends, not approval date trends
        $query = "SELECT 
                  DATE_FORMAT(registration_date, '%Y-%m') as month,
                  COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
                  COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected
                FROM users
                WHERE registration_date IS NOT NULL
                  AND status IN ('approved', 'rejected')
                  AND registration_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(registration_date, '%Y-%m')
                ORDER BY month ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$months]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Prepare data for chart
        $labels = [];
        $approvedData = [];
        $rejectedData = [];
        
        foreach ($results as $row) {
            // Format month for display (e.g., "2023-05" to "May 2023")
            $dateObj = DateTime::createFromFormat('Y-m', $row['month']);
            if ($dateObj) {
                $formattedMonth = $dateObj->format('M Y');
                
                $labels[] = $formattedMonth;
                $approvedData[] = $row['approved'] ?? 0;
                $rejectedData[] = $row['rejected'] ?? 0;
            }
        }
        
        return [
            'labels' => $labels,
            'approved' => $approvedData,
            'rejected' => $rejectedData,
            'avg_time' => array_fill(0, count($labels), null) // We don't have this data
        ];
    } catch (PDOException $e) {
        // Graceful error handling
        error_log("Error getting monthly approval data: " . $e->getMessage());
        return [
            'labels' => [],
            'approved' => [],
            'rejected' => [],
            'avg_time' => []
        ];
    }
}

// Get data for the page - with error handling
try {
    $averageProcessingTime = calculateAverageProcessingTime($pdo);
    $approvalRates = calculateApprovalRates($pdo);
    $trendData = getMonthlyApprovalData($pdo);
    
    // Convert trend data to JSON for JavaScript charts
    $trendDataJson = json_encode($trendData);
    
    // Fetch the count of unread notifications
    $stmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE status = 'unread'");
    $unreadCount = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Log the error
    error_log("Database error: " . $e->getMessage());
    
    // Set default values
    $averageProcessingTime = "N/A";
    $approvalRates = [
        'approval_rate' => 0,
        'rejection_rate' => 0,
        'total_processed' => 0,
        'total_approved' => 0,
        'total_rejected' => 0
    ];
    $trendData = [
        'labels' => [],
        'approved' => [],
        'rejected' => [],
        'avg_time' => []
    ];
    $trendDataJson = json_encode($trendData);
    $unreadCount = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../img/logo.png">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/approval.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Approval Process Metrics</title>
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

            <a href="notif.php" class="notification">
                <i class="bx bxs-bell"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="badge"><?php echo $unreadCount; ?></span>
                <?php endif; ?>
            </a>

            <a href="#" class="profile">
            <img src="../../img/admin.png">
            </a>
        </nav>

        <main>
            <div class="head-title">
                <div class="left">
                    <h1>Approval Process Metrics</h1>
                    </ul>
                </div>
            </div>

            <!-- Key Metrics Overview -->
            <div class="metrics-container">
                <div class="metric-card">
                    <h3>Total Approved Users</h3>
                    <div class="metric-value"><?php echo $approvalRates['total_approved']; ?></div>
                    <p>Users with 'approved' status</p>
                </div>
                <div class="metric-card">
                    <h3>Approval Rate</h3>
                    <div class="metric-value"><?php echo $approvalRates['approval_rate']; ?>%</div>
                    <div class="progress-bar">
                        <div class="progress-bar-fill" style="width: <?php echo $approvalRates['approval_rate']; ?>%"></div>
                    </div>
                    <div class="progress-label">
                        <span>0%</span>
                        <span>100%</span>
                    </div>
                </div>
                <div class="metric-card">
                    <h3>Total Processed</h3>
                    <div class="metric-value"><?php echo $approvalRates['total_processed']; ?></div>
                    <p><?php echo $approvalRates['total_approved']; ?> approved, <?php echo $approvalRates['total_rejected']; ?> rejected</p>
                </div>
            </div>

            <!-- Trends Chart -->
            <div class="chart-container">
                <h3>Registration & Approval Trends</h3>
                <div class="note">
                    <strong>Note:</strong> This chart shows users by registration date, grouped by their current status (approved or rejected).
                </div>
                <canvas id="approvalTrendsChart"></canvas>
            </div>

            <!-- Recent Users -->
            <div class="chart-container">
                <h3>Recently Registered Users</h3>
                <?php
                try {
                    $recentQuery = "SELECT user_id, full_name, registration_date, status, barangay 
                                    FROM users
                                    WHERE status IN ('approved', 'rejected', 'pending')
                                    ORDER BY registration_date DESC
                                    LIMIT 10";
                    
                    $recentStmt = $pdo->query($recentQuery);
                    $hasRecentData = ($recentStmt && $recentStmt->rowCount() > 0);
                } catch (PDOException $e) {
                    error_log("Error fetching recent users: " . $e->getMessage());
                    $hasRecentData = false;
                }
                ?>
                
                <?php if ($hasRecentData): ?>
                <table class="approval-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Registration Date</th>
                            <th>Barangay</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $recentStmt->fetch(PDO::FETCH_ASSOC)):
                            $statusClass = '';
                            if ($row['status'] === 'approved') {
                                $statusClass = 'approved';
                            } elseif ($row['status'] === 'rejected') {
                                $statusClass = 'rejected';
                            } elseif ($row['status'] === 'pending') {
                                $statusClass = 'pending';
                            }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['full_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo date('M d, Y h:i A', strtotime($row['registration_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['barangay'] ?? 'Not specified'); ?></td>
                            <td><span class="status <?php echo $statusClass; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No recent user data available.</p>
                <?php endif; ?>
            </div>
        </main>
    </section>

    <script>
        // Chart for Approval Trends
        document.addEventListener('DOMContentLoaded', function() {
            const trendData = <?php echo $trendDataJson; ?>;
            
            // Check if we have data to display
            if (trendData.labels.length === 0) {
                document.getElementById('approvalTrendsChart').parentNode.innerHTML = 
                    '<h3>Registration & Approval Trends</h3>' +
                    '<div class="note"><strong>Note:</strong> This chart would show users by registration date, grouped by status.</div>' +
                    '<p>No trend data available for the selected time period.</p>';
                return;
            }
            
            const ctx = document.getElementById('approvalTrendsChart').getContext('2d');
            const approvalTrendsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: trendData.labels,
                    datasets: [
                        {
                            label: 'Approved',
                            data: trendData.approved,
                            backgroundColor: 'rgba(60, 145, 230, 0.7)',
                            borderColor: 'rgba(60, 145, 230, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Rejected',
                            data: trendData.rejected,
                            backgroundColor: 'rgba(255, 99, 132, 0.7)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Users'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Monthly Registration by Current Status'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                }
            });
        });

        function confirmLogout() {
            return confirm("Are you sure you want to logout?");
        }
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