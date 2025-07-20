<?php
require 'config.php'; // Database connection

if (isset($_GET['user_id'])) {
    $userId = $_GET['user_id'];

    $stmt = $pdo->prepare("SELECT full_name, username, role, status, barangay, age, contact_number, 
                          DATE_FORMAT(registration_date, '%Y-%m-%d %h:%i %p') AS registration_date 
                          FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($user);
}
?>