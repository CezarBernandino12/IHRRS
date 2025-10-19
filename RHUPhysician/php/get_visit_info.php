<?php
require '../../php/db_connect.php';
require '../../ADMIN/php/log_functions.php';

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
    $sql_visit = "SELECT visit_id, patient_id, visit_date, patient_alert, chief_complaints, blood_pressure, temperature, 
                         weight, height, chest_rate, respiratory_rate, remarks 
                  FROM patient_assessment 
                  WHERE visit_id = :visit_id";
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

    // Fetch RHU medicines dispensed for this visit
    $sql_rhumedicine = "SELECT md.medicine_name, md.quantity_dispensed 
                        FROM rhu_medicine_dispensed md
                        JOIN rhu_consultations rc ON md.consultation_id = rc.consultation_id
                        WHERE rc.visit_id = :visit_id";

    $stmt_rhumedicine = $pdo->prepare($sql_rhumedicine);
    $stmt_rhumedicine->bindParam(':visit_id', $visit_id, PDO::PARAM_INT);
    $stmt_rhumedicine->execute();
    $rhuMedicineInfo = $stmt_rhumedicine->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Ensure all RHU medicine fields are properly formatted
    foreach ($rhuMedicineInfo as &$rhumedicine) {
        $rhumedicine['medicine_name'] = $rhumedicine['medicine_name'] ?? "None";
        $rhumedicine['quantity_dispensed'] = $rhumedicine['quantity_dispensed'] ?? "0";
    }

    // Fetch consultation details
    $sql_consultation = "SELECT rc.*, u.full_name FROM rhu_consultations rc JOIN users u ON rc.doctor_id = u.user_id WHERE visit_id = :visit_id";
    $stmt_consultation = $pdo->prepare($sql_consultation);
    $stmt_consultation->bindParam(':visit_id', $visit_id, PDO::PARAM_INT);
    $stmt_consultation->execute();
    $consultationInfo = $stmt_consultation->fetch(PDO::FETCH_ASSOC) ?: [];

    // Replace NULL or missing values with "N/A"
    foreach ($consultationInfo as $key => $value) {
        $consultationInfo[$key] = $value ?? "N/A";
    }

    // Log the physician's action using standardized function
    if (isset($_SESSION['user_id'])) {
        logActivity($pdo, $_SESSION['user_id'], "Viewed Patient Assessment Record");
    }

    // Return JSON response
    echo json_encode([
        'visit' => $visitInfo,
        'patient' => $patientInfo,
        'medicine' => $medicineInfo,
        'rhumedicine' => $rhuMedicineInfo,
        'consultation' => $consultationInfo
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>