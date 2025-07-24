<?php
require '../../php/db_connect.php';

if (isset($_GET['patient_id'])) {
    header('Content-Type: application/json');
    $patient_id = $_GET['patient_id'];

    $sql = "SELECT CONCAT(last_name, ', ', first_name, ' ', COALESCE(middle_name, '')) AS patient_name FROM patients WHERE patient_id = :patient_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
    $stmt->execute();

    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        echo json_encode(["error" => "Patient not found."]);
        exit;
    }
    $sql2 = "SELECT rc.consultation_id, rc.consultation_date, rc.diagnosis, rc.diagnosis_status
    FROM rhu_consultations rc
    WHERE rc.patient_id = :patient_id 
    ORDER BY rc.consultation_date DESC";
 

    $stmt2 = $pdo->prepare($sql2);
    $stmt2->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
    $stmt2->execute();

    $visit_history = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "patient" => $patient,
        "visit_history" => $visit_history
    ]);
}

?>
