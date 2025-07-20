<?php
require '../../php/db_connect.php';
header('Content-Type: application/json');

try {
    $date = $_GET['date'] ?? '';
    $status = $_GET['status'] ?? 'All';

    $query = "
        SELECT 
            r.visit_id,
            r.referral_date,
            r.referral_id,
            TRIM(
                CONCAT(
                    p.last_name, ', ', 
                    p.first_name, ' ', 
                    COALESCE(NULLIF(p.middle_name, ''), '')
                )
            ) AS patient_name,
            CONCAT(u.full_name, ' - ', UPPER(u.role)) AS referred_by,
            r.referral_status
        FROM referrals r
        JOIN patients p ON r.patient_id = p.patient_id
        JOIN users u ON r.referred_by = u.user_id
    ";

    $params = [];
    $conditions = [];

    if ($status !== 'All') {
        $conditions[] = "r.referral_status = :status";
        $params[':status'] = $status;
    }

    if (!empty($date)) {
        $conditions[] = "DATE(r.referral_date) = :date";
        $params[':date'] = $date;
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY r.referral_date DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debugging step (optional): Check if visit_id is missing
     foreach ($referrals as &$ref) {
         if (!isset($ref['visit_id'])) {
             $ref['visit_id'] = 'MISSING';
        }
 }

    echo json_encode($referrals, JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
