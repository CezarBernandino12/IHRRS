<?php
require_once __DIR__ . '/session_config.php';
require '../../php/db_connect.php';
require '../../ADMIN/php/log_functions.php'; 
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}

try {
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["referral_id"])) {
        $referral_id = $_POST["referral_id"];
        
        $user_id = $_SESSION['user_id'] ?? null;
        
        if (!$user_id) {
            echo json_encode(["success" => false, "error" => "User not logged in."]);
            exit;
        }

        $stmt_get = $pdo->prepare("SELECT patient_id FROM referrals WHERE referral_id = :referral_id");
        $stmt_get->bindParam(":referral_id", $referral_id, PDO::PARAM_INT);
        $stmt_get->execute();
        $referral = $stmt_get->fetch(PDO::FETCH_ASSOC);
        
        if (!$referral) {
            echo json_encode(["success" => false, "error" => "Referral not found."]);
            exit;
        }
        
        $patient_id = $referral['patient_id'];

        $stmt = $pdo->prepare("UPDATE referrals SET referral_status = 'Canceled' WHERE referral_id = :referral_id");
        $stmt->bindParam(":referral_id", $referral_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            logActivity($pdo, $user_id, "Cancelled Referral");
            
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "Failed to update referral status."]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "Invalid request."]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>