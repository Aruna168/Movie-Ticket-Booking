<?php
require_once('db_connect.php');
header('Content-Type: application/json');

$showtime_id = $_GET['showtime_id'] ?? 0;

if (!$showtime_id) {
    echo json_encode([]);
    exit;
}

// For now, return an empty array to avoid errors
// This will show all seats as available
echo json_encode([]);
?>