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
    $patient_id = $_POST['patient_id'] ?? null;
    $visit_id = $_POST['visit_id'] ?? null;
    $issuance_date = $_POST['issuance_date'] ?? null;
    $date_of_examination = $_POST['date_of_examination'] ?? null;
    $diagnosis = $_POST['diagnosis'] ?? '';
    $findings = $_POST['findings'] ?? '';
    $fit_status = $_POST['fit_status'] ?? null;
    $remarks = $_POST['remarks'] ?? '';
    $purpose = $_POST['purpose'] ?? '';
    $rest_period_days = $_POST['rest_period_days'] ?? null;
    $rest_from_date = $_POST['rest_from_date'] ?? null;
    $rest_to_date = $_POST['rest_to_date'] ?? null;
    
    // Laboratory fields
    $lab_cbc = $_POST['lab_cbc'] ?? '';
    $lab_urinalysis = $_POST['lab_urinalysis'] ?? '';
    $lab_fecalysis = $_POST['lab_fecalysis'] ?? '';
    $lab_hbsag = $_POST['lab_hbsag'] ?? '';
    $lab_cxr = $_POST['lab_cxr'] ?? '';
    $lab_sputum_afb = $_POST['lab_sputum_afb'] ?? '';
    $lab_other = $_POST['lab_other'] ?? '';
    
    $prepared_by = $_POST['user_id'] ?? '';
    $issued_by = $_POST['physician'] ?? '';

    // Validate required fields
    if ($patient_id === 0 || $visit_id === 0 || empty($issuance_date) || empty($diagnosis)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $rest_period_days = ($rest_period_days !== '' && $rest_period_days !== null) ? intval($rest_period_days) : null;
    $rest_from_date = ($rest_from_date !== '') ? $rest_from_date : null;
    $rest_to_date = ($rest_to_date !== '') ? $rest_to_date : null;

    if (isset($pdo)) {
        // 🔹 Generate control number (resets every year)
        $year = date('Y');
        $prefix = "MC-" . $year . "-";

        // Find the latest number for this year only
        $stmt = $pdo->prepare("
            SELECT control_number 
            FROM medical_certificates 
            WHERE control_number LIKE :prefix 
            ORDER BY control_number DESC 
            LIMIT 1
        ");
        $stmt->execute([':prefix' => $prefix . '%']);
        $lastControl = $stmt->fetchColumn();

        if ($lastControl) {
            $lastNum = intval(substr($lastControl, -6));
            $newNum = str_pad($lastNum + 1, 6, '0', STR_PAD_LEFT);
        } else {
            // First certificate of the year
            $newNum = "000001";
        }

        $control_number = $prefix . $newNum;

        // 🔹 Insert with control number and new fields
        $sql = "INSERT INTO medical_certificates (
            patient_id, visit_id, control_number, issuance_date, date_of_examination, 
            diagnosis, findings, fit_status, remarks, purpose,
            rest_period_days, rest_from_date, rest_to_date, 
            lab_cbc, lab_urinalysis, lab_fecalysis, lab_hbsag, lab_cxr, lab_sputum_afb, lab_other,
            issued_by, prepared_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $patient_id, $visit_id, $control_number, $issuance_date, $date_of_examination,
            $diagnosis, $findings, $fit_status, $remarks, $purpose,
            $rest_period_days, $rest_from_date, $rest_to_date,
            $lab_cbc, $lab_urinalysis, $lab_fecalysis, $lab_hbsag, $lab_cxr, $lab_sputum_afb, $lab_other,
            $issued_by, $prepared_by
        ]);

        $medcert_id = $pdo->lastInsertId();

        //ADDED GENERATED MED CERT FOR ACTIVITY LOG
        if ($medcert_id) {
            $stmt_log = $pdo->prepare("
                INSERT INTO logs (user_id, action, performed_by, user_affected)
                VALUES (:user_id, :action, :performed_by, :user_affected)
            ");
            $stmt_log->execute([
                ':user_id' => $_SESSION['user_id'],
                ':action' => 'Generated Medical Certificate (' . $control_number . ')',
                ':performed_by' => $_SESSION['user_id'],
                ':user_affected' => $patient_id
            ]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Medical certificate issued successfully',
            'medcert_id' => $medcert_id,
            'control_number' => $control_number
        ]);
        exit;

    } else {
        echo json_encode(['success' => false, 'message' => 'Database connection object not found']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>