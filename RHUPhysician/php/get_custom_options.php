<?php
require '../../php/db_connect.php';

header('Content-Type: application/json');
require_once __DIR__ . '/session_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}


if (isset($_GET['category'])) {
    $category = $_GET['category'];

    $stmt = $pdo->prepare("SELECT value FROM custom_options WHERE category = ?");
    $stmt->execute([$category]);

    $values = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($values);
} else {
    echo json_encode([]);
}
?>
