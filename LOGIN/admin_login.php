<?php
session_start();
require '../ADMIN/php/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") { 
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Fetch user from database (no role filtering yet)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch();

    if (!$admin) {
        logActivity($pdo, null, "Failed Login (Username Not Found): $username");
        header("Location: ../adminlogin.php?error=Invalid credentials.");
        exit();
    }

    if ($admin['role'] !== 'admin') {
        logActivity($pdo, $admin['user_id'], "Failed Login (Unauthorized Role)");
        header("Location: ../adminlogin.php?error=Unauthorized access.");
        exit();
    }

    if ($admin['account_status'] !== 'active') {
        logActivity($pdo, $admin['user_id'], "Failed Login (Inactive Account)");
        header("Location: ../adminlogin.php?error=Your account is not approved.");
        exit();
    }

    // Check if account is locked due to too many failed attempts
    if ($admin['lock_until'] && strtotime($admin['lock_until']) > time()) {
        $remaining_time = strtotime($admin['lock_until']) - time();
        $minutes = ceil($remaining_time / 60);
        logActivity($pdo, $admin['user_id'], "Failed Login (Account Locked)");
        header("Location: ../adminlogin.php?error=Account locked due to too many failed attempts. Try again in $minutes minutes.");
        exit();
    }

    if (password_verify($password, $admin['password_hash'])) {
        // Reset failed attempts on successful login
        $resetStmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, lock_until = NULL WHERE user_id = ?");
        $resetStmt->execute([$admin['user_id']]);

        // Check if user has pending password reset request
        $resetStmt = $pdo->prepare("SELECT request_id FROM forgot_password_requests WHERE user_id = ? AND status = 'pending'");
        $resetStmt->execute([$admin['user_id']]);
        $hasPendingReset = $resetStmt->fetch();

        // Session setup
        $_SESSION['user_id'] = $admin['user_id'];
        $_SESSION['role'] = $admin['role'];
        $_SESSION['full_name'] = $admin['full_name'];

        if ($hasPendingReset) {
            $_SESSION['pending_reset'] = true;
        }

        logActivity($pdo, $admin['user_id'], "Successful Login");

        header("Location: ../ADMIN/php/admin_dashboard2.php");
        exit();
    } else {
        // Increment failed attempts
        $new_attempts = $admin['failed_attempts'] + 1;
        $lock_until = null;

        // Lock account after 5 failed attempts for 10 minutes
        if ($new_attempts >= 5) {
            $lock_until = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $updateStmt = $pdo->prepare("UPDATE users SET failed_attempts = ?, lock_until = ? WHERE user_id = ?");
            $updateStmt->execute([$new_attempts, $lock_until, $admin['user_id']]);
            logActivity($pdo, $admin['user_id'], "Account Locked (5 Failed Attempts)");
            header("Location: ../adminlogin.php?error=Account locked due to too many failed attempts. Try again in 10 minutes.");
        } else {
            $updateStmt = $pdo->prepare("UPDATE users SET failed_attempts = ? WHERE user_id = ?");
            $updateStmt->execute([$new_attempts, $admin['user_id']]);

            // Check if user has pending password reset request for failed login
            $resetStmt = $pdo->prepare("SELECT request_id FROM forgot_password_requests WHERE user_id = ? AND status = 'pending'");
            $resetStmt->execute([$admin['user_id']]);
            $hasPendingReset = $resetStmt->fetch();

            if ($hasPendingReset) {
                logActivity($pdo, $admin['user_id'], "Failed Login (Incorrect Password) - Pending Reset");
                header("Location: ../adminlogin.php?error=Password incorrect. You have a pending password reset request.");
            } else {
                logActivity($pdo, $admin['user_id'], "Failed Login (Incorrect Password)");
                header("Location: ../adminlogin.php?error=Invalid password.");
            }
        }
        exit();
    }
}

// ✅ Logger
function logActivity($pdo, $user_id, $action) {
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, performed_by, action, timestamp) VALUES (:user_id, :performed_by, :action, NOW())");
    $stmt->execute(['user_id' => $user_id, 'performed_by' => $user_id, 'action' => $action]);
}
