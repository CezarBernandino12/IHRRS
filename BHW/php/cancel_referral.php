<?php
session_start(); // Add session_start at the top
require '../../php/db_connect.php';
require '../../ADMIN/php/log_functions.php'; // Include logging functions
header('Content-Type: application/json');

try {
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["referral_id"])) {
        $referral_id = $_POST["referral_id"];
        
        // Get user_id from session
        $user_id = $_SESSION['user_id'] ?? null;
        
        if (!$user_id) {
            echo json_encode(["success" => false, "error" => "User not logged in."]);
            exit;
        }

        // Get patient_id from referral
        $stmt_get = $pdo->prepare("SELECT patient_id FROM referrals WHERE referral_id = :referral_id");
        $stmt_get->bindParam(":referral_id", $referral_id, PDO::PARAM_INT);
        $stmt_get->execute();
        $referral = $stmt_get->fetch(PDO::FETCH_ASSOC);
        
        if (!$referral) {
            echo json_encode(["success" => false, "error" => "Referral not found."]);
            exit;
        }
        
        $patient_id = $referral['patient_id'];

        // Update referral status
        $stmt = $pdo->prepare("UPDATE referrals SET referral_status = 'Canceled' WHERE referral_id = :referral_id");
        $stmt->bindParam(":referral_id", $referral_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            // 🔹 LOG ACTIVITY: Cancelled Referral
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