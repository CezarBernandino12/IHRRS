<?php 
require '../../php/db_connect.php'; // Ensure this file correctly initializes $pdo

ob_start(); 
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    file_put_contents("logs.txt", json_encode($_POST) . PHP_EOL, FILE_APPEND);
error_log("📌 Received data: " . json_encode($_POST));

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

        $required_fields = ['firstName', 'lastName', 'dob', 'sex', 'civilStatus', 'permanent_address'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                error_log("❌ Missing field: $field");
                echo json_encode(["status" => "error", "message" => "Missing required field: $field"]);
                exit;
            }
        }
        

        $first_name = clean_input($_POST['firstName']);
        $middle_name = clean_input($_POST['middleName'] ?? '');
        $last_name = clean_input($_POST['lastName']);
        $extension = clean_input($_POST['extension']);
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
        $birth_weight = clean_input($_POST['birth_weight'] ?? '');
        $philhealth = clean_input($_POST['philhealth'] ?? '');
        $four_ps = clean_input($_POST['4ps'] ?? '');
        $category = clean_input($_POST['category'] ?? '');

        $check_stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE first_name = :first_name AND last_name = :last_name AND date_of_birth = :dob");
        $check_stmt->execute([':first_name' => $first_name, ':last_name' => $last_name, ':dob' => $dob]);
        $existing_patient = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_patient && empty($_POST['existing_patient_id']) && empty($_POST['is_new_patient'])) {
            echo json_encode([
                "status" => "duplicate",
                "message" => "Patient already exists.",
                "patient_id" => $existing_patient['patient_id']
            ]);
            exit;
        }
        
        // Check if 'Use Existing' is selected
// Determine patient ID
$patient_id = null;

if (!empty($_POST['existing_patient_id'])) {
    $patient_id = clean_input($_POST['existing_patient_id']);
    error_log("✅ Using existing patient ID: " . $patient_id);
} else {
    // Insert a new patient
    error_log("🆕 Creating a new patient...");

    $stmt_patient = $pdo->prepare("INSERT INTO patients (
        first_name, middle_name, last_name, extension, family_serial_no, date_of_birth, age, 
        sex, civil_status, address, birthplace, contact_number, 
        educational_attainment, occupation, religion, birth_weight, philhealth_member_no, fourps_status, category
    ) VALUES (
        :first_name, :middle_name, :last_name, :extension, :family_serial_no, :dob, :age, 
        :sex, :civil_status, :address, :birthplace, :contact_number, 
        :education, :occupation, :religion, :birth_weight, :philhealth, :four_ps, :category
    )");

    $stmt_patient->execute([
        ':first_name' => $first_name,
        ':middle_name' => $middle_name,
        ':last_name' => $last_name,
        ':extension' => $extension,
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
        ':birth_weight' => $birth_weight,
        ':philhealth' => $philhealth,
        ':four_ps' => $four_ps,
        ':category' => $category
    ]);

    $patient_id = $pdo->lastInsertId();  // ✅ Get the newly inserted patient ID
    error_log("🆕 New patient ID assigned: " . $patient_id);


    if ($patient_id) {
               

    //ADDED NEW PATIENT RECORD FOR ACTIVITY LOG
    $stmt_log2 = $pdo->prepare("INSERT INTO logs (
        user_id, action, performed_by, user_affected
    ) VALUES (
        :user_id, :action, :performed_by, :user_affected
    )");

    $stmt_log2->execute([
        ':user_id' => $user_id,
        ':action' => "Added New Patient Record",
        ':performed_by' => $user_id,
        ':user_affected' => $patient_id
    ]);
    
        

    }
}

// Ensure patient_id is set
if (empty($patient_id)) {
    error_log("❌ Error: Patient ID is missing.");
    echo json_encode(["status" => "error", "message" => "Error: Patient ID is missing. Unable to proceed."]);
    exit;
}


        
        

        validate_required($_POST, ['user_id', 'bp', 'temp', 'weight', 'height', 'bmi']);

        $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT) ?: 0;
        $bp = clean_input($_POST['bp']);
       
        $temp = clean_input($_POST['temp']);
        $weight = clean_input($_POST['weight']);
        $height = clean_input($_POST['height']);
        $bmi = clean_input($_POST['bmi']);
        $pr = clean_input($_POST['pr']);
        $rr = clean_input($_POST['rr']);
        $chief_complaints = clean_input($_POST['chief_complaints'] ?? '');
        $remarks = clean_input($_POST['remarks'] ?? '');
        $patient_alert = clean_input($_POST['patient_alert'] ?? '');
        $treatment = clean_input($_POST['treatment'] ?? '');

        $stmt_visit = $pdo->prepare("INSERT INTO patient_assessment (
            patient_id, recorded_by, visit_date, blood_pressure, temperature, weight, height, bmi, 
            chest_rate, respiratory_rate, chief_complaints, remarks, patient_alert, treatment
        ) VALUES (
            :patient_id, :user_id, DATE(CURDATE()), :bp, :temp, :weight, :height, :bmi, 
            :pr, :rr, :chief_complaints, :remarks, :patient_alert, :treatment
        )");
        

        $stmt_visit->execute([
            ':patient_id' => $patient_id,  
            ':user_id' => $user_id, 
            ':bp' => $bp,
            ':temp' => $temp,
            ':weight' => $weight,
            ':height' => $height,
            ':bmi' => $bmi,
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
    $stmt_log2 = $pdo->prepare("INSERT INTO logs (
        user_id, action, performed_by, user_affected
    ) VALUES (
        :user_id, :action, :performed_by, :user_affected
    )");

    $stmt_log2->execute([
        ':user_id' => $user_id,
        ':action' => "Added Patient Assessment Record",
        ':performed_by' => $user_id,
        ':user_affected' => $patient_id
    ]);
    
        }


  

        $referral_id = null;
        error_log("Referral Needed Value: " . $referralNeeded);

        if ($referralNeeded === "yes" && !empty($patient_id) && !empty($user_id)) {
            // Only refer if it's an existing patient OR user explicitly picked "Use Existing"
            if (!empty($_POST['existing_patient_id'])) {  
                $referral_status = clean_input($_POST['referral_status'] ?? 'pending');
        
                $stmt_referral = $pdo->prepare("INSERT INTO referrals (patient_id, visit_id, referred_by, referral_status) VALUES (
                    :patient_id, :user_id, :referral_status
                )");
        
                $stmt_referral->execute([
                    ':patient_id' => $patient_id,
                    ':visit_id' => $visit_id,
                    ':user_id' => $user_id,
                    ':referral_status' => $referral_status
                ]);
        
                $referral_id = $pdo->lastInsertId();
                error_log("✅ Referral saved with ID: " . $referral_id);
            } else {
                error_log("⚠️ Referral skipped: This is a new patient.");
            }
        }
         else {
            error_log("⚠️ Skipping referral: Either patient ID or BHW ID is missing.");
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