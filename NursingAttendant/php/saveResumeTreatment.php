<?php
require '../../php/db_connect.php';
require '../../ADMIN/php/log_functions.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (!$pdo) {
            throw new Exception("Database connection failed.");
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

        // ✅ Required fields
        if (empty($_POST['user_id']) || empty($_POST['diagnosis']) || empty($_POST['visit_id'])) {
            echo json_encode(["status" => "error", "message" => "Missing required fields."]);
            exit;
        }

        $temperature = clean_input($_POST['temperature'] ?? '');
        $weight = clean_input($_POST['weight'] ?? '');
        $height = clean_input($_POST['height'] ?? '');
        $blood_pressure = clean_input($_POST['blood_pressure'] ?? '');
        $pulse_rate = clean_input($_POST['pulse_rate'] ?? '');
        $respiratory_rate = clean_input($_POST['respiratory_rate'] ?? '');
        $bmi = clean_input($_POST['bmi'] ?? '');

        $old_visit_id = clean_input($_POST['visit_id']); // old ID (reference)
        $user_id = clean_input($_POST['user_id']);
        $diagnosis = clean_input($_POST['diagnosis']);
        $status = clean_input($_POST['status']);
        $instructions = clean_input($_POST['remarks'] ?? '');
        $consultation_date = date("Y-m-d");
        $followup = clean_input($_POST['followup']);
        $physician = clean_input($_POST['physician'] ?? '');

        // 🔹 Get patient_id using old visit_id
        $stmt_patient = $pdo->prepare("SELECT patient_id FROM rhu_consultations WHERE visit_id = :visit_id");
        $stmt_patient->execute([':visit_id' => $old_visit_id]);
        $patient = $stmt_patient->fetch(PDO::FETCH_ASSOC);

        if (!$patient) {
            throw new Exception("Invalid visit_id. No matching patient found.");
        }

        $patient_id = $patient['patient_id'];

        // 🔹 Step 1: Always create a NEW visit record in patient_assessment
        $stmt_assessment = $pdo->prepare("
            INSERT INTO patient_assessment 
            (patient_id, recorded_by, bmi, temperature, height, weight, blood_pressure, chest_rate, respiratory_rate, visit_date) 
            VALUES (:patient_id, :user_id, :bmi, :temperature, :height, :weight, :blood_pressure, :pulse_rate, :respiratory_rate, NOW())
        ");
        $stmt_assessment->execute([
            ':patient_id' => $patient_id,
            ':user_id' => $user_id,
            ':bmi' => $bmi,
            ':temperature' => $temperature,
            ':height' => $height,
            ':weight' => $weight,
            ':blood_pressure' => $blood_pressure,
            ':pulse_rate' => $pulse_rate,
            ':respiratory_rate' => $respiratory_rate
        ]);
 
        // 🔹 This is the NEW visit_id you should use everywhere
        $new_visit_id = $pdo->lastInsertId();

        // 🔹 Handle photo upload
        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photoTmp = $_FILES['photo']['tmp_name'];
            $photoName = basename($_FILES['photo']['name']);
            $uploadDir = 'uploads/'; 
            $photoPath = $uploadDir . time() . '_' . $photoName;

            if (!move_uploaded_file($photoTmp, $photoPath)) {
                throw new Exception("Error uploading photo.");
            }
        }


  
    $stmt_consultation = $pdo->prepare("
        INSERT INTO rhu_consultations 
        (patient_id, recorded_by, doctor_id, consultation_date, diagnosis, instruction_prescription, visit_id, lab_result_path, diagnosis_status, follow_up_date) 
        VALUES (:patient_id, :recorded_by, :physician, :consultation_date, :diagnosis, :instructions, :visit_id, :lab_result_path, :diagnosis_status, :followup)
    ");
    $stmt_consultation->execute([
        ':patient_id' => $patient_id,
        ':recorded_by' => $user_id,
        ':physician' => $physician,
        ':consultation_date' => $consultation_date,
        ':diagnosis' => $diagnosis,
        ':instructions' => $instructions,
        ':visit_id' => $new_visit_id,
        ':lab_result_path' => $photoPath,
        ':diagnosis_status' => $status,
        ':followup' => $followup
    ]);

    $consultation_id = $pdo->lastInsertId();


    //Update all related consultations for same patient & diagnosis

        $stmt_update_status = $pdo->prepare("
            UPDATE rhu_consultations
            SET diagnosis_status = :new_status
            WHERE patient_id = :patient_id
              AND diagnosis = :diagnosis
        ");
        $stmt_update_status->execute([
            ':new_status' => $status,
            ':patient_id' => $patient_id,
            ':diagnosis' => $diagnosis
        ]);



        // 🔹 Insert follow-up
        if (!empty($followup)) {
            $stmt_followup = $pdo->prepare("
                INSERT INTO follow_ups (consultation_id, date, set_by, followup_status, patient_id) 
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

        // 🔹 Update referral_status for OLD visit_id (not the new one)
        $stmt_update_referral = $pdo->prepare("
            UPDATE referrals SET referral_status = 'Completed' WHERE visit_id = :visit_id
        ");
        $stmt_update_referral->execute([':visit_id' => $old_visit_id]);


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


        $pdo->commit();

        echo json_encode([
            "status" => "success",
            "new_visit_id" => $new_visit_id,
            'message' => 'Record saved successfully!',
            'consultation_id' => $consultation_id,
            'hasMedicine' => $medicine_dispensed,
            'hasPrescription' => $prescriptionSaved
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>
