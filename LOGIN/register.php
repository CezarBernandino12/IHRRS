<?php
require '../ADMIN/php/config.php';

// Function to create a notification for new user registration
function notifyAdminOfRegistration($pdo, $fullName, $username, $role, $userId = null) {
    try {
        $message = "New registration request from $fullName ($username) for $role role";
        $stmt = $pdo->prepare("INSERT INTO notifications (message, type, status, related_id, created_at) 
                            VALUES (:message, 'registration', 'unread', :related_id, NOW())");
        $stmt->execute([
            'message' => $message,
            'related_id' => $userId
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure password hashing
    $barangay = $_POST['barangay'];
    $address = trim($_POST['address']);
    $age = intval($_POST['age']);
    $contact_number = trim($_POST['contact_number']);
    $role = $_POST['role'];
    $status = "pending"; // Default status for new users

    // Validate age (should be between 18 and 100)
    if ($age < 18 || $age > 100) {
        echo "Invalid age. Age must be between 18 and 100.";
        exit();
    }

    // Validate contact number (should contain only digits)
    if (!preg_match('/^[0-9]{10,15}$/', $contact_number)) {
        echo "Invalid contact number. It should be between 10 and 15 digits.";
        exit();
    }

    // Check if username already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    
    if ($stmt->rowCount() > 0) {
        echo "Username already exists. Choose a different one.";
        exit();
    }

    try {
        // Start a transaction to ensure both user creation and notification are successful
        $pdo->beginTransaction();
        
        // Insert into database
        $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password_hash, barangay, address, age, contact_number, role, status) 
                            VALUES (:full_name, :username, :password, :barangay, :address, :age, :contact_number, :role, :status)");
        $stmt->execute([
            'full_name' => $full_name,
            'username' => $username,
            'password' => $password,
            'barangay' => $barangay,
            'address' => $address,
            'age' => $age,
            'contact_number' => $contact_number,
            'role' => $role,
            'status' => $status
        ]);
        
        // Get the ID of the newly created user
        $userId = $pdo->lastInsertId();
        
        // Create notification for admin
        notifyAdminOfRegistration($pdo, $full_name, $username, $role, $userId);
        
        // Commit the transaction
        $pdo->commit();
        
        // Redirect with success message
        header("Location: ../BHWlogin.html?success=Registration successful. Waiting for admin approval.");
        exit();
        
    } catch (PDOException $e) {
        // Rollback the transaction if something failed
        $pdo->rollBack();
        error_log("Registration error: " . $e->getMessage());
        echo "An error occurred during registration. Please try again.";
        exit();
    }
}
?>