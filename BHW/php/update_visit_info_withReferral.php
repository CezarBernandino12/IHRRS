<?php
require '../../php/db_connect.php';

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$log_file = "../../logs/debug.log";

// Read and decode input
$input = json_decode(file_get_contents('php://input'), true);

// Log the raw input to check what data is being sent
file_put_contents($log_file, "[RAW INPUT] " . print_r($input, true) . "\n", FILE_APPEND);

// Check if visit_id is provided and valid
$visit_id = (int)$input['visit_id'];  // Ensure it's an integer

try {
    // Start the transaction
    $pdo->beginTransaction();

    // Check if the visit exists
    $stmt = $pdo->prepare("SELECT patient_id FROM bhs_visits WHERE visit_id = :visit_id");
    $stmt->execute(['visit_id' => $visit_id]);
    $visit = $stmt->fetch(PDO::FETCH_ASSOC);

    // If the visit doesn't exist, return an error
    if (!$visit) {
        throw new Exception('Visit not found');
    }

    // Update visit info
    $stmt = $pdo->prepare("
        UPDATE bhs_visits SET 
            visit_date = :visit_date,
            patient_alert = :patient_alert,
            chief_complaints = :chief_complaints,
            blood_pressure = :blood_pressure,
            temperature = :temperature,
            weight = :weight,
            height = :height,
            chest_rate = :chest_rate,
            respiratory_rate = :respiratory_rate,
            remarks = :remarks
        WHERE visit_id = :visit_id
    ");

    $visitParams = [
        'visit_date' => $input['visit_date'] ?? null,
        'patient_alert' => $input['patient_alert'] ?? null,
        'chief_complaints' => $input['chief_complaints'] ?? null,
        'blood_pressure' => $input['blood_pressure'] ?? 'N/A',
        'temperature' => $input['temperature'] ?? 'N/A',
        'weight' => $input['weight'] ?? 'N/A',
        'height' => $input['height'] ?? 'N/A',
        'chest_rate' => $input['chest_rate'] ?? 'N/A',
        'respiratory_rate' => $input['respiratory_rate'] ?? 'N/A',
        'remarks' => $input['remarks'] ?? null,
        'visit_id' => $visit_id
    ];

    $stmt->execute($visitParams);

    $patient_id = $visit['patient_id'];

    // Update patient info
    $stmt = $pdo->prepare("
      UPDATE patients SET 
            first_name = :first_name,
            last_name = :last_name,
            middle_name = :middle_name,
            extension = :extension,
            birthplace = :birthplace,
            date_of_birth = :date_of_birth,
            address = :address,
            civil_status = :civil_status,
            contact_number = :contact_number,
            religion = :religion,
            occupation = :occupation,
            educational_attainment = :educational_attainment,
            birth_weight = :birth_weight,
            philhealth_member_no = :philhealth_member_no,
             category = :category,
            family_serial_no = :family_serial_no,
            sex = :sex,
            fourps_status = :fourps_status
        WHERE patient_id = :patient_id
    ");
   $patientParams = [
        'first_name' => $input['first_name'] ?? null,
        'last_name' => $input['last_name'] ?? null,
        'middle_name' => $input['middle_name'] ?? null,
        'extension' => $input['extension'] ?? null,
        'birthplace' => $input['birthplace'] ?? null,
        'date_of_birth' => $input['date_of_birth'] ?? null,
        'address' => $input['address'] ?? null,
        'civil_status' => $input['civil_status'] ?? null,
        'contact_number' => $input['contact_number'] ?? null,
        'religion' => $input['religion'] ?? null,
        'occupation' => $input['occupation'] ?? null,
        'educational_attainment' => $input['educational_attainment'] ?? null,
        'birth_weight' => $input['birth_weight'] ?? null,
        'philhealth_member_no' => $input['philhealth_member_no'] ?? null,
        'category' => $input['category'] ?? null,
        'family_serial_no' => $input['family_serial_no'] ?? null,
        'sex' => $input['sex'] ?? null,
        'fourps_status' => $input['fourps_status'] ?? null,
        'patient_id' => $patient_id
    ];

    $stmt->execute($patientParams);

    // Insert referral info into referrals table
// Check if referral already exists for the given visit
// Check if referral already exists for the given visit
$stmt = $pdo->prepare("SELECT COUNT(*) FROM referrals WHERE visit_id = :visit_id");
$stmt->execute(['visit_id' => $visit_id]);
$referralExists = $stmt->fetchColumn();

if (!$referralExists) {
    // Insert referral info into referrals table
    $stmt = $pdo->prepare("
        INSERT INTO referrals (patient_id, visit_id, referred_by, referral_status, referral_date)
        VALUES (:patient_id, :visit_id, :referred_by, 'pending', NOW())
    ");

    $stmt->execute([
        'patient_id' => $patient_id,
        'visit_id' => $visit_id,
        'referred_by' => $input['referralInput']
    ]);
}



    // Commit the transaction
    $pdo->commit();

    // Send success response
    ob_clean();
    echo json_encode([
        'success' => true,
        'referralSkipped' => $referralExists ? true : false
    ]);
    
    exit;

} catch (Exception $e) {
    // Roll back the transaction if an error occurs
    $pdo->rollBack();
    error_log("Update Error: " . $e->getMessage(), 3, "../../logs/error.log");
    file_put_contents($log_file, "[EXCEPTION] " . $e->getMessage() . "\n", FILE_APPEND);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Internal Server Error']);
    exit;
}
?>
