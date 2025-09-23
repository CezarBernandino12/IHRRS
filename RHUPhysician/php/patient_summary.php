<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require '../../php/db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['patient_id']) || !is_numeric($_GET['patient_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing or invalid patient_id"]);
    exit;
}

$patient_id = $_GET['patient_id'];

try {
    // Fetch patient info
    $sql = "SELECT TRIM(CONCAT(last_name, ', ', first_name, ' ', COALESCE(middle_name, ''))) AS full_name, age, sex FROM patients WHERE patient_id = :patient_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
    $stmt->execute();
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        echo json_encode(["error" => "Patient not found."]);
        exit;
    }

    // Fetch visit history
    $sql2 = "SELECT 
                consultation_date AS date_of_visit, 
                diagnosis, 
                diagnosis_status,
                visit_id 
            FROM rhu_consultations 
            WHERE patient_id = :patient_id 
            ORDER BY consultation_date DESC";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
    $stmt2->execute();
    $history = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "full_name" => $patient['full_name'],
        "age" => $patient['age'],
        "sex" => $patient['sex'],
        "history" => $history
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Internal Server Error",
        "details" => $e->getMessage()
    ]);
}

?>
