<?php
require '../../php/db_connect.php';

if (isset($_GET['patient_id'])) {
    $patient_id = $_GET['patient_id'];

    // Fetch patient information
    $sql = "SELECT * FROM patients WHERE patient_id = :patient_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch visit history (only visit date & recorded by)
    $sql2 = "SELECT visit_id, visit_date, bhw_id FROM bhs_visits 
             WHERE patient_id = :patient_id 
             ORDER BY visit_date DESC"; // Latest visits first

             
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
    $stmt2->execute();
    
    $visit_history = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Combine both datasets into a single response
    echo json_encode([
        "patient" => $patient,
        "visit_history" => $visit_history
    ]);
}
?>
