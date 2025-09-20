<?php
require '../../php/db_connect.php';

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
        if (empty($_POST['user_id'])) {
            echo json_encode(["status" => "error", "message" => "Submission unsuccessful."]);
            exit;
        }

        $temperature = clean_input($_POST['temperature'] ?? '');
        $weight = clean_input($_POST['weight'] ?? '');
        $height = clean_input($_POST['height'] ?? '');
        $blood_pressure = clean_input($_POST['blood_pressure'] ?? '');
        $pulse_rate = clean_input($_POST['pulse_rate'] ?? '');
        $respiratory_rate = clean_input($_POST['respiratory_rate'] ?? '');
        $bmi = clean_input($_POST['bmi'] ?? '');

        $patient_id = clean_input($_POST['patient_id']); // old ID (reference)
        $user_id = clean_input($_POST['user_id']);
        $instructions = clean_input($_POST['rhu_remarks'] ?? '');
        $followup = clean_input($_POST['followup']);

        // 🔹 Step 1: Always create a NEW visit record in patient_assessment
        $stmt_assessment = $pdo->prepare("
            INSERT INTO patient_assessment 
            (patient_id, recorded_by, bmi, temperature, height, weight, blood_pressure, chest_rate, respiratory_rate, visit_date, remarks) 
            VALUES (:patient_id, :user_id, :bmi, :temperature, :height, :weight, :blood_pressure, :pulse_rate, :respiratory_rate, NOW(), :remarks)
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
            ':respiratory_rate' => $respiratory_rate,
            ':remarks' => $instructions
        ]);

        // 🔹 This is the NEW visit_id you should use everywhere
        $new_visit_id = $pdo->lastInsertId();

    

    
        // 🔹 Insert follow-up
        if (!empty($followup)) {
            $stmt_followup = $pdo->prepare("
                INSERT INTO follow_ups (visit_id, date, set_by, followup_status, patient_id) 
                VALUES (:visit_id, :followup, :set_by, :status, :patient_id)
            ");
            $stmt_followup->execute([
                ':visit_id' => $new_visit_id,
                ':followup' => date("Y-m-d", strtotime($followup)),
                ':set_by' => $user_id,
                ':status' => 'Pending',
                ':patient_id' => $patient_id
            ]);
        }



        $pdo->commit();

        echo json_encode([
              "status" => "success",
             "message" => "Assessment saved successfully!",
             "action" => "assessment"
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>
