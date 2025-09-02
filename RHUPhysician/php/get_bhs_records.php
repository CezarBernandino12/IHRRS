<?php
require '../../php/db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['patient_id'])) {
    echo json_encode(['error' => 'Patient ID is required']);
    exit;
}

$patient_id = $_GET['patient_id'];
$with_rhu = isset($_GET['with_rhu']) ? $_GET['with_rhu'] === 'true' : false;

try {
    // Base query for BHS visits
    $sql = "SELECT bv.visit_id, bv.visit_date, bv.recorded_by, u.full_name AS bhw_name
            FROM patient_assessment bv
            LEFT JOIN users u ON bv.recorded_by = u.user_id";

    // Add RHU consultations filter if requested
    if ($with_rhu) {
        $sql .= " INNER JOIN rhu_consultations rc ON bv.visit_id = rc.visit_id";
    }

    // Add patient filter and ordering
    $sql .= " WHERE bv.patient_id = :patient_id
              ORDER BY bv.visit_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['records' => $records]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 