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

        // Password check
        if (password_verify($password, $user['password_hash'])) {
            // Secure session
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['username'] = $user['username']; 

            // Log successful login
            logActivity($pdo, $user['user_id'], "Successful Login");
            logUserLogin($pdo, $user['user_id']); // ✅ Track online activity

            header("Location: ../BHW/dashboard.php");
            exit();
        } else {
            logActivity($pdo, $user['user_id'], "Failed Login Attempt");
            header("Location: ../BHWlogin.html?error=Invalid password.");
            exit();
        }
    } else {
        header("Location: ../BHWlogin.html?error=Invalid credentials.");
        exit();
    }
}

// ✅ Log to audit trail
function logActivity($pdo, $user_id, $action) {
    $stmt = $pdo->prepare("INSERT INTO logs (performed_by, action, timestamp) VALUES (:user_id, :action, NOW())");
    $stmt->execute(['user_id' => $user_id, 'action' => $action]);
}

// ✅ Track login for "users currently online"
function logUserLogin($pdo, $user_id) {
    $stmt = $pdo->prepare("INSERT INTO user_logs (user_id, action, log_time) VALUES (:user_id, 'login', NOW())");
    $stmt->execute(['user_id' => $user_id]);
}
?>
