<?php
require_once 'config.php'; // Ensure you have the DB connection

// Function to log activity
function logActivity($pdo, $user_id, $action, $suspicious = 0) {
    $ip_address = $_SERVER['REMOTE_ADDR']; // Get user's IP
    $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $role = $stmt->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO logs (performed_by, role, action, ip_address, suspicious_flag) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $role, $action, $ip_address, $suspicious]);
}

// Function to check suspicious logins
function checkSuspiciousLoginTime($pdo, $user_id) {
    $hour = date("H"); // Get the current hour (24-hour format)

    // If login happens between 12 AM - 5 AM, flag it as suspicious
    if ($hour >= 0 && $hour <= 5) {
        logActivity($pdo, $user_id, "Unusual late-night login detected", 1);
    }
}

function checkFailedLogins($pdo, $user_id) {
    // Fetch failed login attempts in the last 15 minutes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM logs WHERE performed_by = ? AND action = 'Failed login attempt' AND timestamp >= NOW() - INTERVAL 15 MINUTE");
    $stmt->execute([$user_id]);
    $failed_attempts = $stmt->fetchColumn();

    // If 5 or more failed attempts, flag as suspicious
    if ($failed_attempts >= 5) {
        logActivity($pdo, $user_id, "Multiple failed login attempts detected", 1);
    }
}


function checkRapidActions($pdo, $user_id) {
    // Fetch actions in the last 10 seconds
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM logs WHERE performed_by = ? AND timestamp >= NOW() - INTERVAL 10 SECOND");
    $stmt->execute([$user_id]);
    $actions_count = $stmt->fetchColumn();

    // If a user performs 5+ actions in 10 seconds, flag them as suspicious
    if ($actions_count >= 5) {
        logActivity($pdo, $user_id, "Suspicious rapid actions detected", 1);
    }
}

?>
