<?php
require 'config.php';
session_start(); // Start session to track the logged-in admin


$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$offset = 0; // Initial offset
$limit = 10; // Number of users to display initially

// Query to count total approved users matching the filter
$countQuery = "SELECT COUNT(*) as total FROM users u
               WHERE (u.full_name LIKE :search OR u.username LIKE :search)
               AND u.status = 'approved'";  // Add the status filter here

$countParams = [':search' => "%$search%"];

if (!empty($roleFilter)) {
    $countQuery .= " AND role = :role";
    $countParams[':role'] = $roleFilter;
}

$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($countParams);
$totalUsers = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Query to get the first batch of approved users
$query = "SELECT u.*, 
                 COALESCE(fpr.status, 'none') AS reset_status 
          FROM users u 
          LEFT JOIN forgot_password_requests fpr 
          ON u.user_id = fpr.user_id AND fpr.status = 'pending'
          WHERE (u.full_name LIKE :search OR u.username LIKE :search)
          AND u.status = 'approved'";  // Add the status filter here

$params = [':search' => "%$search%"];

if (!empty($roleFilter)) {
    $query .= " AND role = :role";
    $params[':role'] = $roleFilter;
}

$query .= " ORDER BY 
    (COALESCE(fpr.status, 'none') = 'pending') DESC, 
    u.registration_date DESC 
    LIMIT :limit OFFSET :offset";$params[':limit'] = $limit;
$params[':offset'] = $offset;

$stmt = $pdo->prepare($query);
// PDO needs to explicitly identify integer parameters for LIMIT and OFFSET
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => &$val) {
    if ($key != ':limit' && $key != ':offset') {
        $stmt->bindParam($key, $val);
    }
}
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../img/logo.png">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/usermanagement.css">
     
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Admin Dashboard</title>
    
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
            <li>
                <a href="activity_logs.php">
                    <i class="bx bxs-user"></i>
                    <span class="text">Activity Logs</span>
                </a>
            </li>
            <li class="active">
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

        
            <div class="container">
            <h2 class="management-title">User Management</h2>

            

                <button type="button" id="toggleFilterBtn" class="add-filter-btn">
                    <span class="icon">+</span>
                    <span class="label">Add a filter</span>
                </button>

                 <!-- Add the new "Add User" button -->
                <button type="button" id="addUserBtn" class="add-user-btn">
                    <span class="icon">+</span>
                    <span class="label">Add New User</span>
                </button>

                <form method="GET" action="" class="filter-form" id="filterForm" style="<?php echo (!empty($search) || !empty($roleFilter)) ? 'display: flex;' : 'display: none;'; ?>">
                    <input type="text" name="search" placeholder="Search by name or username" value="<?php echo htmlspecialchars($search); ?>">

                    <select name="role" id="roleFilter">
                        <option value="">All Roles</option>
                        <option value="bhw" <?php echo $roleFilter == 'bhw' ? 'selected' : ''; ?>>BHW</option>
                        <option value="doctor" <?php echo $roleFilter == 'doctor' ? 'selected' : ''; ?>>Physician</option>
                        <option value="nursing_attendant" <?php echo $roleFilter == 'nursing_attendant' ? 'selected' : ''; ?>>Nursing Attendant</option>
                    </select>
                </form>

                

                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Account Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    
                    <tbody id="userTableBody">

                    <?php foreach ($users as $user):  
                        $formattedDate = date("F j, Y g:i A", strtotime($user['registration_date'])); // ✅ Convert to 12-hour format
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($user['role'])); ?></td>
                            <td class="status-cell">
                                <?php if ($user['account_status'] === 'active'): ?>
                                    <span class="status-indicator active">
                                    <span class="status-indicator active">Active</span>
                                    </span>
                                <?php else: ?>
                                    <span class="status-indicator inactive">
                                    <span class="status-indicator inactive">Account Terminated</span>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="display: flex; gap: 5px;">
                                <button class="view-user-btn" 
                                    data-user='<?php echo json_encode([
                                        'user_id' => $user['user_id'],
                                        'full_name' => $user['full_name'],
                                        'username' => $user['username'],
                                        'role' => $user['role'],
                                        'account_status' => $user['account_status'],
                                        'barangay' => $user['barangay'],
                                        'address' => $user['address'] ?? 'N/A',
                                        'age' => $user['age'],
                                        'contact_number' => $user['contact_number'],
                                        'registration_date' => $formattedDate // ✅ Correct format
                                    ]); ?>'>View</button>

                                                            <!-- Only show if active -->
    <?php if ($user['account_status'] === 'active'): ?>
        <form method="POST" action="terminated_user.php">
            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
