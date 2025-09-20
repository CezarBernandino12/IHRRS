<?php
session_start();
require_once 'config.php';


// Handle user approval/rejection AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action'];
    $admin_id = $_SESSION['user_id'] ?? 0; // Get current admin ID
    
    // Get user information before updating
    $stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Get admin name
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    $admin_name = $admin ? $admin['full_name'] : 'Unknown Admin';
    
    if ($action === 'approve') {
        $status = 'approved';
        $actionLog = "Approved user";
        
        // Create notification for approval
        notifyAdminOfUserApproval($user['username'], $admin_name);
    } elseif ($action === 'reject') {
        $status = 'rejected';
        $actionLog = "Rejected user";
        
        // Create notification for rejection
        notifyAdminOfUserRejection($user['username'], $admin_name);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }
    
    // Update user status
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
    $success = $stmt->execute([$status, $user_id]);
    
    // Log the action
    if ($success) {
        $logStmt = $pdo->prepare("INSERT INTO logs (performed_by, action, timestamp) VALUES (?, ?, NOW())");
        $logStmt->execute([$admin_id, "$actionLog {$user['username']}"]);
        
        echo json_encode(['success' => true, 'message' => 'User ' . $status . ' successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating user status']);
    }
    
    exit;
}

// Fetch only pending users
$stmt = $pdo->query("SELECT *, DATE_FORMAT(registration_date, '%Y-%m-%d %h:%i %p') AS formatted_date FROM users WHERE status = 'pending'");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Approvals</title>
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
                <a href="admin_approval.php">
                    <i class="bx bxs-user"></i>
                    <span class="text">Approval & Logs</span>
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
                <a href="../../role.html" class="logout" onclick="return confirmLogout()">
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


            <a href="profile.php" class="profile">
                <img src="../../img/profile.jpg">
            </a>
        </nav>

        <main> 

            <!-- Pending User Approvals -->
            <div class="pending-approvals-container">
            <h2>Pending User Approvals</h2>
                <table class="logs-table">
                    <tr>
                        <th>USER ID</th>
                        <th>FULL NAME</th>
                        <th>USERNAME</th>
                        <th>STATUS</th>
                        <th>ACTION</th>
                    </tr>
                    <?php foreach ($users as $user): ?>
                        <tr id="row-<?php echo $user['user_id']; ?>">
                            <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['status']); ?></td>
                            <td>
    <button class="icon-btn approve" onclick="updateUser(<?php echo $user['user_id']; ?>, 'approve')">
        <i class="bx bx-check"></i>
    </button>
    <button class="icon-btn reject" onclick="updateUser(<?php echo $user['user_id']; ?>, 'reject')">
        <i class="bx bx-x"></i>
    </button>
    <button class="icon-btn view" onclick="viewUser(<?php echo $user['user_id']; ?>)">
        <i class="bx bx-show"></i>
    </button>
</td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- Activity logs -->
            <div class="logs-container">
                <div class="logs-header">
                    <h2>System Activity Logs</h2>
                </div>

                <form method="GET" action="" class="logs-filter-grid" id="logFilterForm">
                    <div class="form-group">
                        <label for="user">Select User</label>
                        <select name="user" id="userFilter">
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
    <select name="action" id="action">
        <option value="">All Actions</option>
        <option value="User Login" <?= ($_GET['action'] ?? '') === 'User Login' ? 'selected' : '' ?>>User Login</option>
        <option value="User Logout" <?= ($_GET['action'] ?? '') === 'User Logout' ? 'selected' : '' ?>>User Logout</option>
        <option value="Added New User" <?= ($_GET['action'] ?? '') === 'Added New User' ? 'selected' : '' ?>>Added New User</option>
        <option value="Deactivated User" <?= ($_GET['action'] ?? '') === 'Deactivated User' ? 'selected' : '' ?>>Deactivated User</option>
        <option value="Reset Password" <?= ($_GET['action'] ?? '') === 'Reset Password' ? 'selected' : '' ?>>Reset Password</option>
    </select>
</div>

<div class="form-group">
    <label for="from_date">From</label>
    <input type="date" id="from_date" name="from_date" value="<?= $_GET['from_date'] ?? '' ?>">
</div>

<div class="form-group">
    <label for="to_date">To</label>
    <input type="date" id="to_date" name="to_date" value="<?= $_GET['to_date'] ?? '' ?>">
</div>

                    <div class="form-group">
                        <label style="opacity: 0;">Filter</label>
                        <button type="submit">Filter</button>
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
            </div>

<div id="modalOverlay"></div>

<div id="userModal" class="modal-box">
    <div class="modal-header">
    <h2>User Information and Activity</h2>
    <span class="close-btn" onclick="closeModal()">&times;</span>
    </div>

    <div class="modal-content">
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

        </main>
    </section>

    <button id="loadMoreBtn">Load More</button>


<script src="../js/notif.js"></script>
<script src="../js/admin_approval.js"></script>

</body>
</html>