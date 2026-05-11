<?php
// Connect to DB
require '../../php/db_connect.php';
require_once __DIR__ . '/../php/session_config.php';

if (!isset($_SESSION['user_id'])) {
    echo "User is not logged in.";
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT full_name, rhu FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$rhu = $user ? $user['rhu'] : 'N/A';
$username = $user ? $user['full_name'] : 'N/A';

// Filters
$from_date = $_GET['from_date'] ?? '';
$to_date   = $_GET['to_date'] ?? '';
$status    = $_GET['status'] ?? '';
$barangay  = $_GET['barangay'] ?? '';

$params = [];

// Base query – only include users whose RHU matches current user's RHU
$sql = "
    SELECT 
        u.barangay,
        SUM(CASE WHEN r.referral_status IN ('Completed', 'Uncompleted', 'Pending') THEN 1 ELSE 0 END) AS total_referrals,
        SUM(CASE WHEN r.referral_status = 'Completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN r.referral_status = 'Uncompleted' THEN 1 ELSE 0 END) AS uncompleted,
        SUM(CASE WHEN r.referral_status = 'Pending' THEN 1 ELSE 0 END) AS pending
    FROM referrals r
    LEFT JOIN users u ON r.referred_by = u.user_id
    WHERE u.rhu = :rhu 
      AND u.barangay IS NOT NULL 
      AND u.barangay != ''
";
$params['rhu'] = $rhu;

// Add date filter if present
if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND DATE(r.referral_date) BETWEEN :from_date AND :to_date";
    $params['from_date'] = $from_date;
    $params['to_date'] = $to_date;
}
if (!empty($status)) {
    $sql .= " AND r.referral_status = :status";
    $params['status'] = $status;
}
if (!empty($barangay)) {
    $sql .= " AND u.barangay = :barangay";
    $params['barangay'] = $barangay;
}

// Final group and order clause (only add once!)
$sql .= " GROUP BY u.barangay ORDER BY u.barangay";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Totals
$total_received = 0;
$total_completed = 0;
$total_uncompleted = 0;
$total_pending = 0;

foreach ($rows as $row) {
    $total_received += $row['total_referrals'];
    $total_completed += $row['completed'];
    $total_uncompleted += $row['uncompleted'];
    $total_pending += $row['pending'];
}

$referral_counts = [
    'Pending' => $total_pending,
    'Completed' => $total_completed,
    'Uncompleted' => $total_uncompleted
];

$barangay_labels = array_column($rows, 'barangay');
$barangay_referrals = array_column($rows, 'total_referrals');

// ADDED GENERATED REPORT FOR ACTIVITY LOG
$stmt_log = $pdo->prepare("INSERT INTO logs (
    user_id, action, performed_by
) VALUES (
    :user_id, :action, :performed_by
)");
$stmt_log->execute([
    ':user_id' => $_SESSION['user_id'],
    ':action' => "Generated RHU Referral Summary Report",
    ':performed_by' => $_SESSION['user_id']
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
</head>
<body>

<style>
  #reportTable th {
    cursor: pointer;
    position: relative;
    user-select: none;
  }
  #reportTable th .sort-indicator {
    margin-left: 6px;
    font-size: 11px;
    opacity: 0.7;
  }
  #reportTable th.is-sorted-asc .sort-indicator::after { content: "▲"; }
  #reportTable th.is-sorted-desc .sort-indicator::after { content: "▼"; }

  .checkbox-list {
    max-height: 170px;
    overflow-y: auto;
    border: 1.5px solid var(--border);
    padding: 10px 12px;
    border-radius: var(--r-sm);
    background: var(--grey-100);
  }

  .checkbox-list label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 500;
    color: var(--grey-700);
    text-align: left;
    text-transform: none;
    letter-spacing: 0;
  }

  .chart-card {
    max-width: 680px;
    margin: 24px auto 0;
    text-align: center;
    background: var(--white);
    border: 1px solid var(--border-soft);
    border-radius: var(--r-lg);
    padding: 20px;
    box-shadow: var(--shadow-xs);
  }

  .chart-card.chart-card-sm { max-width: 420px; }

  .chart-card h3 {
    font-size: 15px;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 12px;
  }

  .title { text-align: center; display: none; }
  .print-only-letterhead { display: none; }

  @media print {
    .title { display: block !important; text-align: center; }
    .ph-line-4 { text-align: center; }
    .print-sub { text-align: center; }
    .print-only-letterhead { display: block !important; }
    .summary > h3 { display: none !important; }
    /* Hide charts */
    .chart-title, .chart-controls-panel, .summary-chart-card,
    .referral-summary-charts-grid, canvas,
    .form-submit, .selected-filters,
    nav, #sidebar, .sidebar-overlay { display: none !important; }
    /* Remove thead color */
    thead tr { background: #fff !important; }
    thead th { background: #fff !important; color: #000 !important; }
    /* Consistent font */
    body, table, th, td, #generated_by, .sig-label, .sig-name, .sig-title,
    .ph-line-4, .print-sub { font-family: Arial, sans-serif !important; }

    .print-letterhead {
      display: grid !important;
      grid-template-columns: 72px auto 72px;
      align-items: center;
      justify-content: center;
      column-gap: 60px;
      margin: 0 auto 18px;
      text-align: center;
      width: fit-content;
    }

    .print-logo { width: 64px; height: 64px; object-fit: contain; }
    .print-heading { line-height: 1.1; color: #000; }
    .print-heading .ph-line-1 { font-size: 12pt; font-weight: 500; margin-bottom: 3px; }
    .print-heading .ph-line-2 { font-size: 14pt; font-weight: 500; margin-bottom: 3px; }
    .print-heading .ph-line-3 { font-size: 11pt; font-weight: 500; margin-bottom: 3px; }
    .print-heading .ph-line-4 { font-size: 12pt; font-weight: 600; margin-top: 15px; letter-spacing: .3px; }
    .print-sub { font-size: 12pt; margin-top: 4px; }
    .print-rule { height: 1px; border: 0; background: #cfd8e3; margin: 8px 0 12px; }

    .summary-table th,
    .summary-table td {
      border: 1px solid #000 !important;
      color: #000 !important;
      background: transparent !important;
    }

    #generated_by { margin: 50mm 0 0 10mm !important; }
    #generated_by .sig-label { font-size: 11px; margin-bottom: 60px; display: block; }
    #generated_by .sig-block { display: inline-block; text-align: center; }
    #generated_by .sig-line { display: block; border: none; border-top: 1.5px solid #000; width: 100%; margin: 0 0 4px; }
    #generated_by .sig-name { font-weight: 700; font-size: 12pt; white-space: nowrap; }
    #generated_by .sig-title { font-size: 11pt; }
  }

  @media (max-width: 768px) {
    .chart-card { padding: 16px 12px; }
  }


  /* Layout balance update: side-by-side chart grid with smooth entrance */
  #content main {
    width: 100%;
    padding: 32px 28px;
    max-height: calc(100vh - 56px);
    overflow-y: auto;
  }

  .history-container,
  .main-content,
  .report-content {
    width: 100%;
  }

  .filter-form,
  .print-area {
    border-radius: var(--r-lg, 16px);
  }

  .referral-summary-charts-grid {
    display: grid;
    grid-template-columns: minmax(280px, 420px) minmax(360px, 1fr);
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
    border: 1px solid var(--border-soft, #edf0f7);
    border-radius: var(--r-lg, 16px);
    background: var(--white, #fff);
    box-shadow: var(--shadow-xs, 0 1px 3px rgba(13,45,82,.07));
    opacity: 1;
    transform: translateY(0) scale(1);
    transition: opacity .28s ease, transform .28s ease, box-shadow .18s ease;
    overflow: hidden;
  }

  .summary-chart-card:hover {
    box-shadow: var(--shadow-sm, 0 2px 8px rgba(13,45,82,.09));
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
    color: var(--navy, #0d2d52);
    margin-bottom: 14px;
  }

  @keyframes softChartEntrance {
    from { opacity: 0; transform: translateY(14px) scale(.985); }
    to { opacity: 1; transform: translateY(0) scale(1); }
  }

  .summary-chart-card.chart-entering {
    animation: softChartEntrance .36s ease both;
  }

  .chart-controls-panel {
    margin-bottom: 0 !important;
  }

  .report-table-container {
    margin-top: 24px !important;
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

  @media (max-width: 768px) {
    #content main { padding: 20px 14px; }
    .filter-form,
    .print-area { padding: 20px 18px; }
  }

  @media (max-width: 480px) {
    .summary-chart-card { min-height: 310px; padding: 16px 14px 12px; }
    .summary-chart-card canvas,
    .summary-chart-card.bar-chart canvas { height: 240px !important; max-height: 240px; }
  }

</style>

<!-- Sidebar Section -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<section id="sidebar">
    <a href="#" class="sidebar-brand">
        <img src="../../img/logo.png" alt="RHU Logo" class="brand-logo">
        <div class="brand-text">
            <span class="brand-name">Hello Physician</span>
        </div>
    </a>

    <div class="sidebar-scroll">
        <div class="sidebar-section-label">Main Menu</div>
        <ul class="side-menu top">
            <li>
                <a href="../dashboard" data-tooltip="Dashboard">
                    <i class="bx bxs-dashboard nav-icon"></i>
                    <span class="nav-label">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="../pending" id="updateReferrals" data-tooltip="Pending Referrals">
                    <i class="bx bxs-user nav-icon"></i>
                    <span class="nav-label">Pending Referrals</span>
                </a>
            </li>
            <li>
                <a href="../followUpConsultations" data-tooltip="Follow-Up Visits">
                    <i class="bx bxs-user nav-icon"></i>
                    <span class="nav-label">Follow-Up Visits</span>
                </a>
            </li>
            <li>
                <a href="../searchPatient" data-tooltip="Patient Records">
                    <i class="bx bxs-search nav-icon"></i>
                    <span class="nav-label">Patient Records</span>
                </a>
            </li>
            <li>
                <a href="../history" data-tooltip="Referral History">
                    <i class="bx bx-history nav-icon"></i>
                    <span class="nav-label">Referral History</span>
                </a>
            </li>
            <li class="active">
                <a href="../reports" data-tooltip="Reports">
                    <i class="bx bx-notepad nav-icon"></i>
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
            <img src="../../img/nurse.png" alt="Physician User">
            <div class="sidebar-user-info">
                <div class="user-name" id="sidebarUserName">Physician</div>
                <div class="user-role">Physician</div>
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
            <input type="search" id="patientSearch" placeholder="Enter patient name..." name="search" autocomplete="off">
            <button type="button" id="searchButton" aria-label="Search">
                <i class="bx bx-search"></i>
            </button>
            <div id="resultDropdown" class="dropdown-content"></div>
        </div>
    </nav>

    <main>
        <div class="head-title">
            <div class="left">
                <h1>Referral Summary Report</h1>
                <ul class="breadcrumb">
                    <li><a href="#">Referral Intake Summary Report</a></li>
                    <li><i class="bx bx-chevron-right"></i></li>
                    <li><a class="active" href="#" onclick="history.back(); return false;">Go back</a></li>
                </ul>
            </div>
        </div>

        <div class="history-container">

            <!-- Filter Form Card -->
            <div class="filter-form">
                <h2>Referral Intake Summary Report - <?php echo htmlspecialchars($rhu); ?></h2>

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

                <div class="selected-filters" style="margin-top:20px;">
                    <h3><i class="bx bx-filter-alt"></i> Selected Filters:</h3>
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

                        if ($from_date) renderTag('From', 'from_date', $from_date);
                        if ($to_date) renderTag('To', 'to_date', $to_date);
                        if ($status) renderTag('Status', 'status', $status);
                        if ($barangay) renderTag('Barangay', 'barangay', $barangay);

                        if (!$from_date && !$to_date && !$status && !$barangay) {
                            echo '<span style="color:var(--grey-500);font-size:13px;">None</span>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Filter Modal -->
                <div id="filterModal" class="modal" style="display:none;">
                    <div class="modal-content" style="max-width:600px;">
                        <div class="modal-header">
                            <h3><i class="bx bx-filter-alt" style="margin-right:8px;color:var(--blue);"></i>Apply Filters</h3>
                        </div>
                        <form method="GET" id="filterForm">
                            <div class="modal-body">
                                <div class="form-row">
                                    <div class="form-item">
                                        <label for="from_date">From:</label>
                                        <input type="text" name="from_date" id="from_date" class="form-control" value="<?= $from_date ? htmlspecialchars($from_date) : '' ?>" placeholder="Select date">
                                    </div>
                                    <div class="form-item">
                                        <label for="to_date">To:</label>
                                        <input type="text" name="to_date" id="to_date" class="form-control" value="<?= $to_date ? htmlspecialchars($to_date) : '' ?>" placeholder="Select date">
                                    </div>
                                    <div class="form-item">
                                        <label for="status">Status:</label>
                                        <select name="status" id="status" class="form-control">
                                            <option value="" <?= $status == '' ? 'selected' : '' ?>>All</option>
                                            <option value="Pending" <?= $status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Completed" <?= $status == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="Uncompleted" <?= $status == 'Uncompleted' ? 'selected' : '' ?>>Uncompleted</option>
                                            <option value="Cancelled" <?= $status == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="form-item">
                                        <label for="barangay">Barangay:</label>
                                        <select name="barangay" id="barangay" class="form-control">
                                            <option value="">All</option>
                                            <?php
                                            $barangay_stmt = $pdo->prepare("SELECT DISTINCT u.barangay AS value FROM referrals r LEFT JOIN users u ON r.referred_by = u.user_id WHERE u.barangay IS NOT NULL ORDER BY u.barangay");
                                            $barangay_stmt->execute();

                                            $selected_barangay = $_GET['barangay'] ?? '';
                                            while ($row = $barangay_stmt->fetch()) {
                                                $value = $row['value'];
                                                $selected = ($selected_barangay === $value) ? 'selected' : '';
                                                echo "<option value=\"" . htmlspecialchars($value) . "\" $selected>" . htmlspecialchars($value) . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn" id="closeFilterModal">Cancel</button>
                                <button type="submit" class="btn-submit">Apply Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="main-content">
                <div class="print-area">
                    <!-- PRINT-ONLY LETTERHEAD -->
                    <div class="print-only-letterhead">
                        <div class="print-letterhead">
                            <img src="../../img/daet_logo.png" alt="Left Logo" class="print-logo">
                            <div class="print-heading">
                                <div class="ph-line-1">Republic of the Philippines</div>
                                <div class="ph-line-1">Province of Camarines Norte</div>
                                <div class="ph-line-2">Municipality of Daet</div>
                                <div class="ph-line-3"><?= htmlspecialchars($rhu) ?></div>
                            </div>
                            <img src="../../img/mho_logo.png" alt="Right Logo" class="print-logo">
                        </div>
                        <hr class="print-rule">
                    </div>

                    <div class="report-content">
                        <div class="title">
                            <h2>REFERRAL INTAKE SUMMARY REPORT</h2>
                            <div class="print-sub">
                                (<?php
                                $filters = [];

                                if ($from_date || $to_date) {
                                    $readable_from = $from_date ? date("F j, Y", strtotime($from_date)) : '';
                                    $readable_to = $to_date ? date("F j, Y", strtotime($to_date)) : '';
                                    $filters[] = "<strong>" . trim($readable_from . ($readable_to ? " — " . $readable_to : '')) . "</strong>";
                                }
                                if (!empty($status)) $filters[] = "Status: <strong>" . htmlspecialchars($status) . "</strong>";
                                if (!empty($barangay)) $filters[] = "Barangay: <strong>" . htmlspecialchars($barangay) . "</strong>";
                                echo $filters ? implode(" &nbsp;|&nbsp; ", $filters) : "All Records";
                                ?>)
                            </div>
                        </div>

                        <!-- Chart Visibility Controls -->
                        <div class="chart-controls-panel">
                            <h3>Charts:</h3>
                            <div class="chart-toggle-group">
                                <label><input type="checkbox" id="toggleStatusChart"> Referral Status</label>
                                <label><input type="checkbox" id="toggleBarangayChart" checked> Referrals per Barangay</label>
                            </div>
                        </div>

                        <!-- Charts Grid -->
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

                        <div class="report-table-container">
                            <div class="report-table-scroll">
                                <table id="reportTable">
                                    <thead>
                                        <tr>
                                            <th data-type="string">Barangay</th>
                                            <th data-type="number">Total Referrals Received</th>
                                            <th data-type="number">Completed</th>
                                            <th data-type="number">Uncompleted</th>
                                            <th data-type="number">Pending</th>
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

                        <div class="summary-container">
                            <div class="summary">
                                <h3 class="summary-title"><i class="bx bx-file"></i> Report Details</h3>
                                <table class="summary-table">
                                    <colgroup>
                                        <col style="width:40%">
                                        <col style="width:60%">
                                    </colgroup>
                                    <tbody>
                                        <tr>
                                            <th>Report Generated On</th>
                                            <td class="summary-mono"><?= date('F j, Y g:i:s A') ?></td>
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

                        <span id="generated_by"></span>
                    </div>
                </div>
            </div>
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
            <button onclick="closeModal()" class="logout-cancel-btn">Cancel</button>
            <button onclick="proceedLogout()" class="logout-confirm-btn">Yes, Logout</button>
        </div>
    </div>
</div>

<!-- jsPDF and html2canvas libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
/* Chart data */
const referralLabels = <?= json_encode(array_keys($referral_counts), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const referralData = <?= json_encode(array_values($referral_counts), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const barangayLabels = <?= json_encode($barangay_labels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const barangayData = <?= json_encode($barangay_referrals, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

const reportPalette = ['#0d2d52', '#22a06b', '#e53e3e', '#1c6fba', '#d97706'];

/* Modal and date picker */
document.getElementById('openFilterModal').onclick = function() {
    document.getElementById('filterModal').style.display = 'block';

    setTimeout(() => {
        const fromDateInput = document.getElementById('from_date');
        const toDateInput = document.getElementById('to_date');

        if (fromDateInput._flatpickr) fromDateInput._flatpickr.destroy();
        if (toDateInput._flatpickr) toDateInput._flatpickr.destroy();

        flatpickr('#from_date', { dateFormat: 'Y-m-d', allowInput: true, disableMobile: true });
        flatpickr('#to_date', { dateFormat: 'Y-m-d', allowInput: true, disableMobile: true });
    }, 100);
};

document.getElementById('closeFilterModal').onclick = function() {
    document.getElementById('filterModal').style.display = 'none';
};

document.getElementById('filterForm').onsubmit = function() {
    document.getElementById('filterModal').style.display = 'none';
    return true;
};

window.addEventListener('click', function(event) {
    const filterModal = document.getElementById('filterModal');
    const logoutModal = document.getElementById('logoutModal');
    if (event.target === filterModal) filterModal.style.display = 'none';
    if (event.target === logoutModal) closeModal();
});

/* Charts */
document.addEventListener('DOMContentLoaded', () => {
    const chartInstances = {};

    function hasData(data) {
        return data.reduce((a, b) => a + Number(b || 0), 0) > 0;
    }

    /* Referral status pie chart */
    const statusCanvas = document.getElementById('statusPieChart');
    if (statusCanvas && hasData(referralData)) {
        document.getElementById('statusPieChart').style.display = 'block';
        document.getElementById('noDataMessage').style.display = 'none';

        chartInstances.status = new Chart(statusCanvas.getContext('2d'), {
            type: 'pie',
            data: {
                labels: referralLabels,
                datasets: [{
                    data: referralData,
                    backgroundColor: reportPalette,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 850, easing: 'easeOutQuart' },
                layout: { padding: 8 },
                plugins: {
                    legend: { position: 'bottom', labels: { font: { family: 'Plus Jakarta Sans', size: 12 } } },
                    title: { display: false },
                    tooltip: { bodyFont: { family: 'Plus Jakarta Sans' }, titleFont: { family: 'Plus Jakarta Sans', weight: '700' } }
                }
            }
        });
    } else if (statusCanvas) {
        document.getElementById('statusPieChart').style.display = 'none';
        document.getElementById('noDataMessage').style.display = 'block';
    }

    /* Barangay bar chart */
    const barangayCanvas = document.getElementById('barangayBarChart');
    if (barangayCanvas && hasData(barangayData)) {
        document.getElementById('barangayBarChart').style.display = 'block';
        document.getElementById('noBarDataMessage').style.display = 'none';

        chartInstances.barangay = new Chart(barangayCanvas.getContext('2d'), {
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
                    title: { display: false },
                    tooltip: { bodyFont: { family: 'Plus Jakarta Sans' }, titleFont: { family: 'Plus Jakarta Sans', weight: '700' } }
                },
                scales: {
                    x: {
                        title: { display: true, text: 'Barangay', font: { family: 'Plus Jakarta Sans', size: 12 } },
                        grid: { display: false },
                        ticks: { font: { family: 'Plus Jakarta Sans', size: 11 }, maxRotation: 35, minRotation: 0, autoSkip: false }
                    },
                    y: {
                        title: { display: true, text: 'Total Referrals', font: { family: 'Plus Jakarta Sans', size: 12 } },
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,.04)' },
                        ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } }
                    }
                }
            }
        });
    } else if (barangayCanvas) {
        document.getElementById('barangayBarChart').style.display = 'none';
        document.getElementById('noBarDataMessage').style.display = 'block';
    }

    const chartMapping = {
        toggleStatusChart: 'statusChartWrap',
        toggleBarangayChart: 'barangayChartWrap'
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
        const visibleCount = Object.keys(chartMapping).reduce((count, toggleId) => {
            const checkbox = document.getElementById(toggleId);
            const chartElement = document.getElementById(chartMapping[toggleId]);
            const isVisible = !!(checkbox && checkbox.checked);
            if (chartElement) chartElement.classList.toggle('is-hidden', !isVisible);
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

    Object.keys(chartMapping).forEach(toggleId => {
        const checkbox = document.getElementById(toggleId);
        const chartElement = document.getElementById(chartMapping[toggleId]);
        if (checkbox && chartElement) {
            checkbox.addEventListener('change', () => syncChartLayout(chartElement));
        }
    });

    syncChartLayout();
});
</script>

<script>
function exportTableToExcel(tableID, filename = 'Referral Summary Report') {
    try {
        const tempDiv = document.createElement('div');
        tempDiv.style.position = 'absolute';
        tempDiv.style.left = '-9999px';
        tempDiv.style.top = '-9999px';

        const printHeader = document.querySelector('.print-only-letterhead');
        if (printHeader) {
            const headerClone = printHeader.cloneNode(true);
            headerClone.style.display = 'block';
            headerClone.querySelectorAll('script').forEach(script => script.remove());
            tempDiv.appendChild(headerClone);
        }

        const summary = document.querySelector('.summary-container');
        if (summary) {
            const summaryClone = summary.cloneNode(true);
            summaryClone.querySelectorAll('script').forEach(script => script.remove());
            tempDiv.appendChild(summaryClone);
        }

        const originalTable = document.getElementById(tableID);
        if (!originalTable) {
            alert('Table not found!');
            return;
        }

        const tableClone = originalTable.cloneNode(true);
        tempDiv.appendChild(tableClone);
        document.body.appendChild(tempDiv);

        const htmlContent = `
            <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
            <head>
                <meta charset="UTF-8">
                <!--[if gte mso 9]>
                <xml>
                    <x:ExcelWorkbook>
                        <x:ExcelWorksheets>
                            <x:ExcelWorksheet>
                                <x:Name>Report</x:Name>
                                <x:WorksheetOptions>
                                    <x:DisplayGridlines/>
                                </x:WorksheetOptions>
                            </x:ExcelWorksheet>
                        </x:ExcelWorksheets>
                    </x:ExcelWorkbook>
                </xml>
                <![endif]-->
            </head>
            <body>${tempDiv.innerHTML}</body>
            </html>
        `;

        const blob = new Blob([htmlContent], { type: 'application/vnd.ms-excel' });
        const downloadLink = document.createElement('a');
        downloadLink.href = URL.createObjectURL(blob);
        downloadLink.download = filename + '.xls';
        document.body.appendChild(downloadLink);
        downloadLink.click();

        setTimeout(() => {
            document.body.removeChild(downloadLink);
            document.body.removeChild(tempDiv);
            URL.revokeObjectURL(downloadLink.href);
        }, 100);
    } catch (error) {
        console.error('Excel export error:', error);
        alert('Error exporting to Excel: ' + error.message);
    }
}

(function() {
    const table = document.getElementById('reportTable');
    if (!table) return;

    const thead = table.tHead || table.querySelector('thead');
    const tbody = table.tBodies[0];

    [...thead.querySelectorAll('th')].forEach(th => {
        const ind = document.createElement('span');
        ind.className = 'sort-indicator';
        th.appendChild(ind);
    });

    function parseDate(v) {
        const t = (v || '').trim();
        if (/^\d{4}-\d{2}-\d{2}(?:\s+\d{2}:\d{2}(?::\d{2})?)?$/.test(t)) {
            return new Date(t.replace(' ', 'T'));
        }
        const d = new Date(t);
        return isNaN(d.getTime()) ? null : d;
    }

    function detectType(colIdx) {
        const th = thead.querySelectorAll('th')[colIdx];
        if (th?.dataset?.type) return th.dataset.type;

        for (const tr of tbody.rows) {
            const txt = (tr.cells[colIdx]?.textContent || '').trim();
            if (!txt) continue;

            const d = parseDate(txt);
            if (d) return 'date';

            const n = txt.replace(/,/g, '');
            if (!isNaN(n) && n !== '') return 'number';

            return 'string';
        }
        return 'string';
    }

    function val(tr, idx, type) {
        const raw = (tr.cells[idx]?.textContent || '').trim();
        if (type === 'number') {
            const n = parseFloat(raw.replace(/,/g, ''));
            return isNaN(n) ? Number.NEGATIVE_INFINITY : n;
        }
        if (type === 'date') {
            const d = parseDate(raw);
            return d ? d.getTime() : Number.NEGATIVE_INFINITY;
        }
        return raw.toLowerCase();
    }

    function clearHeaderStates(exceptIdx) {
        [...thead.querySelectorAll('th')].forEach((th, i) => {
            if (i !== exceptIdx) th.classList.remove('is-sorted-asc', 'is-sorted-desc');
        });
    }

    function sortBy(idx, dir) {
        const type = detectType(idx);
        const rows = [...tbody.rows];

        rows.sort((a, b) => {
            const va = val(a, idx, type);
            const vb = val(b, idx, type);
            if (va < vb) return dir === 'asc' ? -1 : 1;
            if (va > vb) return dir === 'asc' ? 1 : -1;
            return 0;
        });

        const frag = document.createDocumentFragment();
        rows.forEach(r => frag.appendChild(r));
        tbody.appendChild(frag);
    }

    [...thead.querySelectorAll('th')].forEach((th, idx) => {
        th.addEventListener('click', () => {
            const isAsc = th.classList.contains('is-sorted-asc');
            const nextDir = isAsc ? 'desc' : 'asc';
            clearHeaderStates(idx);
            th.classList.toggle('is-sorted-asc', nextDir === 'asc');
            th.classList.toggle('is-sorted-desc', nextDir === 'desc');
            sortBy(idx, nextDir);
        });
    });

    const defaultCol = 1, defaultDir = 'desc';
    const defaultTh = thead.querySelectorAll('th')[defaultCol];
    if (defaultTh) {
        defaultTh.classList.add(defaultDir === 'asc' ? 'is-sorted-asc' : 'is-sorted-desc');
        sortBy(defaultCol, defaultDir);
    }
})();

function printDiv() {
    const originalArea = document.querySelector('.print-area');
    if (!originalArea) {
        alert('Error: Missing .print-area on page.');
        return;
    }

    const clone = originalArea.cloneNode(true);
    /* Remove all chart/canvas elements from the clone */
    clone.querySelectorAll('.summary-chart-card, .chart-card, .chart-controls-panel, .referral-summary-charts-grid, canvas').forEach(el => el.remove());

    const w = window.open('', '', 'height=900,width=1100');
    if (!w) {
        alert('Please allow pop-ups to print this report.');
        return;
    }

    w.document.write(`
        <html>
        <head>
            <title>Print Report</title>
            <meta charset="utf-8" />
            <style>
                body{font-family:Arial,sans-serif;font-size:13px;color:#000;}
                table{width:100%;border-collapse:collapse;font-family:Arial,sans-serif;font-size:12px;}
                th,td{border:1px solid #000;padding:4px 6px;text-align:left;font-family:Arial,sans-serif;}
                thead tr{background:#fff!important;}
                thead th{background:#fff!important;color:#000!important;font-weight:700;}
                img{display:block;margin:0 auto;max-width:100%;height:auto;}
                h3{margin:10px 0 5px 0;font-family:Arial,sans-serif;}
                .title{display:block;text-align:center;margin:8px 0;font-family:Arial,sans-serif;}
                .ph-line-4{font-size:12pt;font-weight:800;margin-top:4px;text-align:center;font-family:Arial,sans-serif;}
                .print-only-letterhead{display:block;}
                .print-letterhead{display:grid;grid-template-columns:64px auto 64px;align-items:center;justify-content:center;column-gap:14px;margin:0 auto 10px;text-align:center;width:fit-content;}
                .print-logo{width:64px;height:64px;object-fit:contain;}
                .print-heading{line-height:1.1;color:#000;font-family:Arial,sans-serif;}
                .print-heading .ph-line-1{font-size:12pt;font-weight:500;}
                .print-heading .ph-line-2{font-size:14pt;font-weight:800;}
                .print-heading .ph-line-3{font-size:11pt;font-weight:500;}
                .print-sub{font-size:12pt;margin-top:4px;text-align:center;font-family:Arial,sans-serif;}
                .print-rule{height:1px;border:0;background:#cfd8e3;margin:8px 0 12px;}
                .summary-chart-card,.referral-summary-charts-grid,.chart-controls-panel,canvas{display:none!important;}
                #generated_by{margin:50mm 0 0 10mm;font-family:Arial,sans-serif;}
                .sig-label{font-size:11px;text-transform:uppercase;letter-spacing:.07em;color:#666;margin-bottom:60px;display:block;}
                .sig-block{display:inline-block;text-align:center;}
                .sig-line{display:block;border:none;border-top:1.5px solid #000;margin:0 0 4px;}
                .sig-name{font-weight:700;font-size:13px;white-space:nowrap;}
                .sig-title{font-size:11px;color:#666;}
            </style>
        </head>
        <body>${clone.innerHTML}</body>
        </html>
    `);
    w.document.close();
    w.focus();
    setTimeout(() => { w.print(); w.close(); }, 500);
}

function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'block';
    return false;
}

function closeModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

function proceedLogout() {
    window.location.href = '../../ADMIN/php/logout';
}

document.addEventListener('DOMContentLoaded', () => {
    fetch('../php/getUserName.php')
        .then(r => r.json())
        .then(data => {
            const fullName = (data && data.full_name) ? data.full_name : '';

            const sidebarNameEl = document.getElementById('sidebarUserName');
            if (sidebarNameEl) sidebarNameEl.textContent = fullName || 'Physician';

            const gb = document.getElementById('generated_by');
            if (gb) {
                const name2 = fullName || '________________';
                gb.innerHTML = `<div class="sig-label">Report Generated by:</div><div class="sig-block"><span class="sig-line"></span><div class="sig-name"></div><div class="sig-title">Physician</div></div>`;
                gb.querySelector('.sig-name').textContent = name2;
                const nameEl2 = gb.querySelector('.sig-name');
                const lineEl2 = gb.querySelector('.sig-line');
                requestAnimationFrame(() => { lineEl2.style.width = nameEl2.offsetWidth + 'px'; });
            }
        })
        .catch(() => {
            const sidebarNameEl = document.getElementById('sidebarUserName');
            if (sidebarNameEl) sidebarNameEl.textContent = 'Physician';

            const gb = document.getElementById('generated_by');
            if (gb) {
                gb.innerHTML = `<div class="sig-label">Report Generated by:</div><div class="sig-block"><span class="sig-line" style="width:180px;"></span><div class="sig-name">________________</div><div class="sig-title">Physician</div></div>`;
            }
        });
});

fetch('../php/getUserId.php')
    .then(response => response.json())
    .then(data => {
        if (data.error) window.location.href = '../auth/role';
    })
    .catch(error => {
        console.error('Error checking session:', error);
        window.location.href = '../auth/role';
    });
</script>

<script>
(function () {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');
    const MOBILE_BP = 768;

    if (!sidebar || !toggle || !overlay) return;

    function isMobile() { return window.innerWidth <= MOBILE_BP; }

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
        if (!isMobile()) closeMobileSidebar();
    });
})();

document.addEventListener('DOMContentLoaded', () => {
    const updateReferrals = document.getElementById('updateReferrals');
    const searchInput = document.getElementById('patientSearch');
    const searchButton = document.getElementById('searchButton');

    if (updateReferrals) {
        updateReferrals.addEventListener('click', function (event) {
            event.preventDefault();
            fetch('../php/update_referrals.php')
                .then(response => response.json())
                .then(data => {
                    console.log(data.message);
                    window.location.href = '../pending';
                })
                .catch(error => {
                    console.error('Error updating referrals:', error);
                    window.location.href = '../pending';
                });
        });
    }

    if (searchInput && searchButton) {
        searchInput.addEventListener('keypress', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchButton.click();
            }
        });

        searchButton.addEventListener('click', function () {
            const searchQuery = searchInput.value.trim();
            if (searchQuery) {
                sessionStorage.setItem('searchQuery', searchQuery);
                window.location.href = '../searchPatient';
            } else {
                alert('Please enter a patient name to search.');
            }
        });
    }
});
</script>
</body>
</html>