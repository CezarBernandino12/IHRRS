<?php
require '../../php/db_connect.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9;
$offset = ($page - 1) * $limit;
 
try {
    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM referrals");
    $totalStmt->execute();
    $totalReferrals = $totalStmt->fetchColumn();
    $totalPages = ceil($totalReferrals / $limit);

    $stmt = $pdo->prepare("
        SELECT r.referral_date, 
               CONCAT(p.last_name, ', ', p.first_name, ' ', COALESCE(p.middle_name, '')) AS patient_name,
               CONCAT(u.full_name, ' - ', UPPER(u.role)) AS referred_by,
               COALESCE(r.referral_status, 'N/A') AS referral_status
        FROM referrals r
        JOIN patients p ON r.patient_id = p.patient_id
        JOIN users u ON r.referred_by = u.user_id
        ORDER BY r.referral_date DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'referrals' => $referrals,
        'totalPages' => $totalPages
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
