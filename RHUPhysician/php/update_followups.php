<?php
require_once '../../php/db_connect.php';

header('Content-Type: application/json'); // Important

$data = json_decode(file_get_contents("php://input"), true);
$followup_id = $data['followup_id'] ?? null;
$new_status = $data['new_status'] ?? null;

if ($followup_id && $new_status) {
    $stmt = $pdo->prepare("UPDATE follow_ups SET followup_status = ? WHERE followup_id = ?");
    if ($stmt->execute([$new_status, $followup_id])) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Failed to update status"]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Invalid input"]);
}