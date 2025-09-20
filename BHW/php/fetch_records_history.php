<?php
require '../../php/db_connect.php';
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get the current user's ID from the session
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    // First get the barangay of the current user
    $stmt = $pdo->prepare("
        SELECT barangay 
        FROM users 
        WHERE user_id = ?
    ");
    $stmt->execute([$current_user_id]);
    $user_barangay = $stmt->fetchColumn();

    if (!$user_barangay) {
        echo json_encode(['error' => 'User barangay not found']);
        exit;
    }

    // Now get the records filtered by barangay and search term
    $stmt = $pdo->prepare("
        SELECT 
            b.visit_date, b.visit_id,
            CONCAT(p.first_name, ' ', p.middle_name, ' ', p.last_name) AS patient_name,
            CONCAT(UPPER(u.role), ' - ' ,u.full_name) AS recorded_by
        FROM patient_assessment b
        INNER JOIN patients p ON b.patient_id = p.patient_id
        INNER JOIN users u ON b.recorded_by = u.user_id
        WHERE u.barangay = ?
        AND (
            CONCAT(p.first_name, ' ', p.middle_name, ' ', p.last_name) LIKE ?
            OR CONCAT(UPPER(u.role), ' - ' ,u.full_name) LIKE ?
            OR DATE_FORMAT(b.visit_date, '%M %d, %Y') LIKE ?
        )
        ORDER BY b.visit_date DESC
    ");
    $searchTerm = "%{$search}%";
    $stmt->execute([$user_barangay, $searchTerm, $searchTerm, $searchTerm]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

