<?php
session_start(); // Make sure session is started
require 'config.php';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $fullName = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $barangay = ($role === 'doctor') ? '' : (isset($_POST['barangay']) ? trim($_POST['barangay']) : '');
    $rhu = (in_array($role, ['doctor', 'bhw', 'nursing_attendant'])) ? (isset($_POST['rhu']) ? trim($_POST['rhu']) : '') : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $age = isset($_POST['age']) ? (int)$_POST['age'] : null;
    $contactNumber = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';
    $licenseNumber = ($role === 'doctor') ? trim($_POST['license_number'] ?? '') : null;

// Validate license number for doctors
if ($role === 'doctor' && empty($licenseNumber)) {
    echo "Error: License number is required for doctors.";
    exit;
}

    // Validate required fields
    if (empty($fullName) || empty($username) || empty($password) || empty($role)) {
        echo "Error: All required fields must be completed.";
        exit;
    }

    // Validate password
    if (!preg_match('/^(?=.*[A-Z])(?=.*\d).{6,}$/', $password)) {
        echo "Error: Password must contain at least 1 uppercase letter and 1 number, and be at least 6 characters long.";
        exit;
    }

    try {
        // Check if username already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $checkStmt->execute([$username]);
        $userExists = $checkStmt->fetchColumn();

        if ($userExists) {
            echo "Error: Username already exists. Please choose a different username.";
            exit;
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
            $sql = "INSERT INTO users (full_name, username, password_hash, role, barangay, rhu, address, age, contact_number, license_number, status, registration_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $fullName,
            $username,
            $hashedPassword,
            $role,
            $barangay,
            $rhu,
            $address,
            $age,
            $contactNumber,
            $licenseNumber
        ]);
        // Get the inserted user's ID
        $newUserId = $pdo->lastInsertId();

        // Log activity
// Get the inserted user's ID
$newUserId = $pdo->lastInsertId();

// Get the admin's user_id from session
$adminId = $_SESSION['user_id'] ?? null;

if ($adminId) {
    $activity = "Added new user: $username";
    $logStmt = $pdo->prepare("INSERT INTO logs (performed_by, action, timestamp) VALUES (?, ?, NOW())");
    $logStmt->execute([$adminId, $activity]);
}

        echo "User added successfully!";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    } 
} else {
    echo "Error: Invalid request method.";
}
?>
