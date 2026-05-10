<?php
require 'config.php'; // Ensure your database connection is correctly set up
session_start();

// Check if user is logged in and has BHW role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Destroy any existing session data
    session_destroy();
    // Redirect to BHW login page
    header("Location: ../../role");
    exit();
}

// Get all users with account_status = 'inactive' or 'suspended'
$stmt = $pdo->prepare("
    SELECT full_name, username, role, barangay, contact_number
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
    'nursing_attendant' => 'Nursing Attendant'
];

// Process data for display
foreach ($inactiveUsers as &$user) {
    $user['role_display'] = $roleDisplay[$user['role']] ?? ucfirst($user['role']);
}
?>

<!DOCTYPE html>
<html lang="en"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../img/logo.png">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashstyle.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/approval.css">
    <link rel="stylesheet" href="../css/logout.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Terminated Accouts</title>
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

    <h2>Terminated Accounts</h2>

    <!-- Search/Filter Section -->
    <div class="search-filter-container" style="margin-bottom: 20px;">
        <input type="text" id="searchInput" placeholder="Search by name, username, or role..." style="padding: 8px; width: 300px; border: 1px solid #ddd; border-radius: 4px;">
        <select id="roleFilter" style="padding: 8px; margin-left: 10px; border: 1px solid #ddd; border-radius: 4px;">
            <option value="">All Roles</option>
            <option value="bhw">Barangay Health Worker</option>
            <option value="doctor">Physician</option>
            <option value="nursing_attendant">Nursing Attendant</option>
        </select>
    </div>

    <table id="inactiveUsersTable">
        <thead>
            <tr>
                <th>Full Name</th>
                <th>Username</th>
                <th>Role</th>
                <th>Designated Barangay</th>
                <th>Contact Number</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($inactiveUsers as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo $user['role_display']; ?></td>
                    <td><?php echo htmlspecialchars($user['barangay'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($user['contact_number'] ?? 'N/A'); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($inactiveUsers)): ?>
                <tr><td colspan="5">No terminated users found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

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

    <script>
function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'block';
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

  // Search and filter functionality
  const searchInput = document.getElementById('searchInput');
  const navbarSearch = document.getElementById('patientSearch');
  const searchButton = document.getElementById('searchButton');
  const roleFilter = document.getElementById('roleFilter');
  const table = document.getElementById('inactiveUsersTable');
  const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

  function filterTable() {
    const searchTerm = (searchInput.value + ' ' + (navbarSearch?.value || '')).toLowerCase().trim();
    const roleValue = roleFilter.value.toLowerCase();

    for (let i = 0; i < rows.length; i++) {
      const row = rows[i];
      if (row.cells.length < 5) continue;

      const fullName = row.cells[0].textContent.toLowerCase();
      const username = row.cells[1].textContent.toLowerCase();
      const role = row.cells[2].textContent.toLowerCase();
      const barangay = row.cells[3].textContent.toLowerCase();
      const contact = row.cells[4].textContent.toLowerCase();

      const rowText = `${fullName} ${username} ${role} ${barangay} ${contact}`;
      const matchesSearch = !searchTerm || rowText.includes(searchTerm);
      const matchesRole = !roleValue || role.includes(roleValue);

      row.style.display = matchesSearch && matchesRole ? '' : 'none';
    }
  }

  searchInput.addEventListener('input', filterTable);
  roleFilter.addEventListener('change', filterTable);

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
});
</script>
</body>
</html>
