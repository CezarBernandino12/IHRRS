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

        $pdo->beginTransaction(); // Begin transaction

        function clean_input($data) {
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }

        function clean_input_recursive($data) {
            if (is_array($data)) {
                return array_map('clean_input_recursive', $data);
            }
            return clean_input($data);
        }

        // 🛑 Check required fields
        if (empty($_POST['doctor_id']) || empty($_POST['diagnosis']) || empty($_POST['visit_id'])) {
            echo json_encode(["status" => "error", "message" => "Missing required fields."]);
            exit;
        }

        $visit_id = clean_input($_POST['visit_id']);
        $doctor_id = clean_input($_POST['doctor_id']);
        $diagnosis = clean_input($_POST['diagnosis']);
        $status = clean_input($_POST['status']);
        $instructions = clean_input($_POST['remarks'] ?? '');
        $consultation_date = date("Y-m-d");
        $followup = clean_input($_POST['followup']);

        // 🔹 Get patient_id from bhs_visits
        $stmt_patient = $pdo->prepare("SELECT patient_id FROM bhs_visits WHERE visit_id = :visit_id");
        $stmt_patient->execute([':visit_id' => $visit_id]);
        $patient = $stmt_patient->fetch(PDO::FETCH_ASSOC);

        if (!$patient) {
            echo json_encode(["status" => "error", "message" => "Invalid visit_id. No matching patient found."]);
            exit;
        }

        $patient_id = $patient['patient_id'];

        // Handle photo upload
$photoPath = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $photoTmp = $_FILES['photo']['tmp_name'];
    $photoName = basename($_FILES['photo']['name']);
    $uploadDir = 'uploads/'; // Ensure this folder exists and is writable
    $photoPath = $uploadDir . time() . '_' . $photoName;

    // Save the file
    if (move_uploaded_file($photoTmp, $photoPath)) {
        // File uploaded successfully
    } else {
        echo "Error uploading photo.";
        exit;
    }
}

        // 🔹 Insert into rhu_consultations
        $stmt_consultation = $pdo->prepare("
            INSERT INTO rhu_consultations (patient_id, doctor_id, consultation_date, diagnosis, instruction_prescription, visit_id, lab_result_path, diagnosis_status, follow_up_date) 
            VALUES (:patient_id, :doctor_id, :consultation_date, :diagnosis, :instructions, :visit_id, :lab_result_path, :diagnosis_status, :followup)
        ");

        $stmt_consultation->execute([
            ':patient_id' => $patient_id,
            ':doctor_id' => $doctor_id,
            ':consultation_date' => $consultation_date,
            ':diagnosis' => $diagnosis,
            ':instructions' => $instructions,
            ':visit_id' => $visit_id,
            ':lab_result_path' => $photoPath ? $photoPath : null,
            ':diagnosis_status' => $status,
            ':followup' => $followup
        ]);

        $consultation_id = $pdo->lastInsertId();

        // 🔹 Insert dispensed medicines
        if (!empty($_POST['medicine_given']) && is_array($_POST['medicine_given'])) {
            $_POST['medicine_given'] = clean_input_recursive($_POST['medicine_given']);
            $_POST['quantity_given'] = clean_input_recursive($_POST['quantity_given']);

            $stmt_medicine = $pdo->prepare("
                INSERT INTO rhu_medicine_dispensed (consultation_id, medicine_name, quantity_dispensed, dispensed_by, dispensed_date) 
                VALUES (:consultation_id, :medicine_name, :quantity_dispensed, :dispensed_by, NOW())
            ");

            foreach ($_POST['medicine_given'] as $key => $medicine) {
                if (!empty($medicine) && isset($_POST['quantity_given'][$key]) && $_POST['quantity_given'][$key] > 0) {
                    $stmt_medicine->execute([
                        ':consultation_id' => $consultation_id,
                        ':medicine_name' => $medicine,
                        ':quantity_dispensed' => $_POST['quantity_given'][$key], 
                        ':dispensed_by' => $doctor_id
                    ]);
                }
            }
        }

        if (!empty($_POST['followup'])) {
            $stmt_followup = $pdo->prepare("
                INSERT INTO follow_ups (consultation_id, date, set_by, followup_status, patient_id) 
                VALUES (:consultation_id, :followup, :set_by, :status, :patient_id)
            ");
            $stmt_followup->execute([
                ':consultation_id' => $consultation_id,
                ':followup' => date("Y-m-d", strtotime($_POST['followup'])),
                ':set_by' => $doctor_id,
                ':status' => 'Pending',
                ':patient_id' => $patient_id
            ]);
        }

        // 🔹 Update referral_status in referrals table
        $stmt_update_referral = $pdo->prepare("
            UPDATE referrals 
            SET referral_status = 'Completed'
            WHERE visit_id = :visit_id
        ");
        $stmt_update_referral->execute([':visit_id' => $visit_id]);

        $pdo->commit(); // ✅ Commit transaction if everything is successful

        echo json_encode(["status" => "success", "message" => "Record saved successfully!"]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error: " . $e->getMessage());
        echo json_encode(["status" => "error", "message" => "Error saving record: " . $e->getMessage()]);
    }
}
?>
