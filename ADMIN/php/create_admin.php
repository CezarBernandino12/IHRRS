<?php
require_once 'config.php';

// Admin account details
$full_name = "Lee Ivan Almadrones";
$username = "LeeIvan1234";
$plain_password = "LeeIvan1234";
$password_hash = password_hash($plain_password, PASSWORD_DEFAULT);
$role = "admin";
$contact_number = "09123456789";
$account_status = "active";
$registration_date = date('Y-m-d H:i:s');

// Check if admin already exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    echo "Admin account already exists.";
    exit;
}

// Insert admin account
$sql = "INSERT INTO users (full_name, username, password_hash, role, contact_number, account_status, registration_date)
        VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
if ($stmt->execute([$full_name, $username, $password_hash, $role, $contact_number, $account_status, $registration_date])) {
    echo "Admin account created successfully!";
} else {
    echo "Failed to create admin account.";
}
?>