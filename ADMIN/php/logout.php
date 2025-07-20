<?php
session_start();
require 'config.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];

    // Log logout in logs
    logActivity($pdo, $user_id, "User Logged Out");

    // Insert logout log for online tracking
    $stmt = $pdo->prepare("INSERT INTO user_logs (user_id, action, log_time) VALUES (:user_id, 'logout', NOW())");
    $stmt->execute(['user_id' => $user_id]);


    //  Destroy session
    session_unset();
    session_destroy();

    // Redirect based on saved role
    $redirect = "../../role.html";

    if (isset($role)) {  // Check saved $role
        switch ($role) {
            case 'admin':
                $redirect = "../../ADMINlogin.php";
                break;
            case 'doctor':
                $redirect = "../../DOCTORlogin.html";
                break;
            case 'bhw':
                $redirect = "../../BHWlogin.html";
                break;
            case 'nursing_attendant':
                $redirect = "../../NURSINGattendant.php";
                break;
        }
    }

    header("Location: $redirect");
    exit();
} else {
    // Not logged in, just go to login page
    header("Location: ../../role.html");
    exit();
}
 
// Function for activity logs
function logActivity($pdo, $user_id, $action) {
    $stmt = $pdo->prepare("INSERT INTO logs (performed_by, action, timestamp) VALUES (:user_id, :action, NOW())");
    $stmt->execute(['user_id' => $user_id, 'action' => $action]);
}
?>
