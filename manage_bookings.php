<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

// Delete booking logic
if (isset($_GET['delete'])) {
    $booking_id = $_GET['delete'];

    $stmt = $conn->prepare("SELECT show_id, seats_booked FROM bookings WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->bind_result($show_id, $seats_booked);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE showtimes SET available_seats = available_seats + ? WHERE show_id = ?");
    $stmt->bind_param("ii", $seats_booked, $show_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM bookings WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_bookings.php");
    exit();
}

// Initial fetch for page load
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

if ($result === false) {
    die('Error executing query: ' . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Bookings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">
<div class="container py-4">
    <h2 class="text-center mb-4">Manage Bookings</h2>

    <table class="table table-dark table-striped table-hover">
        <thead>
            <tr>
                <th>User</th>
                <th>Movie</th>
                <th>Theater</th>
                <th>Date</th>
                <th>Time</th>
                <th>Seats</th>
                <th>Price</th>
                <th>Status</th>
                <th>Booked At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="bookingTableBody">
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['theater']) ?></td>
                <td><?= $row['show_date'] ?></td>
                <td><?= $row['show_time'] ?></td>
                <td><?= $row['seats_booked'] ?></td>
                <td>₹<?= number_format($row['total_price'], 2) ?></td>
                <td><?= ucfirst($row['booking_status']) ?></td>
                <td><?= $row['booked_at'] ?></td>
                <td>
                    <a href="?delete=<?= $row['booking_id'] ?>" onclick="return confirm('Are you sure you want to delete this booking?')" class="btn btn-sm btn-danger">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <a href="admin_dashboard.php" class="btn btn-outline-light">Back to Dashboard</a>
</div>

<script>
    function fetchBookings() {
        fetch('get_bookings.php')
            .then(response => response.json())
            .then(data => {
                if (!data.error) {
                    document.getElementById('bookingTableBody').innerHTML = data.rows;
                }
            })
            .catch(err => console.error('Error fetching bookings:', err));
    }

    setInterval(fetchBookings, 5000); // Refresh table every 5 seconds
</script>
</body>
</html>
