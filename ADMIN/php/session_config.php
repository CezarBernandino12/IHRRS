<?php
// Role-specific session isolation — prevents session conflicts across roles
if (session_status() === PHP_SESSION_NONE) {
    session_name('IHRRS_ADMIN');
    session_start();
}
