<?php
require '../../php/db_connect.php';

$consultation_id = $_GET['consultation_id'] ?? null;
if (!$consultation_id) {
    echo json_encode(['error' => 'Consultation ID missing']);
    exit;
}

$typeParam = $_GET['type'] ?? 'all';
$types = explode(',', $typeParam);

try {
    // ===== FETCH CONSULTATION + PATIENT INFO =====
    $stmt = $pdo->prepare("
        SELECT 
            CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
            p.age,
            p.sex,
            p.address,
            c.consultation_date,
            c.instruction_prescription,
            m.medicine_name,
            u.full_name AS physician_name,
            u.license_number AS physician_license,
            u.rhu,
            m.instruction,
            m.dispensed_date,
            m.quantity_dispensed
        FROM rhu_consultations c
        JOIN patients p ON c.patient_id = p.patient_id
        LEFT JOIN rhu_medicine_dispensed m ON c.consultation_id = m.consultation_id
        LEFT JOIN users u ON m.dispensed_by = u.user_id
        WHERE c.consultation_id = :consultation_id
    ");
    $stmt->execute([':consultation_id' => $consultation_id]);
    $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$consultations) {
        echo json_encode(['error' => 'No record found']);
        exit;
    }

    // ===== FETCH PRESCRIPTION DATA =====
    $stmt2 = $pdo->prepare("
        SELECT 
            pr.medicine_name, 
            pr.quantity, 
            pr.instruction, 
            pr.date, 
            u.full_name AS physician_name, 
            u.license_number AS physician_license
        FROM prescription pr
        LEFT JOIN users u ON pr.physician = u.user_id
        WHERE pr.consultation_id = :consultation_id
    ");
    $stmt2->execute([':consultation_id' => $consultation_id]);
    $prescriptions = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'consultations' => $consultations,
        'prescriptions' => $prescriptions
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
