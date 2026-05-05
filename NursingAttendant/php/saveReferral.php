<?php
session_start();
require '../../php/db_connect.php';
require '../../ADMIN/php/log_functions.php';

header('Content-Type: application/json');

ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (!$pdo) {
            throw new Exception("Database connection failed.");
        }

        $pdo->beginTransaction();

        function clean_input($data) {
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }

        if (empty($_POST['patient_id']) || empty($_POST['user_id']) || empty($_POST['visit_id'])) {
            throw new Exception("Missing required fields: patient_id, user_id, or visit_id");
        }

        $patient_id = clean_input($_POST['patient_id']);
        $user_id = clean_input($_POST['user_id']);
        $visit_id = clean_input($_POST['visit_id']);

        $stmt_referral = $pdo->prepare("
            INSERT INTO referrals (patient_id, visit_id, referred_by, referral_status)
            VALUES (:patient_id, :visit_id, :user_id, 'Forwarded to Physician')
        ");

        $stmt_referral->execute([
            ':patient_id' => $patient_id,
            ':visit_id' => $visit_id,
            ':user_id' => $user_id
        ]);

        $referral_id = $pdo->lastInsertId();

        if (!$referral_id) {
            throw new Exception("Failed to save referral.");
        }

        logActivity($pdo, $user_id, "Forwarded Referral to Physician");

        $pdo->commit();
\
        $php_output = ob_get_clean();

        echo json_encode([
            "status" => "success",
            "message" => "Referral saved successfully!",
            "referral_id" => $referral_id,
            "debug" => $php_output
        ]);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();

        $php_output = ob_get_clean(); 

        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage(),
            "error_details" => $php_output
        ]);
        exit;
    }
}
?>
