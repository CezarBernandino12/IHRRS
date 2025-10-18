<?php 
require '../../php/db_connect.php';

ob_start(); 
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 0 to prevent HTML error output

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    file_put_contents("logs.txt", json_encode($_POST) . PHP_EOL, FILE_APPEND);
    error_log("\ud83d\udccc Received data: " . json_encode($_POST));

    try {
        if (!$pdo) {
            throw new Exception("Database connection failed.");
        }

        $pdo->beginTransaction();

        function clean_input($data) {
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }

        function validate_required($data, $required_fields) {
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
        }

        $referralNeeded = clean_input($_POST['referralNeeded'] ?? 'no');

        $patient_id = clean_input($_POST['patient_id'] ?? '');
        if (empty($patient_id)) {
            echo json_encode(["status" => "error", "message" => "Missing patient ID."]);
            exit;
        }

        validate_required($_POST, ['user_id', 'bp', 'temp']);

        $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT) ?: 0;
        $bp = clean_input($_POST['bp']);
        $temp = filter_var($_POST['temp'], FILTER_VALIDATE_FLOAT) ?: 0;
        $bmi = filter_var($_POST['bmi'], FILTER_VALIDATE_FLOAT) ?: null;
        $weight = filter_var($_POST['weight'], FILTER_VALIDATE_FLOAT) ?: null;
        $height = filter_var($_POST['height'], FILTER_VALIDATE_FLOAT) ?: null;
        $pr = filter_var($_POST['pr'], FILTER_VALIDATE_FLOAT) ?: null;
        $rr = filter_var($_POST['rr'], FILTER_VALIDATE_FLOAT) ?: null;

        $chief_complaints = clean_input($_POST['chief_complaints'] ?? '');
        $remarks = clean_input($_POST['remarks'] ?? '');
        $patient_alert = clean_input($_POST['patient_alert'] ?? '');
        $treatment = clean_input($_POST['treatment'] ?? '');

        $stmt_visit = $pdo->prepare("INSERT INTO patient_assessment (
            patient_id, recorded_by, visit_date, blood_pressure, temperature, bmi, weight, height, 
            chest_rate, respiratory_rate, chief_complaints, remarks, patient_alert, treatment
        ) VALUES (
            :patient_id, :user_id, DATE(CURDATE()), :bp, :temp, :bmi, :weight, :height, 
            :pr, :rr, :chief_complaints, :remarks, :patient_alert, :treatment
        )");

        $stmt_visit->execute([
            ':patient_id' => $patient_id,  
            ':user_id' => $user_id, 
            ':bp' => $bp,
            ':temp' => $temp,
            ':bmi' => $bmi,
            ':weight' => $weight,
            ':height' => $height,
            ':pr' => $pr,
            ':rr' => $rr,
            ':chief_complaints' => $chief_complaints,
            ':remarks' => $remarks,
            ':patient_alert' => $patient_alert,
            ':treatment' => $treatment
        ]);

        $visit_id = $pdo->lastInsertId();
        $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT) ?: 0;
       

        if ($visit_id) {
            //ADDED PATIENT ASSESSMENT RECORD FOR ACTIVITY LOG
    $stmt_log = $pdo->prepare("INSERT INTO logs (
        user_id, action, performed_by, user_affected
    ) VALUES (
        :user_id, :action, :performed_by, :user_affected
    )");

    $stmt_log->execute([
        ':user_id' => $user_id,
        ':action' => "Added Patient Assessment Record",
        ':performed_by' => $user_id,
        ':user_affected' => $patient_id
    ]);
    
        }

        if (!empty($_POST['medicine_given']) && is_array($_POST['medicine_given'])) {
            $stmt_medicine = $pdo->prepare("INSERT INTO bhs_medicine_dispensed (
                visit_id, medicine_name, quantity_dispensed, dispensed_by, dispensed_date
            ) VALUES (
                :visit_id, :medicine_name, :quantity_dispensed, :dispensed_by, NOW()
            )");

            foreach ($_POST['medicine_given'] as $index => $medicine_name) {
                $medicine_name = clean_input($medicine_name);
                $quantity_dispensed = filter_var($_POST['quantity_given'][$index] ?? 0, FILTER_VALIDATE_INT) ?: 0;

                if ($medicine_name && $quantity_dispensed > 0) {
                    $stmt_medicine->execute([
                        ':visit_id' => $visit_id,
                        ':medicine_name' => $medicine_name,
                        ':quantity_dispensed' => $quantity_dispensed,
                        ':dispensed_by' => $user_id
                    ]);
                }
            }

            $dispensed_id = $pdo->lastInsertId();
            if ($dispensed_id) {
                //ADDED MEDICINE DISPENSED RECORD FOR ACTIVITY LOG
        $stmt_log2 = $pdo->prepare("INSERT INTO logs (
            user_id, action, performed_by, user_affected
        ) VALUES (
            :user_id, :action, :performed_by, :user_affected
        )");

        $stmt_log2->execute([
            ':user_id' => $user_id,
            ':action' => "Dispensed Medicine to Patient",
            ':performed_by' => $user_id,
            ':user_affected' => $patient_id
        ]);
    
        }

        }

        $referral_id = null;
        error_log("Referral Needed Value: " . $referralNeeded);

        if ($referralNeeded === "yes" && !empty($patient_id) && !empty($user_id)) {
            $referral_status = clean_input($_POST['referral_status'] ?? 'pending');

            $stmt_referral = $pdo->prepare("INSERT INTO referrals (
                patient_id, visit_id, referred_by, referral_status
            ) VALUES (
                :patient_id, :visit_id, :referred_by, :referral_status
            )");

            $stmt_referral->execute([
                ':patient_id' => $patient_id,
                ':visit_id' => $visit_id,
                ':referred_by' => $user_id,
                ':referral_status' => $referral_status
            ]);

            $referral_id = $pdo->lastInsertId();
            error_log("\u2705 Referral saved with ID: " . $referral_id);
        } else {
            error_log("\u26a0\ufe0f Skipping referral.");
        }


        if ($referral_id) {
            //ADDED REFERRAL FOR ACTIVITY LOG
    $stmt_log3 = $pdo->prepare("INSERT INTO logs (
        user_id, action, performed_by, user_affected
    ) VALUES (
        :user_id, :action, :performed_by, :user_affected
    )");
    $stmt_log3->execute([
        ':user_id' => $user_id,
        ':action' => "Sent Referral to RHU",
        ':performed_by' => $user_id,
        ':user_affected' => $patient_id
    ]);
        }

        $pdo->commit();

        echo json_encode([
            "status" => "success",
            "message" => "Visit record saved successfully!",
            "patient_id" => $patient_id,
            "visit_id" => $visit_id,
            "referral_id" => $referral_id  
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["status" => "error", "message" => "Error: " . $e->getMessage()]);
    }
}
?>
