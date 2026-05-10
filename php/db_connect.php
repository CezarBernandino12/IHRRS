<?php
/**
 * Database Connection using Environment Variables
 */
 
// Load environment variables
require_once __DIR__ . '/env.loader.php';

// Get database credentials from environment
$host = env('DB_HOST', 'localhost');
$dbname = env('DB_NAME', 'ihrrs_dbase');
$username = env('DB_USER', 'root');
$password = env('DB_PASSWORD', '');
$port = env('DB_PORT', '3306');

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please contact the administrator.");
}
?>
 