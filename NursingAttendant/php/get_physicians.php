<?php
require '../../php/db_connect.php';
header('Content-Type: application/json');

if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);

    try {
        // Step 1: Get the logged-in user's RHU
        $stmt = $pdo->prepare("SELECT rhu FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $userRhu = $stmt->fetchColumn();

        // Step 2: If no RHU found, return empty
        if (!$userRhu) {
            echo json_encode([]);
            exit;
        }

        // Step 3: Normalize the RHU string (trim whitespace)
        $userRhu = trim($userRhu);

        // Step 4: Get all doctors from the same RHU
        $stmt = $pdo->prepare("
            SELECT user_id AS id, full_name 
            FROM users 
            WHERE role = 'doctor' 
            AND TRIM(rhu) = ? 
            ORDER BY full_name ASC
        ");
        $stmt->execute([$userRhu]);
        $physicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Step 5: Log for debugging
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