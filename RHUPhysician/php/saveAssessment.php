<?php
// debug-friendly version of your script
require '../../php/db_connect.php';

// Toggle this to false when you're done debugging
$DEBUG = true;

if ($DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

header('Content-Type: application/json; charset=utf-8');

/**
 * Send a structured JSON error for debugging.
 * Includes exception details when available and extra diagnostic info.
 */
function sendErrorJson($message, $exception = null, $extra = [], $http_code = 500) {
    if (!headers_sent()) {
        http_response_code($http_code);
        header('Content-Type: application/json; charset=utf-8');
    }

    $payload = [
        'status' => 'error',
        'message' => $message,
    ];

    if (!empty($extra)) {
        $payload['extra'] = $extra;
    }

    if ($exception !== null && is_object($exception)) {
        $payload['exception'] = [
            'type'    => get_class($exception),
            'message' => $exception->getMessage(),
            'code'    => $exception->getCode(),
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
            'trace'   => $exception->getTraceAsString()
        ];
        if ($exception instanceof PDOException && isset($exception->errorInfo)) {
            $payload['exception']['errorInfo'] = $exception->errorInfo;
        }
        // include previous exception chain
        $prev = $exception->getPrevious();
        if ($prev) {
            $payload['exception']['previous'] = [
                'type' => get_class($prev),
                'message' => $prev->getMessage(),
                'file' => $prev->getFile(),
                'line' => $prev->getLine()
            ];
        }
    }

    echo json_encode($payload, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Simple sanitizer for debug output (truncate very long values)
 */
function sanitize_for_debug($data) {
    if (is_array($data)) {
        $out = [];
        foreach ($data as $k => $v) {
            $out[$k] = sanitize_for_debug($v);
        }
        return $out;
    }
    if (is_string($data)) {
        return mb_substr($data, 0, 1000);
    }
    return $data;
}

/**
 * Catch fatal errors that bypass the try/catch block.
 */
register_shutdown_function(function() use ($DEBUG) {
    $err = error_get_last();
    if ($err !== null) {
        $fatal_types = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (in_array($err['type'], $fatal_types, true)) {
            $extra = ['php_error' => $err];
            if ($DEBUG) {
                sendErrorJson('Fatal PHP error', (object)[
                    'message' => $err['message'],
                    'file' => $err['file'],
                    'line' => $err['line'],
                    'trace' => ''
                ], $extra, 500);
            } else {
                sendErrorJson('A fatal error occurred. Enable debug for details.', null, $extra, 500);
            }
        }
    }
});

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        sendErrorJson("Invalid request method. POST required.", null, ['method' => $_SERVER['REQUEST_METHOD']], 405);
    }

    // Check $pdo from db_connect.php
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection failed or \$pdo is not set.");
    }

    // ensure PDO throws exceptions
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->beginTransaction();

    // sanitize helpers
    function clean_input($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    function clean_input_recursive($data) {
        if (is_array($data)) {
            return array_map('clean_input_recursive', $data);
        }
        return clean_input($data);
    }

    // required check
    if (empty($_POST['user_id'])) {
        sendErrorJson("Submission unsuccessful. 'user_id' is required.", null, ['post'=> sanitize_for_debug($_POST)], 400);
    }

    // gather inputs
    $temperature       = clean_input($_POST['temperature'] ?? '');
    $weight            = clean_input($_POST['weight'] ?? '');
    $height            = clean_input($_POST['height'] ?? '');
    $blood_pressure    = clean_input($_POST['blood_pressure'] ?? '');
    $pulse_rate        = clean_input($_POST['pulse_rate'] ?? '');
    $respiratory_rate  = clean_input($_POST['respiratory_rate'] ?? '');
    $bmi               = clean_input($_POST['bmi'] ?? '');
    $patient_id        = clean_input($_POST['patient_id'] ?? '');
    $user_id           = clean_input($_POST['user_id']);
    $instructions      = clean_input($_POST['rhu_remarks'] ?? '');
    $diagnosis         = clean_input($_POST['diagnosis'] ?? '');
    $status            = clean_input($_POST['status'] ?? '');
    $consultation_date = date("Y-m-d");
    $followup          = clean_input($_POST['followup'] ?? '');

    // Handle photo upload
    $photoPath = null;
    if (isset($_FILES['photo']) && is_array($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photoTmp  = $_FILES['photo']['tmp_name'];
        $photoName = basename($_FILES['photo']['name']);
        $uploadDir = 'uploads/';
        $photoPath = rtrim($uploadDir, '/') . '/' . time() . '_' . $photoName;

        if (!move_uploaded_file($photoTmp, $photoPath)) {
            throw new Exception("Error moving uploaded photo to: {$photoPath} (check folder exists and is writable).");
        }
    }

    // Step 1: Insert into patient_assessment
    $stmt_assessment = $pdo->prepare("
        INSERT INTO patient_assessment 
        (patient_id, recorded_by, bmi, temperature, height, weight, blood_pressure, chest_rate, respiratory_rate, visit_date) 
        VALUES (:patient_id, :user_id, :bmi, :temperature, :height, :weight, :blood_pressure, :pulse_rate, :respiratory_rate, NOW())
    ");
    $stmt_assessment->execute([
        ':patient_id'       => $patient_id,
        ':user_id'          => $user_id,
        ':bmi'              => $bmi,
        ':temperature'      => $temperature,
        ':height'           => $height,
        ':weight'           => $weight,
        ':blood_pressure'   => $blood_pressure,
        ':pulse_rate'       => $pulse_rate,
        ':respiratory_rate' => $respiratory_rate
    ]);
    $new_visit_id = $pdo->lastInsertId();
    $consultation_id = null;

    // Step 2: Insert consultation if diagnosis provided
    if (!empty($diagnosis)) {
        $stmt_consultation = $pdo->prepare("
            INSERT INTO rhu_consultations 
            (patient_id, doctor_id, consultation_date, diagnosis, instruction_prescription, visit_id, lab_result_path, diagnosis_status, follow_up_date) 
            VALUES (:patient_id, :doctor_id, :consultation_date, :diagnosis, :instructions, :visit_id, :lab_result_path, :diagnosis_status, :followup)
        ");
        $stmt_consultation->execute([
            ':patient_id'       => $patient_id,
            ':doctor_id'        => $user_id,
            ':consultation_date'=> $consultation_date,
            ':diagnosis'        => $diagnosis,
            ':instructions'     => $instructions,
            ':visit_id'         => $new_visit_id,
            ':lab_result_path'  => $photoPath ?: null,
            ':diagnosis_status' => $status,
            ':followup'         => !empty($followup) ? $followup : null
        ]);

        $consultation_id = $pdo->lastInsertId();
    }

    // Step 3: Insert dispensed medicines (only if consultation exists)
    if (!empty($consultation_id) && !empty($_POST['medicine_given']) && is_array($_POST['medicine_given'])) {
        $_POST['medicine_given'] = clean_input_recursive($_POST['medicine_given']);
        $_POST['quantity_given'] = clean_input_recursive($_POST['quantity_given'] ?? []);

        $stmt_medicine = $pdo->prepare("
            INSERT INTO rhu_medicine_dispensed 
            (consultation_id, medicine_name, quantity_dispensed, dispensed_by, dispensed_date) 
            VALUES (:consultation_id, :medicine_name, :quantity_dispensed, :dispensed_by, NOW())
        ");

        foreach ($_POST['medicine_given'] as $key => $medicine) {
            if (!empty($medicine) && isset($_POST['quantity_given'][$key]) && $_POST['quantity_given'][$key] > 0) {
                $stmt_medicine->execute([
                    ':consultation_id'   => $consultation_id,
                    ':medicine_name'     => $medicine,
                    ':quantity_dispensed'=> $_POST['quantity_given'][$key],
                    ':dispensed_by'      => $user_id
                ]);
            }
        }
    }

    // Step 4: Insert follow-up
    if (!empty($followup)) {
        $stmt_followup = $pdo->prepare("
            INSERT INTO follow_ups (visit_id, date, set_by, followup_status, patient_id) 
            VALUES (:visit_id, :followup, :set_by, :status, :patient_id)
        ");
        $stmt_followup->execute([
            ':visit_id'   => $new_visit_id,
            ':followup'   => date("Y-m-d", strtotime($followup)),
            ':set_by'     => $user_id,
            ':status'     => 'Pending',
            ':patient_id' => $patient_id
        ]);
    }

    $pdo->commit();

    echo json_encode([
        "status"  => "success",
        "message" => "Assessment saved successfully!",
        "action"  => "assessment"
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // Try to rollback if a transaction is still active
    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (Exception $rollEx) {
            // ignore rollback failures, but include in debug output
        }
    }

    // Prepare debug snapshot of POST/FILES (sanitized)
    $postDebug = sanitize_for_debug($_POST ?? []);
    $filesDebug = [];
    if (!empty($_FILES)) {
        foreach ($_FILES as $k => $f) {
            $filesDebug[$k] = [
                'name' => $f['name'] ?? null,
                'type' => $f['type'] ?? null,
                'size' => $f['size'] ?? null,
                'error' => $f['error'] ?? null,
                'tmp_name' => $f['tmp_name'] ?? null
            ];
        }
    }

    $extra = [
        'post' => $postDebug,
        'files' => $filesDebug,
    ];

    sendErrorJson("Exception caught: " . $e->getMessage(), $e, $extra, 500);
}
?>
