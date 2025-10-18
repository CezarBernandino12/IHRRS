<?php
require '../../php/db_connect.php';
header('Content-Type: application/json');

try {
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["referral_id"])) {
        $referral_id = $_POST["referral_id"];

        $stmt = $pdo->prepare("UPDATE referrals SET referral_status = 'Canceled' WHERE referral_id = :referral_id");
        $stmt->bindParam(":referral_id", $referral_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(["success" => true]);

            
            //ADDED CANCELLED REFERRAL FOR ACTIVITY LOG
    $stmt_log = $pdo->prepare("INSERT INTO logs (
        user_id, action, performed_by, user_affected
    ) VALUES (
        :user_id, :action, :performed_by, :user_affected
    )");
    $stmt_log->execute([
        ':user_id' => $user_id,
        ':action' => "Cancelled Referral",
        ':performed_by' => $user_id,
        ':user_affected' => $patient_id
    ]);
        

        } else {
            echo json_encode(["success" => false, "error" => "Failed to update referral status."]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "Invalid request."]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
