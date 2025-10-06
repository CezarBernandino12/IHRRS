<?php
require '../../php/db_connect.php';
header('Content-Type: application/json');

if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // Get user's RHU
    $stmt = $pdo->prepare("SELECT TRIM(rhu) FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $userRhu = $stmt->fetchColumn();

    if ($userRhu) {
        // Get doctors from same RHU
        $stmt = $pdo->prepare("SELECT user_id AS id, full_name FROM users WHERE role = 'doctor' AND TRIM(rhu) = ?");
        $stmt->execute([$userRhu]);
        $values = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Example output: [{ "id": 5, "name": "Dr. Reyes" }, { "id": 9, "name": "Dr. Cruz" }]
        echo json_encode($values);
    } else {
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>
