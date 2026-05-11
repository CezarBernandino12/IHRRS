<?php
require '../../php/db_connect.php';
require_once __DIR__ . '/session_config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Unauthorized");
}

try {
    $stmt = $pdo->prepare("
        UPDATE referrals 
        SET referral_status = 'Uncompleted' 
        WHERE referral_status = 'Pending' 
        AND referral_date < CURDATE();
    ");
    
    $stmt->execute();
    
    // Log output for debugging
    file_put_contents('/path/to/log.txt', "Updated " . $stmt->rowCount() . " rows on " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

} catch (Exception $e) {
    file_put_contents('/path/to/error_log.txt', "Error: " . $e->getMessage() . " on " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
}
?>
