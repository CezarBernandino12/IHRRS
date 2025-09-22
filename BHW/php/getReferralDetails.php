<?php
require '../../php/db_connect.php';



// Get referral ID
$referralId = isset($_GET['referral_id']) ? (int) $_GET['referral_id'] : 0;
if ($referralId <= 0) {
    echo json_encode(['error' => 'Invalid referral ID.']);
    exit;
}

// Fetch data
$sql = "SELECT 
r.referral_id,
r.referral_date,
CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.extension) AS name,
p.age, p.date_of_birth, p.sex, p.address,
v.weight, v.height, v.temperature, v.blood_pressure, v.chief_complaints,
v.visit_id
FROM referrals r
JOIN patients p ON p.patient_id = r.patient_id
JOIN patient_assessment v ON v.patient_id = p.patient_id
WHERE r.referral_id = :referral_id
ORDER BY v.visit_date DESC
LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute(['referral_id' => $referralId]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    error_log("No data found for referral_id: " . $referralId);
    echo json_encode(['error' => 'No data found.']);
    exit;
}

error_log("Retrieved data: " . print_r($data, true));
echo json_encode($data);