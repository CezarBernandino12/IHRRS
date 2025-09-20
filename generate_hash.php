<?php

$admin_password = "adminpassword"; 
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Database connection details
$host = "localhost";
$dbname = "ihrrs";
$db_user = "root"; // Corrected variable name
$db_pass = "";     // Corrected variable name

// Define the DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

try {
    // Create a PDO instance
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL query to insert the admin user
    $sql = "INSERT INTO users (full_name, username, password_hash, role, status) 
            VALUES (:full_name, :username, :password_hash, :role, :status)";

    // Prepare the statement
    $stmt = $pdo->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':full_name', $full_name);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password_hash', $hashed_password);
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':status', $status);

    // Set values for the admin user
    $full_name = "IHRRS Admin";
    $username = "ihrrs_admin";
    $admin_password = "IHRRSadminpass2025";
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    $role = "admin";
    $status = "approved";

    // Execute the query
    $stmt->execute();

    echo "Admin user added successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>