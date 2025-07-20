<?php
require '../../php/db_connect.php';

if (isset($_GET['referral_id'])) {
    $referral_id = $_GET['referral_id'];

    $query = "SELECT r.referral_id, r.referral_date, p.first_name, p.last_name, 
                     p.age, p.sex, p.address, r.referred_by
              FROM referrals r
              JOIN patients p ON r.patient_id = p.patient_id
              WHERE r.referral_id = :referral_id";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':referral_id', $referral_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode(["status" => "success", "referral" => $row]);
    } else {
        echo json_encode(["status" => "error", "message" => "Referral not found."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing referral_id."]);
}
?>
