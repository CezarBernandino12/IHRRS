<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['full_name'])) {
    echo json_encode([
        'full_name' => $_SESSION['full_name'],
        'barangay' => $_SESSION['barangay'] ?? ''
    ]);
} else {
    echo json_encode(['error' => 'Not logged in']);
}
?> 