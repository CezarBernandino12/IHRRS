<?php
session_start(); // Required to get the admin session user_id
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $admin_id = $_SESSION['user_id'] ?? 0;

    // Get the username of the user being Terminated
    $stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<script>alert('User not found!'); window.location.href='admin_user.php';</script>";
        exit;
    } 

    // Terminated user
    $stmt = $pdo->prepare("UPDATE users SET account_status = 'inactive' WHERE user_id = :user_id");
    if ($stmt->execute(['user_id' => $user_id])) {

        // Log the action
        $log = $pdo->prepare("INSERT INTO logs (user_id, performed_by, action, timestamp) VALUES (?, ?, ?, NOW())");
        $log->execute([$user_id, $admin_id, "Terminated User {$user['username']}"]);

        echo "<script>alert('User has been Terminated successfully!'); window.location.href='admin_user.php';</script>";
    } else {
        echo "<script>alert('Error deactivating user!'); window.location.href='admin_user.php';</script>";
    }
}
?>
