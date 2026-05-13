<?php
require '../../php/db_connect.php';
require_once __DIR__ . '/../php/session_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../role");
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT barangay FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
$barangayName = $user ? $user['barangay'] : 'N/A';

/* ---------- Filters ---------- */
$from_date  = $_GET['from_date']  ?? '';
$to_date    = $_GET['to_date']    ?? '';
$medicine   = isset($_GET['medicine']) ? (array)$_GET['medicine'] : [];
$bhw_id     = $_GET['bhw']        ?? '';
$sex        = $_GET['sex']        ?? '';
$age_group  = $_GET['age_group']  ?? '';

/* ---------- Query ---------- */
$sql = "SELECT m.*, v.visit_date, v.recorded_by,
               p.first_name, p.last_name, p.sex, p.age,
               u.full_name AS bhw_name
        FROM bhs_medicine_dispensed m
        JOIN patient_assessment v ON m.visit_id = v.visit_id
        JOIN patients p ON v.patient_id = p.patient_id
        LEFT JOIN users u ON v.recorded_by = u.user_id
        WHERE p.address LIKE :barangay";

$params = ['barangay' => '%' . $barangayName . '%'];

if (!empty($from_date) && !empty($to_date)) {
    $sql .= " AND DATE(m.dispensed_date) BETWEEN :from_date AND :to_date";
    $params['from_date'] = $from_date;
    $params['to_date']   = $to_date;
}

if (!empty($medicine)) {
    $placeholders = [];
    foreach ($medicine as $i => $med) {
        $ph = ":medicine_$i";
        $placeholders[] = $ph;
        $params["medicine_$i"] = $med;
    }
    $sql .= " AND m.medicine_name IN (" . implode(',', $placeholders) . ")";
}

if (!empty($bhw_id)) {
    $sql .= " AND v.recorded_by = :bhw_id";
    $params['bhw_id'] = $bhw_id;
}

if (!empty($sex)) {
    $sql .= " AND p.sex = :sex";
    $params['sex'] = $sex;
}

if (!empty($age_group)) {
    switch ($age_group) {
        case 'child':  $sql .= " AND p.age < 13"; break;
        case 'teen':   $sql .= " AND p.age BETWEEN 13 AND 19"; break;
        case 'adult':  $sql .= " AND p.age BETWEEN 20 AND 59"; break;
        case 'senior': $sql .= " AND p.age >= 60"; break;
    }
}

$sql .= " ORDER BY m.dispensed_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

/* ---------- BHW list ---------- */
$bhw_stmt = $pdo->query("SELECT user_id, full_name FROM users WHERE role = 'BHW'");
$bhws = $bhw_stmt->fetchAll();

