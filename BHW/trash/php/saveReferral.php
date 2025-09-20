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

        if (empty($_POST['patient_id']) || empty($_POST['bhw_id'])) {
            echo json_encode(["status" => "error", "message" => "Missing required fields: patient_id or bhw_id"]);
            exit;
        }

        $patient_id = clean_input($_POST['patient_id']);
        $bhw_id = clean_input($_POST['bhw_id']);

        $stmt_referral = $pdo->prepare("INSERT INTO referrals (patient_id, referred_by, referral_status) VALUES (
            :patient_id, :bhw_id, 'pending'
        )");

        $stmt_referral->execute([
            ':patient_id' => $patient_id,
            ':bhw_id' => $bhw_id
        ]);

        $referral_id = $pdo->lastInsertId();
        $pdo->commit();

        echo json_encode(["status" => "success", "referral_id" => $referral_id]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>
