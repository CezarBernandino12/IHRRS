<?php
require '../../php/db_connect.php';

ob_start();
header('Content-Type: application/json');

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {
        if (!$pdo) {
            throw new Exception("Database connection failed.");
        }

        $pdo->beginTransaction();

        /* -----------------------------------------------------
           Helpers
        ----------------------------------------------------- */
        function clean($v) {
            return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
        }

        function required($data, $fields) {
            foreach ($fields as $f) {
                if (!isset($data[$f]) || $data[$f] === "") {
                    echo json_encode(["status" => "error", "message" => "Missing required field: $f"]);
                    exit;
                }
            }
        }

        /* -----------------------------------------------------
           Retrieve & Validate Basic POST Data
        ----------------------------------------------------- */
        $referralNeeded = clean($_POST['referralNeeded'] ?? 'no');

        // SET USER ID FIRST — VERY IMPORTANT
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;

        // Required fields for patient record
        required($_POST, ['firstName', 'lastName', 'dob', 'sex', 'civilStatus', 'permanent_address']);

        /* -----------------------------------------------------
           Collect Patient Fields
        ----------------------------------------------------- */
        $first_name = clean($_POST['firstName']);
        $middle_name = clean($_POST['middleName'] ?? '');
        $last_name = clean($_POST['lastName']);
        $extension = clean($_POST['extension'] ?? '');
        $family_serial_no = clean($_POST['familySerialNo'] ?? '');
        $dob = clean($_POST['dob']);
        $age = clean($_POST['age'] ?? '');
        $sex = in_array($_POST['sex'], ['Male', 'Female']) ? $_POST['sex'] : null;

        $civil_status = clean($_POST['civilStatus']);
        $address = clean($_POST['permanent_address']);
        $birthplace = clean($_POST['birthplace'] ?? '');
        $contact_number = clean($_POST['mobile'] ?? '');
        $education = clean($_POST['education'] ?? '');
        $occupation = clean($_POST['occupation'] ?? '');
        $religion = clean($_POST['religion'] ?? '');
        $birth_weight = clean($_POST['birth_weight'] ?? '');
        $philhealth = clean($_POST['philhealth'] ?? '');
        $four_ps = clean($_POST['4ps'] ?? '');
        $category = clean($_POST['category'] ?? '');

        /* -----------------------------------------------------
           Check for existing patient
        ----------------------------------------------------- */
        $stmt = $pdo->prepare("SELECT patient_id 
                               FROM patients 
                               WHERE first_name = :fn AND last_name = :ln AND date_of_birth = :dob");
        $stmt->execute([
            ':fn' => $first_name,
            ':ln' => $last_name,
            ':dob' => $dob
        ]);

        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing && empty($_POST['existing_patient_id']) && empty($_POST['is_new_patient'])) {
            echo json_encode([
                "status" => "duplicate",
                "message" => "Patient already exists.",
                "patient_id" => $existing['patient_id']
            ]);
            exit;
        }

        /* -----------------------------------------------------
           Determine Patient ID (existing or new)
        ----------------------------------------------------- */
        if (!empty($_POST['existing_patient_id'])) {

            $patient_id = intval($_POST['existing_patient_id']);

        } else {

            // Insert new patient
            $stmt = $pdo->prepare("
                INSERT INTO patients (
                    first_name, middle_name, last_name, extension, family_serial_no,
                    date_of_birth, age, sex, civil_status, address, birthplace,
                    contact_number, educational_attainment, occupation, religion,
                    birth_weight, philhealth_member_no, fourps_status, category
                ) VALUES (
                    :fn, :mn, :ln, :ext, :fsn,
                    :dob, :age, :sex, :cs, :addr, :bp,
                    :contact, :edu, :occ, :rel,
                    :bw, :phil, :fourps, :cat
                )
            ");

            $stmt->execute([
                ':fn' => $first_name,
                ':mn' => $middle_name,
                ':ln' => $last_name,
                ':ext' => $extension,
                ':fsn' => $family_serial_no,
                ':dob' => $dob,
                ':age' => $age,
                ':sex' => $sex,
                ':cs' => $civil_status,
                ':addr' => $address,
                ':bp' => $birthplace,
                ':contact' => $contact_number,
                ':edu' => $education,
                ':occ' => $occupation,
                ':rel' => $religion,
                ':bw' => $birth_weight,
                ':phil' => $philhealth,
                ':fourps' => $four_ps,
                ':cat' => $category
            ]);

            $patient_id = $pdo->lastInsertId();

            // LOG: New Patient Record
            if ($patient_id && $user_id) {
                $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, performed_by, user_affected)
                                       VALUES (:uid, :action, :pb, :ua)");
                $stmt->execute([
                    ':uid' => $user_id,
                    ':action' => "Added New Patient Record",
                    ':pb' => $user_id,
                    ':ua' => $patient_id
                ]);
            }
        }

        if (!$patient_id) {
            echo json_encode(["status" => "error", "message" => "Failed to assign patient ID."]);
            exit;
        }

        /* -----------------------------------------------------
           Validate Visit Assessment Required Fields
        ----------------------------------------------------- */
        required($_POST, ['bp', 'temp', 'weight', 'height', 'bmi']);

        /* -----------------------------------------------------
           Insert Patient Assessment (Visit)
        ----------------------------------------------------- */
        $stmt = $pdo->prepare("
            INSERT INTO patient_assessment (
                patient_id, recorded_by, visit_date, blood_pressure, temperature,
                weight, height, bmi, chest_rate, respiratory_rate,
                chief_complaints, remarks, patient_alert, treatment
            ) VALUES (
                :pid, :uid, CURDATE(), :bp, :temp,
                :wt, :ht, :bmi, :pr, :rr,
                :cc, :rmk, :alert, :treat
            )
        ");

        $stmt->execute([
            ':pid' => $patient_id,
            ':uid' => $user_id,
            ':bp' => clean($_POST['bp']),
            ':temp' => clean($_POST['temp']),
            ':wt' => clean($_POST['weight']),
            ':ht' => clean($_POST['height']),
            ':bmi' => clean($_POST['bmi']),
            ':pr' => clean($_POST['pr'] ?? ''),
            ':rr' => clean($_POST['rr'] ?? ''),
            ':cc' => clean($_POST['chief_complaints'] ?? ''),
            ':rmk' => clean($_POST['remarks'] ?? ''),
            ':alert' => clean($_POST['patient_alert'] ?? ''),
            ':treat' => clean($_POST['treatment'] ?? '')
        ]);

        $visit_id = $pdo->lastInsertId();

        // LOG assessment
        if ($visit_id && $user_id) {
            $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, performed_by, user_affected)
                                   VALUES (:uid, :act, :pb, :ua)");
            $stmt->execute([
                ':uid' => $user_id,
                ':act' => "Added Patient Assessment Record",
                ':pb' => $user_id,
                ':ua' => $patient_id
            ]);
        }

        /* -----------------------------------------------------
           Referral (only existing patients)
        ----------------------------------------------------- */
        $referral_id = null;

        if ($referralNeeded === "yes" && !empty($_POST['existing_patient_id']) && $user_id) {

            $referral_status = clean($_POST['referral_status'] ?? 'pending');

            $stmt = $pdo->prepare("
                INSERT INTO referrals (patient_id, visit_id, referred_by, referral_status)
                VALUES (:pid, :vid, :uid, :status)
            ");

            $stmt->execute([
                ':pid' => $patient_id,
                ':vid' => $visit_id,
                ':uid' => $user_id,
                ':status' => $referral_status
            ]);

            $referral_id = $pdo->lastInsertId();
        }

        /* -----------------------------------------------------
           Done
        ----------------------------------------------------- */
        $pdo->commit();

        echo json_encode([
            "status" => "success",
            "message" => "Patient information and visit record saved successfully!",
            "patient_id" => $patient_id,
            "visit_id" => $visit_id,
            "referral_id" => $referral_id
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>
