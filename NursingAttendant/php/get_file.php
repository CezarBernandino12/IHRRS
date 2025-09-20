<?php
include '../../php/db_connect.php';

$visit_id = $_GET['visit_id'] ?? null;

if ($visit_id) {
    $stmt = $pdo->prepare("SELECT rc.lab_result_path FROM rhu_consultations rc JOIN patient_assessment p ON rc.visit_id = p.visit_id WHERE rc.visit_id = ?");
    $stmt->execute([$visit_id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($file && !empty($file['lab_result_path'])) {
        echo json_encode([
            "status" => "success",
            "file" => "../RHUPhysician/php/" . $file['lab_result_path']
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "No file found"
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "No visit_id provided"
    ]);
}
?>
