<?php
require_once __DIR__ . '/session_config.php';
header('Content-Type: application/json');

if (isset($_SESSION['barangay'])) {
    echo json_encode(['barangay' => $_SESSION['barangay']]);
} else {
    echo json_encode(['error' => 'Not logged in']);
}
?> 