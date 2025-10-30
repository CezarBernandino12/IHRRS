<?php
session_start();
require '../ADMIN/php/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $remember_me = isset($_POST['remember_me']); // Check if "Remember Me" is checked
    $role = "bhw"; // Only allow BHW logins 

    // Fetch user from database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND role = :role");
    $stmt->execute(['username' => $username, 'role' => $role]);
    $user = $stmt->fetch();

    if ($user) {
        // ✅ Check if the account is inactive
        if ($user['account_status'] === 'inactive') {
            header("Location: ../BHWlogin.html?error=Your account is deactivated.");
            exit();
        }

        // ✅ Check if the account is still pending approval
        if ($user['account_status'] !== 'active') { 
            header("Location: ../BHWlogin.html?error=Your account is pending approval.");
            exit();
        }

        // Verify the password
        if (password_verify($password, $user['password_hash'])) {
            // ✅ Secure session handling
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

            logActivity($pdo, $user['user_id'], "Successful Login");

            // ✅ If "Remember Me" is checked, store user in cookies for 30 days
            if ($remember_me) {
                setcookie("username", $username, time() + (30 * 24 * 60 * 60), "/"); // 30 days
                setcookie("role", $user['role'], time() + (30 * 24 * 60 * 60), "/");
            }

            // ✅ Correct Redirect (Ensure BHW goes to the right page)
            header("Location: ../BHW/dashboard.html");
            exit();
        } else {
            logActivity($pdo, $user['user_id'], "Failed Login Attempt");
            header("Location: ../BHWlogin.html?error=Invalid password.");
            exit();
        }
    } else {
        header("Location: ../BHWlogin.html?error=Invalid credentials");
        exit();
    }
}

// ✅ Function to log events
function logActivity($pdo, $user_id, $action) {
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, performed_by, action, timestamp) VALUES (:user_id, :performed_by, :action, NOW())");
    $stmt->execute(['user_id' => $user_id, 'performed_by' => $user_id, 'action' => $action]);
}
?>
