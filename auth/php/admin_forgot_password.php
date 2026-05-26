<?php
require '../../ADMIN/php/config.php';
require_once __DIR__ . '/rate_limit.php';

header('Content-Type: application/json');

$genericSuccess = "If your details match our records, a request has been submitted. An administrator will contact you shortly.";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $ip = $_SERVER['REMOTE_ADDR'];
    if (!checkPasswordResetRateLimit($pdo, $ip)) {
        echo json_encode(["error" => "Too many requests. Please try again in 15 minutes."]);
        exit;
    }

    $username       = trim($_POST['username']       ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');

    if (empty($username) || empty($contact_number)) {
        echo json_encode(["error" => "All fields are required."]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND contact_number = ? AND role = 'admin'");
    $stmt->execute([$username, $contact_number]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["success" => $genericSuccess]);
        exit;
    }

    $user_id = $user['user_id'];

    $stmt = $pdo->prepare(
        "SELECT request_id FROM forgot_password_requests
         WHERE user_id = ?
           AND (status = 'pending' OR (status = 'approved' AND token_expires_at > NOW()))"
    );
    $stmt->execute([$user_id]);

    if ($stmt->fetch()) {
        echo json_encode(["success" => $genericSuccess]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO forgot_password_requests (user_id, status, request_time) VALUES (?, 'pending', NOW())");
    $stmt->execute([$user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => $genericSuccess]);
    } else {
        echo json_encode(["error" => "Error submitting request. Please try again."]);
    }
}
?>