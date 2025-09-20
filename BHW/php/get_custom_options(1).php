<?php
require '../../php/db_connect.php';

header('Content-Type: application/json');

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
