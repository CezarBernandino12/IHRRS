<?php
require '../../php/db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../role");
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT full_name, rhu FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
$rhu      = $user ? $user['rhu']       : 'N/A';
$username = $user ? $user['full_name'] : 'N/A';

/* ---------- Filters ---------- */
$from_date = $_GET['from_date'] ?? '';
$to_date   = $_GET['to_date']   ?? '';
$status    = $_GET['status']    ?? '';
$barangay  = $_GET['barangay']  ?? '';

/* ---------- Query ---------- */
$sql = "
    SELECT
        u.barangay,
        SUM(CASE WHEN r.referral_status IN ('Completed','Uncompleted','Pending') THEN 1 ELSE 0 END) AS total_referrals,
        SUM(CASE WHEN r.referral_status = 'Completed'   THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN r.referral_status = 'Uncompleted' THEN 1 ELSE 0 END) AS uncompleted,
        SUM(CASE WHEN r.referral_status = 'Pending'     THEN 1 ELSE 0 END) AS pending
    FROM referrals r
    LEFT JOIN users u ON r.referred_by = u.user_id
    WHERE u.rhu = :rhu
      AND u.barangay IS NOT NULL
      AND u.barangay != ''
";
$params = ['rhu' => $rhu];

if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND DATE(r.referral_date) BETWEEN :from_date AND :to_date";
    $params['from_date'] = $from_date;
    $params['to_date']   = $to_date;
}
if (!empty($status)) {
    $sql .= " AND r.referral_status = :status";
    $params['status'] = $status;
}
if (!empty($barangay)) {
    $sql .= " AND u.barangay = :barangay";
    $params['barangay'] = $barangay;
}
$sql .= " GROUP BY u.barangay ORDER BY u.barangay";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Totals ---------- */
$total_received   = 0;
$total_completed  = 0;
$total_uncompleted = 0;
$total_pending    = 0;
foreach ($rows as $row) {
    $total_received    += $row['total_referrals'];
    $total_completed   += $row['completed'];
    $total_uncompleted += $row['uncompleted'];
    $total_pending     += $row['pending'];
}

/* ---------- Chart data ---------- */
$referral_counts = [
    'Pending'     => $total_pending,
    'Completed'   => $total_completed,
    'Uncompleted' => $total_uncompleted,
];
$barangay_labels    = array_column($rows, 'barangay');
$barangay_referrals = array_column($rows, 'total_referrals');

