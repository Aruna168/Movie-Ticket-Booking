<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

function getMovies($conn) {
    $sql = "SELECT movie_id, title FROM movies";
    $result = $conn->query($sql);
    $movies = [];
    while ($row = $result->fetch_assoc()) {
        $movies[] = $row;
    }
    return $movies;
}

function getTheaters($conn) {
    $sql = "SELECT theater_id, name FROM theaters";
    $result = $conn->query($sql);
    $theaters = [];
    while ($row = $result->fetch_assoc()) {
        $theaters[] = $row;
    }
    return $theaters;
}

// Add Showtime
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_showtime'])) {
    $movie_id = $_POST['movie_id'];
    $theater_id = $_POST['theater_id'];
    $show_date = $_POST['show_date'];
    $show_time = $_POST['show_time'];

    $seats_sql = "SELECT total_seats FROM theaters WHERE theater_id = ?";
    $stmt = $conn->prepare($seats_sql);
    $stmt->bind_param("i", $theater_id);
    $stmt->execute();
    $stmt->bind_result($total_seats);
    $stmt->fetch();
    $stmt->close();

    $insert_sql = "INSERT INTO showtimes (movie_id, theater_id, show_date, show_time, available_seats)
                   VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("iissi", $movie_id, $theater_id, $show_date, $show_time, $total_seats);
    $stmt->execute();
    $stmt->close();
}

// Delete Showtime
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM showtimes WHERE show_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

$movies = getMovies($conn);
$theaters = getTheaters($conn);
$showtimes = $conn->query("SELECT s.show_id, m.title, t.name AS theater_name, s.show_date, s.show_time, s.available_seats
                           FROM showtimes s
                           JOIN movies m ON s.movie_id = m.movie_id
                           JOIN theaters t ON s.theater_id = t.theater_id");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Showtimes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">
<div class="container py-4">
    <h2 class="text-center mb-4">Manage Showtimes</h2>

    <!-- Add Showtime Form -->
    <form method="post" class="row g-3 bg-secondary p-3 rounded mb-4">
        <div class="col-md-4">
            <label class="form-label">Movie</label>
            <select name="movie_id" class="form-select" required>
                <option value="">Select Movie</option>
                <?php foreach ($movies as $movie): ?>
                    <option value="<?= $movie['movie_id'] ?>"><?= $movie['title'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Theater</label>
            <select name="theater_id" class="form-select" required>
                <option value="">Select Theater</option>
                <?php foreach ($theaters as $theater): ?>
                    <option value="<?= $theater['theater_id'] ?>"><?= $theater['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Date</label>
            <input type="date" name="show_date" class="form-control" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Time</label>
            <input type="time" name="show_time" class="form-control" required>
        </div>
        <div class="col-12 text-end">
            <button type="submit" name="add_showtime" class="btn btn-light">Add Showtime</button>
        </div>
    </form>

    <!-- Showtime List -->
    <table class="table table-dark table-hover">
        <thead>
            <tr>
                <th>Movie</th>
                <th>Theater</th>
                <th>Date</th>
                <th>Time</th>
                <th>Available Seats</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $showtimes->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['title'] ?></td>
                    <td><?= $row['theater_name'] ?></td>
                    <td><?= $row['show_date'] ?></td>
                    <td><?= $row['show_time'] ?></td>
                    <td><?= $row['available_seats'] ?></td>
                    <td>
                        <a href="?delete=<?= $row['show_id'] ?>" onclick="return confirm('Delete this showtime?')" class="btn btn-sm btn-danger">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <a href="admin_dashboard.php" class="btn btn-outline-light">Back to Dashboard</a>
</div>
</body>
</html>
