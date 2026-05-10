<?php
session_start();
require '../../php/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}

$rhu = $_SESSION['rhu'];

// Set timezone to Asia/Manila (UTC+8)
$pdo->exec("SET time_zone = '+08:00'");

try {
    
    $date_stmt = $pdo->query("SELECT CURDATE()");
    $date = $date_stmt->fetchColumn();

    // Count pending referrals today
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
FROM referrals r
JOIN users u ON r.referred_by = u.user_id
WHERE r.referral_date = :date
  AND r.referral_status = 'Pending'
  AND u.rhu = :rhu
    ");
    $stmt->execute([':date' => $date, ':rhu' => $rhu]);
    $pending_referrals = $stmt->fetchColumn();

    // Count consultations today
    $stmt2 = $pdo->prepare("
        SELECT COUNT(*)
FROM rhu_consultations rc
JOIN users u ON rc.recorded_by = u.user_id
WHERE rc.consultation_date = :date
  AND u.rhu = :rhu
    ");
    $stmt2->execute([':date' => $date, ':rhu' => $rhu]);
    $consultations = $stmt2->fetchColumn();

    echo json_encode([
        'pending_referrals' => (int)$pending_referrals,
        'consultations' => (int)$consultations,
        'date' => $date
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
} 
