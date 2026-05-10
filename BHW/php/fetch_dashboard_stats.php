<?php
ob_start();
session_start();
require '../../php/db_connect.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $date_stmt = $pdo->query("SELECT CURDATE()");
    $date = $date_stmt->fetchColumn();

    $user_id = $_SESSION['user_id'];

    $stmt_user = $pdo->prepare("SELECT barangay FROM users WHERE user_id = :user_id");
    $stmt_user->execute([':user_id' => $user_id]);
    $user_barangay = $stmt_user->fetchColumn();

    if (!$user_barangay) {
        throw new Exception('User barangay not found');
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM patient_assessment p INNER JOIN users u ON p.recorded_by = u.user_id WHERE p.visit_date = :date AND u.barangay = :barangay");
    $stmt->execute([':date' => $date, ':barangay' => $user_barangay]);
    $visits_today = $stmt->fetchColumn();

    $stmt2 = $pdo->prepare("
        SELECT COUNT(*) 
        FROM referrals r
        INNER JOIN users u ON r.referred_by = u.user_id
        WHERE r.referral_status = 'Pending' 
        AND r.referral_date = :date
        AND u.barangay = :barangay
    ");
    $stmt2->execute([
        ':date' => $date,
        ':barangay' => $user_barangay
    ]);
    $pending_referrals = $stmt2->fetchColumn();

    echo json_encode([
        'visits_today' => (int)$visits_today,
        'pending_referrals' => (int)$pending_referrals,
        'date' => $date,
        'barangay' => $user_barangay
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['error' => $e->getMessage()]);
}
