<?php
require_once __DIR__ . '/session_config.php';
header('Content-Type: application/json');
require '../../php/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT barangay FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['error' => 'User not found']);
    exit;
}

$barangay = ucwords(strtolower($user['barangay']));

$stmt = $pdo->prepare("SELECT value FROM custom_options WHERE category = ?");
$stmt->execute([$barangay]);
$puroks = $stmt->fetchAll(PDO::FETCH_COLUMN);

$addresses = array_map(function($purok) use ($barangay) {
    return "$purok";
}, $puroks);

echo json_encode($addresses);
?>
