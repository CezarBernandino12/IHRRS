<?php
$admin_password = "adminpassword"; 
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

echo "Hashed Password: " . $hashed_password;
?>
