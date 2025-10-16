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

try {
    // Get form data
    $patient_id = $_POST['patient_id'] ?? null;
    $visit_id = $_POST['visit_id'] ?? null;
    $issuance_date = $_POST['issuance_date'] ?? null;
    $diagnosis = $_POST['diagnosis'] ?? '';
    $findings = $_POST['findings'] ?? '';
    $purpose = $_POST['purpose'] ?? '';
    $rest_period_days = $_POST['rest_period_days'] ?? null;
    $rest_from_date = $_POST['rest_from_date'] ?? null;
    $rest_to_date = $_POST['rest_to_date'] ?? null;
    $prepared_by = $_POST['user_id'] ?? '';
    $issued_by = $_POST['physician'] ?? '';
 


    // Validate required fields
    if (!$patient_id || !$visit_id || !$issuance_date || !$diagnosis) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Convert empty strings to NULL for integer/date fields
    $rest_period_days = ($rest_period_days !== '' && $rest_period_days !== null) ? intval($rest_period_days) : null;
    $rest_from_date = ($rest_from_date !== '') ? $rest_from_date : null;
    $rest_to_date = ($rest_to_date !== '') ? $rest_to_date : null;
    

    // Check if we're using PDO or mysqli
    if (isset($pdo)) {
        // PDO version
        $sql = "INSERT INTO medical_certificates (
            patient_id, visit_id, issuance_date, diagnosis, findings, purpose,
            rest_period_days, rest_from_date, rest_to_date, issued_by, prepared_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $patient_id, $visit_id, $issuance_date, $diagnosis, $findings, $purpose,
            $rest_period_days, $rest_from_date, $rest_to_date,
            $issued_by, $prepared_by
        ]);

        $medcert_id = $pdo->lastInsertId();

    } elseif (isset($conn)) {
        // MySQLi version
         $sql = "INSERT INTO medical_certificates (
            patient_id, visit_id, issuance_date, diagnosis, findings, purpose,
            rest_period_days, rest_from_date, rest_to_date, issued_by, prepared_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param(
            "iissssissii",
            $patient_id, $visit_id, $issuance_date, $diagnosis, $findings, $purpose,
            $rest_period_days, $rest_from_date, $rest_to_date,
            $issued_by, $prepared_by
        );

        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Database execute error: ' . $stmt->error]);
            exit;
        }

        $medcert_id = $conn->insert_id;
        $stmt->close();
        $conn->close();

    } else {
        echo json_encode(['success' => false, 'message' => 'Database connection object not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Medical certificate issued successfully',
        'medcert_id' => $medcert_id
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>