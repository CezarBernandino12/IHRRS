<?php
require '../../php/db_connect.php';

header('Content-Type: application/json');


error_reporting(E_ALL);
ini_set('display_errors', 1);


// Validate visit_id
if (!isset($_GET['visit_id']) || empty($_GET['visit_id'])) {
    echo json_encode(['error' => 'Missing visit_id']);
    exit;
}

$visit_id = (int) $_GET['visit_id']; // Ensure it's a valid integer

try { 
    // Fetch visit details and patient_id
    $sql_visit = "SELECT 
    bv.visit_id, bv.patient_id, DATE_FORMAT(bv.visit_date, '%Y-%m-%d') as visit_date, bv.patient_alert, bv.chief_complaints, 
    bv.blood_pressure, bv.temperature, bv.weight, bv.height,  bv.bmi, 
    bv.chest_rate, bv.respiratory_rate, bv.remarks,
    u.full_name AS bhw_name, u.barangay AS bhw_barangay
FROM bhs_visits bv
LEFT JOIN users u ON bv.bhw_id = u.user_id
WHERE bv.visit_id = :visit_id";

    $stmt_visit = $pdo->prepare($sql_visit);
    $stmt_visit->bindParam(':visit_id', $visit_id, PDO::PARAM_INT);
    $stmt_visit->execute();
    $visitInfo = $stmt_visit->fetch(PDO::FETCH_ASSOC);

// Ensure visit was found and contains patient_id
if (!$visitInfo) {
    echo json_encode(['error' => 'Visit not found']);
    exit;
}

$patient_id = $visitInfo['patient_id'] ?? null;

if (!$patient_id) {
    echo json_encode(['error' => 'No patient_id found for visit']);
    exit;
}

// Debugging: Log the patient_id
error_log("Fetched patient_id: " . $patient_id);


    $patient_id = $visitInfo['patient_id']; // Retrieve patient_id for lookup

    // Replace NULL or missing values with "N/A"
    foreach ($visitInfo as $key => $value) {
        $visitInfo[$key] = $value ?? "N/A";
    }

    // Fetch patient details using patient_id
    $sql_patient = "SELECT * FROM patients WHERE patient_id = :patient_id";
    $stmt_patient = $pdo->prepare($sql_patient);
    $stmt_patient->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
    $stmt_patient->execute();
    $patientInfo = $stmt_patient->fetch(PDO::FETCH_ASSOC) ?: [];

    // Replace NULL or missing values with "N/A"
    foreach ($patientInfo as $key => $value) {
        $patientInfo[$key] = $value ?? "N/A";
    }

    // Fetch all medicines dispensed for this visit
    $sql_medicine = "SELECT medicine_name, quantity_dispensed FROM bhs_medicine_dispensed WHERE visit_id = :visit_id";
    $stmt_medicine = $pdo->prepare($sql_medicine);
    $stmt_medicine->bindParam(':visit_id', $visit_id, PDO::PARAM_INT);
    $stmt_medicine->execute();
    $medicineInfo = $stmt_medicine->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Ensure all medicine fields are properly formatted
    foreach ($medicineInfo as &$medicine) {
        $medicine['medicine_name'] = $medicine['medicine_name'] ?? "None";
        $medicine['quantity_dispensed'] = $medicine['quantity_dispensed'] ?? "0";
    }

    // Return JSON response
    echo json_encode([
        'visit' => $visitInfo,
        'patient' => $patientInfo,
        'medicine' => $medicineInfo
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