<button type="button" class="deactivate-btn" onclick="showTerminateModal(<?php echo $user['user_id']; ?>)">Terminate</button>
        </form>

        <button class="reset-password-btn <?php echo ($user['reset_status'] == 'pending') ? 'pending-reset' : ''; ?>"
    onclick="showResetPasswordModal(<?php echo $user['user_id']; ?>)">
    <?php echo ($user['reset_status'] == 'pending') ? 'Pending Reset' : 'Change Password'; ?>
</button>
    <?php endif; ?>
</td>

                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                
<tfoot>
    <tr>
        <td colspan="5" class="pagination-container">
            <div class="pagination-controls">
                <button id="prevBtn" class="pagination-btn prev-btn" disabled>
                    <i class='bx bx-chevron-left'></i> Previous
                </button>

                <button id="nextBtn" class="pagination-btn next-btn" <?php echo ($totalUsers <= $limit) ? 'disabled' : ''; ?>>
                    Load More <i class='bx bx-chevron-right'></i>
                </button>
            </div>
        </td>
    </tr>
</tfoot>
            </div>

            

            <div id="modalOverlay"></div>

            <div id="userModal" class="modal-box">
                <div class="modal-header">
                    <h2>User Details</h2>
                    <span class="close-btn" onclick="closeUserModal()">&times;</span>
                </div>

                <div class="modal-content">
                    <p><strong>Full Name:</strong> <span id="modalFullName"></span></p>
                    <p><strong>Username:</strong> <span id="modalUsername"></span></p>
                    <p><strong>Role:</strong> <span id="modalRole"></span></p>
                    <p><strong>Account Status:</strong> <span id="modalStatus"></span></p>
                    <p id="modalBarangayRow"><strong>Barangay:</strong> <span id="modalBarangay"></span></p>
                    <p><strong>Address:</strong> <span id="modalAddress"></span></p>
                    <p><strong>Age:</strong> <span id="modalAge"></span></p>
                    <p><strong>Contact Number:</strong> <span id="modalContact"></span></p>
                    <p><strong>Registration Date:</strong> <span id="modalRegistrationDate"></span></p>
                </div>
            </div>


<!-- Reset/Change Password Modal -->
<div id="resetPasswordModal" class="modal-box" style="display:none;">
  <div class="modal-header">
    <h2>Change User Password</h2>
    <span class="close-btn" onclick="closeResetPasswordModal()">&times;</span>
  </div>
  <div class="modal-content">
    <form id="resetPasswordForm" method="POST" action="reset_password.php">
      <input type="hidden" name="user_id" id="resetPasswordUserId">
      <div class="form-group">
        <label for="newPassword">New Password</label>
        <div class="password-container">
          <input type="password" name="new_password" id="newPassword" required placeholder="Enter new password">
          <i class="bx bx-hide password-toggle" id="toggleNewPassword" style="cursor:pointer; position:absolute; right:10px; top:50%; transform:translateY(-50%);"></i>
        </div>
        <small id="passwordHelp" style="display:block; color:#888; margin-top:5px;">
          Password must be at least 8 characters, contain a number and a capital letter.
        </small>
      </div>
      <div class="form-group">
        <label for="confirmNewPassword">Confirm New Password</label>
        <div class="password-container">
          <input type="password" name="confirm_new_password" id="confirmNewPassword" required placeholder="Re-type new password">
          <i class="bx bx-hide password-toggle" id="toggleConfirmPassword" style="cursor:pointer; position:absolute; right:10px; top:50%; transform:translateY(-50%);"></i>
        </div>
      </div>
      <div id="resetPasswordError" style="color:red; margin-bottom:10px; display:none;"></div>
      <div class="modal-footer">
        <button type="button" class="cancel-btn" onclick="closeResetPasswordModal()">Cancel</button>
        <button type="submit" class="save-btn">Change Password</button>
      </div>
    </form>
  </div>
</div>

<!-- Success Modal -->
<div id="resetSuccessModal" class="modal-box" style="display:none;">
  <div class="modal-header">
    <h2>Password Reset</h2>
    <span class="close-btn" onclick="closeResetSuccessModal()">&times;</span>
  </div>
  <div class="modal-content">
    <p style="color:green;">Password Change successfully!</p>
    <div class="modal-footer">
      <button type="button" class="save-btn" onclick="closeResetSuccessModal()">OK</button>
    </div>
  </div>
</div>
            <!-- Terminate User Confirmation Modal -->
