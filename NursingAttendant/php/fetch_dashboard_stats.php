<?php
require '../../php/db_connect.php';
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}

// Set timezone to Asia/Manila (UTC+8)
$pdo->exec("SET time_zone = '+08:00'");

try {
    
    $date_stmt = $pdo->query("SELECT CURDATE()");
    $date = $date_stmt->fetchColumn();

    // Count pending referrals today
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM referrals 
        WHERE referral_date = :date 
        AND referral_status = 'Pending'
    ");
    $stmt->execute([':date' => $date]);
    $pending_referrals = $stmt->fetchColumn();

    // Count consultations today
    $stmt2 = $pdo->prepare("
        SELECT COUNT(*) 
        FROM rhu_consultations 
        WHERE consultation_date = :date
    ");
    $stmt2->execute([':date' => $date]);
    $consultations = $stmt2->fetchColumn();

    echo json_encode([
        'pending_referrals' => (int)$pending_referrals,
        'consultations' => (int)$consultations,
        'date' => $date
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
