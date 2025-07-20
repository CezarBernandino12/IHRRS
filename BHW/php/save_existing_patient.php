<?php 
require '../../php/db_connect.php'; // Ensure this file correctly initializes $pdo

ob_start(); 
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (!$pdo) {
            throw new Exception("Database connection failed.");
        }

        $pdo->beginTransaction();

        function clean_input($data) {
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }

        function validate_required($data, $fields) {
            foreach ($fields as $field) {
                if (empty($data[$field])) {
                    echo json_encode(["status" => "error", "message" => "Missing required field: $field"]);
                    exit;
                }
            }
        }

        $referralNeeded = clean_input($_POST['referralNeeded'] ?? 'no');

        validate_required($_POST, ['firstName', 'lastName', 'dob', 'sex', 'civilStatus', 'permanent_address']);

        $first_name = clean_input($_POST['firstName']);
        $middle_name = clean_input($_POST['middleName'] ?? '');
        $last_name = clean_input($_POST['lastName']);
        $family_serial_no = clean_input($_POST['familySerialNo'] ?? '');
        $dob = clean_input($_POST['dob']);
        $age = clean_input($_POST['age'] ?? '');
        $sex = in_array($_POST['sex'], ['Male', 'Female']) ? $_POST['sex'] : null; // Ensure valid values
        $civil_status = clean_input($_POST['civilStatus']);
        $address = clean_input($_POST['permanent_address']);
        $birthplace = clean_input($_POST['birthplace'] ?? '');
        $contact_number = clean_input($_POST['mobile'] ?? '');
        $education = clean_input($_POST['education'] ?? '');
        $occupation = clean_input($_POST['occupation'] ?? '');
        $religion = clean_input($_POST['religion'] ?? '');
        $philhealth = clean_input($_POST['philhealth'] ?? '');
        $four_ps = clean_input($_POST['4ps'] ?? '');

        $is_new_patient = $_POST['is_new_patient'] ?? 'false';

        $check_stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE first_name = :first_name AND last_name = :last_name AND date_of_birth = :dob");
        $check_stmt->execute([':first_name' => $first_name, ':last_name' => $last_name, ':dob' => $dob]);
        $existing_patient = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($is_new_patient === 'true' || !$existing_patient) {
            // Always insert a new patient when "is_new_patient" is true
            $stmt_patient = $pdo->prepare("INSERT INTO patients (
                first_name, middle_name, last_name, family_serial_no, date_of_birth, age, 
                sex, civil_status, address, birthplace, contact_number, 
                educational_attainment, occupation, religion, philhealth_member_no, fourps_status
            ) VALUES (
                :first_name, :middle_name, :last_name, :family_serial_no, :dob, :age, 
                :sex, :civil_status, :address, :birthplace, :contact_number, 
                :education, :occupation, :religion, :philhealth, :four_ps
            )");
        
            $stmt_patient->execute([
                ':first_name' => $first_name,
                ':middle_name' => $middle_name,
                ':last_name' => $last_name,
                ':family_serial_no' => $family_serial_no,
                ':dob' => $dob,
                ':age' => $age,
                ':sex' => $sex,
                ':civil_status' => $civil_status,
                ':address' => $address,
                ':birthplace' => $birthplace,
                ':contact_number' => $contact_number,
                ':education' => $education,
                ':occupation' => $occupation,
                ':religion' => $religion,
                ':philhealth' => $philhealth,
                ':four_ps' => $four_ps
            ]);
        
            $patient_id = $pdo->lastInsertId();
        } else {
            // If the patient exists and "is_new_patient" is NOT true, use the existing one
            $patient_id = $existing_patient['patient_id'];
        }
        

        validate_required($_POST, ['user_id', 'bp', 'temp', 'weight', 'height', 'pr', 'rr']);

        $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT) ?: 0;
        $bp = clean_input($_POST['bp']);
        $temp = clean_input($_POST['temp']);
        $weight = clean_input($_POST['weight']);
        $height = clean_input($_POST['height']);
        $pr = clean_input($_POST['pr']);
        $rr = clean_input($_POST['rr']);
        $chief_complaints = clean_input($_POST['chief_complaints'] ?? '');
        $remarks = clean_input($_POST['remarks'] ?? '');

        $stmt_visit = $pdo->prepare("INSERT INTO bhs_visits (
            patient_id, bhw_id, visit_date, blood_pressure, temperature, weight, height, 
            chest_rate, respiratory_rate, chief_complaints, remarks
        ) VALUES (
            :patient_id, :user_id, NOW(), :bp, :temp, :weight, :height, 
            :pr, :rr, :chief_complaints, :remarks
        )");

        $stmt_visit->execute([
            ':patient_id' => $patient_id,  
            ':user_id' => $user_id, 
            ':bp' => $bp,
            ':temp' => $temp,
            ':weight' => $weight,
            ':height' => $height,
            ':pr' => $pr,
            ':rr' => $rr,
            ':chief_complaints' => $chief_complaints,
            ':remarks' => $remarks
        ]);

        $visit_id = $pdo->lastInsertId();

        if (!empty($_POST['medicine_given']) && is_array($_POST['medicine_given'])) {
            $stmt_medicine = $pdo->prepare("INSERT INTO bhs_medicine_dispensed (
                visit_id, medicine_name, quantity_dispensed, dispensed_by, dispensed_date
            ) VALUES (
                :visit_id, :medicine_name, :quantity_dispensed, :dispensed_by, NOW()
            )");

            foreach ($_POST['medicine_given'] as $index => $medicine_name) {
                $medicine_name = clean_input($medicine_name);
                $quantity_dispensed = clean_input($_POST['quantity_given'][$index] ?? '0');

                if ($medicine_name && $quantity_dispensed > 0) {
                    $stmt_medicine->execute([
                        ':visit_id' => $visit_id,
                        ':medicine_name' => $medicine_name,
                        ':quantity_dispensed' => $quantity_dispensed,
                        ':dispensed_by' => $user_id
                    ]);
                }
            }
        }

        $referral_id = null;
        if ($referralNeeded === "yes") {
            $referral_status = clean_input($_POST['referral_status'] ?? 'pending');

            $stmt_referral = $pdo->prepare("INSERT INTO referrals (patient_id, referred_by, referral_status) VALUES (
                :patient_id, :user_id, :referral_status
            )");

            $stmt_referral->execute([
                ':patient_id' => $patient_id,
                ':user_id' => $user_id,
                ':referral_status' => $referral_status
            ]);

            $referral_id = $pdo->lastInsertId();
        }

        $pdo->commit();

        echo json_encode([
            "status" => "success",
            "message" => "Patient information and visit record saved successfully!",
            "patient_id" => $patient_id,
            "referral_id" => $referral_id  
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["status" => "error", "message" => "Error: " . $e->getMessage()]);
    }
}
?>
