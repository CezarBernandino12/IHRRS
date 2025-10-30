<?php
require 'config.php';
session_start(); // Start session to track the logged-in admin

header('Content-Type: application/json'); // Ensure responses are JSON format

function notifyAdminOfPasswordReset($username, $email) {
    global $pdo;
    
    // Include notification functions if not already included
    if (!function_exists('createNotification')) {
        require_once 'notif.php';
    }
    
    $message = "Password reset request from $username ($email)";
    createNotification($pdo, $message, 'reset');
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['user_id']) || !isset($_POST['new_password'])) {
        echo json_encode(["error" => "Invalid request."]);
        exit;
    }

    $user_id = (int) $_POST['user_id']; // ✅ Ensure user_id is an integer
    $new_password = trim($_POST['new_password']);
    $admin_id = $_SESSION['user_id'] ?? null; // Get admin ID from session

    if (!$admin_id) {
        echo json_encode(["error" => "Admin session not found. Please log in again."]);
        exit;
    }

    if (empty($new_password)) {
        echo json_encode(["error" => "Password cannot be empty."]);
        exit;
    }

    // ✅ Validate password: At least 1 uppercase letter and 1 number, min 6 characters
    if (!preg_match('/^(?=.*[A-Z])(?=.*\d).{6,}$/', $new_password)) {
        echo json_encode(["error" => "Password must contain at least 1 uppercase letter and 1 number, and be at least 6 characters long."]);
        exit;
    }

    // ✅ Check if the user exists
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(["error" => "User not found."]);
        exit;
    } 

    // ✅ Hash the new password for security
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // ✅ Update the password in the `users` table
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
    $stmt->execute([$hashed_password, $user_id]);

    if ($stmt->rowCount() > 0) {
        // ✅ Mark the forgot password request as resolved
        $updateStmt = $pdo->prepare("UPDATE forgot_password_requests SET status = 'resolved' WHERE user_id = ? AND status = 'pending'");
        $updateStmt->execute([$user_id]);

        // ✅ Log the password reset action
        $logStmt = $pdo->prepare("INSERT INTO logs (user_id, performed_by, action, timestamp) VALUES (?, ?, ?, NOW())");
        $logStmt->execute([$user_id, $admin_id,  "Change password for " . $user['full_name']]);

       echo json_encode(["success" => true, "message" => "Password changed successfully!"]); 
    } else {
        echo json_encode(["error" => "Error Resetting Password."]);

    }
}
?> 
