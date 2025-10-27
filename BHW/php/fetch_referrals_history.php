<?php
require '../../php/db_connect.php';
header('Content-Type: application/json');

try {
    $date = $_GET['date'] ?? '';
    $status = $_GET['status'] ?? 'All';

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

    // ✅ Base query (no WHERE yet)
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
        WHERE u.barangay = :barangay
    ";

    // ✅ Add dynamic filters
    $params = [':barangay' => $user_barangay];
    $conditions = [];

    if ($status !== 'All') {
        $conditions[] = "r.referral_status = :status";
        $params[':status'] = $status;
    }

    if (!empty($date)) {
        $conditions[] = "DATE(r.referral_date) = :date";
        $params[':date'] = $date;
    }

    // ✅ Append conditions properly using AND
    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY r.referral_date DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debugging: Fill missing visit_id
    foreach ($referrals as &$ref) {
        if (!isset($ref['visit_id'])) {
            $ref['visit_id'] = 'MISSING';
        }
    }

    echo json_encode($referrals, JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