/* ---------- Audit log ---------- */
$stmt_log = $pdo->prepare("INSERT INTO logs (user_id, action, performed_by) VALUES (:user_id, :action, :performed_by)");
$stmt_log->execute([
    ':user_id'      => $_SESSION['user_id'],
    ':action'       => "Generated RHU Referral Summary Report",
    ':performed_by' => $_SESSION['user_id'],
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../img/logo.png">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/reportsDesign.css">
    <link rel="stylesheet" href="../css/logout.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <title>Referral Summary Report</title>

    <style>
    /* ── Layout fix for this report page ── */
    .filter-form {
        position: relative;
    }

    .referral-summary-charts-grid {
        display: grid;
        grid-template-columns: minmax(260px, 420px) minmax(360px, 1fr);
        gap: 24px;
        align-items: stretch;
        margin: 24px 0 30px;
        transition: grid-template-columns .35s ease, gap .35s ease;
    }

    .referral-summary-charts-grid.single-chart {
        grid-template-columns: minmax(320px, 760px);
        justify-content: center;
    }

    .summary-chart-card {
        width: 100%;
        max-width: none !important;
        margin: 0 !important;
        padding: 20px 20px 16px;
        text-align: center;
        min-height: 350px;
        border: 1px solid var(--border-soft);
        border-radius: var(--r-lg);
        background: var(--white);
        box-shadow: var(--shadow-xs);
        opacity: 1;
        transform: translateY(0) scale(1);
        transition: opacity .28s ease, transform .28s ease, box-shadow .18s ease;
        overflow: hidden;
    }

    .summary-chart-card:hover {
        box-shadow: var(--shadow-sm);
    }

    .summary-chart-card.is-hidden {
        display: none !important;
    }

    .summary-chart-card canvas {
        display: block;
        width: 100% !important;
        height: 280px !important;
        max-height: 280px;
    }

    .summary-chart-card.bar-chart canvas {
        height: 310px !important;
        max-height: 310px;
    }

    .summary-chart-card h3 {
        font-size: 15px;
        font-weight: 700;
        color: var(--navy);
        margin-bottom: 14px;
    }

    @keyframes softChartEntrance {
        from { opacity: 0; transform: translateY(14px) scale(.985); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .summary-chart-card.chart-entering {
        animation: softChartEntrance .36s ease both;
    }

    @media (max-width: 980px) {
        .referral-summary-charts-grid,
        .referral-summary-charts-grid.single-chart {
            grid-template-columns: 1fr;
        }

        .summary-chart-card {
            max-width: 720px !important;
            margin: 0 auto !important;
        }
    }

    @media (max-width: 480px) {
        .summary-chart-card { min-height: 310px; padding: 16px 14px 12px; }
        .summary-chart-card canvas,
        .summary-chart-card.bar-chart canvas { height: 240px !important; max-height: 240px; }
    }
    

    @media print {
        .chart-controls-panel,
        .selected-filters,
        .summary-chart-card,
        .referral-summary-charts-grid,
        canvas {
            display: none !important;
        }
        .title { display: block !important; text-align: center; }
        .ph-line-4 { text-align: center; }
        .print-sub { text-align: center; }
        body, table, th, td, #generated_by, .sig-label, .sig-name, .sig-title,
        .ph-line-4, .print-sub { font-family: Arial, sans-serif !important; }
        thead tr { background: #fff !important; }
        thead th { background: #fff !important; color: #000 !important; }
    }
</style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ═══ SIDEBAR ═══ -->
<section id="sidebar">
    <a href="#" class="sidebar-brand">
        <img src="../../img/logo.png" alt="RHU Logo" class="brand-logo">
        <div class="brand-text">
            <span class="brand-name">Hello Nurse</span>
        </div>
    </a>

    <div class="sidebar-scroll">
        <p class="sidebar-section-label">Main Menu</p>
        <ul class="side-menu top">
            <li><a href="../dashboard" data-tooltip="Dashboard"><i class="bx bxs-dashboard nav-icon"></i><span class="nav-label">Dashboard</span></a></li>
            <li><a href="../ITR" data-tooltip="Add New ITR"><i class="bx bxs-notepad nav-icon"></i><span class="nav-label">Add New ITR</span></a></li>
            <li><a href="../pending" id="updateReferrals" data-tooltip="Pending Referrals"><i class="bx bxs-user nav-icon"></i><span class="nav-label">Pending Referrals</span></a></li>
            <li><a href="../followUpConsultations" data-tooltip="Follow-Up Visits"><i class="bx bxs-user nav-icon"></i><span class="nav-label">Follow-Up Visits</span></a></li>
            <li><a href="../searchPatient" data-tooltip="Patient Records"><i class="bx bxs-search nav-icon"></i><span class="nav-label">Patient Records</span></a></li>
            <li><a href="../history" data-tooltip="Referral History"><i class="bx bx-history nav-icon"></i><span class="nav-label">Referral History</span></a></li>
            <li class="active"><a href="../reports" data-tooltip="Reports"><i class="bx bx-notepad nav-icon"></i><span class="nav-label">Reports</span></a></li>
        </ul>

        <div class="sidebar-divider"></div>

        <ul class="side-menu">
            <li><a href="#" class="logout" data-tooltip="Logout" onclick="return confirmLogout()"><i class="bx bxs-log-out-circle nav-icon"></i><span class="nav-label">Logout</span></a></li>
        </ul>
    </div>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <img src="../../img/nurse.png" alt="Nurse User">
            <div class="sidebar-user-info">
                <div class="user-name" id="sidebarUserName">Nurse User</div>
                <div class="user-role">RHU Nurse</div>
            </div>
        </div>
    </div>
</section>

<!-- ═══ MAIN CONTENT ═══ -->
<section id="content">
    <nav>
        <button class="nav-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="bx bx-menu"></i>
        </button>
        <div class="nav-search" style="position:relative;">
            <input type="search" id="patientSearch" placeholder="Enter patient name..." autocomplete="off">
            <button type="button" id="searchButton" aria-label="Search"><i class="bx bx-search"></i></button>
            <div id="resultDropdown" class="dropdown-content"></div>
        </div>
        <span id="userGreeting" style="display:none;"></span>
    </nav>

    <main>
        <div class="head-title">
            <div class="left">
                <h1>Referral Summary Report</h1>
            </div>
        </div>

        <br>

        <div class="history-container">

            <!-- ─── Filter Form Card ─── -->
            <div class="filter-form">

                <h2>Referral Intake Summary Report — <?php echo htmlspecialchars($rhu); ?></h2>

                <!-- Action Buttons -->
                <div class="form-submit">
                    <button type="button" class="btn-export" id="openFilterModal">
                        <i class="bx bx-filter-alt"></i> Select Filters
                    </button>
                    <button type="button" class="btn-export" onclick="exportTableToExcel('reportTable')">
                        <i class="bx bx-spreadsheet"></i> Export to Excel
                    </button>
                    <button type="button" class="btn-print" onclick="printDiv()">
                        <i class="bx bx-printer"></i> Print Report
                    </button>
                </div>

                <!-- Active Filter Tags -->
                <div class="selected-filters" style="margin-top:20px;">
                    <h3><i class="bx bx-filter-alt"></i> Active Filters</h3>
                    <div id="filterTags" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;">
                        <?php
                        function renderTag($label, $param, $value) {
                            $display = htmlspecialchars($label . ': ' . $value);
                            $url = $_GET;
                            unset($url[$param]);
                            $query = http_build_query($url);
                            echo '<span class="filter-tag">';
                            echo $display;
                            echo ' <a href="?' . $query . '" title="Remove filter">&times;</a>';
                            echo '</span>';
                        }

                        if ($from_date) renderTag('From',     'from_date', $from_date);
                        if ($to_date)   renderTag('To',       'to_date',   $to_date);
                        if ($status)    renderTag('Status',   'status',    $status);
                        if ($barangay)  renderTag('Barangay', 'barangay',  $barangay);

                        if (!$from_date && !$to_date && !$status && !$barangay) {
                            echo '<span style="color:var(--grey-500);font-size:13px;">All records — no filters applied</span>';
                        }
                        ?>
                    </div>
                </div>

                <!-- ─── Filter Modal ─── -->
                <div id="filterModal" class="modal" style="display:none;">
                    <div class="modal-content" style="max-width:520px;">
                        <div class="modal-header">
                            <h3><i class="bx bx-filter-alt" style="margin-right:8px;color:var(--blue);"></i>Apply Filters</h3>
                        </div>
                        <form method="GET" id="filterForm">
                            <div class="modal-body">
                                <div class="form-row">
                                    <div class="form-item">
                                        <label for="from_date">From Date</label>
                                        <input type="date" name="from_date" id="from_date" class="form-control"
                                               value="<?= $from_date ? htmlspecialchars($from_date) : '' ?>">
                                    </div>
                                    <div class="form-item">
                                        <label for="to_date">To Date</label>
                                        <input type="date" name="to_date" id="to_date" class="form-control"
                                               value="<?= $to_date ? htmlspecialchars($to_date) : '' ?>">
                                    </div>
                                    <div class="form-item">
                                        <label for="status">Status</label>
                                        <select name="status" id="status" class="form-control">
                                            <option value="" <?= $status=='' ? 'selected':'' ?>>All</option>
                                            <option value="Pending"     <?= $status=='Pending'     ? 'selected':'' ?>>Pending</option>
                                            <option value="Completed"   <?= $status=='Completed'   ? 'selected':'' ?>>Completed</option>
                                            <option value="Uncompleted" <?= $status=='Uncompleted' ? 'selected':'' ?>>Uncompleted</option>
                                            <option value="Cancelled"   <?= $status=='Cancelled'   ? 'selected':'' ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="form-item">
                                        <label for="barangay">Barangay</label>
                                        <select name="barangay" id="barangay" class="form-control">
                                            <option value="">All</option>
                                            <?php
                                            $bar_stmt = $pdo->prepare("SELECT DISTINCT u.barangay AS value FROM referrals r LEFT JOIN users u ON r.referred_by = u.user_id WHERE u.barangay IS NOT NULL ORDER BY u.barangay");
                                            $bar_stmt->execute();
                                            while ($row = $bar_stmt->fetch()) {
                                                $val = $row['value'];
                                                $sel = ($barangay === $val) ? 'selected' : '';
                                                echo '<option value="' . htmlspecialchars($val) . '" ' . $sel . '>' . htmlspecialchars($val) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn" id="closeFilterModal">Cancel</button>
                                <button type="submit" class="btn-submit">Apply Filters</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div><!-- /filter-form -->

            <!-- ═══ PRINT AREA ═══ -->
            <div class="main-content">
                <div class="print-area">

                    <!-- Letterhead (print only) -->
                    <div class="print-letterhead">
                        <img src="../../img/daet_logo.png" alt="Left Logo" class="print-logo">
                        <div class="print-heading">
                            <div class="ph-line-1">Republic of the Philippines</div>
                            <div class="ph-line-1">Province of Camarines Norte</div>
                            <div class="ph-line-2">Municipality of Daet</div>
                            <div class="ph-line-3"><?= htmlspecialchars($rhu, ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <img src="../../img/mho_logo.png" alt="Right Logo" class="print-logo">
                    </div>
                    <hr class="print-rule">

                    <!-- Report title (print only) -->
                    <div class="title">
                        <div class="ph-line-4">REFERRAL INTAKE SUMMARY REPORT</div>
                        <div class="print-sub">
                            (<?php
                            $filters = [];
                            if ($from_date || $to_date) {
                                $rf = $from_date ? date("F j, Y", strtotime($from_date)) : '';
                                $rt = $to_date   ? date("F j, Y", strtotime($to_date))   : '';
                                $filters[] = "<strong>" . trim($rf . ($rt ? " — " . $rt : '')) . "</strong>";
                            }
                            if ($status)   $filters[] = "Status: <strong>" . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . "</strong>";
                            if ($barangay) $filters[] = "Barangay: <strong>" . htmlspecialchars($barangay, ENT_QUOTES, 'UTF-8') . "</strong>";
                            echo $filters ? implode(" &nbsp;|&nbsp; ", $filters) : "All Records";
                            ?>)
                        </div>
                    </div>

                    <!-- ─── Chart Controls ─── -->
                    <div class="chart chart-controls-panel">
                        <h3>Charts</h3>
                        <div class="chart-toggle-group">
                            <label><input type="checkbox" id="toggleStatusChart"> Referral Status</label>
                            <label><input type="checkbox" id="toggleBarangayChart" checked> Referrals per Barangay</label>
                        </div>
                    </div>

                    <!-- ─── Charts Grid ─── -->
                    <div id="summaryChartsGrid" class="referral-summary-charts-grid single-chart">
                        <!-- Status Pie Chart -->
                        <div class="chart summary-chart-card pie-chart is-hidden" id="statusChartWrap">
                            <h3>Referral Status Distribution</h3>
                            <canvas id="statusPieChart"></canvas>
                            <p id="noDataMessage" style="display:none;color:var(--grey-500);margin-top:10px;font-size:13px;">No data available</p>
                        </div>

                        <!-- Barangay Bar Chart -->
                        <div class="chart summary-chart-card bar-chart" id="barangayChartWrap">
                            <h3>Total Referrals Received per Barangay</h3>
                            <canvas id="barangayBarChart"></canvas>
                            <p id="noBarDataMessage" style="display:none;color:var(--grey-500);margin-top:10px;font-size:13px;">No data available</p>
                        </div>
                    </div>

                    <!-- ─── Report Table ─── -->
                    <div class="report-table-container">
                        <div class="report-table-scroll">
                            <table id="reportTable">
                                <thead>
                                    <tr>
                                        <th data-type="string">Barangay<span class="sort-indicator"></span></th>
                                        <th data-type="number">Total Referrals Received<span class="sort-indicator"></span></th>
                                        <th data-type="number">Completed<span class="sort-indicator"></span></th>
                                        <th data-type="number">Uncompleted<span class="sort-indicator"></span></th>
                                        <th data-type="number">Pending<span class="sort-indicator"></span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td data-label="Barangay"><?= htmlspecialchars($row['barangay']) ?></td>
                                        <td data-label="Total Referrals Received"><?= (int)$row['total_referrals'] ?></td>
                                        <td data-label="Completed"><?= (int)$row['completed'] ?></td>
                                        <td data-label="Uncompleted"><?= (int)$row['uncompleted'] ?></td>
                                        <td data-label="Pending"><?= (int)$row['pending'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($rows)): ?>
                                    <tr><td colspan="5" class="no-records" style="text-align:center;padding:32px;">No referral records found for the selected filters.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <br>

                    <!-- ─── Summary Section ─── -->
                    <div class="summary-container">
                        <div class="summary">
                            <h3 class="summary-title"><i class="bx bx-file"></i> Report Details</h3>

                            <table class="summary-table">
                                <tbody>
                                    <tr>
                                        <th>Report Generated On</th>
                                        <td class="summary-mono"><?= htmlspecialchars(date('F jS, Y \a\t g:i A'), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Total Referrals Received</th>
                                        <td><strong><?= (int)$total_received ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>Completed Referrals</th>
                                        <td><?= (int)$total_completed ?></td>
                                    </tr>
                                    <tr>
                                        <th>Uncompleted Referrals</th>
                                        <td><?= (int)$total_uncompleted ?></td>
                                    </tr>
                                    <tr>
                                        <th>Pending Referrals</th>
                                        <td><?= (int)$total_pending ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <br><br>
                    <span id="generated_by" style="font-size:16px;"></span>

                </div><!-- /print-area -->
            </div><!-- /main-content -->

        </div><!-- /history-container -->
    </main>
</section>

<!-- ═══ LOGOUT MODAL ═══ -->
<div id="logoutModal" class="logout-modal">
    <div class="logout-modal-content">
        <div class="logout-modal-header"><h3>Confirm Logout</h3></div>
        <div class="logout-modal-body"><p>Are you sure you want to logout?</p></div>
        <div class="logout-modal-footer">
            <button onclick="closeModal()" class="logout-cancel-btn">Cancel</button>
            <button onclick="proceedLogout()" class="logout-confirm-btn">Yes, Logout</button>
        </div>
    </div>
</div>

<!-- ═══ SCRIPTS ═══ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
/* ─── Chart data from PHP ─── */
const referralLabels  = <?= json_encode(array_keys($referral_counts)) ?>;
const referralData    = <?= json_encode(array_values($referral_counts)) ?>;
const barangayLabels  = <?= json_encode($barangay_labels) ?>;
const barangayData    = <?= json_encode($barangay_referrals) ?>;

const palette     = ['#0d2d52','#22a06b','#e53e3e','#1c6fba','#d97706'];
const navyPalette = ['#1c6fba'];
const chartInstances = {};

document.addEventListener('DOMContentLoaded', () => {
    /* Status pie chart */
    const pieCtx = document.getElementById('statusPieChart');
    const pieTotal = referralData.reduce((a, b) => a + b, 0);
    if (pieCtx && pieTotal > 0) {
        chartInstances.status = new Chart(pieCtx.getContext('2d'), {
            type: 'pie',
            data: {
                labels: referralLabels,
                datasets: [{ data: referralData, backgroundColor: palette, borderWidth: 2, borderColor: '#fff' }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 850, easing: 'easeOutQuart' },
                layout: { padding: 8 },
                plugins: {
                    legend: { position: 'bottom', labels: { font: { family: 'Plus Jakarta Sans', size: 12 } } },
                    tooltip: { bodyFont: { family: 'Plus Jakarta Sans' }, titleFont: { family: 'Plus Jakarta Sans', weight: '700' } }
                }
            }
        });
    } else if (pieCtx) {
        document.getElementById('statusPieChart').style.display = 'none';
        document.getElementById('noDataMessage').style.display = 'block';
    }

    /* Barangay bar chart */
    const barCtx = document.getElementById('barangayBarChart');
    const barTotal = barangayData.reduce((a, b) => a + b, 0);
    if (barCtx && barTotal > 0) {
        chartInstances.barangay = new Chart(barCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: barangayLabels,
                datasets: [{
                    label: 'Total Referrals',
                    data: barangayData,
                    backgroundColor: '#1c6fba',
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 850, easing: 'easeOutQuart' },
                layout: { padding: 8 },
                plugins: {
                    legend: { display: false },
                    tooltip: { bodyFont: { family: 'Plus Jakarta Sans' }, titleFont: { family: 'Plus Jakarta Sans', weight: '700' } }
                },
                scales: {
                    x: { title: { display: true, text: 'Barangay', font: { family: 'Plus Jakarta Sans', size: 12 } }, grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans', size: 11 }, maxRotation: 35, minRotation: 0, autoSkip: false } },
                    y: { title: { display: true, text: 'Total Referrals', font: { family: 'Plus Jakarta Sans', size: 12 } }, beginAtZero: true, grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } } }
                }
            }
        });
    } else if (barCtx) {
        document.getElementById('barangayBarChart').style.display = 'none';
        document.getElementById('noBarDataMessage').style.display = 'block';
    }

    /* Chart toggles with smoother layout */
    const chartMapping = {
        toggleStatusChart:   'statusChartWrap',
        toggleBarangayChart: 'barangayChartWrap',
    };
    const chartGrid = document.getElementById('summaryChartsGrid');

    function animateIn(el) {
        if (!el) return;
        el.classList.remove('chart-entering');
        void el.offsetWidth;
        el.classList.add('chart-entering');
        window.setTimeout(() => el.classList.remove('chart-entering'), 420);
    }

    function syncChartLayout(changedEl = null) {
        const visibleCount = Object.keys(chartMapping).reduce((count, id) => {
            const cb = document.getElementById(id);
            const el = document.getElementById(chartMapping[id]);
            const isVisible = !!(cb && cb.checked);
            if (el) el.classList.toggle('is-hidden', !isVisible);
            return count + (isVisible ? 1 : 0);
        }, 0);

        if (chartGrid) chartGrid.classList.toggle('single-chart', visibleCount <= 1);
        if (changedEl && !changedEl.classList.contains('is-hidden')) animateIn(changedEl);

        window.setTimeout(() => {
            Object.values(chartInstances).forEach(chart => {
                if (chart && typeof chart.resize === 'function') chart.resize();
            });
        }, 260);
    }

    Object.keys(chartMapping).forEach(id => {
        const cb = document.getElementById(id);
        const el = document.getElementById(chartMapping[id]);
        if (cb && el) cb.addEventListener('change', () => syncChartLayout(el));
    });
    syncChartLayout();
});
</script>

<script>
/* ─── Table sort ─── */
(function () {
    const table = document.getElementById('reportTable');
    if (!table) return;
    const thead = table.tHead;
    const tbody = table.tBodies[0];

    function parseDate(v) {
        if (/^\d{4}-\d{2}-\d{2}/.test(v)) return new Date(v.slice(0, 10) + 'T00:00:00');
        const d = new Date(v); return isNaN(d.getTime()) ? null : d;
    }
    function detectType(idx) {
        const th = thead.querySelectorAll('th')[idx];
        if (th?.dataset?.type) return th.dataset.type;
        for (const tr of tbody.rows) {
            const t = (tr.cells[idx]?.textContent || '').trim();
            if (!t) continue;
            if (parseDate(t)) return 'date';
            if (!isNaN(t.replace(/,/g, ''))) return 'number';
            return 'string';
        }
        return 'string';
    }
    function getCellValue(tr, idx, type) {
        const raw = (tr.cells[idx]?.textContent || '').trim();
        if (type === 'number') { const n = parseFloat(raw.replace(/,/g, '')); return isNaN(n) ? -Infinity : n; }
        if (type === 'date')   { const d = parseDate(raw); return d ? d.getTime() : -Infinity; }
        return raw.toLowerCase();
    }
    function sortBy(idx, dir) {
        const type = detectType(idx);
        const rows = [...tbody.rows].sort((a, b) => {
            const va = getCellValue(a, idx, type), vb = getCellValue(b, idx, type);
            return va < vb ? (dir === 'asc' ? -1 : 1) : va > vb ? (dir === 'asc' ? 1 : -1) : 0;
        });
        const frag = document.createDocumentFragment();
        rows.forEach(r => frag.appendChild(r));
        tbody.appendChild(frag);
    }

    [...thead.querySelectorAll('th')].forEach((th, idx) => {
        th.addEventListener('click', () => {
            const nextDir = th.classList.contains('is-sorted-asc') ? 'desc' : 'asc';
            [...thead.querySelectorAll('th')].forEach(h => h.classList.remove('is-sorted-asc', 'is-sorted-desc'));
            th.classList.add(nextDir === 'asc' ? 'is-sorted-asc' : 'is-sorted-desc');
            sortBy(idx, nextDir);
        });
    });

    /* Default: sort by Total Referrals (col 1) desc */
    const def = thead.querySelectorAll('th')[1];
    if (def) { def.classList.add('is-sorted-desc'); sortBy(1, 'desc'); }
})();
</script>

<script>
/* ─── Filter Modal ─── */
document.getElementById('openFilterModal').onclick  = () => document.getElementById('filterModal').style.display = 'block';
document.getElementById('closeFilterModal').onclick = () => document.getElementById('filterModal').style.display = 'none';

document.getElementById('openFilterModal').addEventListener('click', () => {
    setTimeout(() => {
        ['from_date', 'to_date'].forEach(id => {
            const el = document.getElementById(id);
            if (el._flatpickr) el._flatpickr.destroy();
            flatpickr('#' + id, { dateFormat: 'Y-m-d', allowInput: true, disableMobile: true });
        });
    }, 100);
});

window.addEventListener('click', e => {
    const modal  = document.getElementById('filterModal');
    if (e.target === modal)  modal.style.display = 'none';
    const logout = document.getElementById('logoutModal');
    if (e.target === logout) closeModal();
});
</script>

<script>
/* ─── Excel Export ─── */
function exportTableToExcel(tableID, filename = 'Referral Summary Report') {
    try {
        const tempDiv = document.createElement('div');
        tempDiv.style.cssText = 'position:absolute;left:-9999px;top:-9999px;';

        const summary = document.querySelector('.summary-container');
        if (summary) { const cl = summary.cloneNode(true); cl.querySelectorAll('script').forEach(s => s.remove()); tempDiv.appendChild(cl); }

        const orig = document.getElementById(tableID);
        if (!orig) { alert('Table not found!'); return; }
        tempDiv.appendChild(orig.cloneNode(true));
        document.body.appendChild(tempDiv);

        const html = `<html xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:excel"
            xmlns="http://www.w3.org/TR/REC-html40">
            <head><meta charset="UTF-8"></head>
            <body>${tempDiv.innerHTML}</body></html>`;

        const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = filename + '.xls';
        document.body.appendChild(a); a.click();
        setTimeout(() => { document.body.removeChild(a); document.body.removeChild(tempDiv); URL.revokeObjectURL(a.href); }, 100);
    } catch (err) { console.error(err); alert('Export error: ' + err.message); }
}

/* ─── Print ─── */
function printDiv() {
    const headerEl = document.querySelector('.print-letterhead');
    const printHeader = headerEl ? headerEl.outerHTML : '';
    const area = document.querySelector('.print-area');
    if (!area) return;
    const clone = area.cloneNode(true);

    /* Remove all chart/canvas elements from the clone */
    clone.querySelectorAll('.chart-controls-panel, .selected-filters, .referral-summary-charts-grid, .summary-chart-card, canvas').forEach(el => el.remove());

    const headerInClone = clone.querySelector('.print-letterhead');
    if (headerInClone) headerInClone.remove();

    const w = window.open('', '', 'height=900,width=1100');
    if (!w) { alert('Please allow pop-ups to print this report.'); return; }
    w.document.write(`<html><head><title>Print Report</title><meta charset="utf-8"/>
    <style>
      body{font-family:Arial,sans-serif;font-size:13px;color:#000;}
      table{width:100%;border-collapse:collapse;font-family:Arial,sans-serif;font-size:12px;}
      th,td{border:1px solid #000;padding:5px 8px;text-align:left;font-family:Arial,sans-serif;}
      thead tr{background:#fff!important;}
      thead th{background:#fff!important;color:#000!important;font-weight:700;}
      .print-letterhead{display:grid;grid-template-columns:64px auto 64px;align-items:center;column-gap:60px;margin:0 auto 10px;text-align:center;width:fit-content;}
      .print-logo{width:64px;height:64px;object-fit:contain;}
      .print-heading{font-family:Arial,sans-serif;}
      .print-heading .ph-line-1{font-size:12pt;font-weight:500;}
      .print-heading .ph-line-2{font-size:14pt;font-weight:800;}
      .print-heading .ph-line-3{font-size:12pt;font-weight:500;}
      .title{text-align:center;margin:8px 0;font-family:Arial,sans-serif;}
      .ph-line-4{font-size:12pt;font-weight:800;margin-top:4px;text-align:center;font-family:Arial,sans-serif;}
      .print-sub{font-size:11pt;margin-top:4px;text-align:center;font-family:Arial,sans-serif;}
      .print-rule{height:1px;border:0;background:#ccc;margin:8px 0 12px;}
      .chart-controls-panel,.btn-export,.btn-print,.selected-filters,
      .referral-summary-charts-grid,.summary-chart-card,canvas{display:none!important;}
      #generated_by{margin-top:48px;font-family:Arial,sans-serif;}
      .sig-label{font-size:11px;text-transform:uppercase;letter-spacing:.07em;color:#666;margin-bottom:60px;display:block;}
      .sig-block{display:inline-block;text-align:center;}
      .sig-line{display:block;border:none;border-top:1.5px solid #000;margin:0 0 4px;}
      .sig-name{font-weight:700;font-size:13px;white-space:nowrap;}
      .sig-title{font-size:11px;color:#666;}
    </style></head>
    <body>${printHeader}${clone.innerHTML}</body></html>`);
    w.document.close(); w.focus();
    setTimeout(() => { w.print(); w.close(); }, 350);
}

/* ─── User name ─── */
fetch('../php/getUserName.php')
    .then(r => r.json())
    .then(data => {
        const fullName = (data && data.full_name) ? data.full_name : '';
        const sn = document.getElementById('sidebarUserName');
        if (sn) sn.textContent = fullName || 'Nurse User';
        const gb = document.getElementById('generated_by');
        if (gb) {
            const name = fullName || '________________';
            gb.innerHTML = `<div class="sig-label">Report Generated by:</div><div class="sig-block"><span class="sig-line"></span><div class="sig-name"></div><div class="sig-title">Nursing Attendant</div></div>`;
            gb.querySelector('.sig-name').textContent = name;
            const nameEl = gb.querySelector('.sig-name');
            const lineEl = gb.querySelector('.sig-line');
            requestAnimationFrame(() => { lineEl.style.width = nameEl.offsetWidth + 'px'; });
        }
    })
    .catch(() => {
        const sn = document.getElementById('sidebarUserName');
        if (sn) sn.textContent = 'Nurse User';
        const gb = document.getElementById('generated_by');
        if (gb) {
            gb.innerHTML = `<div class="sig-label">Report Generated by:</div><div class="sig-block"><span class="sig-line" style="width:180px;"></span><div class="sig-name">________________</div><div class="sig-title">Nursing Attendant</div></div>`;
        }
    });

/* ─── Logout ─── */
function confirmLogout() { document.getElementById('logoutModal').classList.add('open'); return false; }
function closeModal()    { document.getElementById('logoutModal').classList.remove('open'); }
function proceedLogout() { window.location.href = '../../ADMIN/php/logout'; }
</script>

<script>
/* ─── Sidebar toggle ─── */
(function () {
    const sidebar = document.getElementById('sidebar');
    const toggle  = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');
    if (!sidebar || !toggle || !overlay) return;

    function isMobile() { return window.innerWidth <= 768; }
    function closeMobile() { sidebar.classList.remove('mobile-open'); overlay.classList.remove('active'); document.body.style.overflow = ''; }

    toggle.addEventListener('click', () => {
        if (isMobile()) {
            const open = sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active', open);
            document.body.style.overflow = open ? 'hidden' : '';
        } else {
            sidebar.classList.toggle('collapsed');
        }
    });
    overlay.addEventListener('click', closeMobile);
    window.addEventListener('resize', () => { if (!isMobile()) closeMobile(); });
})();

/* ─── Nav search & pending referrals ─── */
document.addEventListener('DOMContentLoaded', () => {
    const updateReferrals = document.getElementById('updateReferrals');
    const searchInput     = document.getElementById('patientSearch');
    const searchButton    = document.getElementById('searchButton');

    if (updateReferrals) {
        updateReferrals.addEventListener('click', e => {
            e.preventDefault();
            fetch('../php/update_referrals.php')
                .then(r => r.json())
                .then(() => window.location.href = '../pending')
                .catch(() => window.location.href = '../pending');
        });
    }

    if (searchInput && searchButton) {
        searchInput.addEventListener('keypress', e => { if (e.key === 'Enter') { e.preventDefault(); searchButton.click(); } });
        searchButton.addEventListener('click', () => {
            const q = searchInput.value.trim();
            if (q) { sessionStorage.setItem('searchQuery', q); window.location.href = '../searchPatient'; }
            else alert('Please enter a patient name to search.');
        });
    }
});
</script>

</body>
</html>