<?php
require 'config.php';
require_once __DIR__ . '/session_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => "Invalid request."]);
    exit;
}

$admin_id = $_SESSION['user_id'] ?? null;
if (!$admin_id) {
    echo json_encode(["error" => "Admin session not found. Please log in again."]);
    exit;
}

$user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
if ($user_id <= 0) {
    echo json_encode(["error" => "Invalid user ID."]);
    exit;
}

// Verify a pending request exists for this user
$stmt = $pdo->prepare(
    "SELECT request_id FROM forgot_password_requests
     WHERE user_id = ? AND status = 'pending'"
);
$stmt->execute([$user_id]);
$request = $stmt->fetch();

if (!$request) {
    echo json_encode(["error" => "No pending reset request found for this user."]);
    exit;
}

// Generate a secure, single-use token (raw = 64 hex chars, stored as SHA-256 hash)
$raw_token    = bin2hex(random_bytes(32));
$hashed_token = hash('sha256', $raw_token);
$expires_at   = date('Y-m-d H:i:s', strtotime('+24 hours'));

// Mark request as approved and store the hashed token with expiry
$stmt = $pdo->prepare(
    "UPDATE forgot_password_requests
     SET status = 'approved', reset_token = ?, token_expires_at = ?, approved_by = ?
     WHERE user_id = ? AND status = 'pending'"
);
$stmt->execute([$hashed_token, $expires_at, $admin_id, $user_id]);

if ($stmt->rowCount() === 0) {
    echo json_encode(["error" => "Failed to approve the reset request. It may have already been processed."]);
    exit;
}

// Log the approval action
$pdo->prepare(
    "INSERT INTO logs (user_id, performed_by, action, timestamp)
     VALUES (?, ?, 'Approved password reset request', NOW())"
)->execute([$user_id, $admin_id]);

// Build the absolute reset URL based on the server's current host/path
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'];
// From /IHRRS/ADMIN/php/ go up two levels to /IHRRS/
$app_root  = dirname(dirname(dirname($_SERVER['PHP_SELF'])));
$reset_url = $protocol . '://' . $host . $app_root . '/auth/reset_password?token=' . $raw_token;

echo json_encode([
    "success"    => true,
    "reset_url"  => $reset_url,
    "expires_at" => $expires_at,
]);
