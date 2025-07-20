<?php
require 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $admin_id = $_SESSION['user_id']; // Admin performing the action

    // Get user info before deleting
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user) {
        // Delete user
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // Log deletion
        $logStmt = $pdo->prepare("INSERT INTO logs (action, performed_by, user_affected) VALUES (?, ?, ?)");
        $logStmt->execute(["Deleted user: " . $user['full_name'], $admin_id, $user_id]);

        echo "User deleted successfully!";
    } else {
        echo "User not found!";
    }
}
?>