/* ---------- Audit log ---------- */
$stmt_log = $pdo->prepare("INSERT INTO logs (user_id, action, performed_by) VALUES (:user_id, :action, :performed_by)");
$stmt_log->execute([
    ':user_id'      => $_SESSION['user_id'],
    ':action'       => "Generated BHS Medicine Dispensation Report",
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
    <title>Medicine Dispensation Report</title>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ═══ SIDEBAR ═══ -->
<section id="sidebar">
    <a href="#" class="sidebar-brand">
        <img src="../../img/logo.png" alt="RHU Logo" class="brand-logo">
        <div class="brand-text">
            <span class="brand-name">Hello BHW</span>
        </div>
    </a>

    <div class="sidebar-scroll">
        <p class="sidebar-section-label">Main Menu</p>
        <ul class="side-menu top">
            <li><a href="../dashboard" data-tooltip="Dashboard"><i class="bx bxs-dashboard nav-icon"></i><span class="nav-label">Dashboard</span></a></li>
            <li><a href="../ITR" data-tooltip="Add New ITR"><i class="bx bxs-notepad nav-icon"></i><span class="nav-label">Add New ITR</span></a></li>
            <li><a href="../searchPatient" data-tooltip="Patient Records"><i class="bx bxs-search nav-icon"></i><span class="nav-label">Patient Records</span></a></li>
            <li><a href="../History" data-tooltip="Referral History"><i class="bx bx-history nav-icon"></i><span class="nav-label">Referral History</span></a></li>
            <li class="active"><a href="../reports" data-tooltip="Reports"><i class="bx bx-notepad nav-icon"></i><span class="nav-label">Reports</span></a></li>
        </ul>

        <div class="sidebar-divider"></div>

        <ul class="side-menu">
            <li><a href="#" class="logout" data-tooltip="Logout" onclick="return confirmLogout()"><i class="bx bxs-log-out-circle nav-icon"></i><span class="nav-label">Logout</span></a></li>
        </ul>
    </div>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <img src="../../img/bhw.png" alt="BHW User">
            <div class="sidebar-user-info">
                <div class="user-name" id="sidebarUserName">BHW User</div>
                <div class="user-role">Barangay Health Worker</div>
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
    </nav>

    <main>

        <br>

        <div class="history-container">

            <!-- ─── Filter Form Card ─── -->
            <div class="filter-form">

                <h2>Medicine Dispensation Report — BHS <?php echo htmlspecialchars($barangayName); ?></h2>

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

                            if (substr($param, -2) === '[]') {
                                $base = substr($param, 0, -2);
                                if (isset($url[$base]) && is_array($url[$base])) {
                                    $url[$base] = array_values(array_diff($url[$base], [$value]));
                                    if (empty($url[$base])) unset($url[$base]);
                                }
                                $query = http_build_query($url);
                                $query = preg_replace('/%5B\d+%5D=/', '%5B%5D=', $query);
                            } else {
                                unset($url[$param]);
                                $query = http_build_query($url);
                            }

                            echo '<span class="filter-tag">';
                            echo $display;
                            echo ' <a href="?' . $query . '" title="Remove filter">&times;</a>';
                            echo '</span>';
                        }

                        if ($from_date) renderTag('From', 'from_date', $from_date);
                        if ($to_date)   renderTag('To',   'to_date',   $to_date);
                        if ($sex)        renderTag('Sex',  'sex',       $sex);
                        if ($bhw_id) {
                            $bhw_name = '';
                            foreach ($bhws as $bhw) {
                                if ($bhw['user_id'] == $bhw_id) { $bhw_name = $bhw['full_name']; break; }
                            }
                            renderTag('BHW', 'bhw', $bhw_name);
                        }
                        if ($age_group) {
                            $age_labels = ['child'=>'Child (0–12)','teen'=>'Teen (13–19)','adult'=>'Adult (20–59)','senior'=>'Senior (60+)'];
                            renderTag('Age Group', 'age_group', $age_labels[$age_group] ?? ucfirst($age_group));
                        }
                        if ($medicine) {
                            foreach ($medicine as $med) renderTag('Medicine', 'medicine[]', $med);
                        }

                        if (!$from_date && !$to_date && !$sex && !$age_group && !$medicine && !$bhw_id) {
                            echo '<span style="color:var(--grey-500);font-size:13px;">All records — no filters applied</span>';
                        }
                        ?>
                    </div>
                </div>

                <!-- ─── Filter Modal ─── -->
                <div id="filterModal" class="modal" style="display:none;">
                    <div class="modal-content" style="max-width:580px;">
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
                                        <label for="sex">Sex</label>
                                        <select name="sex" id="sex" class="form-control">
                                            <option value="" <?= $sex=='' ? 'selected':'' ?>>All</option>
                                            <option value="Male"   <?= $sex=='Male'   ? 'selected':'' ?>>Male</option>
                                            <option value="Female" <?= $sex=='Female' ? 'selected':'' ?>>Female</option>
                                        </select>
                                    </div>
                                    <div class="form-item">
                                        <label for="age_group">Age Group</label>
                                        <select name="age_group" id="age_group" class="form-control">
                                            <option value="">All</option>
                                            <option value="child"  <?= $age_group=='child'  ? 'selected':'' ?>>Child (0–12)</option>
                                            <option value="teen"   <?= $age_group=='teen'   ? 'selected':'' ?>>Teen (13–19)</option>
                                            <option value="adult"  <?= $age_group=='adult'  ? 'selected':'' ?>>Adult (20–59)</option>
                                            <option value="senior" <?= $age_group=='senior' ? 'selected':'' ?>>Senior (60+)</option>
                                        </select>
                                    </div>
                                    <div class="form-item">
                                        <label for="bhw">Dispensed By</label>
                                        <select name="bhw" id="bhw" class="form-control">
                                            <option value="">All</option>
                                            <?php foreach ($bhws as $bhw): ?>
                                                <option value="<?= $bhw['user_id'] ?>" <?= $bhw['user_id'] == $bhw_id ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($bhw['full_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-item" style="grid-column:1/-1;">
                                        <label>Given Medicine</label>
                                        <div class="medicine-checkbox-group">
                                            <?php
                                            $medicine_stmt = $pdo->prepare("SELECT value FROM custom_options WHERE category = 'medicine'");
                                            $medicine_stmt->execute();
                                            $selected_medicines = isset($_GET['medicine']) ? (array)$_GET['medicine'] : [];
                                            while ($row = $medicine_stmt->fetch()) {
                                                $val     = $row['value'];
                                                $checked = in_array($val, $selected_medicines) ? 'checked' : '';
                                                echo '<label class="medicine-checkbox-label">';
                                                echo '<input type="checkbox" name="medicine[]" value="' . htmlspecialchars($val) . '" ' . $checked . '> ';
                                                echo htmlspecialchars($val);
                                                echo '</label>';
                                            }
                                            ?>
                                        </div>
                                        <small style="color:var(--grey-500);font-size:12px;margin-top:4px;display:block;">You may select multiple medicines.</small>
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
                            <div class="ph-line-3"><?= htmlspecialchars($barangayName, ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <img src="../../img/mho_logo.png" alt="Right Logo" class="print-logo">
                    </div>
                    <hr class="print-rule">

                    <!-- Report title (print only) -->
                    <div class="title">
                        <div class="ph-line-4">MEDICINE DISPENSATION REPORT</div>
                        <div class="print-sub">
                            (<?php
                            $filters = [];
                            if ($from_date || $to_date) {
                                $readable_from = $from_date ? date("F j, Y", strtotime($from_date)) : '';
                                $readable_to   = $to_date   ? date("F j, Y", strtotime($to_date))   : '';
                                $filters[] = "<strong>" . trim($readable_from . ($readable_to ? " — " . $readable_to : '')) . "</strong>";
                            }
                            if ($medicine) {
                                $ml = is_array($medicine) ? $medicine : [$medicine];
                                $filters[] = "Medicine: <strong>" . implode(', ', array_map('htmlspecialchars', $ml)) . "</strong>";
                            }
                            if ($bhw_id) {
                                $bname = '';
                                foreach ($bhws as $b) { if ($b['user_id'] == $bhw_id) { $bname = $b['full_name']; break; } }
                                $filters[] = "Given by: <strong>" . htmlspecialchars($bname) . "</strong>";
                            }
                            if ($sex)       $filters[] = "Sex: <strong>" . htmlspecialchars($sex, ENT_QUOTES, 'UTF-8') . "</strong>";
                            if ($age_group) {
                                $age_labels = ['child'=>'Child (0–12)','teen'=>'Teen (13–19)','adult'=>'Adult (20–59)','senior'=>'Senior (60+)'];
                                $filters[] = "Age Group: <strong>" . ($age_labels[$age_group] ?? htmlspecialchars($age_group, ENT_QUOTES, 'UTF-8')) . "</strong>";
                            }
                            echo $filters ? implode(" &nbsp;|&nbsp; ", $filters) : "All Records";
                            ?>)
                        </div>
                    </div>

                    <!-- ─── Chart Controls ─── -->
                    <div class="chart chart-controls-panel">
                        <h3>Charts</h3>
                        <div class="chart-toggle-group">
                            <label><input type="checkbox" id="toggleTrendChart" checked> Dispensation Trends</label>
                            <label><input type="checkbox" id="toggleMedChart"> Medicine Totals</label>
                        </div>
                    </div>

                    <!-- ─── Charts Row ─── -->
                    <div id="medicineChartGrid" class="medicine-chart-grid single-chart">
                        <!-- Dispensation Trend Chart -->
                        <div class="chart report-chart-card line-chart-card chart-toggle-target is-visible" id="trendChartWrap">
                            <h3 class="chart-title">Medicine Dispensation Trends</h3>
                            <canvas id="dispensationChart"></canvas>
                        </div>

                        <!-- Medicine Totals Bar Chart -->
                        <div class="chart report-chart-card medium-chart-card chart-toggle-target" id="medChartWrap" style="display:none;">
                            <h3 class="chart-title">Total Quantity per Medicine</h3>
                            <canvas id="medTotalsChart"></canvas>
                        </div>
                    </div>

                    <!-- ─── Report Table ─── -->
                    <?php if ($rows): ?>
                    <div class="report-table-container">
                        <div class="report-table-scroll">
                            <table id="reportTable">
                                <thead>
                                    <tr>
                                        <th data-type="date">Dispensed Date<span class="sort-indicator"></span></th>
                                        <th data-type="string">Patient Name<span class="sort-indicator"></span></th>
                                        <th data-type="string">Sex<span class="sort-indicator"></span></th>
                                        <th data-type="number">Age<span class="sort-indicator"></span></th>
                                        <th data-type="string">Medicine Name<span class="sort-indicator"></span></th>
                                        <th data-type="number">Quantity<span class="sort-indicator"></span></th>
                                        <th data-type="date">Visit Date<span class="sort-indicator"></span></th>
                                        <th data-type="string">Dispensed By<span class="sort-indicator"></span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td data-label="Dispensed Date"><?= htmlspecialchars($row['dispensed_date']) ?></td>
                                        <td data-label="Patient Name"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                        <td data-label="Sex"><?= htmlspecialchars($row['sex']) ?></td>
                                        <td data-label="Age"><?= htmlspecialchars($row['age']) ?></td>
                                        <td data-label="Medicine Name"><?= htmlspecialchars($row['medicine_name']) ?></td>
                                        <td data-label="Quantity"><?= htmlspecialchars($row['quantity_dispensed']) ?></td>
                                        <td data-label="Visit Date"><?= htmlspecialchars($row['visit_date']) ?></td>
                                        <td data-label="Dispensed By"><?= htmlspecialchars($row['bhw_name']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <br>

                    <!-- ─── Summary Section ─── -->
                    <?php
                    /* Medicine totals */
                    $medicine_totals = [];
                    foreach ($rows as $row) {
                        $med = $row['medicine_name'];
                        $medicine_totals[$med] = ($medicine_totals[$med] ?? 0) + (int)$row['quantity_dispensed'];
                    }
                    arsort($medicine_totals);
                    $grand_total = array_sum($medicine_totals);
                    $top_medicine = key($medicine_totals);
                    ?>

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
                                        <th>Total Dispensation Records</th>
                                        <td><strong><?= count($rows) ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>Total Quantity Dispensed</th>
                                        <td><strong><?= (int)$grand_total ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>Most Dispensed Medicine</th>
                                        <td><?= htmlspecialchars($top_medicine ?: 'N/A', ENT_QUOTES, 'UTF-8') ?><?= $top_medicine ? ' (' . $medicine_totals[$top_medicine] . ' units)' : '' ?></td>
                                    </tr>
                                    <tr>
                                        <th>Quantity per Medicine</th>
                                        <td>
                                            <ul class="summary-sublist">
                                                <?php foreach ($medicine_totals as $med => $total): ?>
                                                <li><?= htmlspecialchars($med, ENT_QUOTES, 'UTF-8') ?> – <?= (int)$total ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php else: ?>
                    <div class="no-records">
                        <i class="bx bx-file-blank" style="font-size:48px;color:var(--grey-500);display:block;margin-bottom:12px;"></i>
                        No dispensation records found for the selected filters.
                    </div>
                    <?php endif; ?>

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

<!-- ═══ STYLES (medicine-dispensation-specific only) ═══ -->
<style>
/* Medicine checkbox group inside modal */
.medicine-checkbox-group {
    max-height: 160px;
    overflow-y: auto;
    border: 1.5px solid var(--border);
    border-radius: var(--r-sm);
    padding: 10px 14px;
    background: var(--grey-100);
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.medicine-checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13.5px;
    font-weight: 400;
    color: var(--grey-700);
    cursor: pointer;
    padding: 2px 0;
    text-transform: none;
    letter-spacing: 0;
}

.medicine-checkbox-label input[type="checkbox"] {
    accent-color: var(--blue);
    width: 14px;
    height: 14px;
    cursor: pointer;
    flex-shrink: 0;
}


/* ═══════════════════════════════════════════════
   UI CONSISTENCY OVERRIDES - BHW MEDICINE DISPENSATION
═══════════════════════════════════════════════ */
#content main {
    padding: 32px 28px;
    max-height: calc(100vh - 56px);
    overflow-y: auto;
}

.history-container,
.main-content,
.report-content {
    width: 100%;
}

.filter-form {
    background: var(--surface, #fff);
    border: 1px solid var(--border, #dde4ef);
    border-radius: var(--r-lg, 16px);
    padding: 28px 32px 24px;
    margin-bottom: 24px;
    box-shadow: var(--shadow-sm, 0 2px 8px rgba(13,45,82,.09));
}

.filter-form h2 {
    font-size: 17px;
    font-weight: 700;
    color: var(--navy, #0d2d52);
    letter-spacing: -.2px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-form h2::before {
    content: '';
    width: 4px;
    height: 18px;
    border-radius: 2px;
    background: var(--blue, #1c6fba);
    flex-shrink: 0;
}

.form-submit {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 0 !important;
}

.selected-filters h3 {
    font-size: 13px;
    font-weight: 600;
    color: var(--grey-700, #4a5568);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
    text-transform: uppercase;
    letter-spacing: .06em;
}

.filter-tag {
    background: var(--blue-pale, #f0f6ff) !important;
    color: var(--navy, #0d2d52) !important;
    border: 1px solid var(--border, #dde4ef) !important;
    padding: 5px 12px !important;
    border-radius: 20px !important;
    font-size: 13px !important;
    font-weight: 500 !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
}

.filter-tag a {
    color: var(--grey-500, #8c96aa) !important;
    font-weight: 700 !important;
    font-size: 14px !important;
    line-height: 1;
    margin-left: 2px !important;
    text-decoration: none !important;
}

.filter-tag a:hover { color: var(--red, #e53e3e) !important; }

.modal-content {
    width: 90%;
    max-width: 620px !important;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px 20px;
}

.form-item label {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--grey-700, #4a5568);
    text-transform: uppercase;
    letter-spacing: .05em;
}

.print-area {
    background: var(--white, #fff);
    border: 1px solid var(--border, #dde4ef);
    border-radius: var(--r-lg, 16px);
    padding: 28px 32px;
    box-shadow: var(--shadow-sm, 0 2px 8px rgba(13,45,82,.09));
}

.print-letterhead,
.print-rule,
.title { display: none; }
.title { text-align: center; }

.chart-controls-panel {
    background: var(--grey-100, #f8f9fc);
    border: 1px solid var(--border-soft, #edf0f7);
    border-radius: var(--r-md, 10px);
    padding: 16px 20px;
    margin: 0 0 24px;
}

.chart-controls-panel h3 {
    font-size: 13px;
    font-weight: 700;
    color: var(--grey-700, #4a5568);
    text-transform: uppercase;
    letter-spacing: .07em;
    margin-bottom: 12px;
}

.chart-toggle-group {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.chart-toggle-group label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 14px;
    background: var(--white, #fff);
    border: 1.5px solid var(--border, #dde4ef);
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    color: var(--grey-700, #4a5568);
    cursor: pointer;
    transition: border-color .18s, background .18s, color .18s;
    user-select: none;
}

.chart-toggle-group label:has(input:checked) {
    background: var(--navy, #0d2d52);
    border-color: var(--navy, #0d2d52);
    color: var(--white, #fff);
}

.chart-toggle-group input[type="checkbox"] {
    width: 14px;
    height: 14px;
    accent-color: var(--white, #fff);
    cursor: pointer;
}

.medicine-chart-grid {
    display: grid;
    grid-template-columns: minmax(280px, 820px);
    justify-content: center;
    gap: 24px;
    align-items: start;
    margin: 24px 0 30px;
    transition: grid-template-columns .28s ease, gap .28s ease;
}

.medicine-chart-grid.has-two-charts {
    grid-template-columns: minmax(360px, 1.2fr) minmax(320px, .8fr);
    justify-content: stretch;
}

.report-chart-card {
    width: 100%;
    max-width: none !important;
    margin: 0 !important;
    text-align: center;
    background: var(--white, #fff);
    border: 1px solid var(--border-soft, #edf0f7);
    border-radius: var(--r-lg, 16px);
    padding: 20px;
    box-shadow: var(--shadow-xs, 0 1px 3px rgba(13,45,82,.07));
}

.chart-toggle-target.is-visible {
    animation: chartFadeIn .38s ease both;
}

.line-chart-card canvas,
.medium-chart-card canvas {
    width: 100% !important;
    height: 300px !important;
    max-height: 300px;
}

.chart-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--navy, #0d2d52);
    margin-bottom: 12px;
}

.report-table-container {
    width: 100%;
    border-radius: var(--r-lg, 16px);
    border: 1px solid var(--border, #dde4ef);
    overflow: hidden;
    box-shadow: var(--shadow-md, 0 4px 20px rgba(13,45,82,.10));
    background: var(--white, #fff);
    margin: 24px 0 32px !important;
}

.report-table-scroll {
    width: 100%;
    overflow-x: auto;
    max-height: 560px;
    overflow-y: auto;
}

#reportTable {
    width: 100%;
    min-width: 1020px;
    border-collapse: collapse;
    font-size: 13.5px;
}

#reportTable thead {
    position: sticky;
    top: 0;
    z-index: 10;
}

#reportTable thead tr {
    background: linear-gradient(135deg, var(--navy, #0d2d52) 0%, var(--navy-mid, #1a4477) 100%);
}

#reportTable th {
    padding: 13px 14px;
    text-align: left;
    font-size: 11.5px;
    font-weight: 700;
    color: rgba(255,255,255,.92);
    text-transform: uppercase;
    letter-spacing: .07em;
    white-space: nowrap;
    border-right: 1px solid rgba(255,255,255,.08);
    cursor: pointer;
    user-select: none;
}

#reportTable th .sort-indicator {
    margin-left: 5px;
    font-size: 10px;
    opacity: .65;
}

#reportTable th.is-sorted-asc .sort-indicator::after { content: "▲"; }
#reportTable th.is-sorted-desc .sort-indicator::after { content: "▼"; }

#reportTable td {
    padding: 11px 14px;
    color: var(--grey-700, #4a5568);
    font-size: 13.5px;
    vertical-align: middle;
    border-right: 1px solid var(--border-soft, #edf0f7);
    border-bottom: 1px solid var(--border-soft, #edf0f7);
    white-space: nowrap;
}

#reportTable td:nth-child(2),
#reportTable td:nth-child(5),
#reportTable td:nth-child(8) {
    white-space: normal;
    word-break: break-word;
    min-width: 150px;
}

#reportTable tbody tr:nth-child(odd) { background: var(--white, #fff); }
#reportTable tbody tr:nth-child(even) { background: var(--grey-100, #f8f9fc); }
#reportTable tbody tr:hover { background: var(--blue-pale, #f0f6ff); }

.summary-container { margin-top: 32px; }
.summary-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--navy, #0d2d52);
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
}

.summary-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    border: 1px solid var(--border, #dde4ef);
    border-radius: var(--r-lg, 16px);
    overflow: hidden;
    box-shadow: var(--shadow-sm, 0 2px 8px rgba(13,45,82,.09));
    font-size: 14px;
    background: var(--white, #fff);
}

.summary-table th,
.summary-table td {
    padding: 13px 18px;
    border-bottom: 1px solid var(--border-soft, #edf0f7);
    vertical-align: top;
}

.summary-table th {
    width: 34%;
    background: var(--grey-100, #f8f9fc);
    color: var(--navy, #0d2d52);
    font-weight: 700;
    text-align: left;
    border-right: 1px solid var(--border-soft, #edf0f7);
}

.summary-table td { color: var(--grey-700, #4a5568); }
.summary-table tr:last-child th,
.summary-table tr:last-child td { border-bottom: none; }
.summary-sublist { margin: 0; padding-left: 18px; }
.summary-sublist li { margin-bottom: 4px; }

#generated_by {
    display: block;
    margin: 48px 0 0 4px;
    color: var(--dark, #0f1d31);
}
#generated_by .sig-label {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--grey-500, #8c96aa);
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 60px;
    display: block;
}
#generated_by .sig-block {
    display: inline-block;
    text-align: center;
    min-width: 200px;
}
#generated_by .sig-line {
    display: block;
    border: none;
    border-top: 1.5px solid #000;
    width: 100%;
    margin: 0 0 4px;
}
#generated_by .sig-name {
    font-weight: 700;
    font-size: 15px;
    color: var(--navy, #0d2d52);
    white-space: nowrap;
}
#generated_by .sig-title {
    font-size: 12px;
    color: var(--grey-500, #8c96aa);
    margin-top: 2px;
}

@keyframes chartFadeIn {
    from { opacity: 0; transform: translateY(10px) scale(.985); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

@media print {
    @page { size: landscape; margin: 1cm; }
    body * { visibility: hidden; }
    .print-area, .print-area * { visibility: visible; }
    .print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none;
        border: none;
        padding: 0;
        border-radius: 0;
    }
    .title { display: block !important; }
    .print-letterhead { display: grid !important; }
    .print-rule { display: block !important; }
    .chart-controls-panel,
    .form-submit,
    .selected-filters,
    nav,
    #sidebar,
    .sidebar-overlay { display: none !important; }
    .print-letterhead {
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
    .print-heading .ph-line-1 { font-size: 12pt; font-weight: 500; margin-bottom: 4px; }
    .print-heading .ph-line-2 { font-size: 14pt; font-weight: 800; margin-bottom: 4px; }
    .print-heading .ph-line-3 { font-size: 11pt; font-weight: 500; margin-bottom: 4px; }
    .ph-line-4 { font-size: 12pt; font-weight: 800; margin-top: 4px; }
    .print-sub { font-size: 10.5pt; margin-top: 4px; }
    .print-rule { height: 1px; border: 0; background: #cfd8e3; margin: 8px 0 12px; }
    .report-table-container {
        box-shadow: none;
        border: 1px solid #000;
        border-radius: 0;
        max-height: none !important;
        overflow: visible !important;
        margin-bottom: 40px !important;
    }
    .report-table-scroll { overflow: visible !important; max-height: none !important; }
    #reportTable { min-width: unset; font-size: 10pt; }
    #reportTable th,
    #reportTable td,
    .summary-table th,
    .summary-table td {
        border: 1px solid #000 !important;
        padding: 7px 10px;
        font-size: 10pt;
        color: #000 !important;
        background: transparent !important;
    }
    .summary-title { display: none !important; }
    /* Title centered */
    .title { text-align: center !important; }
    .ph-line-4, .print-sub { text-align: center; }
    /* Hide charts */
    .chart-controls-panel, .report-chart-card, .line-chart-card,
    .medicine-chart-grid, canvas { display: none !important; }
    /* Remove thead color */
    #reportTable thead tr { background: #fff !important; }
    #reportTable th { background: #fff !important; color: #000 !important; }
    /* Consistent font */
    body, table, th, td, #generated_by, .sig-label, .sig-name, .sig-title,
    .ph-line-4, .print-sub { font-family: Arial, sans-serif !important; }
    /* Signature */
    #generated_by { margin: 50mm 0 0 10mm !important; }
    #generated_by .sig-label { font-size: 11px; margin-bottom: 60px; display: block; }
    #generated_by .sig-block { display: inline-block; text-align: center; }
    #generated_by .sig-line { display: block; border: none; border-top: 1.5px solid #000; width: 100%; margin: 0 0 4px; }
    #generated_by .sig-name { font-weight: 700; font-size: 12pt; white-space: nowrap; }
    #generated_by .sig-title { font-size: 11pt; }
}

@media (max-width: 900px) {
    .medicine-chart-grid.has-two-charts,
    .medicine-chart-grid.single-chart {
        grid-template-columns: minmax(0, 1fr);
    }
}

@media (max-width: 768px) {
    #content main { padding: 20px 14px; }
    .filter-form,
    .print-area { padding: 20px 18px; }
    .form-row { grid-template-columns: 1fr; }
    .modal-content { padding: 24px 20px 20px; }
    .line-chart-card canvas,
    .medium-chart-card canvas { height: 250px !important; max-height: 250px; }

    #reportTable { min-width: unset; }
    #reportTable thead { display: none; }
    #reportTable,
    #reportTable tbody,
    #reportTable tr,
    #reportTable td { display: block; width: 100%; }
    #reportTable tr {
        margin: 0 0 12px;
        padding: 14px 14px 8px;
        border: 1px solid var(--border, #dde4ef);
        border-radius: var(--r-md, 10px);
        background: var(--white, #fff);
        box-shadow: var(--shadow-xs, 0 1px 3px rgba(13,45,82,.07));
    }
    #reportTable td {
        border: 0 !important;
        border-bottom: 1px solid var(--border-soft, #edf0f7) !important;
        padding: 9px 0;
        white-space: normal;
        font-size: 13px;
    }
    #reportTable td:last-child { border-bottom: none !important; }
    #reportTable td::before {
        content: attr(data-label);
        display: block;
        font-size: 10.5px;
        font-weight: 700;
        color: var(--grey-500, #8c96aa);
        text-transform: uppercase;
        letter-spacing: .07em;
        margin-bottom: 2px;
    }
}

</style>

<!-- ═══ SCRIPTS ═══ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
/* ─── Chart data from PHP ─── */
const rows = <?= json_encode($rows) ?>;

/* Build trend chart data */
const medicines   = [...new Set(rows.map(r => r.medicine_name))];
const allDates    = [...new Set(rows.map(r => r.dispensed_date))].sort();

const medDateMap = {};
medicines.forEach(med => {
    medDateMap[med] = {};
    allDates.forEach(d => medDateMap[med][d] = 0);
});
rows.forEach(r => {
    if (medDateMap[r.medicine_name]?.[r.dispensed_date] !== undefined) {
        medDateMap[r.medicine_name][r.dispensed_date] += Number(r.quantity_dispensed) || 1;
    }
});

const palette = ['#0d2d52','#1c6fba','#2196f3','#22a06b','#d97706','#e53e3e','#64b5f6','#4caf50','#9c27b0','#ff9800'];

const trendDatasets = medicines.map((med, i) => ({
    label: med,
    data: allDates.map(d => medDateMap[med][d]),
    borderColor: palette[i % palette.length],
    backgroundColor: palette[i % palette.length] + '22',
    fill: false,
    tension: 0.3,
    pointRadius: 4,
    pointHoverRadius: 6
}));

/* Medicine totals for bar chart */
const medTotals = <?= json_encode($medicine_totals ?? []) ?>;
const medTotalsLabels = Object.keys(medTotals);
const medTotalsData   = Object.values(medTotals);

document.addEventListener('DOMContentLoaded', () => {
    /* Trend line chart */
    const tCtx = document.getElementById('dispensationChart');
    if (tCtx && allDates.length) {
        window.dispensationChartInstance = new Chart(tCtx.getContext('2d'), {
            type: 'line',
            data: { labels: allDates, datasets: trendDatasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 700, easing: 'easeOutQuart' },
                plugins: {
                    legend: { position: 'top', labels: { font: { family: 'Plus Jakarta Sans', size: 12 } } },
                    tooltip: { bodyFont: { family: 'Plus Jakarta Sans' }, titleFont: { family: 'Plus Jakarta Sans', weight: '700' } }
                },
                scales: {
                    x: { title: { display: true, text: 'Dispensed Date', font: { family: 'Plus Jakarta Sans', size: 12 } }, grid: { display: false } },
                    y: { title: { display: true, text: 'Quantity Dispensed', font: { family: 'Plus Jakarta Sans', size: 12 } }, beginAtZero: true, grid: { color: 'rgba(0,0,0,.04)' } }
                }
            }
        });
    }

    /* Medicine totals bar chart */
    const mCtx = document.getElementById('medTotalsChart');
    if (mCtx && medTotalsLabels.length) {
        window.medTotalsChartInstance = new Chart(mCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: medTotalsLabels,
                datasets: [{ label: 'Qty Dispensed', data: medTotalsData, backgroundColor: palette, borderWidth: 0 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 700, easing: 'easeOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: { bodyFont: { family: 'Plus Jakarta Sans' }, titleFont: { family: 'Plus Jakarta Sans', weight: '700' } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } } },
                    x: { grid: { display: false }, ticks: { font: { family: 'Plus Jakarta Sans', size: 11 } } }
                }
            }
        });
    }

    /* Chart toggle controls with smoother entrance and balanced grid layout */
    const trendToggle = document.getElementById('toggleTrendChart');
    const medToggle = document.getElementById('toggleMedChart');
    const trendWrap = document.getElementById('trendChartWrap');
    const medWrap = document.getElementById('medChartWrap');
    const chartGrid = document.getElementById('medicineChartGrid');

    function showChart(el, chartInstance, show) {
        if (!el) return;
        if (show) {
            el.style.display = 'block';
            el.classList.remove('is-visible');
            requestAnimationFrame(() => el.classList.add('is-visible'));
            setTimeout(() => chartInstance?.resize?.(), 120);
        } else {
            el.classList.remove('is-visible');
            el.style.display = 'none';
        }
    }

    function syncMedicineChartLayout() {
        const showTrend = !!trendToggle?.checked;
        const showMeds = !!medToggle?.checked;
        showChart(trendWrap, window.dispensationChartInstance, showTrend);
        showChart(medWrap, window.medTotalsChartInstance, showMeds);

        const visibleCount = Number(showTrend) + Number(showMeds);
        if (chartGrid) {
            chartGrid.classList.toggle('has-two-charts', visibleCount > 1);
            chartGrid.classList.toggle('single-chart', visibleCount <= 1);
        }
    }

    trendToggle?.addEventListener('change', syncMedicineChartLayout);
    medToggle?.addEventListener('change', syncMedicineChartLayout);
    syncMedicineChartLayout();
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

    /* Default sort: Dispensed Date desc */
    const def = thead.querySelectorAll('th')[0];
    if (def) { def.classList.add('is-sorted-desc'); sortBy(0, 'desc'); }
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
function exportTableToExcel(tableID, filename = 'Medicine Dispensation Report') {
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
    const headerEl = document.querySelector('.print-letterhead, .print-header');
    const printHeader = headerEl ? headerEl.outerHTML : '';
    const area = document.querySelector('.print-area');
    if (!area) return;
    const clone = area.cloneNode(true);

    /* Remove all chart/canvas elements from the clone */
    clone.querySelectorAll('canvas, .chart-controls-panel, .report-chart-card, .line-chart-card, .medicine-chart-grid').forEach(el => el.remove());

    const headerInClone = clone.querySelector('.print-letterhead, .print-header');
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
      .report-chart-card,.line-chart-card,.medicine-chart-grid,canvas{display:none!important;}
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
        if (sn) sn.textContent = fullName || 'BHW User';
        const gb = document.getElementById('generated_by');
        if (gb) {
            const name = fullName || '________________';
            gb.innerHTML = `<div class="sig-label">Report Generated by:</div>
                <div class="sig-block">
                    <span class="sig-line"></span>
                    <div class="sig-name"></div>
                    <div class="sig-title">Barangay Health Worker</div>
                </div>`;
            gb.querySelector('.sig-name').textContent = name;
            const nameEl = gb.querySelector('.sig-name');
            const lineEl = gb.querySelector('.sig-line');
            requestAnimationFrame(() => { lineEl.style.width = nameEl.offsetWidth + 'px'; });
        }
    })
    .catch(() => {
        const sn = document.getElementById('sidebarUserName');
        if (sn) sn.textContent = 'BHW User';
        const gb = document.getElementById('generated_by');
        if (gb) {
            gb.innerHTML = `<div class="sig-label">Report Generated by:</div>
                <div class="sig-block">
                    <span class="sig-line" style="width:180px;"></span>
                    <div class="sig-name">________________</div>
                    <div class="sig-title">Barangay Health Worker</div>
                </div>`;
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
</script>

</body>
</html>