<?php
require 'db_connect.php'; // Ensure this file has the $pdo connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Start Transaction
        $pdo->beginTransaction();

        // 1️⃣ Insert into `patients` table

        //TEMPORARILY REMOVED AGE
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
            ':first_name' => trim($_POST['firstName']),
            ':middle_name' => trim($_POST['middleName']),
            ':last_name' => trim($_POST['lastName']),
            ':family_serial_no' => trim($_POST['familySerialNo']),
            ':dob' => trim($_POST['dob']),
            ':age' => trim($_POST['age']),
            ':sex' => trim($_POST['sex']),
            ':civil_status' => trim($_POST['civilStatus']),
            ':address' => trim($_POST['permanent_address']),
            ':birthplace' => trim($_POST['birthplace']),
            ':contact_number' => trim($_POST['mobile']),
            ':education' => trim($_POST['education']),
            ':occupation' => trim($_POST['occupation']),
            ':religion' => trim($_POST['religion']),
            ':philhealth' => trim($_POST['philhealth']),
            ':four_ps' => trim($_POST['4ps'])
        ]);

        // Get the last inserted patient_id
        $patient_id = $pdo->lastInsertId();

        // 2️⃣ Insert into `patient_assessment` table
        $stmt_visit = $pdo->prepare("INSERT INTO patient_assessment (
            patient_id, recorded_by, visit_date, blood_pressure, temperature, weight, height, 
            pulse_rate, respiratory_rate, chief_complaints, remarks
        ) VALUES (
            :patient_id, :bhw_id, NOW(), :bp, :temp, :weight, :height, 
            :pr, :rr, :chief_complaints, :remarks
        )");

        $stmt_visit->execute([
            ':patient_id' => $patient_id,  
            ':bhw_id' => trim($_POST['bhw_id']), 
            ':bp' => trim($_POST['bp']),
            ':temp' => trim($_POST['temp']),
            ':weight' => trim($_POST['weight']),
            ':height' => trim($_POST['height']),
            ':pr' => trim($_POST['pr']),
            ':rr' => trim($_POST['rr']),
            ':chief_complaints' => trim($_POST['chief_complaints']),
            ':remarks' => trim($_POST['remarks'])
            //':referred_to_rhu' => isset($_POST['referred_to_rhu']) ? 1 : 0
        ]);

        // Get the last inserted visit_id
        $visit_id = $pdo->lastInsertId();

        // 3️⃣ Insert into `bhs_medicine_dispensed` table
        if (!empty($_POST['medicine_given']) && !empty($_POST['quantity_given'])) {
            $stmt_medicine = $pdo->prepare("INSERT INTO bhs_medicine_dispensed (
                visit_id, medicine_name, quantity_dispensed, dispensed_by, dispensed_date
            ) VALUES (
                :visit_id, :medicine_name, :quantity_dispensed, :dispensed_by, NOW()
            )");

            $stmt_medicine->execute([
                ':visit_id' => $visit_id,
                ':medicine_name' => trim($_POST['medicine_given']),
                ':quantity_dispensed' => trim($_POST['quantity_given']),
                ':dispensed_by' => trim($_POST['bhw_id']) // The BHW dispensing the medicine
            ]);
        }

        // Commit the transaction
        $pdo->commit();

        echo "Patient information, visit details, and medicines dispensed successfully saved!";
    } catch (PDOException $e) {
        // Rollback if there's an error
        $pdo->rollBack();
        echo "Error saving data: " . $e->getMessage();
    }
}
?>
