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

// === REQUIRED FIELDS VALIDATION ===
$requiredFields = [
    'patient_id',
    'first_name',
    'last_name',
    'date_of_birth',
    'sex',
    'address'
];

$missingFields = [];
foreach ($requiredFields as $field) {
    if (!isset($input[$field]) || trim($input[$field]) === '') {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields: ' . implode(', ', $missingFields)
    ]);
    exit;
}

// Sanitize and typecast patient_id
$patient_id = (int)$input['patient_id'];

try {
    // Start transaction
    $pdo->beginTransaction();

    // Check if patient exists
    $stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE patient_id = :patient_id");
    $stmt->execute(['patient_id' => $patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'error' => 'Patient not found'
        ]);
        exit;
    }

    // Prepare update query
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
        'first_name' => $input['first_name'],
        'last_name' => $input['last_name'],
        'middle_name' => $input['middle_name'] ?? null,
        'extension' => $input['extension'] ?? null,
        'birthplace' => $input['birthplace'] ?? null,
        'date_of_birth' => $input['date_of_birth'],
        'address' => $input['address'],
        'civil_status' => $input['civil_status'] ?? null,
        'contact_number' => $input['contact_number'] ?? null,
        'religion' => $input['religion'] ?? null,
        'occupation' => $input['occupation'] ?? null,
        'educational_attainment' => $input['educational_attainment'] ?? null,
        'birth_weight' => $input['birth_weight'] ?? null,
        'philhealth_member_no' => $input['philhealth_member_no'] ?? null,
        'category' => $input['category'] ?? null,
        'family_serial_no' => $input['family_serial_no'] ?? null,
        'sex' => $input['sex'],
        'fourps_status' => $input['fourps_status'] ?? null,
        'patient_id' => $patient_id
    ];

    $stmt->execute($patientParams);



    //ADDED UPDATED PATIENT INFO FOR ACTIVITY LOG
    $stmt_log2 = $pdo->prepare("INSERT INTO logs (
        user_id, action, performed_by, user_affected
    ) VALUES (
        :user_id, :action, :performed_by, :user_affected
    )");

    $stmt_log2->execute([
        ':user_id' => $user_id,
        ':action' => "Updated Patient Information",
        ':performed_by' => $user_id,
        ':user_affected' => $patient_id
    ]);
    
    

    $pdo->commit();

    // Send success response
    ob_clean();
    echo json_encode(['success' => true]);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Update Error: " . $e->getMessage(), 3, "../../logs/error.log");
    file_put_contents($log_file, "[EXCEPTION] " . $e->getMessage() . "\n", FILE_APPEND);
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Internal Server Error'
    ]);
    exit;
}
?>
