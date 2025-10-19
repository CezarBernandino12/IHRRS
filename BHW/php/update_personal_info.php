<?php
session_start();
require '../../php/db_connect.php';
require '../../ADMIN/php/log_functions.php';

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$log_file = "../../logs/debug.log";

// Read and decode input
$input = json_decode(file_get_contents('php://input'), true);

// Log the raw input
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
    $value = isset($input[$field]) ? trim((string)$input[$field]) : '';
    if ($value === '' || $value === 'N/A' || $value === null) {  // Allow empty for optional fields
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    ob_clean();
    file_put_contents($log_file, "[ERROR] Missing required fields: " . implode(', ', $missingFields) . " | Input: " . print_r($input, true) . "\n", FILE_APPEND);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields: ' . implode(', ', $missingFields),
        'debug' => $input  // Return the input for debugging
    ]);
    exit;
}

// Get user_id from session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    ob_clean();
    file_put_contents($log_file, "[ERROR] User not logged in\n", FILE_APPEND);
    echo json_encode([
        'success' => false,
        'error' => 'User not logged in'
    ]);
    exit;
}

$patient_id = (int)$input['patient_id'];

file_put_contents($log_file, "[PATIENT_ID] " . $patient_id . "\n", FILE_APPEND);
file_put_contents($log_file, "[USER_ID] " . $user_id . "\n", FILE_APPEND);

try {
    $pdo->beginTransaction();

    // Check if patient exists
    $stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE patient_id = :patient_id");
    $stmt->execute(['patient_id' => $patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        ob_clean();
        file_put_contents($log_file, "[ERROR] Patient ID $patient_id not found\n", FILE_APPEND);
        echo json_encode([
            'success' => false,
            'error' => 'Patient not found'
        ]);
        exit;
    }

    file_put_contents($log_file, "[SUCCESS] Patient found, updating...\n", FILE_APPEND);

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

    file_put_contents($log_file, "[PARAMS] " . print_r($patientParams, true) . "\n", FILE_APPEND);

    $stmt->execute($patientParams);
    $rowsAffected = $stmt->rowCount();

    file_put_contents($log_file, "[ROWS_AFFECTED] " . $rowsAffected . "\n", FILE_APPEND);

    if (function_exists('logActivity')) {
        logActivity($pdo, $user_id, "Updated Patient Information for Patient ID: $patient_id");
    }

    $pdo->commit();

    file_put_contents($log_file, "[COMMIT] Success!\n", FILE_APPEND);

    ob_clean();
    echo json_encode(['success' => true, 'message' => 'Patient updated successfully']);
    exit;

} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Update Error: " . $e->getMessage(), 3, "../../logs/error.log");
    file_put_contents($log_file, "[EXCEPTION] " . $e->getMessage() . "\n", FILE_APPEND);
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Internal Server Error: ' . $e->getMessage()
    ]);
    exit;
}
?>