<div id="terminateModal" class="modal-box" style="display:none;">
  <div class="modal-header">
    <h2>Terminate User</h2>
    <span class="close-btn" onclick="closeTerminateModal()">&times;</span>
  </div>
  <div class="modal-content">
    <p>Are you sure you want to terminate this user?</p>
  </div>
  <div class="modal-footer">
    <form id="terminateForm" method="POST" action="terminated_user.php" style="display:inline;">
      <input type="hidden" name="user_id" id="terminateUserId">
      <button type="button" class="cancel-btn" onclick="closeTerminateModal()">Cancel</button>
      <button type="submit" class="deactivate-btn">Terminate</button>
    </form>
  </div>
</div>

<div id="logoutModal" class="logout-modal" style="display:none;">
    <div class="logout-modal-content">
        <div class="logout-modal-header">
            <h3>Confirm Logout</h3>
        </div>
        <div class="logout-modal-body">
            <p>Are you sure you want to logout?</p>
        </div>
        <div class="logout-modal-footer">
            <button onclick="closeModal()" class="logout-btn yes">Cancel</button>
            <button onclick="proceedLogout()" class="logout-btn no">Yes, Logout</button>
        </div>
    </div>
</div>
            

            <div id="addUserModal" class="modal-box">
    <div class="modal-header">
        <h2>Add New User</h2>
        <span class="close-btn" onclick="closeAddUserModal()">&times;</span>
    </div>

    <div class="modal-content">
        <form id="addUserForm">
            <div class="form-group">
                <label for="fullName">FULL NAME</label>
                <input type="text" id="fullName" name="full_name" required>
            </div>
            
            <div class="form-group">
                <label for="username">USERNAME</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="age">AGE</label>
                <input type="number" id="age" name="age" min="18" max="100">
            </div>
            
            
            <div class="form-group">
                <label for="password">PASSWORD</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required placeholder="At least 1 uppercase & 1 number">
                    <i class="bx bx-hide password-toggle" id="passwordToggle"></i>
                </div>
            </div>
 
            <div class="form-group">
    <label for="confirmPassword">CONFIRM PASSWORD:</label>
    <div class="password-container">
        <input type="password" id="confirmPassword" name="confirmPassword" required placeholder="Re-type the password">
        <i class="bx bx-hide password-toggle" id="confirmPasswordToggle"></i>
    </div>
</div>

            
            <div class="form-group">
                <label for="role">ROLE</label>
                <select id="role" name="role" required onchange="toggleBarangayField()">
                <option value="" disabled selected>SELECT ROLE</option>
    <option value="admin">Admin</option>
    <option value="doctor">Physician</option>
    <option value="bhw">BHW</option>
    <option value="nursing_attendant">Nursing Attendant</option>
</select>

<!-- RHU dropdown (initially hidden) -->
<div id="rhu-group" style="display: none; margin-top: 10px;">
  <label for="rhu">Select RHU:</label>
  <select id="rhu" name="rhu">
    <option value="" disabled selected>Select RHU</option>
    <option value="RHU I">RHU I</option>
    <option value="RHU II">RHU II</option>
    <option value="RHU III">RHU III</option>
  </select>
</div>
            
            <div class="form-group" id="barangayGroup">
    <label for="barangay">DESIGNATED BARANGAY</label>
    <select id="barangay" name="barangay" required>
                    <option value="" disabled selected>Select Barangay</option>
                    <option value="Barangay 1">Barangay 1</option>
                    <option value="Barangay 6">Barangay 6</option>
                    <option value="Barangay 7">Barangay 7</option>
                    <option value="Barangay 8">Barangay 8</option>
                    <option value="Barangay Gubat">Barangay Gubat</option>
                    <option value="Barangay San Isidro">Barangay San Isidro</option>
                    <option value="Barangay Cobangbang">Barangay Cobangbang</option>
                    <option value="Barangay Bagasbas">Barangay Bagasbas</option>
                    <option value="Barangay Manbalite">Barangay Manbalite</option>
                    </select>
                    </div>
            
            <div class="form-group">
                <label for="address">PERMANENT ADDRESS</label>
                <input type="text" name="address" style="text-transform: uppercase;" required>
            </div>
                        
            <div class="form-group">
            <label for="contactNumber">MOBILE NUMBER</label>
            <input type="text" id="contactNumber" name="contact_number" 
           pattern="\d{11}" 
           title="Mobile number must be exactly 11 digits" 
           maxlength="11" 
           required>
</div>

        </form> 
    </div>
    
    <div class="modal-footer">
        <button type="button" class="cancel-btn" onclick="closeAddUserModal()">Cancel</button>
        <button type="button" class="save-btn" onclick="saveNewUser()">Save User</button>
    </div>
</div>  

</main>
    </section>

    <script src="../js/admin_user.js"></script>
</body>
</html> 