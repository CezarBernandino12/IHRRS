<?php
require 'config.php';
session_start();


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "Access denied.";
    exit();
}

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    $admin_id = $_SESSION['user_id']; // Get the admin's ID

    // Approve the user
// Example code to use when you approve a user
$stmt = $pdo->prepare("UPDATE users SET status = 'approved', approval_date = NOW() WHERE user_id = ?");
$stmt->execute([$userId]);
        // Insert log entry
        $log_stmt = $pdo->prepare("INSERT INTO logs (user_id, action, performed_by, timestamp) 
                                   VALUES (:user_id, 'approved', :admin_id, NOW())");
        $log_stmt->execute(['user_id' => $user_id, 'admin_id' => $admin_id]);

        echo "User approved successfully!";
        header("Location: admin_approve.php");
        exit();
    } else {
        echo "Error approving user.";
}
?>

