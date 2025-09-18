<?php
require '../../php/db_connect.php'; // adjust path

header('Content-Type: application/json');

$patient_id = $_GET['patient_id'] ?? null;

if (!$patient_id) {
    echo json_encode(['error' => 'No patient_id provided']);
    exit;
}

try {
    // ✅ Patient Info
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        echo json_encode(['error' => 'Patient not found']);
        exit;
    }

    echo json_encode([
        'patient' => $patient
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

?>
