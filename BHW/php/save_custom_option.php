<?php
require_once __DIR__ . '/session_config.php';
require '../../php/db_connect.php'; 
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}


if (!isset($_POST['category']) || !isset($_POST['value'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$category = $_POST['category'];
$value = trim($_POST['value']);

if ($category === 'address') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT barangay FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $category = $row['barangay'];
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM custom_options WHERE category = ? AND value = ?");
$stmt->execute([$category, $value]);
$count = $stmt->fetchColumn();

if ($count > 0) {
    echo json_encode(['success' => false, 'message' => 'This option already exists.']);
} else {
    $stmt = $pdo->prepare("INSERT INTO custom_options (category, value) VALUES (?, ?)");
    if ($stmt->execute([$category, $value])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to insert custom option.']);
    }
}
?>
