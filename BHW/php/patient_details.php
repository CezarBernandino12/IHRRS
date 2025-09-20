<?php
require '../../php/db_connect.php';

if (isset($_GET['patient_id'])) {
    header('Content-Type: application/json');
    $patient_id = $_GET['patient_id'];

    $sql = "SELECT * FROM patients WHERE patient_id = :patient_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
    $stmt->execute();

    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        echo json_encode(["error" => "Patient not found."]);
        exit;
    }
    $sql2 = "SELECT bv.visit_id, bv.visit_date, bv.recorded_by, u.full_name AS bhw_name
    FROM patient_assessment bv
    LEFT JOIN users u ON bv.recorded_by = u.user_id
    WHERE bv.patient_id = :patient_id 
    ORDER BY bv.visit_date DESC";


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
