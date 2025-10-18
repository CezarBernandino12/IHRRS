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
            echo json_encode(["status" => "error", "message" => "Missing required fields: patient_id or bhw_id"]);
            exit;
        }

        $patient_id = clean_input($_POST['patient_id']);
        $user_id = clean_input($_POST['user_id']);
        

       $visit_id = $_POST['visit_id'];

        // 🔹 Insert into referrals with visit_id
        $stmt_referral = $pdo->prepare("INSERT INTO referrals (patient_id, visit_id, referred_by, referral_status) 
                                        VALUES (:patient_id, :visit_id, :user_id, 'Forwarded to Physician')");

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
            ':action' => "Forwarded Referral to Physician",
            ':performed_by' => $user_id,
            ':user_affected' => $patient_id
        ]);
        }




        $pdo->commit();

        echo json_encode([
    "status" => "success",
    "message" => "Referral saved successfully!",
    "action" => "referral"
]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>
