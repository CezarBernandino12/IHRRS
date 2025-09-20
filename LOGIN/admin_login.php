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
        header("Location: ../ADMINlogin.php?error=Invalid credentials.");
        exit();
    }

    if ($admin['role'] !== 'admin') {
        logActivity($pdo, $admin['user_id'], "Failed Login (Unauthorized Role)");
        header("Location: ../ADMINlogin.php?error=Unauthorized access.");
        exit();
    }

    if ($admin['account_status'] !== 'active') {
        logActivity($pdo, $admin['user_id'], "Failed Login (Inactive Account)");
        header("Location: ../ADMINlogin.php?error=Your account is not approved.");
        exit();
    }

    if (password_verify($password, $admin['password_hash'])) {
        // Session setup
        $_SESSION['user_id'] = $admin['user_id'];
        $_SESSION['role'] = $admin['role'];
        $_SESSION['full_name'] = $admin['full_name'];

        logActivity($pdo, $admin['user_id'], "Successful Login");

        header("Location: ../ADMIN/php/admin_dashboard2.php");
        exit();
    } else {
        logActivity($pdo, $admin['user_id'], "Failed Login (Incorrect Password)");
        header("Location: ../ADMINlogin.php?error=Invalid password.");
        exit();
    }
}

// ✅ Logger
function logActivity($pdo, $user_id, $action) {
    $stmt = $pdo->prepare("INSERT INTO logs (performed_by, action, timestamp) VALUES (:user_id, :action, NOW())");
    $stmt->execute(['user_id' => $user_id, 'action' => $action]);
}
