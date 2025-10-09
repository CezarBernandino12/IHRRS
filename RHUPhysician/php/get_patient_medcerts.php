<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Database connection
require '../../php/db_connect.php';

$patient_id = $_GET['patient_id'] ?? null;

if (!$patient_id) {
    echo json_encode(['error' => 'Patient ID is required']);
    exit;
}

try {
    // Get all medical certificates for this patient
    $sql = "SELECT 
                mc.medcert_id,
                mc.issuance_date,
                mc.diagnosis,
                mc.issued_by_name,
                pa.visit_date
            FROM medical_certificates mc
            LEFT JOIN patient_assessment pa ON mc.visit_id = pa.visit_id
            WHERE mc.patient_id = ?
            ORDER BY mc.issuance_date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $certificates = [];
    while ($row = $result->fetch_assoc()) {
        $certificates[] = $row;
    }

    echo json_encode(['certificates' => $certificates]);

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>