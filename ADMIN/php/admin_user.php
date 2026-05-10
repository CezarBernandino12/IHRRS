<?php
require 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    header("Location: ../../role");
    exit();
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function userInitials($name) {
    $name = trim((string)$name);
    if ($name === '') {
        return 'U';
    }

    $parts = preg_split('/\s+/', $name);
    $first = strtoupper(substr($parts[0], 0, 1));
    $second = count($parts) > 1 ? strtoupper(substr(end($parts), 0, 1)) : '';
    return $first . $second;
}

function roleLabel($role) {
    $labels = [
        'admin' => 'Admin',
        'Admin' => 'Admin',
        'bhw' => 'BHW',
        'doctor' => 'Physician',
        'midwife' => 'Midwife',
        'nursing_attendant' => 'Nursing Attendant'
    ];

    return $labels[$role] ?? ucfirst(str_replace('_', ' ', (string)$role));
}

function pageUrl($page) {
    $params = $_GET;
    $params['page'] = max(1, (int)$page);
    return '?' . http_build_query($params) . '#users-table';
}

$search = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;

$validRoles = ['bhw', 'doctor', 'nursing_attendant', 'midwife', 'admin', 'Admin'];
if ($roleFilter !== '' && !in_array($roleFilter, $validRoles, true)) {
    $roleFilter = '';
}

$approvedTotal = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'approved'")->fetchColumn();
$activeTotal = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'approved' AND account_status = 'active'")->fetchColumn();
$terminatedTotal = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'approved' AND account_status IN ('inactive', 'suspended')")->fetchColumn();
$pendingResetTotal = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM forgot_password_requests WHERE status = 'pending'")->fetchColumn();

$countQuery = "SELECT COUNT(*) AS total
               FROM users u
               WHERE (u.full_name LIKE :search OR u.username LIKE :search)
               AND u.status = 'approved'";
$countParams = [':search' => '%' . $search . '%'];

if ($roleFilter !== '') {
    $countQuery .= " AND u.role = :role";
    $countParams[':role'] = $roleFilter;
}

