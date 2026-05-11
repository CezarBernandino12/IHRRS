<?php

require_once __DIR__ . '/session_config.php';
require '../../php/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}

$rhu = $_SESSION['rhu'];

try {
    $stmt = $pdo->prepare("
SELECT f.followup_id, f.patient_id, f.date, f.set_by, f.followup_status,
       CONCAT(p.last_name, ', ', p.first_name, ' ', COALESCE(p.middle_name, '')) AS patient_name,
       u.full_name, rc.visit_id
FROM follow_ups f
JOIN patients p ON f.patient_id = p.patient_id
JOIN users u ON f.set_by = u.user_id
LEFT JOIN rhu_consultations rc ON f.consultation_id = rc.consultation_id
WHERE f.followup_status = 'Pending'
  AND DATE(f.date) = CURDATE()
  AND u.rhu = :rhu
ORDER BY f.date DESC
");
    
    $stmt->execute([':rhu' => $rhu]);
    $followups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($followups);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
 