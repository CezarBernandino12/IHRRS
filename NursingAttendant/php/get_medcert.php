<?php
// Prevent any output before JSON
ob_start();

session_start();

// Clear any previous output
ob_end_clean();

header('Content-Type: application/json');

// Disable error display (log errors instead)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Database connection
try {
    require '../../php/db_connect.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$medcert_id = $_GET['medcert_id'] ?? null;

if (!$medcert_id) {
    echo json_encode(['success' => false, 'message' => 'Certificate ID is required']);
    exit;
}

try {
    // Get certificate with patient information
$sql = "SELECT  
    mc.*,
    p.first_name,
    p.middle_name,
    p.last_name,
    p.date_of_birth,
    p.age,
    p.sex,
    p.address,
    p.civil_status,
    p.birthplace,
    pa.visit_date,
    pa.chief_complaints,
    u.full_name AS issued_by,
    u.license_number AS license_number
FROM medical_certificates mc
INNER JOIN patients p 
    ON mc.patient_id = p.patient_id
LEFT JOIN patient_assessment pa 
    ON mc.visit_id = pa.visit_id
LEFT JOIN users u
    ON mc.issued_by_user_id = u.user_id
WHERE mc.medcert_id = ?";

    if (isset($pdo)) {
        // PDO version
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$medcert_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

    } elseif (isset($conn)) {
        // MySQLi version
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        
        $stmt->bind_param("i", $medcert_id);
        
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Database execute error: ' . $stmt->error]);
            exit;
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $stmt->close();
        $conn->close();

    } else {
        echo json_encode(['success' => false, 'message' => 'Database connection object not found']);
        exit;
    }

    if ($row) {
        // Format patient name
        $patient_name = trim($row['first_name'] . ' ' . ($row['middle_name'] ?? '') . ' ' . $row['last_name']);
        
        // Calculate age if not set
        if (!$row['age'] && $row['date_of_birth']) {
            $dob = new DateTime($row['date_of_birth']);
            $now = new DateTime();
            $row['age'] = $now->diff($dob)->y;
        }

        $certificate = [
            'medcert_id' => $row['medcert_id'],
            'patient_name' => $patient_name,
            'age' => $row['age'],
            'sex' => $row['sex'],
            'civil_status' => $row['civil_status'],
            'date_of_birth' => $row['date_of_birth'],
            'birthplace' => $row['birthplace'] ?? '',
            'address' => $row['address'] ?? '',
            'issuance_date' => $row['issuance_date'],
            'diagnosis' => $row['diagnosis'],
            'findings' => $row['findings'] ?? '',
            'purpose' => $row['purpose'] ?? '',
            'rest_period_days' => $row['rest_period_days'],
            'rest_from_date' => $row['rest_from_date'],
            'rest_to_date' => $row['rest_to_date'],
            'issued_by' => $row['issued_by'],
            'prepared_by' => $row['prepared_by'],
            'license_number' => $row['license_number'],
            'visit_date' => $row['visit_date'] ?? '',
            'chief_complaints' => $row['chief_complaints'] ?? ''
        ];

        echo json_encode(['success' => true, 'certificate' => $certificate]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Certificate not found']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>