<?php
ob_start();
session_start();
ob_end_clean();

header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    require '../../php/db_connect.php';
    require '../../ADMIN/php/log_functions.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get form data
    $patient_id = intval($_POST['patient_id'] ?? 0);
    $visit_id = intval($_POST['visit_id'] ?? 0);
    $issuance_date = trim($_POST['issuance_date'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $findings = trim($_POST['findings'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $rest_period_days = trim($_POST['rest_period_days'] ?? '');
    $rest_from_date = trim($_POST['rest_from_date'] ?? '');
    $rest_to_date = trim($_POST['rest_to_date'] ?? '');
    $issued_by = $_SESSION['user_id'];

    // Validate required fields
    if ($patient_id === 0 || $visit_id === 0 || empty($issuance_date) || empty($diagnosis)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Verify visit exists and belongs to patient
    if (isset($pdo)) {
        $verify_stmt = $pdo->prepare("
            SELECT visit_id FROM patient_assessment 
            WHERE visit_id = ? AND patient_id = ?
        ");
        $verify_stmt->execute([$visit_id, $patient_id]);
        
        if (!$verify_stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Selected visit does not exist for this patient']);
            exit;
        }
    }

    // Convert empty strings to NULL for optional fields
    $rest_period_days = ($rest_period_days !== '') ? intval($rest_period_days) : null;
    $rest_from_date = ($rest_from_date !== '') ? $rest_from_date : null;
    $rest_to_date = ($rest_to_date !== '') ? $rest_to_date : null;

    if (isset($pdo)) {
        // PDO version -  FIXED: Use 'issued_by' not 'issued_by_user_id'
        $sql = "INSERT INTO medical_certificates (
            patient_id, visit_id, issuance_date, diagnosis, findings, purpose,
            rest_period_days, rest_from_date, rest_to_date, issued_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $patient_id, $visit_id, $issuance_date, $diagnosis, $findings, $purpose,
            $rest_period_days, $rest_from_date, $rest_to_date,
            $issued_by
        ]);

        $medcert_id = $pdo->lastInsertId();

        if ($medcert_id) {
            //  LOG ACTIVITY: Generated Medical Certificate
            logActivity($pdo, $_SESSION['user_id'], "Generated Medical Certificate");
        }

    } elseif (isset($conn)) {
        // MySQLi version -  FIXED: Use 'issued_by' not 'issued_by_user_id'
        $sql = "INSERT INTO medical_certificates (
            patient_id, visit_id, issuance_date, diagnosis, findings, purpose,
            rest_period_days, rest_from_date, rest_to_date, issued_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param(
            "iissssissi",
            $patient_id, $visit_id, $issuance_date, $diagnosis, $findings, $purpose,
            $rest_period_days, $rest_from_date, $rest_to_date,
            $issued_by
        );

        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Database execute error: ' . $stmt->error]);
            exit;
        }

        $medcert_id = $conn->insert_id;

        if ($medcert_id) {
            //  LOG ACTIVITY: Generated Medical Certificate (FIXED: use $conn not $pdo)
            logActivity($conn, $_SESSION['user_id'], "Generated Medical Certificate");
        }

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