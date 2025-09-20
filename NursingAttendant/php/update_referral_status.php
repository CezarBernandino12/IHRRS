<?php
require_once '../../php/db_connect.php';

header('Content-Type: application/json'); // Important

$data = json_decode(file_get_contents("php://input"), true);
$referral_id = $data['referral_id'] ?? null;
$new_status = $data['new_status'] ?? null;

if ($referral_id && $new_status) {
    $stmt = $pdo->prepare("UPDATE referrals SET referral_status = ? WHERE referral_id = ?");
    if ($stmt->execute([$new_status, $referral_id])) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Failed to update status"]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Invalid input"]);
}