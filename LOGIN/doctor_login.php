<?php
session_start();
require '../ADMIN/php/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $remember_me = isset($_POST['remember_me']); // Check if "Remember Me" is checked

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

    // ✅ Verify the password
    if (password_verify($password, $user['password_hash'])) {
        // Store session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];

        logActivity($pdo, $user['user_id'], "Successful Login");

        // ✅ "Remember Me" functionality
        if ($remember_me) {
            setcookie("username", $username, time() + (30 * 24 * 60 * 60), "/"); // 30 days
            setcookie("role", $user['role'], time() + (30 * 24 * 60 * 60), "/");
        }

        // Redirect to doctor dashboard
        header("Location: ../RHU/dashboard.html");
        exit();
    } else {
        logActivity($pdo, $user['user_id'], "Failed Login (Incorrect Password)");
        header("Location: ../doctorlogin.html?error=Invalid password.");
        exit();
    }
}

// ✅ Function to log events
function logActivity($pdo, $user_id, $action) {
    $stmt = $pdo->prepare("INSERT INTO logs (performed_by, action, timestamp) VALUES (:user_id, :action, NOW())");
    $stmt->execute(['user_id' => $user_id, 'action' => $action]);
}
?>
