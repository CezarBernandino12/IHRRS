<?php
session_start();
require '../../php/db_connect.php';
require '../../ADMIN/php/log_functions.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

try {
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection failed.");
    }

    $pdo->beginTransaction();

    function clean_input($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    function clean_input_recursive($data) {
        if (is_array($data)) {
            return array_map('clean_input_recursive', $data);
        }
        return clean_input($data);
    }

    if (empty($_POST['user_id']) || empty($_POST['diagnosis']) || empty($_POST['visit_id'])) {
        throw new Exception("Missing required fields.");
    }

    $visit_id = clean_input($_POST['visit_id']);
    $user_id = clean_input($_POST['user_id']);
    $diagnosis = clean_input($_POST['diagnosis']);
    $status = clean_input($_POST['status'] ?? '');
    $physician = clean_input($_POST['physician2'] ?? '');
if (empty($physician)) {
    throw new Exception("Physician selection is required.");
}
$physician = intval($physician);
    $remarks = clean_input($_POST['rhu_remarks'] ?? '');
    $consultation_date = date("Y-m-d");
    $followup = isset($_POST['followup']) ? clean_input($_POST['followup']) : null;

    // Get patient_id
    $stmt_patient = $pdo->prepare("SELECT patient_id FROM patient_assessment WHERE visit_id = :visit_id");
    $stmt_patient->execute([':visit_id' => $visit_id]);
    $patient = $stmt_patient->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        throw new Exception("Invalid visit_id. No matching patient found.");
    }

    $patient_id = $patient['patient_id'];

    // Handle photo upload
    $photoPath = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photoTmp = $_FILES['photo']['tmp_name'];
        $photoName = basename($_FILES['photo']['name']);
        $uploadDir = 'uploads';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $photoPath = $uploadDir . '/' . time() . '_' . $photoName;

        if (!move_uploaded_file($photoTmp, $photoPath)) {
            throw new Exception("Error uploading photo.");
        }
    }

    // Insert into rhu_consultations
    $stmt_consultation = $pdo->prepare("
        INSERT INTO rhu_consultations 
        (patient_id, doctor_id, recorded_by, consultation_date, diagnosis, instruction_prescription, visit_id, lab_result_path, diagnosis_status, follow_up_date) 
        VALUES 
        (:patient_id, :physician, :user_id, :consultation_date, :diagnosis, :remarks, :visit_id, :lab_result_path, :diagnosis_status, :followup)
    ");

    $stmt_consultation->execute([
        ':patient_id' => $patient_id,
        ':physician' => $physician,
        ':user_id' => $user_id,
        ':consultation_date' => $consultation_date,
        ':diagnosis' => $diagnosis,
        ':remarks' => $remarks,
        ':visit_id' => $visit_id,
        ':lab_result_path' => $photoPath ?? null,
        ':diagnosis_status' => $status,
        ':followup' => !empty($followup) ? date("Y-m-d", strtotime($followup)) : null
    ]);

    $consultation_id = $pdo->lastInsertId();

    if ($consultation_id) {
        logActivity($pdo, $user_id, "Added Diagnosis/Consultation Record");
    }

    // Update related consultations if status not "Ongoing"
    if ($status !== 'Ongoing') {
        $stmt_update_status = $pdo->prepare("
            UPDATE rhu_consultations
            SET diagnosis_status = :new_status
            WHERE patient_id = :patient_id
              AND diagnosis = :diagnosis
              AND diagnosis_status != 'Ongoing'
        ");
        $stmt_update_status->execute([
            ':new_status' => $status,
            ':patient_id' => $patient_id,
            ':diagnosis' => $diagnosis
        ]);
    }

    // ✅ IMPROVED: Track if medicines were dispensed
    $medicine_dispensed = false;
    if (!empty($_POST['medicine_given']) && is_array($_POST['medicine_given'])) {
        $_POST['medicine_given'] = clean_input_recursive($_POST['medicine_given']);
        $_POST['quantity_given'] = clean_input_recursive($_POST['quantity_given'] ?? []);
        $_POST['med_instruction'] = clean_input_recursive($_POST['med_instruction'] ?? []);

        $stmt_medicine_dispensed = $pdo->prepare("
            INSERT INTO rhu_medicine_dispensed 
            (consultation_id, medicine_name, quantity_dispensed, instruction, dispensed_by, dispensed_date) 
            VALUES 
            (:consultation_id, :medicine_name, :quantity_dispensed, :instruction, :dispensed_by, NOW())
        ");

        foreach ($_POST['medicine_given'] as $key => $medicine) {
            if (!empty($medicine) && isset($_POST['quantity_given'][$key]) && $_POST['quantity_given'][$key] > 0) {
                $stmt_medicine_dispensed->execute([
                    ':consultation_id' => $consultation_id,
                    ':medicine_name' => $medicine,
                    ':quantity_dispensed' => $_POST['quantity_given'][$key],
                    ':instruction' => $_POST['med_instruction'][$key] ?? '',
                    ':dispensed_by' => $physician
                ]);
                $medicine_dispensed = true;
            }
        }
    }

    // Insert follow-up
    if (!empty($followup)) {
        $stmt_followup = $pdo->prepare("
            INSERT INTO follow_ups 
            (consultation_id, date, set_by, followup_status, patient_id) 
            VALUES (:consultation_id, :followup, :set_by, :status, :patient_id)
        ");
        $stmt_followup->execute([
            ':consultation_id' => $consultation_id,
            ':followup' => date("Y-m-d", strtotime($followup)),
            ':set_by' => $user_id,
            ':status' => 'Pending',
            ':patient_id' => $patient_id
        ]);
    }

    // Update referral status
    $stmt_update_referral = $pdo->prepare("
        UPDATE referrals 
        SET referral_status = 'Completed'
        WHERE visit_id = :visit_id
    ");
    $stmt_update_referral->execute([':visit_id' => $visit_id]);

    // ✅ LOG: Dispensed Medicine (after transaction confirms it was saved)
    if ($medicine_dispensed) {
        logActivity($pdo, $user_id, "Dispensed Medicine to Patient (RHU)");
    }

    // ✅ IMPROVED: Track if prescriptions were generated
    $prescriptionSaved = false;
    $medicines = array_filter($_POST['medicine_prescription'] ?? [], fn($m) => trim($m) !== '');

    if (!empty($medicines)) {
        $_POST['medicine_prescription'] = clean_input_recursive($_POST['medicine_prescription']);
        $_POST['quantity_prescription'] = clean_input_recursive($_POST['quantity_prescription'] ?? []);
        $_POST['prescription_instruction'] = clean_input_recursive($_POST['prescription_instruction'] ?? []);

        foreach ($medicines as $key => $medicine) {
            if (empty($_POST['prescription_instruction'][$key] ?? '')) {
                throw new Exception("All prescription instruction fields must be filled.");
            }
        }

        if (empty($_POST['physician'])) {
            throw new Exception("Physician fields cannot be empty.");
        }

 $stmt_prescription = $pdo->prepare("
            INSERT INTO prescription 
            (consultation_id, medicine_name, quantity, instruction, date, physician) 
            VALUES (?, ?, ?, ?, NOW(), ?)
        ");

        foreach ($medicines as $key => $medicine) {
            if (isset($_POST['quantity_prescription'][$key]) && intval($_POST['quantity_prescription'][$key]) > 0) {
                $stmt_prescription->execute([
                    $consultation_id,
                    $medicine,
                    intval($_POST['quantity_prescription'][$key]),
                    $_POST['prescription_instruction'][$key] ?? '',
                    intval($_POST['physician'])
                ]);
                $prescriptionSaved = true;
            }
        }
    }

    if ($prescriptionSaved && function_exists('logActivity')) {
        logActivity($pdo, $user_id, "Generated Prescription");
    }

    // Commit transaction
    $pdo->commit();

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Record saved successfully!',
        'consultation_id' => $consultation_id,
        'hasPrescription' => $prescriptionSaved
    ]);
    exit;

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Consultation Error: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        "status" => "error", 
        "message" => $e->getMessage()
    ]);
    exit;
}
?>