<?php
require '../../php/db_connect.php';

header('Content-Type: application/json');


error_reporting(E_ALL);
ini_set('display_errors', 1);



if (!isset($_GET['visit_id']) || empty($_GET['visit_id'])) {
    echo json_encode(['error' => 'Missing visit_id']);
    exit;
}

$visit_id = (int) $_GET['visit_id']; 

try { 
    $sql_visit = "SELECT visit_id, patient_id, visit_date, patient_alert, chief_complaints, blood_pressure, temperature, 
                         weight, height, chest_rate, respiratory_rate, remarks 
                  FROM patient_assessment 
                  WHERE visit_id = :visit_id";
    $stmt_visit = $pdo->prepare($sql_visit);
    $stmt_visit->bindParam(':visit_id', $visit_id, PDO::PARAM_INT);
    $stmt_visit->execute();
    $visitInfo = $stmt_visit->fetch(PDO::FETCH_ASSOC);

if (!$visitInfo) {
    echo json_encode(['error' => 'Visit not found']);
    exit;
}

$patient_id = $visitInfo['patient_id'] ?? null;

if (!$patient_id) {
    echo json_encode(['error' => 'No patient_id found for visit']);
    exit;
}
error_log("Fetched patient_id: " . $patient_id);


    $patient_id = $visitInfo['patient_id']; 

    foreach ($visitInfo as $key => $value) {
        $visitInfo[$key] = $value ?? "N/A";
    }

    $sql_patient = "SELECT * FROM patients WHERE patient_id = :patient_id";
    $stmt_patient = $pdo->prepare($sql_patient);
    $stmt_patient->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
    $stmt_patient->execute();
    $patientInfo = $stmt_patient->fetch(PDO::FETCH_ASSOC) ?: [];

    foreach ($patientInfo as $key => $value) {
        $patientInfo[$key] = $value ?? "N/A";
    }

    $sql_medicine = "SELECT medicine_name, quantity_dispensed FROM bhs_medicine_dispensed WHERE visit_id = :visit_id";
    $stmt_medicine = $pdo->prepare($sql_medicine);
    $stmt_medicine->bindParam(':visit_id', $visit_id, PDO::PARAM_INT);
    $stmt_medicine->execute();
    $medicineInfo = $stmt_medicine->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($medicineInfo as &$medicine) {
        $medicine['medicine_name'] = $medicine['medicine_name'] ?? "None";
        $medicine['quantity_dispensed'] = $medicine['quantity_dispensed'] ?? "0";
    }

    echo json_encode([
        'visit' => $visitInfo,
        'patient' => $patientInfo,
        'medicine' => $medicineInfo
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
