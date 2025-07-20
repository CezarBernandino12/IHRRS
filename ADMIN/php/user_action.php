<?php
session_start();
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_POST['user_id'];
    $action = $_POST['action'];
    $admin_id = $_SESSION['user_id'] ?? 0; // Ensure admin is logged in

    // Get target user info for logging
    $stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    $username = $user['username'];
    $logMessage = '';
    $status = '';
    $success = false;

    if ($action == "approve") {
        $stmt = $pdo->prepare("UPDATE users SET status = 'approved' WHERE user_id = ?");
        $success = $stmt->execute([$userId]);
        $logMessage = "Approved user $username";
        $status = 'approved';
    } elseif ($action == "reject") {
        $stmt = $pdo->prepare("UPDATE users SET status = 'rejected' WHERE user_id = ?");
        $success = $stmt->execute([$userId]);
        $logMessage = "Rejected user $username";
        $status = 'rejected';
    } elseif ($action == "delete") {
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $success = $stmt->execute([$userId]);
        $logMessage = "Deleted user $username";
        $status = 'deleted';
    }

    if ($success && $action !== "delete") {
        // Insert log
        $logStmt = $pdo->prepare("INSERT INTO logs (user_id, performed_by, action, timestamp) VALUES (?, ?, ?, NOW())");
        $logInserted = $logStmt->execute([$userId, $admin_id, $logMessage]);

        if (!$logInserted) {
            $err = $logStmt->errorInfo();
            file_put_contents("debug.log", "Log insert failed: " . print_r($err, true), FILE_APPEND);
        }
    }

    echo json_encode([
        'success' => $success,
        'message' => $success ? "User $status successfully!" : "Failed to $action user."
    ]);
}
?>
