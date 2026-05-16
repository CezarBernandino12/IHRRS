<?php
require_once __DIR__ . '/session_config.php';
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    header("Location: ../../role");
    exit();
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function calculateAverageProcessingTime($pdo) {
    return "N/A";
}

function calculateApprovalRates($pdo) {
    $totalQuery = "SELECT COUNT(*) as total FROM users WHERE status IN ('approved', 'rejected')";
    $totalStmt = $pdo->query($totalQuery);
    $totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
    $total = (int)($totalResult['total'] ?? 0);

    $approvedQuery = "SELECT COUNT(*) as approved FROM users WHERE status = 'approved'";
    $approvedStmt = $pdo->query($approvedQuery);
    $approvedResult = $approvedStmt->fetch(PDO::FETCH_ASSOC);
    $approved = (int)($approvedResult['approved'] ?? 0);

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

function getMonthlyApprovalData($pdo, $months = 6) {
    try {
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

        $labels = [];
        $approvedData = [];
        $rejectedData = [];

        foreach ($results as $row) {
            $dateObj = DateTime::createFromFormat('Y-m', $row['month']);
            if ($dateObj) {
                $labels[] = $dateObj->format('M Y');
                $approvedData[] = (int)($row['approved'] ?? 0);
                $rejectedData[] = (int)($row['rejected'] ?? 0);
            }
        }

        return [
            'labels' => $labels,
            'approved' => $approvedData,
            'rejected' => $rejectedData,
            'avg_time' => array_fill(0, count($labels), null)
        ];
    } catch (PDOException $e) {
        error_log("Error getting monthly approval data: " . $e->getMessage());
        return [
            'labels' => [],
            'approved' => [],
            'rejected' => [],
            'avg_time' => []
        ];
    }
}

try {
    $averageProcessingTime = calculateAverageProcessingTime($pdo);
    $approvalRates = calculateApprovalRates($pdo);
    $trendData = getMonthlyApprovalData($pdo);
    $trendDataJson = json_encode($trendData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

    $stmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE status = 'unread'");
    $unreadCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'");
    $pendingUsers = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE registration_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $newUsers30Days = (int)$stmt->fetchColumn();

    $recentQuery = "SELECT user_id, full_name, registration_date, status, barangay 
                    FROM users
                    WHERE status IN ('approved', 'rejected', 'pending')
                    ORDER BY registration_date DESC
                    LIMIT 10";
    $recentStmt = $pdo->query($recentQuery);
    $recentUsers = $recentStmt ? $recentStmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());

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
    $pendingUsers = 0;
    $newUsers30Days = 0;
    $recentUsers = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../img/logo.png">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/logout.css">
    <link rel="stylesheet" href="../css/metrics.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Approval Process Metrics</title>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<section id="sidebar">
    <a href="#" class="sidebar-brand">
        <img src="../../img/logo.png" alt="Admin Logo" class="brand-logo">
        <div class="brand-text">
            <span class="brand-name">IHRRS</span>
        </div>
    </a>

    <div class="sidebar-scroll">
        <div class="sidebar-section-label">Main Menu</div>
        <ul class="side-menu top">
            <li>
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
                    <span class="nav-label">User Management</span>
                </a>
            </li>
            <li class="active">
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

<section id="content">
    <nav>
        <button class="nav-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="bx bx-menu"></i>
        </button>

        <div class="nav-search">
            <input type="search" id="patientSearch" placeholder="Search recent users..." name="search" autocomplete="off">
            <button type="button" id="searchButton" aria-label="Search">
                <i class="bx bx-search"></i>
            </button>
            <div id="resultDropdown" class="dropdown-content"></div>
        </div>
    </nav>

    <main>
        <div class="metrics-page">
            <section class="page-hero">
                <div class="hero-copy">
                    <div class="eyebrow">
                        <i class="bx bx-bar-chart-alt-2"></i>
                        Admin Reports
                    </div>
                    <h1>Approval Process Metrics</h1>
                    <p>Monitor approval performance, processed registrations, and recent user account activity in one clean dashboard.</p>
                </div>

                <div class="hero-actions">
                    <a href="admin_approval" class="hero-btn secondary">
                        <i class="bx bx-user-check"></i>
                        Approval Logs
                    </a>
                    <a href="../reports" class="hero-btn primary">
                        <i class="bx bx-arrow-back"></i>
                        Back to Reports
                    </a>
                </div>
            </section>

            <section class="metrics-grid" aria-label="Approval metric summary">
                <article class="metric-card approved-card">
                    <span class="metric-icon"><i class="bx bx-check-shield"></i></span>
                    <div>
                        <p>Total Approved Users</p>
                        <h3><?php echo number_format($approvalRates['total_approved']); ?></h3>
                        <small>Users currently marked as approved.</small>
                    </div>
                </article>

                <article class="metric-card rate-card">
                    <span class="metric-icon"><i class="bx bx-trending-up"></i></span>
                    <div>
                        <p>Approval Rate</p>
                        <h3><?php echo e($approvalRates['approval_rate']); ?>%</h3>
                        <div class="mini-progress" aria-hidden="true">
                            <span style="width: <?php echo min(100, max(0, $approvalRates['approval_rate'])); ?>%"></span>
                        </div>
                    </div>
                </article>

                <article class="metric-card rejected-card">
                    <span class="metric-icon"><i class="bx bx-x-circle"></i></span>
                    <div>
                        <p>Rejection Rate</p>
                        <h3><?php echo e($approvalRates['rejection_rate']); ?>%</h3>
                        <small><?php echo number_format($approvalRates['total_rejected']); ?> rejected users.</small>
                    </div>
                </article>

                <article class="metric-card pending-card">
                    <span class="metric-icon"><i class="bx bx-time-five"></i></span>
                    <div>
                        <p>Pending Reviews</p>
                        <h3><?php echo number_format($pendingUsers); ?></h3>
                        <small><?php echo number_format($newUsers30Days); ?> registrations in the last 30 days.</small>
                    </div>
                </article>
            </section>

            <section class="insight-grid">
                <article class="chart-card searchable-card">
                    <div class="section-heading">
                        <div>
                            <span class="section-kicker">Six-month overview</span>
                            <h2>Registration & Approval Trends</h2>
                            <p>This chart uses registration date as the time basis and groups users by current approval status.</p>
                        </div>
                        <span class="section-icon"><i class="bx bx-line-chart"></i></span>
                    </div>

                    <div class="chart-shell">
                        <canvas id="approvalTrendsChart"></canvas>
                    </div>

                    <div class="note-box">
                        <i class="bx bx-info-circle"></i>
                        <span>Average approval processing time is unavailable because approval timestamps are not stored yet.</span>
                    </div>
                </article>

                <aside class="snapshot-card searchable-card">
                    <div class="section-heading compact">
                        <div>
                            <span class="section-kicker">Processing snapshot</span>
                            <h2>Current Totals</h2>
                        </div>
                        <span class="section-icon"><i class="bx bx-pie-chart-alt-2"></i></span>
                    </div>

                    <div class="snapshot-list">
                        <div class="snapshot-item">
                            <span>Processed Users</span>
                            <strong><?php echo number_format($approvalRates['total_processed']); ?></strong>
                        </div>
                        <div class="snapshot-item">
                            <span>Approved Users</span>
                            <strong><?php echo number_format($approvalRates['total_approved']); ?></strong>
                        </div>
                        <div class="snapshot-item">
                            <span>Rejected Users</span>
                            <strong><?php echo number_format($approvalRates['total_rejected']); ?></strong>
                        </div>
                        <div class="snapshot-item">
                            <span>Unread Notifications</span>
                            <strong><?php echo number_format($unreadCount); ?></strong>
                        </div>
                        <div class="snapshot-item muted">
                            <span>Avg. Processing Time</span>
                            <strong><?php echo e($averageProcessingTime); ?></strong>
                        </div>
                    </div>
                </aside>
            </section>

            <section class="table-card searchable-card">
                <div class="section-heading">
                    <div>
                        <span class="section-kicker">Latest records</span>
                        <h2>Recently Registered Users</h2>
                        <p>Latest approved, rejected, and pending user registrations.</p>
                    </div>
                    <span class="section-icon"><i class="bx bx-user-plus"></i></span>
                </div>

                <div class="table-wrap">
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
                            <?php if (!empty($recentUsers)): ?>
                                <?php foreach ($recentUsers as $row):
                                    $status = strtolower((string)($row['status'] ?? 'pending'));
                                    $statusClass = in_array($status, ['approved', 'rejected', 'pending'], true) ? $status : 'pending';
                                    $registrationDate = !empty($row['registration_date']) ? date('M d, Y h:i A', strtotime($row['registration_date'])) : 'N/A';
                                ?>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <strong><?php echo e($row['full_name'] ?? 'Unknown'); ?></strong>
                                            <span>User ID: <?php echo e($row['user_id'] ?? 'N/A'); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo e($registrationDate); ?></td>
                                    <td><?php echo e($row['barangay'] ?? 'Not specified'); ?></td>
                                    <td><span class="status-badge <?php echo e($statusClass); ?>"><?php echo e(ucfirst($status)); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="empty-state">
                                        <i class="bx bx-folder-open"></i>
                                        <span>No recent user data available.</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</section>

<div id="logoutModal" class="logout-modal">
    <div class="logout-modal-content">
        <div class="logout-modal-header">
            <h3>Confirm Logout</h3>
        </div>
        <div class="logout-modal-body">
            <p>Are you sure you want to logout?</p>
        </div>
        <div class="logout-modal-footer">
            <button type="button" onclick="closeLogoutModal()" class="logout-cancel-btn">Cancel</button>
            <button type="button" onclick="proceedLogout()" class="logout-confirm-btn">Yes, Logout</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    setupApprovalChart();
    setupSidebar();
    setupLocalSearch();
    setupSidebarUserName();
});

