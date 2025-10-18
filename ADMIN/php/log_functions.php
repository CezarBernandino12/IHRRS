<?php
require_once 'config.php'; // Ensure you have the DB connection

// Function to log activity
function logActivity($pdo, $user_id, $action, $suspicious = 0) {
    $stmt = $pdo->prepare("INSERT INTO logs (performed_by, action, timestamp) 
                           VALUES (?, ?, NOW())");
    $stmt->execute([$user_id, $action]);
}

// Track logins for "users currently online"
function logUserLogin($pdo, $user_id) {
    $stmt = $pdo->prepare("INSERT INTO user_logs (user_id, action, log_time) VALUES (:user_id, 'login', NOW())");
    $stmt->execute(['user_id' => $user_id]);
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