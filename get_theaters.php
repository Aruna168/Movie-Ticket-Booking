<?php
require_once('db_connect.php');

// Fetch all theaters
$theaters = [];
$result = $conn->query("SELECT theater_id, name, location FROM theaters ORDER BY name");

while ($row = $result->fetch_assoc()) {
    $theaters[] = $row;
}

// Return as JSON
header('Content-Type: application/json');
echo json_encode($theaters);
?>