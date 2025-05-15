<?php
$servername = "127.0.0.1"; // Use IP instead of "localhost"
$username = "root"; // Default for XAMPP
$password = ""; // Default for XAMPP
$dbname = "movie_booking"; // Your database name
$port = 3306; // Default MySQL port

// Try to connect with explicit port
try {
    $conn = new mysqli($servername, $username, $password, $dbname, $port);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}
?>