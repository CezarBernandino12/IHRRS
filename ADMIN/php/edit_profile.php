<?php
session_start();
require 'config.php';

$userId = $_SESSION['user_id'];

// Fetch current data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['full_name'];
    $contact = $_POST['contact_number'];
    $age = $_POST['age'];
    $barangay = $_POST['barangay'];

    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, contact_number = ?, age = ?, barangay = ? WHERE user_id = ?");
    $stmt->execute([$fullName, $contact, $age, $barangay, $userId]);

    header("Location: profile");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../img/logo.png">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/Profile.css">
    <link rel="stylesheet" href="../css/sidebar.css">   
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Personal Information</title>
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
                <li>
                    <a href="admin_dashboard2" data-tooltip="Dashboard">
                        <i class="bx bxs-dashboard nav-icon"></i>
                        <span class="nav-label">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="admin_approval" data-tooltip="Approval & Logs">
                        <i class="bx bxs-user nav-icon"></i>
                        <span class="nav-label">Approval & Logs</span>
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
                    <a href="../../role" class="logout" data-tooltip="Logout" onclick="return confirmLogout()">
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

    <!-- Content Section -->
    <section id="content">
        <nav>
            <button class="nav-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="bx bx-menu"></i>
            </button>

            <div class="nav-search" style="position: relative;">
                <input type="search" id="patientSearch" placeholder="Search profile..." name="search" autocomplete="off">
                <button type="button" id="searchButton" aria-label="Search">
                    <i class="bx bx-search"></i>
                </button>
                <div id="resultDropdown" class="dropdown-content"></div>
            </div>
        </nav>
        <main>
            <div class="container">
                <h2>  <i class="bx bxs-edit-alt"></i> Edit Profile</h2>
                <form method="POST" class="card">
                    <div class="row">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($user['contact_number']) ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group">
                            <label class="form-label">Age</label>
                            <input type="number" name="age" class="form-control" value="<?= htmlspecialchars($user['age']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Barangay</label>
                            <select name="barangay" class="form-select" required>
                                <?php
                                $barangays = ['Barangay 1', 'Barangay 6', 'Barangay 7', 'Barangay 8', 'Gubat', 'San Isidro', 'Cobangbang', 'Bagasbas', 'Manbalite'];
                                foreach ($barangays as $brgy) {
                                    $selected = $user['barangay'] == $brgy ? 'selected' : '';
                                    echo "<option value='$brgy' $selected>$brgy</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success">Save Changes</button>
                    <a href="profile" class="btn btn-secondary cancel-btn">Cancel</a>
                </form>
            </div>
        </main>
    </section>

    <script>
function confirmLogout() {
    return confirm("Are you sure you want to logout?");
}

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

document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('patientSearch');
  const searchButton = document.getElementById('searchButton');
  const profileCard = document.querySelector('.card');

  function filterPageContent() {
    const term = (searchInput?.value || '').toLowerCase().trim();
    if (!profileCard) return;
    profileCard.style.display = profileCard.textContent.toLowerCase().includes(term) || !term ? '' : 'none';
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
});
</script>
</body>
</html>
