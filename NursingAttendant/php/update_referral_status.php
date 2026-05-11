<?php
require_once __DIR__ . '/session_config.php';
require_once '../../php/db_connect.php';
require_once '../../ADMIN/php/log_functions.php'; 


header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$referral_id = $data['referral_id'] ?? null;
$new_status = $data['new_status'] ?? null;

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["success" => false, "error" => "User not logged in"]);
    exit;
}

if ($referral_id && $new_status) {
    $stmt_get = $pdo->prepare("SELECT patient_id FROM referrals WHERE referral_id = ?");
    $stmt_get->execute([$referral_id]);
    $referral = $stmt_get->fetch(PDO::FETCH_ASSOC);
    
    if (!$referral) {
        echo json_encode(["success" => false, "error" => "Referral not found"]);
        exit;
    }
    
    $patient_id = $referral['patient_id'];
    
    $stmt = $pdo->prepare("UPDATE referrals SET referral_status = ? WHERE referral_id = ?");
    if ($stmt->execute([$new_status, $referral_id])) {
        if ($new_status === 'Forwarded to Physician') {
            logActivity($pdo, $user_id, "Forwarded Referral to Physician");
        }
        
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Failed to update status"]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Invalid input"]);
}
?>