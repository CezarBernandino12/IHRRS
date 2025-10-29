<?php
require '../ADMIN/php/config.php';
header('Content-Type: application/json'); // Ensure responses are in JSON format

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $contact_number = trim($_POST['contact_number']);

    // Validate input fields
    if (empty($username) || empty($contact_number)) {
        echo json_encode(["error" => "All fields are required."]);
        exit;
    }

    // Check if user exists and contact number matches
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND contact_number = ? AND role = 'bhw'");
    $stmt->execute([$username, $contact_number]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["error" => "Invalid username or contact number."]);
        exit;
    }

    $user_id = $user['user_id'];

    // Check if there is an existing pending request
    $stmt = $pdo->prepare("SELECT request_id FROM forgot_password_requests WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);

    if ($stmt->fetch()) {
        echo json_encode(["error" => "A password reset request is already pending. Please wait for admin approval."]);
        exit;
    }

    // Insert new password reset request
    $stmt = $pdo->prepare("INSERT INTO forgot_password_requests (user_id, status, request_time) VALUES (?, 'pending', NOW())");
    $stmt->execute([$user_id]);

   if ($stmt->rowCount() > 0) {
    echo json_encode(["success" => "Your request has been submitted. The admin will contact you soon."]);
} else {
    $error = $stmt->errorInfo();
    echo json_encode(["error" => "Error submitting request: " . $error[2]]);
}
}
?>