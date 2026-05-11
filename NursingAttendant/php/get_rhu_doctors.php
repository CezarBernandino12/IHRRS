<?php
require_once __DIR__ . '/session_config.php';
header('Content-Type: application/json');

$dsn = "mysql:host=localhost;dbname=ihrrs_dbase;charset=utf8mb4";
$username = "root";
$password = "";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT rhu FROM users WHERE user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['error' => 'User not found']);
    exit();
}

$user_rhu = $user['rhu'];

$sql = "SELECT user_id, full_name, license_number 
        FROM users 
        WHERE role = 'doctor' 
        AND rhu = ? 
        AND account_status = 'active' 
        AND status = 'approved'
        ORDER BY full_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_rhu]);
$doctors = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'user_rhu' => $user_rhu,
    'doctors' => $doctors
]);
?>
