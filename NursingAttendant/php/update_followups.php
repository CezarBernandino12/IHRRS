<?php
session_start();
require '../../php/db_connect.php';
require '../../ADMIN/php/log_functions.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

try {
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection failed.");
    }

    // Get user_id from session
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$user_id) {
        throw new Exception("User not logged in.");
    }

    // Read and decode input
    $data = json_decode(file_get_contents("php://input"), true);

    $followup_id = $data['followup_id'] ?? null;
    $new_status = $data['new_status'] ?? null;

    if (!$followup_id || !$new_status) {
        throw new Exception("Missing required fields: followup_id or new_status.");
    }

    $followup_id = intval($followup_id);

    // Validate status
    $valid_statuses = ['Pending', 'Completed', 'Missed'];
    if (!in_array($new_status, $valid_statuses)) {
        throw new Exception("Invalid status value.");
    }

    $pdo->beginTransaction();

    // Get followup details before update (to get old status and patient_id)
    $stmt_get = $pdo->prepare("
        SELECT followup_id, patient_id, followup_status
        FROM follow_ups
        WHERE followup_id = ?
    ");
    $stmt_get->execute([$followup_id]);
    $followup = $stmt_get->fetch(PDO::FETCH_ASSOC);

    if (!$followup) {
        throw new Exception("Follow-up record not found.");
    }

    $patient_id = $followup['patient_id'];
    $old_status = $followup['followup_status'];

    // Update follow_ups table
    $stmt = $pdo->prepare("UPDATE follow_ups SET followup_status = ? WHERE followup_id = ?");

    if (!$stmt->execute([$new_status, $followup_id])) {
        throw new Exception("Failed to update follow-up status.");
    }

    // Log the activity using logActivity function
    $action = "Updated Follow-up Status from '" . $old_status . "' to '" . $new_status . "'";
    logActivity($pdo, $user_id, $action);

    $pdo->commit();

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Follow-up status updated successfully.",
        "followup_id" => $followup_id,
        "new_status" => $new_status
    ]);
    exit;

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Follow-up Update Error: " . $e->getMessage());

    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
    exit;
}
?>