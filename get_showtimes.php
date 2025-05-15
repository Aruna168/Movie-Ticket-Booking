<?php
require_once('db_connect.php');

// Get theater ID from request
$theater_id = $_GET['theater_id'] ?? 0;

if (!$theater_id) {
    echo json_encode([]);
    exit();
}

// Fetch showtimes for the theater
$showtimes = [];
$stmt = $conn->prepare("
    SELECT s.show_id, s.show_date, s.show_time, m.title
    FROM showtimes s
    JOIN movies m ON s.movie_id = m.movie_id
    WHERE s.theater_id = ? AND s.show_date >= CURDATE()
    ORDER BY s.show_date, s.show_time
");
$stmt->bind_param("i", $theater_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Format date and time for display
    $row['show_date'] = date('M d, Y', strtotime($row['show_date']));
    $row['show_time'] = date('h:i A', strtotime($row['show_time']));
    $showtimes[] = $row;
}

$stmt->close();

// Return as JSON
header('Content-Type: application/json');
echo json_encode($showtimes);
?>