$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($countParams);
$totalUsers = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = max(1, (int)ceil($totalUsers / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

$query = "SELECT u.*,
                 COALESCE(fpr.status, 'none') AS reset_status
          FROM users u
          LEFT JOIN (
              SELECT user_id, 'pending' AS status
              FROM forgot_password_requests
              WHERE status = 'pending'
              GROUP BY user_id
          ) fpr ON u.user_id = fpr.user_id
          WHERE (u.full_name LIKE :search OR u.username LIKE :search)
          AND u.status = 'approved'";
$params = [':search' => '%' . $search . '%'];

if ($roleFilter !== '') {
    $query .= " AND u.role = :role";
    $params[':role'] = $roleFilter;
}

$query .= " ORDER BY
                (COALESCE(fpr.status, 'none') = 'pending') DESC,
                u.registration_date DESC
            LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':search', $params[':search'], PDO::PARAM_STR);
if (isset($params[':role'])) {
    $stmt->bindValue(':role', $params[':role'], PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filterIsActive = $search !== '' || $roleFilter !== '';
$startRecord = $totalUsers > 0 ? $offset + 1 : 0;
$endRecord = min($offset + count($users), $totalUsers);
$visibleStartPage = max(1, min($page - 1, max(1, $totalPages - 2)));
$visibleEndPage = min($totalPages, $visibleStartPage + 2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../img/logo.png">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/logout.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/usermanagement.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>User Management</title>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

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
                <a href="activity_logs" data-tooltip="Activity Logs">
                    <i class="bx bxs-user nav-icon"></i>
                    <span class="nav-label">Activity Logs</span>
                </a>
            </li>
            <li class="active">
                <a href="admin_user" data-tooltip="User Management">
                    <i class="bx bxs-notepad nav-icon"></i>
                    <span class="nav-label">User management</span>
                </a>
            </li>
            <li>
                <a href="../reports" data-tooltip="Reports">
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

<section id="content">
    <nav>
        <button class="nav-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="bx bx-menu"></i>
        </button>

        <div class="nav-search">
            <input type="search" id="patientSearch" placeholder="Search users on this page..." name="search" autocomplete="off">
            <button type="button" id="searchButton" aria-label="Search">
                <i class="bx bx-search"></i>
            </button>
            <div id="resultDropdown" class="dropdown-content"></div>
        </div>
    </nav>

    <main>
        <div class="user-management-page">
            <section class="page-title-card">
                <div>
                    <span class="eyebrow">Admin Workspace</span>
                    <h1>User Management</h1>
                    <ul class="breadcrumb">
                        <li><a href="admin_dashboard2">Dashboard</a></li>
                        <li><i class="bx bx-chevron-right"></i></li>
                        <li><a class="active" href="admin_user">User Management</a></li>
                    </ul>
                    <p>Manage approved users, account status, password reset requests, and staff access in one place.</p>
                </div>

                <div class="header-actions">
                    <a href="activity_logs" class="secondary-action">
                        <i class="bx bx-history"></i>
                        Activity Logs
                    </a>
                    <button type="button" id="addUserBtn" class="primary-action add-user-btn">
                        <i class="bx bx-user-plus"></i>
                        Add New User
                    </button>
                </div>
            </section>

            <section class="stat-grid">
                <article class="stat-card">
                    <span class="stat-icon"><i class="bx bx-group"></i></span>
                    <div>
                        <p>Approved Users</p>
                        <h3><?php echo number_format($approvedTotal); ?></h3>
                    </div>
                </article>

                <article class="stat-card">
                    <span class="stat-icon active"><i class="bx bx-user-check"></i></span>
                    <div>
                        <p>Active Accounts</p>
                        <h3><?php echo number_format($activeTotal); ?></h3>
                    </div>
                </article>

                <article class="stat-card">
                    <span class="stat-icon danger"><i class="bx bx-user-x"></i></span>
                    <div>
                        <p>Terminated Accounts</p>
                        <h3><?php echo number_format($terminatedTotal); ?></h3>
                    </div>
                </article>

                <article class="stat-card">
                    <span class="stat-icon warning"><i class="bx bx-key"></i></span>
                    <div>
                        <p>Pending Resets</p>
                        <h3><?php echo number_format($pendingResetTotal); ?></h3>
                    </div>
                </article>
            </section>

            <section class="management-card" id="users-table">
                <div class="card-heading">
                    <div class="card-heading-left">
                        <span class="section-icon"><i class="bx bx-id-card"></i></span>
                        <div>
                            <h2>Approved User Directory</h2>
                            <p>Showing <?php echo number_format($totalUsers); ?> user<?php echo $totalUsers === 1 ? '' : 's'; ?> based on the current filters.</p>
                        </div>
                    </div>

                    <button type="button" id="toggleFilterBtn" class="filter-toggle add-filter-btn">
                        <i class="bx bx-filter-alt"></i>
                        <span class="label"><?php echo $filterIsActive ? 'Edit Filters' : 'Add Filter'; ?></span>
                    </button>
                </div>

                <form method="GET" action="" class="filter-form" id="filterForm" style="<?php echo $filterIsActive ? 'display: grid;' : 'display: none;'; ?>">
                    <div class="form-group search-field">
                        <label for="filterSearch">Search User</label>
                        <input type="text" id="filterSearch" name="search" placeholder="Search by name or username" value="<?php echo e($search); ?>">
                    </div>

                    <div class="form-group">
                        <label for="roleFilter">Role</label>
                        <select name="role" id="roleFilter">
                            <option value="">All Roles</option>
                            <option value="bhw" <?php echo $roleFilter === 'bhw' ? 'selected' : ''; ?>>BHW</option>
                            <option value="doctor" <?php echo $roleFilter === 'doctor' ? 'selected' : ''; ?>>Physician</option>
                            <option value="nursing_attendant" <?php echo $roleFilter === 'nursing_attendant' ? 'selected' : ''; ?>>Nursing Attendant</option>
                            <option value="midwife" <?php echo $roleFilter === 'midwife' ? 'selected' : ''; ?>>Midwife</option>
                            <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>

                    <input type="hidden" name="page" value="1">

                    <div class="filter-actions">
                        <button type="submit" class="apply-filter-btn">
                            <i class="bx bx-check"></i>
                            Apply
                        </button>
                        <?php if ($filterIsActive): ?>
                            <a href="admin_user" class="reset-filter-btn">
                                <i class="bx bx-refresh"></i>
                                Reset
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="table-shell">
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
                                $formattedDate = !empty($user['registration_date']) ? date("F j, Y g:i A", strtotime($user['registration_date'])) : 'N/A';
                                $payload = [
                                    'user_id' => $user['user_id'],
                                    'full_name' => $user['full_name'],
                                    'username' => $user['username'],
                                    'role' => $user['role'],
                                    'account_status' => $user['account_status'],
                                    'barangay' => $user['barangay'] ?? '',
                                    'rhu' => $user['rhu'] ?? '',
                                    'address' => $user['address'] ?? 'N/A',
                                    'age' => $user['age'] ?? '',
                                    'contact_number' => $user['contact_number'] ?? '',
                                    'registration_date' => $formattedDate
                                ];
                            ?>
                                <tr>
                                    <td>
                                        <div class="user-cell no-avatar">
                                            <strong><?php echo e($user['full_name']); ?></strong>
                                            <small>User ID: <?php echo e($user['user_id']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="plain-table-text">@<?php echo e($user['username']); ?></span>
                                    </td>
                                    <td>
                                        <span class="plain-table-text"><?php echo e(roleLabel($user['role'])); ?></span>
                                    </td>
                                    <td class="status-cell">
                                        <?php if ($user['account_status'] === 'active'): ?>
                                            <span class="status-indicator active">
                                                <i class="bx bx-check-circle"></i>
                                                Active
                                            </span>
                                        <?php else: ?>
                                            <span class="status-indicator inactive">
                                                <i class="bx bx-x-circle"></i>
                                                Account Terminated
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-group simple-actions">
                                            <button type="button" class="simple-action-btn view-user-btn" data-user='<?php echo e(json_encode($payload)); ?>'>
                                                View
                                            </button>

                                            <?php if ($user['account_status'] === 'active'): ?>
                                                <form method="POST" action="terminated_user" class="inline-action-form">
                                                    <input type="hidden" name="user_id" value="<?php echo e($user['user_id']); ?>">
                                                    <button type="button" class="simple-action-btn danger deactivate-btn" onclick="showTerminateModal(<?php echo (int)$user['user_id']; ?>)">
                                                        Terminate
                                                    </button>
                                                </form>

                                                <button type="button"
                                                    class="simple-action-btn reset-password-btn <?php echo ($user['reset_status'] === 'pending') ? 'pending-reset' : ''; ?>"
                                                    data-userid="<?php echo (int)$user['user_id']; ?>"
                                                    data-reset-status="<?php echo e($user['reset_status']); ?>"
                                                    data-contact="<?php echo e($user['contact_number'] ?? ''); ?>"
                                                    onclick="showResetPasswordModal(<?php echo (int)$user['user_id']; ?>)">
                                                    <?php echo ($user['reset_status'] === 'pending') ? 'Pending Reset' : 'Password'; ?>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="5" class="empty-table">
                                        <i class="bx bx-search-alt"></i>
                                        <strong>No users found</strong>
                                        <span>Try changing the search keyword or role filter.</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination-container">
                    <div class="pagination-info">
                        Showing <span id="startRecord"><?php echo number_format($startRecord); ?></span> to <span id="displayedUsers"><?php echo number_format($endRecord); ?></span> of <span id="totalUsers"><?php echo number_format($totalUsers); ?></span> users
                    </div>

                    <div class="pagination-buttons compact-pagination">
                        <?php if ($page > 1): ?>
                            <a href="<?php echo e(pageUrl($page - 1)); ?>" class="pagination-btn" data-preserve-scroll>Prev</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">Prev</span>
                        <?php endif; ?>

                        <?php for ($i = $visibleStartPage; $i <= $visibleEndPage; $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="page-number active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="<?php echo e(pageUrl($i)); ?>" class="page-number" data-preserve-scroll><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="<?php echo e(pageUrl($page + 1)); ?>" class="pagination-btn" data-preserve-scroll>Next</a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">Next</span>
                        <?php endif; ?>
                    </div>
                </div>

                <button id="prevBtn" type="button" hidden></button>
                <button id="loadMoreBtn" type="button" hidden></button>
            </section>
        </div>

        <div id="modalOverlay"></div>

        <div id="userModal" class="modal-box">
            <div class="modal-header">
                <h2>User Details</h2>
                <span class="close-btn" onclick="closeUserModal()">&times;</span>
            </div>

            <div class="modal-content details-modal-grid">
                <p><strong>Full Name:</strong> <span id="modalFullName"></span></p>
                <p><strong>Username:</strong> <span id="modalUsername"></span></p>
                <p><strong>Role:</strong> <span id="modalRole"></span></p>
                <p><strong>Account Status:</strong> <span id="modalStatus"></span></p>
                <p id="modalBarangayRow"><strong>Barangay:</strong> <span id="modalBarangay"></span></p>
                <p id="modalRhuRow"><strong>RHU:</strong> <span id="modalRhu"></span></p>
                <p><strong>Address:</strong> <span id="modalAddress"></span></p>
                <p><strong>Age:</strong> <span id="modalAge"></span></p>
                <p><strong>Contact Number:</strong> <span id="modalContact"></span></p>
                <p><strong>Registration Date:</strong> <span id="modalRegistrationDate"></span></p>
            </div>
        </div>

        <div id="resetPasswordModal" class="change-password-modal" style="display:none;">
            <div class="change-password-modal-content">
                <div class="change-password-modal-header">
                    <h3 id="resetModalTitle">Change User Password</h3>
                </div>
                <div class="change-password-modal-body">
                    <div id="pendingResetInfo" class="pending-reset-info" style="display:none;">
                        <h3><i class="bx bx-info-circle"></i> Pending Password Reset Request</h3>
                        <p>This user has requested a password reset. Please contact them at:</p>
                        <p class="contact-line"><i class="bx bx-phone"></i> <span id="userContactNumber"></span></p>
                    </div>

                    <form id="resetPasswordForm" method="POST" action="reset_password">
                        <input type="hidden" name="user_id" id="resetPasswordUserId">
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <div class="password-container">
                                <input type="password" name="new_password" id="newPassword" required placeholder="Enter new password">
                                <i class="bx bx-hide password-toggle" id="toggleNewPassword"></i>
                            </div>
                            <small id="passwordHelp">Password must be at least 8 characters, contains a number and a capital letter.</small>
                        </div>
                        <div class="form-group">
                            <label for="confirmNewPassword">Confirm New Password</label>
                            <div class="password-container">
                                <input type="password" name="confirm_new_password" id="confirmNewPassword" required placeholder="Re-type new password">
                                <i class="bx bx-hide password-toggle" id="toggleConfirmPassword"></i>
                            </div>
                        </div>
                        <div id="resetPasswordError" class="form-error" style="display:none;"></div>
                        <div class="change-password-modal-footer">
                            <button type="button" onclick="closeResetPasswordModal()" class="change-password-cancel-btn">Cancel</button>
                            <button type="submit" class="change-password-confirm-btn">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div id="resetSuccessModal" class="logout-modal" style="display:none;">
            <div class="logout-modal-content">
                <div class="logout-modal-header">
                    <h3>Password Reset</h3>
                </div>
                <div class="logout-modal-body">
                    <p style="color:green;">Password changed successfully!</p>
                </div>
                <div class="logout-modal-footer">
                    <button type="button" onclick="closeResetSuccessModal()" class="logout-confirm-btn">OK</button>
                </div>
            </div>
        </div>

        <div id="changePasswordConfirmModal" class="change-password-modal" style="display:none;">
            <div class="change-password-modal-content">
                <div class="change-password-modal-header">
                    <h3>Confirm Password Change</h3>
                </div>
                <div class="change-password-modal-body">
                    <p>Are you sure you want to change this user's password?</p>
                </div>
                <div class="change-password-modal-footer">
                    <button type="button" onclick="closeChangePasswordConfirmModal()" class="change-password-cancel-btn">Cancel</button>
                    <button type="button" onclick="confirmChangePassword()" class="change-password-confirm-btn">Yes, Change Password</button>
                </div>
            </div>
        </div>

        <div id="terminateModal" class="logout-modal" style="display:none;">
            <div class="logout-modal-content">
                <div class="logout-modal-header">
                    <h3>Confirm Termination</h3>
                </div>
                <div class="logout-modal-body">
                    <p>Are you sure you want to terminate this user?</p>
                </div>
                <div class="logout-modal-footer">
                    <form id="terminateForm" method="POST" action="terminated_user" style="display:inline;">
                        <input type="hidden" name="user_id" id="terminateUserId">
                        <button type="button" onclick="closeTerminateModal()" class="logout-cancel-btn">Cancel</button>
                        <button type="button" onclick="confirmTerminate()" class="logout-confirm-btn">Yes, Terminate</button>
                    </form>
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

        <div id="addUserModal" class="modal-box add-user-modal-box">
            <div class="modal-header">
                <h2>Add New User</h2>
                <span class="close-btn" onclick="closeAddUserModal()">&times;</span>
            </div>

            <div class="modal-content">
                <form id="addUserForm">
                    <div class="form-section">
                        <h3 class="section-title">Personal Information</h3>
                        <div class="form-group">
                            <label for="fullName">Full Name</label>
                            <input type="text" id="fullName" name="full_name" required>
                        </div>

                        <div class="form-group">
                            <label for="age">Age</label>
                            <input type="number" id="age" name="age" min="18" max="100">
                        </div>

                        <div class="form-group">
                            <label for="contactNumber">Mobile Number</label>
                            <input type="text" id="contactNumber" name="contact_number" pattern="\d{11}" title="Mobile number must be exactly 11 digits" maxlength="11" required>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">Account Details</h3>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required>
                        </div>

                        <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" name="role" required onchange="toggleBarangayField(); showAssignmentFields()">
                                <option value="" disabled selected>Select role</option>
                                <option value="admin">Admin</option>
                                <option value="doctor">Physician</option>
                                <option value="bhw">BHW</option>
                                <option value="nursing_attendant">Nursing Attendant</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-section" id="roleSection" style="display: none;">
                        <h3 class="section-title">Role & Assignment</h3>

                        <div class="form-group" id="rhu-group" style="display: none;">
                            <label for="rhu"><i class="bx bx-building-house"></i> Select RHU</label>
                            <select id="rhu" name="rhu" onchange="filterBarangaysByRHU()">
                                <option value="" disabled selected>Select RHU</option>
                                <option value="Rural Health Unit I">Rural Health Unit I</option>
                                <option value="Rural Health Unit II">Rural Health Unit II</option>
                                <option value="Rural Health Unit III">Rural Health Unit III</option>
                            </select>
                        </div>

                        <div class="form-group" id="licenseGroup" style="display: none;">
                            <label for="licenseNumber"><i class="bx bx-id-card"></i> Physician License Number</label>
                            <input type="text" id="licenseNumber" name="license_number" placeholder="Enter physician license number">
                        </div>

                        <div class="form-group" id="barangayGroup" style="display: none;">
                            <label for="barangayDisplay"><i class="bx bx-map-pin"></i> Designated Barangay</label>
                            <div class="combobox-container">
                                <input type="text" id="barangayDisplay" readonly placeholder="Click to select barangay" onclick="toggleBarangayDropdown()">
                                <input type="hidden" id="barangay" name="barangay" required>
                                <i class="bx bx-chevron-down combobox-arrow" onclick="toggleBarangayDropdown()"></i>
                            </div>
                            <div class="combobox-dropdown" id="barangayDropdown" style="display: none;">
                                <input type="text" id="barangaySearch" placeholder="Search barangays..." onkeyup="filterBarangayOptions()">
                                <select id="barangaySelect" size="6" onchange="selectBarangay(this)">
                                    <optgroup label="Rural Health Unit I">
                                        <option value="Barangay 2">Barangay 2</option>
                                        <option value="Barangay Pamorangon">Barangay Pamorangon</option>
                                        <option value="Barangay Mancruz">Barangay Mancruz</option>
                                        <option value="Barangay Magang">Barangay Magang</option>
                                        <option value="Barangay Calasgasan">Barangay Calasgasan</option>
                                        <option value="Barangay Bibirao">Barangay Bibirao</option>
                                        <option value="Barangay Camambugan">Barangay Camambugan</option>
                                        <option value="Barangay Alawihao">Barangay Alawihao</option>
                                        <option value="Barangay Dogongan">Barangay Dogongan</option>
                                    </optgroup>
                                    <optgroup label="Rural Health Unit II">
                                        <option value="Barangay 1">Barangay 1</option>
                                        <option value="Barangay 6">Barangay 6</option>
                                        <option value="Barangay 7">Barangay 7</option>
                                        <option value="Barangay 8">Barangay 8</option>
                                        <option value="Barangay Gubat">Barangay Gubat</option>
                                        <option value="Barangay San Isidro">Barangay San Isidro</option>
                                        <option value="Barangay Cobangbang">Barangay Cobangbang</option>
                                        <option value="Barangay Bagasbas">Barangay Bagasbas</option>
                                        <option value="Barangay Manbalite">Barangay Manbalite</option>
                                    </optgroup>
                                    <optgroup label="Rural Health Unit III">
                                        <option value="Barangay 3">Barangay 3</option>
                                        <option value="Barangay 4">Barangay 4</option>
                                        <option value="Barangay 5">Barangay 5</option>
                                        <option value="Barangay Awitan">Barangay Awitan</option>
                                        <option value="Barangay Gahonon">Barangay Gahonon</option>
                                        <option value="Barangay Borabod">Barangay Borabod</option>
                                        <option value="Barangay Lag-on">Barangay Lag-on</option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section" id="addressSection" style="display: none;">
                        <h3 class="section-title">Address Information</h3>
                        <div class="form-group">
                            <label for="address">Permanent Address</label>
                            <input type="text" id="address" name="address" style="text-transform: uppercase;" required>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">Security Information</h3>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="password-container">
                                <input type="password" id="password" name="password" required placeholder="At least 1 uppercase, 1 number, 6+ characters">
                                <i class="bx bx-hide password-toggle" id="passwordToggle"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirmPassword">Confirm Password</label>
                            <div class="password-container">
                                <input type="password" id="confirmPassword" name="confirmPassword" required placeholder="Re-type the password">
                                <i class="bx bx-hide password-toggle" id="confirmPasswordToggle"></i>
                            </div>
                        </div>
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

<script>
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
  const savedScrollY = sessionStorage.getItem("adminUserScrollY");
  if (savedScrollY !== null) {
    window.scrollTo(0, Number(savedScrollY));
    sessionStorage.removeItem("adminUserScrollY");
  }

  document.querySelectorAll("[data-preserve-scroll]").forEach(link => {
    link.addEventListener("click", () => {
      sessionStorage.setItem("adminUserScrollY", String(window.scrollY));
    });
  });

  const searchInput = document.getElementById("patientSearch");
  const searchButton = document.getElementById("searchButton");
  const rows = () => Array.from(document.querySelectorAll("#userTableBody tr"));

  function filterUserRows() {
    const term = (searchInput?.value || "").toLowerCase().trim();
    rows().forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(term) ? "" : "none";
    });
  }

  if (searchInput && searchButton) {
    searchInput.addEventListener("input", filterUserRows);
    searchInput.addEventListener("keypress", function (event) {
      if (event.key === "Enter") {
        event.preventDefault();
        filterUserRows();
      }
    });
    searchButton.addEventListener("click", filterUserRows);
  }
});
</script>

<script>
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

function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'block';
    return false;
}

function closeModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

function proceedLogout() {
    window.location.href = 'logout';
}

window.addEventListener('click', function(event) {
    const logoutModal = document.getElementById('logoutModal');
    if (event.target === logoutModal) {
        closeModal();
    }
});
</script>
<script src="../js/admin_user.js"></script>
<script>
(function () {
  const overlay = document.getElementById("modalOverlay");

  function showOverlay() {
    if (overlay) overlay.style.display = "block";
  }

  function hideOverlayIfNoModal() {
    const visibleModal = document.querySelector(
      ".modal-box[style*='block'], .change-password-modal[style*='block'], .logout-modal[style*='block']"
    );
    if (!visibleModal && overlay) overlay.style.display = "none";
  }

  function showBox(id) {
    const el = document.getElementById(id);
    if (el) {
      el.style.display = "block";
      showOverlay();
    }
  }

  function hideBox(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = "none";
    hideOverlayIfNoModal();
  }

  window.closeUserModal = function () {
    hideBox("userModal");
  };

  window.closeAddUserModal = function () {
    hideBox("addUserModal");
  };

  window.closeResetPasswordModal = function () {
    hideBox("resetPasswordModal");
    const form = document.getElementById("resetPasswordForm");
    const error = document.getElementById("resetPasswordError");
    if (form) form.reset();
    if (error) {
      error.textContent = "";
      error.style.display = "none";
    }
  };

  window.closeChangePasswordConfirmModal = function () {
    hideBox("changePasswordConfirmModal");
  };

  window.closeResetSuccessModal = function () {
    hideBox("resetSuccessModal");
    window.location.reload();
  };

  window.showTerminateModal = function (userId) {
    const input = document.getElementById("terminateUserId");
    if (input) input.value = userId;
    showBox("terminateModal");
  };

  window.closeTerminateModal = function () {
    hideBox("terminateModal");
  };

  window.confirmTerminate = function () {
    const form = document.getElementById("terminateForm");
    if (form) form.submit();
  };

  window.showResetPasswordModal = function (userId) {
    const input = document.getElementById("resetPasswordUserId");
    const pendingInfo = document.getElementById("pendingResetInfo");
    const contactEl = document.getElementById("userContactNumber");
    const title = document.getElementById("resetModalTitle");
    const btn = document.querySelector(`.reset-password-btn[data-userid="${userId}"]`);

    if (input) input.value = userId;

    const isPending = btn && btn.dataset.resetStatus === "pending";
    if (title) title.textContent = isPending ? "Complete Password Reset" : "Change User Password";
    if (pendingInfo) pendingInfo.style.display = isPending ? "block" : "none";
    if (contactEl) contactEl.textContent = (btn && btn.dataset.contact) ? btn.dataset.contact : "No contact number saved";

    showBox("resetPasswordModal");
  };

  window.confirmChangePassword = function () {
    const form = document.getElementById("resetPasswordForm");
    if (form) {
      form.dataset.confirmed = "true";
      form.submit();
    }
  };

  window.toggleBarangayDropdown = function () {
    const dropdown = document.getElementById("barangayDropdown");
    if (dropdown) dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
  };

  window.selectBarangay = function (select) {
    const hidden = document.getElementById("barangay");
    const display = document.getElementById("barangayDisplay");
    const dropdown = document.getElementById("barangayDropdown");
    if (hidden) hidden.value = select.value;
    if (display) display.value = select.value;
    if (dropdown) dropdown.style.display = "none";
  };

  window.filterBarangayOptions = function () {
    const search = (document.getElementById("barangaySearch")?.value || "").toLowerCase();
    document.querySelectorAll("#barangaySelect option").forEach(option => {
      option.style.display = option.textContent.toLowerCase().includes(search) ? "" : "none";
    });
  };

  window.filterBarangaysByRHU = function () {
    const rhu = document.getElementById("rhu")?.value || "";
    const select = document.getElementById("barangaySelect");
    if (!select) return;
    Array.from(select.querySelectorAll("optgroup")).forEach(group => {
      group.style.display = !rhu || group.label === rhu ? "" : "none";
    });
  };

  window.showAssignmentFields = function () {
    const role = document.getElementById("role")?.value || "";
    const roleSection = document.getElementById("roleSection");
    const rhuGroup = document.getElementById("rhu-group");
    const licenseGroup = document.getElementById("licenseGroup");
    const barangayGroup = document.getElementById("barangayGroup");
    const addressSection = document.getElementById("addressSection");

    const needsAssignment = ["bhw", "doctor", "nursing_attendant"].includes(role);
    if (roleSection) roleSection.style.display = needsAssignment ? "block" : "none";
    if (addressSection) addressSection.style.display = role ? "block" : "none";
    if (rhuGroup) rhuGroup.style.display = ["bhw", "doctor", "nursing_attendant"].includes(role) ? "block" : "none";
    if (licenseGroup) licenseGroup.style.display = role === "doctor" ? "block" : "none";
    if (barangayGroup) barangayGroup.style.display = role === "bhw" ? "block" : "none";

    const rhu = document.getElementById("rhu");
    const license = document.getElementById("licenseNumber");
    const barangay = document.getElementById("barangay");
    const address = document.getElementById("address");

    if (rhu) rhu.required = ["bhw", "doctor", "nursing_attendant"].includes(role);
    if (license) license.required = role === "doctor";
    if (barangay) barangay.required = role === "bhw";
    if (address) address.required = !!role;
  };

  window.toggleBarangayField = window.showAssignmentFields;

  function openUserDetails(button) {
    let data = {};
    try {
      data = JSON.parse(button.getAttribute("data-user") || "{}");
    } catch (error) {
      console.error("Invalid user data:", error);
      return;
    }

    const setText = (id, value) => {
      const el = document.getElementById(id);
      if (el) el.textContent = value || "N/A";
    };

    setText("modalFullName", data.full_name);
    setText("modalUsername", data.username);
    setText("modalRole", data.role ? data.role.replace(/_/g, " ").replace(/\b\w/g, c => c.toUpperCase()) : "N/A");
    setText("modalStatus", data.account_status);
    setText("modalBarangay", data.barangay);
    setText("modalRhu", data.rhu);
    setText("modalAddress", data.address);
    setText("modalAge", data.age);
    setText("modalContact", data.contact_number);
    setText("modalRegistrationDate", data.registration_date);

    const barangayRow = document.getElementById("modalBarangayRow");
    const rhuRow = document.getElementById("modalRhuRow");
    if (barangayRow) barangayRow.style.display = data.barangay ? "" : "none";
    if (rhuRow) rhuRow.style.display = data.rhu ? "" : "none";

    showBox("userModal");
  }

  if (typeof window.saveNewUser !== "function") {
    window.saveNewUser = function () {
      const form = document.getElementById("addUserForm");
      if (!form) return;

      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const password = document.getElementById("password")?.value || "";
      const confirm = document.getElementById("confirmPassword")?.value || "";
      if (password !== confirm) {
        alert("Passwords do not match.");
        return;
      }

      fetch("add_user", {
        method: "POST",
        body: new FormData(form)
      })
        .then(response => response.json().catch(() => ({ success: response.ok, message: response.ok ? "User saved successfully." : "Unable to save user." })))
        .then(data => {
          alert(data.message || (data.success ? "User saved successfully." : "Unable to save user."));
          if (data.success) window.location.reload();
        })
        .catch(() => alert("Unable to save user. Please check the add_user endpoint."));
    };
  }

  document.addEventListener("DOMContentLoaded", function () {
    const filterButton = document.getElementById("toggleFilterBtn");
    const filterForm = document.getElementById("filterForm");
    const addUserButton = document.getElementById("addUserBtn");
    const resetForm = document.getElementById("resetPasswordForm");

    if (filterButton && filterForm) {
      filterButton.addEventListener("click", function () {
        const shown = getComputedStyle(filterForm).display !== "none";
        filterForm.style.display = shown ? "none" : "grid";
      });
    }

    if (addUserButton) {
      addUserButton.addEventListener("click", function () {
        showBox("addUserModal");
      });
    }

    document.querySelectorAll(".view-user-btn").forEach(button => {
      button.addEventListener("click", function (event) {
        event.preventDefault();
        openUserDetails(button);
      });
    });

    document.querySelectorAll(".password-toggle").forEach(icon => {
      icon.addEventListener("click", function () {
        const input = icon.parentElement?.querySelector("input");
        if (!input) return;
        input.type = input.type === "password" ? "text" : "password";
        icon.classList.toggle("bx-hide", input.type === "password");
        icon.classList.toggle("bx-show", input.type === "text");
      });
    });

    if (resetForm) {
      resetForm.addEventListener("submit", function (event) {
        if (resetForm.dataset.confirmed === "true") return;

        const password = document.getElementById("newPassword")?.value || "";
        const confirm = document.getElementById("confirmNewPassword")?.value || "";
        const error = document.getElementById("resetPasswordError");
        const valid = password.length >= 8 && /[A-Z]/.test(password) && /\d/.test(password);

        if (!valid || password !== confirm) {
          event.preventDefault();
          if (error) {
            error.textContent = !valid
              ? "Password must be at least 8 characters and include one uppercase letter and one number."
              : "Passwords do not match.";
            error.style.display = "block";
          }
          return;
        }

        event.preventDefault();
        showBox("changePasswordConfirmModal");
      });
    }

    if (overlay) {
      overlay.addEventListener("click", function () {
        ["userModal", "addUserModal", "resetPasswordModal", "changePasswordConfirmModal"].forEach(hideBox);
      });
    }
  });
})();
</script>
</body>
</html>
