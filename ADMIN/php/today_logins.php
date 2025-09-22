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

$today = date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT u.full_name, u.role, u.barangay, ul.log_time
    FROM users u
    INNER JOIN user_logs ul ON u.user_id = ul.user_id
    WHERE (u.role = 'bhw' OR u.role = 'doctor' OR u.role = 'nursing_attendant') 
      AND ul.action = 'login'
      AND DATE(ul.log_time) = :today
      AND NOT EXISTS (
          SELECT 1
          FROM user_logs ul2
          WHERE ul2.user_id = ul.user_id
            AND ul2.action = 'logout'
            AND ul2.log_time > ul.log_time
      )
    ORDER BY ul.log_time DESC
");

$stmt->execute(['today' => $today]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Log In Today</title>
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
            <li> 
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
                <a href="logout.php" class="logout" onclick="return confirmLogout()">
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

    <h2>Users Logged In Today (<?php echo $today; ?>)</h2>
    <table>
        <thead>
            <tr>
                <th>Full Name</th>
                <th>Role</th>
                <th>Designated Barangay</th>
                <th>Login Time</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><?php echo ucfirst($user['role']); ?></td>
                    <td><?php echo htmlspecialchars($user['barangay']); ?></td>
                    <td><?php echo date("h:i A", strtotime($user['log_time'])); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
                <tr><td colspan="4">No logins today.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

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
