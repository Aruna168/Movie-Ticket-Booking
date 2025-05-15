<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$sql = "
    SELECT b.booking_id, u.name AS username, m.title, t.name AS theater, s.show_date, s.show_time,
           b.seats_booked, b.total_price, b.booking_status, b.booked_at
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN showtimes s ON b.show_id = s.show_id
    JOIN movies m ON s.movie_id = m.movie_id
    JOIN theaters t ON s.theater_id = t.theater_id
    ORDER BY b.booked_at DESC
";

$result = $conn->query($sql);
$rows = '';

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows .= "<tr>
            <td>" . htmlspecialchars($row['username']) . "</td>
            <td>" . htmlspecialchars($row['title']) . "</td>
            <td>" . htmlspecialchars($row['theater']) . "</td>
            <td>" . $row['show_date'] . "</td>
            <td>" . $row['show_time'] . "</td>
            <td>" . $row['seats_booked'] . "</td>
            <td>₹" . number_format($row['total_price'], 2) . "</td>
            <td>" . ucfirst($row['booking_status']) . "</td>
            <td>" . $row['booked_at'] . "</td>
            <td>
                <a href='?delete=" . $row['booking_id'] . "' onclick='return confirm(\"Are you sure you want to delete this booking?\")' class='btn btn-sm btn-danger'>Delete</a>
            </td>
        </tr>";
    }
}
echo json_encode(['rows' => $rows]);
?>
