<?php
session_start();
require_once('db_connect.php');

// Get showtime ID from request
$showtime_id = isset($_GET['showtime_id']) ? (int)$_GET['showtime_id'] : 0;

if (!$showtime_id) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid showtime ID']);
    exit;
}

// Get recent bookings for this showtime
$stmt = $conn->prepare("
    SELECT b.booking_id, 
           b.user_id, 
           u.name as user_name,
           b.seats,
           b.total_price,
           b.booking_date,
           p.payment_status
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    LEFT JOIN payments p ON b.booking_id = p.booking_id
    WHERE b.showtime_id = ? AND (b.booking_status IS NULL OR b.booking_status != 'cancelled')
    ORDER BY b.booking_date DESC
    LIMIT 10
");
$stmt->bind_param("i", $showtime_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Return as JSON
header('Content-Type: application/json');
echo json_encode($bookings);
?>