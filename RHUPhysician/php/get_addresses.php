<?php
session_start();
header('Content-Type: application/json');
require '../../php/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}


// Get puroks from custom_options
$stmt = $pdo->prepare("SELECT value FROM custom_options WHERE category LIKE 'Barangay%'");
$stmt->execute([]);
$puroks = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Format to full address
$addresses = array_map(function($purok) {
    return "$purok";
}, $puroks);    


echo json_encode($addresses);
?>
