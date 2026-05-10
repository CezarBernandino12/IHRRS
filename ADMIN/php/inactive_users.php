<?php
require 'config.php';
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    header("Location: ../../role");
    exit();
}

// Get all users with account_status = inactive or suspended
$stmt = $pdo->prepare("
    SELECT full_name, username, role, barangay, contact_number, account_status
    FROM users
    WHERE account_status = 'inactive' OR account_status = 'suspended'
    ORDER BY full_name ASC
");
$stmt->execute();
$inactiveUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Role mapping for display
$roleDisplay = [
    'bhw' => 'Barangay Health Worker',
    'doctor' => 'Physician',
    'nursing_attendant' => 'Nursing Attendant',
    'admin' => 'Administrator'
];

$totalInactiveUsers = count($inactiveUsers);
$inactiveOnlyCount = 0;
$suspendedCount = 0;
$withContactCount = 0;
$roleCounts = [
    'bhw' => 0,
    'doctor' => 0,
    'nursing_attendant' => 0,
    'admin' => 0
];

// Process data for display and summary cards
foreach ($inactiveUsers as &$user) {
    $roleKey = strtolower((string)($user['role'] ?? ''));
    $statusKey = strtolower((string)($user['account_status'] ?? ''));

    $user['role_display'] = $roleDisplay[$roleKey] ?? ucfirst((string)$user['role']);
    $user['status_display'] = $statusKey === 'suspended' ? 'Suspended' : 'Inactive';

    if ($statusKey === 'suspended') {
        $suspendedCount++;
    } else {
        $inactiveOnlyCount++;
    }

    if (!empty($user['contact_number'])) {
        $withContactCount++;
    }

    if (isset($roleCounts[$roleKey])) {
        $roleCounts[$roleKey]++;
    }
}
unset($user);
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
    <link rel="stylesheet" href="../css/inactive.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Terminated Accounts</title>
</head>

<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar Section -->
    <section id="sidebar">
        <a href="#" class="sidebar-brand">
            <img src="../../img/logo.png" alt="Admin Logo" class="brand-logo">
            <div class="brand-text">
                <span class="brand-name">Hello Admin</span>
            </div>
        </a>

        <div class="sidebar-scroll">
            <div class="sidebar-section-label">Main Menu</div>
            <ul class="side-menu top">
                <li class="active">
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
                        <span class="nav-label">User management</span>
                    </a>
                </li>
                <li>
                    <a href="admin_reports" data-tooltip="Reports">
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


    <!-- Main Content Section -->
    <section id="content">
        <nav>
            <button class="nav-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="bx bx-menu"></i>
            </button>

            <div class="nav-search" style="position: relative;">
                <input type="search" id="patientSearch" placeholder="Search terminated accounts..." name="search" autocomplete="off">
                <button type="button" id="searchButton" aria-label="Search">
                    <i class="bx bx-search"></i>
                </button>
                <div id="resultDropdown" class="dropdown-content"></div>
            </div>
        </nav>

        <main>
            <div class="pending-approvals-container">
                <div class="head-title">
                    <div class="left">
                        <span class="page-kicker"><i class="bx bx-user-x"></i> Account Monitoring</span>
                        <h1>Terminated Accounts</h1>
                        <p>Review inactive and suspended accounts, contact details, and assigned roles.</p>
                    </div>

                    <div class="page-actions">
                        <a href="admin_user" class="page-action-btn secondary-action">
                            <i class="bx bx-arrow-back"></i>
                            User Management
                        </a>
                        <a href="activity_logs" class="page-action-btn primary-action">
                            <i class="bx bx-history"></i>
                            View Logs
                        </a>
                    </div>
                </div>

                <div class="terminated-summary-grid">
                    <div class="summary-card">
                        <span class="summary-icon red-icon"><i class="bx bx-user-x"></i></span>
                        <div>
                            <p>Total Terminated</p>
                            <h3><?php echo number_format($totalInactiveUsers); ?></h3>
                        </div>
                    </div>

                    <div class="summary-card">
                        <span class="summary-icon gray-icon"><i class="bx bx-block"></i></span>
                        <div>
                            <p>Inactive</p>
                            <h3><?php echo number_format($inactiveOnlyCount); ?></h3>
                        </div>
                    </div>

                    <div class="summary-card">
                        <span class="summary-icon orange-icon"><i class="bx bx-error-circle"></i></span>
                        <div>
                            <p>Suspended</p>
                            <h3><?php echo number_format($suspendedCount); ?></h3>
                        </div>
                    </div>

                    <div class="summary-card">
                        <span class="summary-icon blue-icon"><i class="bx bx-phone"></i></span>
                        <div>
                            <p>With Contact</p>
                            <h3><?php echo number_format($withContactCount); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Search/Filter Section -->
                <div class="search-filter-container">
                    <div class="filter-header">
                        <div>
                            <h2>Account List</h2>
                            <p>Use the search and role filter to narrow terminated accounts.</p>
                        </div>
                        <span class="result-count"><span id="visibleCount"><?php echo number_format($totalInactiveUsers); ?></span> shown</span>
                    </div>

                    <div class="filter-controls">
                        <div class="filter-field search-field">
                            <label for="searchInput">Search</label>
                            <div class="input-with-icon">
                                <i class="bx bx-search"></i>
                                <input type="text" id="searchInput" placeholder="Search by name, username, barangay, or contact...">
                            </div>
                        </div>

                        <div class="filter-field role-field">
                            <label for="roleFilter">Role</label>
                            <select id="roleFilter">
                                <option value="">All Roles</option>
                                <option value="bhw">Barangay Health Worker</option>
                                <option value="doctor">Physician</option>
                                <option value="nursing_attendant">Nursing Attendant</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>

                        <button type="button" id="resetFiltersBtn" class="reset-filter-btn">
                            <i class="bx bx-refresh"></i>
                            Reset
                        </button>
                    </div>
                </div>

                <div class="table-shell">
                    <table id="inactiveUsersTable">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Designated Barangay</th>
                                <th>Contact Number</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inactiveUsers as $user): ?>
                                <?php $rawRole = strtolower((string)($user['role'] ?? '')); ?>
                                <?php $rawStatus = strtolower((string)($user['account_status'] ?? 'inactive')); ?>
                                <tr data-role="<?php echo htmlspecialchars($rawRole); ?>" data-status="<?php echo htmlspecialchars($rawStatus); ?>">
                                    <td class="name-cell">
                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['role_display']); ?></td>
                                    <td><?php echo htmlspecialchars($user['barangay'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($user['contact_number'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $rawStatus === 'suspended' ? 'suspended' : 'inactive'; ?>">
                                            <?php echo htmlspecialchars($user['status_display']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($inactiveUsers)): ?>
                                <tr class="server-empty-row"><td colspan="6">No terminated users found.</td></tr>
                            <?php endif; ?>

                            <tr id="noFilterResults" style="display:none;"><td colspan="6">No accounts match your search or selected role.</td></tr>
                        </tbody>
                    </table>
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
        </main>
    </section>

    <script>
function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'grid';
    return false;
}

function closeModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

function proceedLogout() {
    window.location.href='logout';
}

window.addEventListener('click', function(event) {
    const modal = document.getElementById('logoutModal');
    if (event.target === modal) {
        closeModal();
    }
});

(function () {
  const sidebar = document.getElementById('sidebar');
  const toggle = document.getElementById('sidebarToggle');
  const overlay = document.getElementById('sidebarOverlay');
  const MOBILE_BP = 768;

  if (!sidebar || !toggle || !overlay) return;

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
})();

document.addEventListener("DOMContentLoaded", () => {
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

  const searchInput = document.getElementById('searchInput');
  const navbarSearch = document.getElementById('patientSearch');
  const searchButton = document.getElementById('searchButton');
  const roleFilter = document.getElementById('roleFilter');
  const resetFiltersBtn = document.getElementById('resetFiltersBtn');
  const table = document.getElementById('inactiveUsersTable');
  const visibleCount = document.getElementById('visibleCount');
  const noFilterResults = document.getElementById('noFilterResults');

  if (!table) return;

  const rows = Array.from(table.querySelectorAll('tbody tr')).filter(row => {
    return !row.classList.contains('server-empty-row') && row.id !== 'noFilterResults';
  });

  function filterTable() {
    const localSearch = (searchInput?.value || '').toLowerCase().trim();
    const navSearch = (navbarSearch?.value || '').toLowerCase().trim();
    const searchTerm = `${localSearch} ${navSearch}`.trim();
    const roleValue = (roleFilter?.value || '').toLowerCase();
    let shown = 0;

    rows.forEach(row => {
      const rawRole = (row.getAttribute('data-role') || '').toLowerCase();
      const rowText = row.textContent.toLowerCase();
      const matchesSearch = !searchTerm || rowText.includes(searchTerm);
      const matchesRole = !roleValue || rawRole === roleValue;
      const isVisible = matchesSearch && matchesRole;

      row.style.display = isVisible ? '' : 'none';
      if (isVisible) shown++;
    });

    if (visibleCount) {
      visibleCount.textContent = shown.toLocaleString();
    }

    if (noFilterResults) {
      noFilterResults.style.display = rows.length > 0 && shown === 0 ? '' : 'none';
    }
  }

  if (searchInput) {
    searchInput.addEventListener('input', filterTable);
  }

  if (roleFilter) {
    roleFilter.addEventListener('change', filterTable);
  }

  if (navbarSearch && searchButton) {
    navbarSearch.addEventListener('input', filterTable);
    navbarSearch.addEventListener('keypress', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        filterTable();
      }
    });
    searchButton.addEventListener('click', filterTable);
  }

  if (resetFiltersBtn) {
    resetFiltersBtn.addEventListener('click', function () {
      if (searchInput) searchInput.value = '';
      if (navbarSearch) navbarSearch.value = '';
      if (roleFilter) roleFilter.value = '';
      filterTable();
    });
  }

  filterTable();
});
</script>
</body>
</html>