function setupApprovalChart() {
    const trendData = <?php echo $trendDataJson; ?>;
    const canvas = document.getElementById('approvalTrendsChart');

    if (!canvas) {
        return;
    }

    if (!trendData.labels || trendData.labels.length === 0) {
        const shell = canvas.closest('.chart-shell');
        if (shell) {
            shell.innerHTML = '<div class="chart-empty"><i class="bx bx-bar-chart-alt-2"></i><p>No trend data available for the selected time period.</p></div>';
        }
        return;
    }

    const ctx = canvas.getContext('2d');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: trendData.labels,
            datasets: [
                {
                    label: 'Approved',
                    data: trendData.approved,
                    backgroundColor: 'rgba(21, 145, 90, 0.78)',
                    borderColor: 'rgba(21, 145, 90, 1)',
                    borderWidth: 1,
                    borderRadius: 10,
                    barPercentage: 0.72,
                    categoryPercentage: 0.72
                },
                {
                    label: 'Rejected',
                    data: trendData.rejected,
                    backgroundColor: 'rgba(220, 38, 38, 0.72)',
                    borderColor: 'rgba(220, 38, 38, 1)',
                    borderWidth: 1,
                    borderRadius: 10,
                    barPercentage: 0.72,
                    categoryPercentage: 0.72
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    },
                    grid: {
                        color: 'rgba(15, 23, 42, 0.07)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8,
                        boxHeight: 8
                    }
                },
                tooltip: {
                    backgroundColor: '#061b3a',
                    titleColor: '#ffffff',
                    bodyColor: '#e2e8f0',
                    padding: 12,
                    cornerRadius: 12
                }
            }
        }
    });
}

function setupSidebar() {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');
    const MOBILE_BP = 768;

    if (!sidebar || !toggle || !overlay) {
        return;
    }

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
}

function setupLocalSearch() {
    const searchInput = document.getElementById('patientSearch');
    const searchButton = document.getElementById('searchButton');

    function filterPageContent() {
        const term = (searchInput?.value || '').toLowerCase().trim();
        const rows = document.querySelectorAll('.approval-table tbody tr');

        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    }

    if (searchInput && searchButton) {
        searchInput.addEventListener('input', filterPageContent);
        searchInput.addEventListener('keypress', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                filterPageContent();
            }
        });
        searchButton.addEventListener('click', filterPageContent);
    }
}

function setupSidebarUserName() {
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
}

function confirmLogout() {
    const modal = document.getElementById('logoutModal');
    if (modal) {
        modal.style.display = 'grid';
    }
    return false;
}

function closeLogoutModal() {
    const modal = document.getElementById('logoutModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function proceedLogout() {
    window.location.href = 'logout';
}

window.addEventListener('click', function(event) {
    const logoutModal = document.getElementById('logoutModal');
    if (event.target === logoutModal) {
        closeLogoutModal();
    }
});
</script>
</body>
</html>
