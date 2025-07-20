<?php
$password = "IHRRSadminpass2025"; // Change to your desired password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
echo "Hashed Password: " . $hashed_password;
?>
