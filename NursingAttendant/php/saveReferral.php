<?php
session_start();
require '../../php/db_connect.php';
require '../../ADMIN/php/log_functions.php';

// Always return JSON
header('Content-Type: application/json');

// Capture ALL output (including warnings)
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (!$pdo) {
            throw new Exception("Database connection failed.");
        }

        $pdo->beginTransaction();

        // Helper function to clean input
        function clean_input($data) {
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }

        // Check required fields
        if (empty($_POST['patient_id']) || empty($_POST['user_id']) || empty($_POST['visit_id'])) {
            throw new Exception("Missing required fields: patient_id, user_id, or visit_id");
        }

        $patient_id = clean_input($_POST['patient_id']);
        $user_id = clean_input($_POST['user_id']);
        $visit_id = clean_input($_POST['visit_id']);

        // Insert referral
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

        // Log activity
        logActivity($pdo, $user_id, "Forwarded Referral to Physician");

        $pdo->commit();

        // Capture any warnings but only display in debug mode
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

        $php_output = ob_get_clean(); // capture warnings/notices

        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage(),
            "error_details" => $php_output
        ]);
        exit;
    }
}
?>
