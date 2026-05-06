<?php
require '../../php/db_connect.php';
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}

if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);

    try {
        $stmt = $pdo->prepare("SELECT rhu FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $userRhu = $stmt->fetchColumn();

        if (!$userRhu) {
            echo json_encode([]);
            exit;
        }

        $userRhu = trim($userRhu);

        $stmt = $pdo->prepare("
            SELECT user_id AS id, full_name 
            FROM users 
            WHERE role = 'doctor' 
            AND TRIM(rhu) = ? 
            ORDER BY full_name ASC
        ");
        $stmt->execute([$userRhu]);
        $physicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

        error_log("User $user_id searching for doctors. RHU: '$userRhu'. Found: " . count($physicians) . " doctors");

        echo json_encode($physicians);

    } catch (Exception $e) {
        error_log("Error in get_physicians.php: " . $e->getMessage());
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>