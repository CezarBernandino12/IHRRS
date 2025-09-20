<?php
require '../../php/db_connect.php';
header('Content-Type: application/json');

try {
    // Get current MySQL server date
    $date_stmt = $pdo->query("SELECT CURDATE()");
    $date = $date_stmt->fetchColumn();

    // Start session and get user ID
    session_start();
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$user_id) {
        throw new Exception('User not logged in');
    }

    // Get user's barangay
    $stmt_user = $pdo->prepare("SELECT barangay FROM users WHERE user_id = :user_id");
    $stmt_user->execute([':user_id' => $user_id]);
    $user_barangay = $stmt_user->fetchColumn();

    if (!$user_barangay) {
        throw new Exception('User barangay not found');
    }

    // Count visits today
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM patient_assessment WHERE visit_date = :date");
    $stmt->execute([':date' => $date]);
    $visits_today = $stmt->fetchColumn();

    // Count pending referrals today from users in the same barangay
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

    // Return results
    echo json_encode([
        'visits_today' => (int)$visits_today,
        'pending_referrals' => (int)$pending_referrals,
        'date' => $date,
        'barangay' => $user_barangay
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
