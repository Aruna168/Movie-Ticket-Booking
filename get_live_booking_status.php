<?php
require_once('db_connect.php');

// Get show ID from request
$show_id = $_GET['show_id'] ?? 0;

if (!$show_id) {
    echo json_encode([
        'error' => 'Invalid show ID',
        'occupied_seats' => [],
        'pending_seats' => []
    ]);
    exit();
}

// Fetch occupied seats (confirmed bookings)
$occupied_seats = [];
$stmt = $conn->prepare("
    SELECT s.seat_number 
    FROM seats s
    JOIN bookings b ON s.booking_id = b.booking_id
    JOIN showtimes st ON b.showtime_id = st.show_id
    WHERE st.show_id = ? AND b.status = 'confirmed'
");
$stmt->bind_param("i", $show_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $occupied_seats[] = $row['seat_number'];
}
$stmt->close();

// Fetch pending seats (seats in the process of being booked)
$pending_seats = [];
$stmt = $conn->prepare("
    SELECT s.seat_number 
    FROM seats s
    JOIN bookings b ON s.booking_id = b.booking_id
    JOIN showtimes st ON b.showtime_id = st.show_id
    WHERE st.show_id = ? AND b.status = 'pending'
");
$stmt->bind_param("i", $show_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $pending_seats[] = $row['seat_number'];
}
$stmt->close();

// Get show details
$show_details = null;
$stmt = $conn->prepare("
    SELECT s.show_date, s.show_time, m.title, t.name AS theater_name
    FROM showtimes s
    JOIN movies m ON s.movie_id = m.movie_id
    JOIN theaters t ON s.theater_id = t.theater_id
    WHERE s.show_id = ?
");
$stmt->bind_param("i", $show_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $show_details = $row;
}
$stmt->close();

// Return as JSON
header('Content-Type: application/json');
echo json_encode([
    'occupied_seats' => $occupied_seats,
    'pending_seats' => $pending_seats,
    'show_details' => $show_details
]);
?>