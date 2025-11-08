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

        $pdo->beginTransaction();

        function clean_input($data) {
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }

        if (empty($_POST['patient_id']) || empty($_POST['user_id'])) {
            echo json_encode([
                "status" => "error",
                "message" => "Missing required fields: patient_id or user_id"
            ]);
            exit;
        } 

        $patient_id = clean_input($_POST['patient_id']);
        $user_id = clean_input($_POST['user_id']);
        $visit_id = isset($_POST['visit_id']) ? clean_input($_POST['visit_id']) : null;

        if (empty($visit_id)) {
            throw new Exception("Missing visit ID for this referral.");
        }


        // 🔹 update treatment in patient_assessment to Referred

        if ($visit_id) {
    $stmt_treatment = $pdo->prepare("
        UPDATE patient_assessment
        SET treatment = :treatment
        WHERE visit_id = :visit_id
    ");

    $stmt_treatment->execute([
        ':treatment' => 'Referred',
        ':visit_id' => $visit_id
    ]);
}



        // 🔹 Insert into referrals with visit_id
        $stmt_referral = $pdo->prepare("
            INSERT INTO referrals (patient_id, visit_id, referred_by, referral_status, referral_date) 
            VALUES (:patient_id, :visit_id, :user_id, 'pending', NOW())
        ");

        $stmt_referral->execute([
            ':patient_id' => $patient_id,
            ':visit_id' => $visit_id,
            ':user_id' => $user_id
        ]);

        $referral_id = $pdo->lastInsertId();



        if ($referral_id) {
            //ADDED REFERRAL FOR ACTIVITY LOG
    $stmt_log = $pdo->prepare("INSERT INTO logs (
        user_id, action, performed_by, user_affected
    ) VALUES (
        :user_id, :action, :performed_by, :user_affected
    )");
    $stmt_log->execute([
        ':user_id' => $user_id,
        ':action' => "Sent Referral to RHU",
        ':performed_by' => $user_id,
        ':user_affected' => $patient_id
    ]);
        }

    



        $pdo->commit();

        echo json_encode([
            "status" => "success",
            "referral_id" => $referral_id,
            "visit_id" => $visit_id
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>
