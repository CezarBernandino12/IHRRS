<?php
session_start();
require '../../php/db_connect.php'; // Adjust path as needed
header('Content-Type: application/json');

// Check if value and category are set
if (!isset($_POST['category']) || !isset($_POST['value'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$category = $_POST['category'];
$value = trim($_POST['value']);

// If category is "address", replace it with the user's barangay
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

    $category = $row['barangay']; // Use barangay as category for address
}

// Check for duplicates
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
