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

// Get all users with account_status = 'inactive' or 'suspended'
$stmt = $pdo->prepare("
    SELECT full_name, username, role, barangay, contact_number
    FROM users
    WHERE account_status = 'inactive' OR account_status = 'suspended'
    ORDER BY full_name ASC
");
$stmt->execute();
$inactiveUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../img/logo.png">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashstyle.css">
    <link rel="stylesheet" href="../css/approval.css">
    <link rel="stylesheet" href="../css/logout.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Terminated Accouts</title>
</head>

<body>
    
    <!-- Sidebar Section -->
    <section id="sidebar">
        <a href="#" class="brand">
            <img src="../../img/logo.png" alt="RHULogo" class="logo"> 
            <span class="text">Hello Admin</span>
        </a>
        <ul class="side-menu top">
            <li class="active">
                <a href="admin_dashboard2.php">
                    <i class="bx bxs-dashboard"></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li > 
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
            </div>
            <a href="profile.php" class="profile">
            </a>
        </nav>
        <main>		
        <div class="pending-approvals-container">	

    <h2>Terminated Accounts</h2>
    <table>
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
                    <td><?php echo ucfirst($user['role']); ?></td>
                    <td><?php echo htmlspecialchars($user['barangay']); ?></td>
                    <td><?php echo htmlspecialchars($user['contact_number']); ?></td>
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
