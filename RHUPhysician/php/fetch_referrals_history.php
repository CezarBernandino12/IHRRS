<?php
require '../../php/db_connect.php';

header('Content-Type: application/json');

try {
    $date = $_GET['date'] ?? '';
    $status = $_GET['status'] ?? '';

    // Base SQL query
    $query = "
        SELECT r.referral_date, r.visit_id, 
               CONCAT(p.last_name, ', ', p.first_name, ' ', COALESCE(p.middle_name, '')) AS patient_name,
               CONCAT(u.full_name, ' - ', UPPER(u.role)) AS referred_by,
               r.referral_status
        FROM referrals r
        JOIN patients p ON r.patient_id = p.patient_id
        JOIN users u ON r.referred_by = u.user_id
        WHERE (r.referral_status = 'Completed' OR r.referral_status = 'Uncompleted')
    ";

    // Parameters array
    $params = [];

    // Apply Date Filter
    if (!empty($date)) {
        $query .= " AND DATE(r.referral_date) = :date";
        $params[':date'] = $date;
    }

    // Apply Status Filter
    if (!empty($status)) {
        $query .= " AND r.referral_status = :status";
        $params[':status'] = $status;
    }

    $query .= " ORDER BY r.referral_date DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($referrals);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
