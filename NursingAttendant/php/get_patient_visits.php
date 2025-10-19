<?php
ob_start();
session_start();
ob_end_clean();

header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

try {
    require '../../php/db_connect.php';
} catch (Exception $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$patient_id = $_GET['patient_id'] ?? null;

if (!$patient_id) {
    echo json_encode(['error' => 'Patient ID is required']);
    exit;
}

try {
    //  FIXED: Query patient_assessment table (NOT rhu_consultations)
    if (isset($pdo)) {
        // PDO version
        $sql = "SELECT 
                    pa.visit_id,
                    pa.visit_date,
                    pa.chief_complaints,
                    rc.diagnosis
                FROM patient_assessment pa
                LEFT JOIN rhu_consultations rc ON pa.visit_id = rc.visit_id
                WHERE pa.patient_id = ?
                ORDER BY pa.visit_date DESC
                LIMIT 50";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$patient_id]);
        $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif (isset($conn)) {
        // MySQLi version
        $sql = "SELECT 
                    pa.visit_id,
                    pa.visit_date,
                    pa.chief_complaints,
                    rc.diagnosis
                FROM patient_assessment pa
                LEFT JOIN rhu_consultations rc ON pa.visit_id = rc.visit_id
                WHERE pa.patient_id = ?
                ORDER BY pa.visit_date DESC
                LIMIT 50";

        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
            exit;
        }
        
        $stmt->bind_param("i", $patient_id);
        
        if (!$stmt->execute()) {
            echo json_encode(['error' => 'Database execute error: ' . $stmt->error]);
            exit;
        }
        
        $result = $stmt->get_result();
        $visits = [];
        
        while ($row = $result->fetch_assoc()) {
            $visits[] = $row;
        }
        
        $stmt->close();
        $conn->close();
    } else {
        echo json_encode(['error' => 'Database connection object not found']);
        exit;
    }

    // Format the response
    $formatted_visits = [];
    foreach ($visits as $visit) {
        $formatted_visits[] = [
            'visit_id' => $visit['visit_id'],
            'visit_date' => $visit['visit_date'],
            'chief_complaints' => $visit['chief_complaints'] ?? '',
            'diagnosis' => $visit['diagnosis'] ?? ''
        ];
    }

    echo json_encode(['visits' => $formatted_visits]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>