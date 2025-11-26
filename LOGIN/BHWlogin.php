<?php
session_start();
require '../ADMIN/php/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $remember_me = isset($_POST['remember_me']);
   $role = "bhw"; // Limit to BHW role only

    // Fetch user based on username and role
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND role = :role");
    $stmt->execute(['username' => $username, 'role' => $role]);
    $user = $stmt->fetch();

    if ($user) {
        // Check if account is inactive
        if ($user['account_status'] === 'inactive') {
            header("Location: ../BHWlogin.html?error=Your account is deactivated.");
            exit();
        }

        // Check if account is still pending
        if ($user['account_status'] !== 'active') {
            header("Location: ../BHWlogin.html?error=Your account is pending approval.");
            exit();
        }

        // Check if account is locked due to too many failed attempts
        if ($user['lock_until'] && strtotime($user['lock_until']) > time()) {
            $remaining_time = strtotime($user['lock_until']) - time();
            $minutes = ceil($remaining_time / 60);
            logActivity($pdo, $user['user_id'], "Failed Login (Account Locked)");
            header("Location: ../BHWlogin.html?error=Account locked due to too many failed attempts. Try again in $minutes minutes.");
            exit();
        }

        // Password check
        if (password_verify($password, $user['password_hash'])) {
            // Reset failed attempts on successful login
            $resetStmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, lock_until = NULL WHERE user_id = ?");
            $resetStmt->execute([$user['user_id']]);

            // Check if user has pending password reset request
            $resetStmt = $pdo->prepare("SELECT request_id FROM forgot_password_requests WHERE user_id = ? AND status = 'pending'");
            $resetStmt->execute([$user['user_id']]);
            $hasPendingReset = $resetStmt->fetch();

            // Secure session
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['barangay'] = $user['barangay'];

            if ($hasPendingReset) {
                $_SESSION['pending_reset'] = true;
                echo "<script>sessionStorage.setItem('showPendingReset', 'true');</script>";
            }

            // Log successful login
            logActivity($pdo, $user['user_id'], "Successful Login");
            logUserLogin($pdo, $user['user_id']); // ✅ Track online activity

            header("Location: ../BHW/dashboard.html");
            exit();
        } else {
            // Increment failed attempts
            $new_attempts = $user['failed_attempts'] + 1;
            $lock_until = null;

            // Lock account after 5 failed attempts for 10 minutes
            if ($new_attempts >= 5) {
                $lock_until = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                $updateStmt = $pdo->prepare("UPDATE users SET failed_attempts = ?, lock_until = ? WHERE user_id = ?");
                $updateStmt->execute([$new_attempts, $lock_until, $user['user_id']]);
                logActivity($pdo, $user['user_id'], "Account Locked (5 Failed Attempts)");
                header("Location: ../BHWlogin.html?error=Account locked due to too many failed attempts. Try again in 10 minutes.");
            } else {
                $updateStmt = $pdo->prepare("UPDATE users SET failed_attempts = ? WHERE user_id = ?");
                $updateStmt->execute([$new_attempts, $user['user_id']]);

                // Check if user has pending password reset request for failed login
                $resetStmt = $pdo->prepare("SELECT request_id FROM forgot_password_requests WHERE user_id = ? AND status = 'pending'");
                $resetStmt->execute([$user['user_id']]);
                $hasPendingReset = $resetStmt->fetch();

                if ($hasPendingReset) {
                    logActivity($pdo, $user['user_id'], "Failed Login Attempt - Pending Reset");
                    header("Location: ../BHWlogin.html?error=Password incorrect. You have a pending password reset request.");
                } else {
                    logActivity($pdo, $user['user_id'], "Failed Login Attempt");
                    header("Location: ../BHWlogin.html?error=Invalid password.");
                }
            }
            exit();
        }
    } else {
        header("Location: ../BHWlogin.html?error=Invalid credentials.");
        exit();
    }
}

// ✅ Log to audit trail
function logActivity($pdo, $user_id, $action) {
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, performed_by, action, timestamp) VALUES (:user_id, :performed_by, :action, NOW())");
    $stmt->execute(['user_id' => $user_id, 'performed_by' => $user_id, 'action' => $action]);
}

// ✅ Track login for "users currently online"
function logUserLogin($pdo, $user_id) {
    $stmt = $pdo->prepare("INSERT INTO user_logs (user_id, action, log_time) VALUES (:user_id, 'login', NOW())");
    $stmt->execute(['user_id' => $user_id]);
}
?>
