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
       

        // 🔹 Get the latest visit_id for this patient
        $stmt_visit = $pdo->prepare("SELECT visit_id FROM patient_assessment WHERE patient_id = :patient_id ORDER BY visit_id DESC LIMIT 1");
        $stmt_visit->execute([':patient_id' => $patient_id]);
        $visit = $stmt_visit->fetch(PDO::FETCH_ASSOC);

        if (!$visit) {
            throw new Exception("No visit found for this patient.");
        }

        $visit_id = $visit['visit_id'];

        // 🔹 Insert into referrals with visit_id
        $stmt_referral = $pdo->prepare("INSERT INTO referrals (patient_id, visit_id, referred_by, referral_status, referral_date) 
                                        VALUES (:patient_id, :visit_id, :user_id, 'pending', NOW())");

        $stmt_referral->execute([
            ':patient_id' => $patient_id,
            ':visit_id' => $visit_id,
            ':user_id' => $user_id
        ]);

        $referral_id = $pdo->lastInsertId();
        $pdo->commit();

        echo json_encode(["status" => "success", "referral_id" => $referral_id, "visit_id" => $visit_id]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>
