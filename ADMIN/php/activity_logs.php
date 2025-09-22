<?php
require 'config.php'; // Ensure your database connection is correctly set up
session_start();

// Check if user is logged in and has BHW role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Destroy any existing session data
    session_destroy();
    // Redirect to BHW login page
    header("Location: ../../role.html");
    exit();
}
// Fetch activity logs with filtering
$query = "SELECT logs.*, users.full_name AS performed_by_name,
          DATE_FORMAT(logs.timestamp, '%M %e, %Y %l:%i %p') AS formatted_timestamp
          FROM logs 
          JOIN users ON logs.performed_by = users.user_id 
          WHERE 1";

$limit = 10;  // Default limit
$offset = 0;  // Default offset
$params = []; // Initialize parameters array

// Filter by User
if (!empty($_GET['user'])) {
    $query .= " AND logs.performed_by = :user";
    $params[':user'] = $_GET['user'];
}

// Filter by Action Type
if (!empty($_GET['action'])) {
    $query .= " AND logs.action LIKE :action";
    $params[':action'] = '%' . $_GET['action'] . '%';
}

// Filter by Date Range
if (!empty($_GET['from_date'])) {
    $query .= " AND DATE(logs.timestamp) >= :from_date";
    $params[':from_date'] = $_GET['from_date'];
}

if (!empty($_GET['to_date'])) {
    $query .= " AND DATE(logs.timestamp) <= :to_date";
    $params[':to_date'] = $_GET['to_date'];
}

// Append LIMIT and OFFSET correctly
$query .= " ORDER BY logs.timestamp DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

// Bind other parameters safely
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../img/logo.png">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/approval.css">
     <link rel="stylesheet" href="../css/logout.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <title>Activity Logs</title>
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
            <li class="active"> 
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

            <li>
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
            <!-- Activity logs -->
            <div class="logs-container">
                <div class="logs-header">
                    <h2>System Activity Logs</h2>
                </div>

                <form method="GET" action="" class="logs-filter-grid" id="logFilterForm">
                    <div class="form-group">
                        <label for="user">Select User</label>
                        <select name="user" id="userFilter" class="auto-submit">
                            <option value="">All Users</option>
                            <?php
                            $userStmt = $pdo->query("SELECT user_id, full_name FROM users");
                            while ($user = $userStmt->fetch(PDO::FETCH_ASSOC)) {
                                $selected = ($_GET['user'] ?? '') == $user['user_id'] ? 'selected' : '';
                                echo '<option value="' . $user['user_id'] . '" ' . $selected . '>' . htmlspecialchars($user['full_name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="action">Action Type</label>
                        <select name="action" id="action" class="auto-submit">
                            <option value="">All Actions</option>
                            <option value="Successful Login" <?= ($_GET['action'] ?? '') === 'Successful Login' ? 'selected' : '' ?>>User Login</option>
                            <option value="User Logged Out" <?= ($_GET['action'] ?? '') === 'User Logged Out' ? 'selected' : '' ?>>User Logout</option>
                            <option value="Added New User" <?= ($_GET['action'] ?? '') === 'Added New User' ? 'selected' : '' ?>>Added New User</option>
                            <option value="Terminated User" <?= ($_GET['action'] ?? '') === 'Terminated User' ? 'selected' : '' ?>>Terminated User</option>
                            <option value="Reset Password" <?= ($_GET['action'] ?? '') === 'Reset Password' ? 'selected' : '' ?>>Password Change</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="from_date">From</label>
                        <input type="text" id="from_date" name="from_date" class="flatpickr" 
                            placeholder="Select a date" value="<?= $_GET['from_date'] ?? '' ?>">
                    </div>

                    <div class="form-group">
                        <label for="to_date">To</label>
                        <input type="text" id="to_date" name="to_date" class="flatpickr" 
                            placeholder="Select a date" value="<?= $_GET['to_date'] ?? '' ?>">
                    </div>


                    <div class="form-group">
                        <label style="opacity: 0;">Filter</label>
                        <button type="submit" id="filterButton">Filter</button>
                    </div>
                </form>

                <table class="logs-table" id="logTable">
                    <thead>
                        <tr>
                            <th>SUMMARY</th>
                            <th>USER</th>
                            <th>TIMESTAMP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['action']) ?></td>
                                <td><a href="#" class="user-link" data-userid="<?= $log['performed_by'] ?>" data-action="<?= htmlspecialchars($log['action']) ?>"><?= htmlspecialchars($log['performed_by_name']) ?></a></td>
                                <td><?= htmlspecialchars($log['formatted_timestamp']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody> 
                </table>
                
                <div class="pagination-container">
    <div class="pagination-buttons">
        <button id="prevBtn" class="pagination-btn prev-btn hidden">
            <i class="bx bx-chevron-left"></i> Previous
        </button>
        <button id="loadMoreBtn" class="pagination-btn load-more-btn">
            Load More <i class="bx bx-chevron-right"></i>
        </button>
    </div>
            </div>

            <div id="modalOverlay"></div>

            <div id="userModal" class="modal-box">
            <div class="modal-header">
                    <h2>User Information and Activity</h2>
                    <span class="close-btn" onclick="closeModal()">&times;</span>
                </div>
    <div class="modal-contents">
        <div class="user-details">
            <h3>User Details</h3>
            <p><strong>Full Name:</strong> <span id="logUserFullName"></span></p>
            <p><strong>User Name:</strong> <span id="logUserName"></span></p>
            <p><strong>Status:</strong> <span id="logUserStatus"></span></p>
            <p><strong>Barangay:</strong> <span id="logUserBarangay"></span></p>
            <p><strong>Role:</strong> <span id="logUserRole"></span></p>
            </div>
            
            <div class="activity-details">
            <h3>Activity Details</h3>
            <p><strong>Action:</strong> <span id="logUserAction"></span></p>
            <p><strong>Timestamp:</strong> <span id="logUserTimestamp"></span></p>
            </div>
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

    <script src="../js/activity_logs.js"></script>

     <script>
            function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'block';
    return false; // Prevent the default link behavior
}
 
function closeModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

function proceedLogout() {
    window.location.href = 'logout.php';
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

  // keep the rest of your existing code (auth, stats, modals, etc.)
});
</script>
</body>
</html> 