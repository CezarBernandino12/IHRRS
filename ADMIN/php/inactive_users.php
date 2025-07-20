<?php
require 'config.php';

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
                <span id="userGreeting">Hello Admin!</span>
            </div>
            <a href="profile.php" class="profile">
                <img src="../../img/profile.jpg">
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
                <tr><td colspan="5">No inactive users found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
