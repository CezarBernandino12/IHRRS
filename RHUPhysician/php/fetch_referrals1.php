<?php
require '../../php/db_connect.php';

try {
    $stmt = $pdo->prepare("
    SELECT r.referral_id, r.referral_date, r.visit_id,
           CONCAT(p.last_name, ', ', p.first_name, ' ', COALESCE(p.middle_name, '')) AS patient_name,
           u.barangay AS referral_from,
           r.referral_status 
    FROM referrals r
    JOIN patients p ON r.patient_id = p.patient_id
    JOIN users u ON r.referred_by = u.user_id
    WHERE r.referral_status = 'Forwarded to Physician'
    AND DATE(r.referral_date) = CURDATE()
    ORDER BY r.referral_date DESC
");


    
    $stmt->execute();
    $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($referrals);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
