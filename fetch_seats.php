<?php
include 'db_connect.php';

$theaterId = isset($_GET['theater']) ? intval($_GET['theater']) : 0;
$showtimeId = isset($_GET['showtime']) ? intval($_GET['showtime']) : 0;
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

// Build query dynamically
$sql = "SELECT seat_number, status FROM bookings WHERE 1=1";
$params = [];

if ($theaterId > 0) {
    $sql .= " AND theater_id = ?";
    $params[] = $theaterId;
}
if ($showtimeId > 0) {
    $sql .= " AND showtime_id = ?";
    $params[] = $showtimeId;
}
if (!empty($query)) {
    $sql .= " AND (location LIKE ? OR showtime LIKE ?)";
    $query = "%$query%";
    $params[] = $query;
    $params[] = $query;
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$result = $stmt->get_result();

$seats = [];
while ($row = $result->fetch_assoc()) {
    $seats[] = ["seat_number" => $row["seat_number"], "status" => $row["status"]];
}

echo json_encode($seats);
?>
