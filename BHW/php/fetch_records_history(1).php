
<?php
require '../../php/db_connect.php';
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);


try {
    $stmt = $pdo->prepare("
        SELECT 
            b.visit_date, b.visit_id,
            CONCAT(p.first_name, ' ', p.middle_name, ' ', p.last_name) AS patient_name,
            CONCAT(UPPER(u.role), ' - ' ,u.full_name) AS recorded_by
        FROM bhs_visits b
        INNER JOIN patients p ON b.patient_id = p.patient_id
        INNER JOIN users u ON b.bhw_id = u.user_id
        ORDER BY b.visit_date DESC
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

