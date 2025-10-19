<?php
require '../../php/db_connect.php';

try {
    // Get patient_id from URL parameter
    $patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : null;

    if (!$patient_id) {
        echo json_encode(['error' => 'No patient_id provided']);
        exit;
    }

    // --- Query 1: Medical Certificates ---
    $stmt1 = $pdo->prepare("
        SELECT 
            mc.medcert_id,
            mc.control_number, 
            mc.issuance_date, 
            CONCAT(p.last_name, ', ', p.first_name, ' ', COALESCE(p.middle_name, '')) AS patient_name,
            prep.full_name AS prepared_by,
            issu.full_name AS issued_by
        FROM medical_certificates mc
        JOIN patients p ON mc.patient_id = p.patient_id
        JOIN users prep ON mc.prepared_by = prep.user_id
        JOIN users issu ON mc.issued_by = issu.user_id
        WHERE mc.patient_id = :patient_id
        ORDER BY mc.issuance_date DESC
    ");
    $stmt1->execute([':patient_id' => $patient_id]);
    $medical_certificates = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // --- Query 2: Prescriptions ---
    $stmt2 = $pdo->prepare("
  SELECT 
    r.consultation_id,
    MAX(r.date) AS issuance_date,
    CONCAT(p.last_name, ', ', p.first_name, ' ', COALESCE(p.middle_name, '')) AS patient_name,
    gen.full_name AS generated_by,
    phy.full_name AS prescribed_by,
    GROUP_CONCAT(r.medicine_name SEPARATOR ', ') AS prescribed_medicines
FROM prescription r
JOIN patients p ON r.patient_id = p.patient_id
JOIN users gen ON r.generated_by = gen.user_id
JOIN users phy ON r.physician = phy.user_id
WHERE r.patient_id = :patient_id
GROUP BY 
    r.consultation_id, 
    p.last_name, p.first_name, p.middle_name, 
    gen.full_name, phy.full_name
ORDER BY issuance_date DESC;

    ");
    $stmt2->execute([':patient_id' => $patient_id]);
    $prescriptions = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Return both sets in one JSON object
    echo json_encode([
        'medical_certificates' => $medical_certificates,
        'prescriptions' => $prescriptions
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
