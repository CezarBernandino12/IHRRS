<?php
require 'config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['unread_count' => 0]);
    exit();
}

if (isset($_GET['get_unread_count'])) {
    echo json_encode(['unread_count' => 0]);
    exit();
}

echo json_encode(['unread_count' => 0]);
