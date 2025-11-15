<?php
session_start();
require '../ADMIN/php/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Log failed login (user not found)
        logActivity($pdo, null, "Failed Login (Username Not Found): $username");
        header("Location: ../doctorlogin.html?error=Invalid credentials");
        exit();
    }

    // ✅ Check if the account is inactive
    if ($user['account_status'] === 'inactive') {
        logActivity($pdo, $user['user_id'], "Failed Login (Inactive Account)");
        header("Location: ../doctorlogin.html?error=Your account is deactivated.");
        exit();
    }

    // ✅ Ensure only doctors can log in
    if ($user['role'] !== 'doctor') {
        logActivity($pdo, $user['user_id'], "Failed Login (Unauthorized Role)");
        header("Location: ../doctorlogin.html?error=You are not authorized to access this page.");
        exit();
    }

    // Check if account is locked due to too many failed attempts
    if ($user['lock_until'] && strtotime($user['lock_until']) > time()) {
        $remaining_time = strtotime($user['lock_until']) - time();
        $minutes = ceil($remaining_time / 60);
        logActivity($pdo, $user['user_id'], "Failed Login (Account Locked)");
        header("Location: ../doctorlogin.html?error=Account locked due to too many failed attempts. Try again in $minutes minutes.");
        exit();
    }

    // ✅ Verify the password
    if (password_verify($password, $user['password_hash'])) {
        // Reset failed attempts on successful login
        $resetStmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, lock_until = NULL WHERE user_id = ?");
        $resetStmt->execute([$user['user_id']]);

        // Store session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];

        logActivity($pdo, $user['user_id'], "Successful Login");
        logUserLogin($pdo, $user['user_id']);

        // Redirect to doctor dashboard
        header("Location: ../RHUPhysician/dashboard.html");
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
            header("Location: ../doctorlogin.html?error=Account locked due to too many failed attempts. Try again in 10 minutes.");
        } else {
            $updateStmt = $pdo->prepare("UPDATE users SET failed_attempts = ? WHERE user_id = ?");
            $updateStmt->execute([$new_attempts, $user['user_id']]);
            logActivity($pdo, $user['user_id'], "Failed Login (Incorrect Password)");
            header("Location: ../doctorlogin.html?error=Invalid password.");
        }
        exit();
    }
} 

// ✅ Function to log events
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
