<?php
require '../../ADMIN/php/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => "Invalid request."]);
    exit;
}

$raw_token    = trim($_POST['token']        ?? '');
$new_password = trim($_POST['new_password'] ?? '');

if (empty($raw_token) || empty($new_password)) {
    echo json_encode(["error" => "All fields are required."]);
    exit;
}

// Basic token format check (must be 64 hex characters)
if (!preg_match('/^[0-9a-f]{64}$/', $raw_token)) {
    echo json_encode(["error" => "Invalid reset link."]);
    exit;
}

// Validate password strength server-side
if (!preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', $new_password)) {
    echo json_encode(["error" => "Password must be at least 8 characters with one uppercase letter and one number."]);
    exit;
}

// Hash the submitted token to compare against the stored hash
$hashed_token = hash('sha256', $raw_token);

// Look up a matching approved, non-expired request
$stmt = $pdo->prepare(
    "SELECT request_id, user_id
     FROM forgot_password_requests
     WHERE reset_token = ?
       AND status = 'approved'
       AND token_expires_at > NOW()"
);
$stmt->execute([$hashed_token]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    echo json_encode(["error" => "This reset link is invalid or has expired. Please contact your administrator for a new one."]);
    exit;
}

$user_id    = (int) $request['user_id'];
$request_id = (int) $request['request_id'];

// Hash and apply the new password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
$stmt->execute([$hashed_password, $user_id]);

// Mark request as resolved and clear the token so it cannot be reused
$pdo->prepare(
    "UPDATE forgot_password_requests
     SET status = 'resolved', reset_token = NULL, token_expires_at = NULL
     WHERE request_id = ?"
)->execute([$request_id]);

echo json_encode(["success" => true]);
