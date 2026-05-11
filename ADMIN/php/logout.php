<?php
require 'config.php';

// Each role has its own session cookie name to prevent cross-role session conflicts.
// Detect which session cookie the browser sent and destroy only that session.
$roleSessions = [
    'IHRRS_ADMIN'  => 'admin',
    'IHRRS_BHW'    => 'bhw',
    'IHRRS_NURSE'  => 'nursing_attendant',
    'IHRRS_DOCTOR' => 'doctor',
];

$user_id  = null;
$role     = null;

foreach ($roleSessions as $sessionName => $roleName) {
    if (isset($_COOKIE[$sessionName])) {
        session_name($sessionName);
        session_start();

        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $role    = $_SESSION['role'] ?? $roleName;

            // Log logout in logs
            logActivity($pdo, $user_id, "User Logged Out");

            // Insert logout log for online tracking
            $stmt = $pdo->prepare("INSERT INTO user_logs (user_id, action, log_time) VALUES (:user_id, 'logout', NOW())");
            $stmt->execute(['user_id' => $user_id]);
        }

        session_unset();
        session_destroy();
        session_write_close();
        break;
    }
}

if ($user_id !== null) {
    // Redirect based on saved role
    $redirect = "../../auth/role";

    switch ($role) {
        case 'admin':
            $redirect = "../../auth/role";
            break;
        case 'doctor':
            $redirect = "../../auth/doctorlogin";
            break;
        case 'bhw':
            $redirect = "../../auth/BHWlogin";
            break;
        case 'nursing_attendant':
            $redirect = "../../auth/role";
            break;
    }

    header("Location: $redirect");
    exit();
} else {
    // No active session found — redirect to role selection
    header("Location: ../../auth/role");
    exit();
}
 
// Function for activity logs
function logActivity($pdo, $user_id, $action) {
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, performed_by, action, timestamp) VALUES (:user_id, :performed_by, :action, NOW())");
    $stmt->execute(['user_id' => $user_id, 'performed_by' => $user_id, 'action' => $action]);
}
?>
