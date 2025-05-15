<?php
session_start();
require_once('db_connect.php');

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$movie_id = $_GET['movie_id'] ?? null;

if (!$movie_id) {
    echo "Invalid request.";
    exit();
}

// Fetch movie details
$stmt = $conn->prepare("SELECT * FROM movies WHERE movie_id = ?");
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$result = $stmt->get_result();
$movie = $result->fetch_assoc();
$stmt->close();

if (!$movie) {
    echo "Movie not found.";
    exit();
}

// Fetch available showtimes for this movie
$stmt = $conn->prepare("
    SELECT s.show_id, s.show_date, s.show_time, t.name AS theater_name, t.theater_id
    FROM showtimes s
    JOIN theaters t ON s.theater_id = t.theater_id
    WHERE s.movie_id = ? AND s.show_date >= CURDATE()
    ORDER BY s.show_date, s.show_time
");
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$showtimes = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Movie - <?php echo htmlspecialchars($movie['title']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        .showtime-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: transform 0.3s;
        }
        
        .showtime-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .movie-poster {
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .date-badge {
            background-color: #007bff;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .time-badge {
            background-color: #6c757d;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            display: inline-block;
            margin-right: 5px;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🎟️ Book Movie: <?php echo htmlspecialchars($movie['title']); ?></h2>
        <a href="user_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
    
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <img src="uploads/<?php echo $movie['image']; ?>" class="card-img-top movie-poster" alt="Movie Poster">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($movie['title']); ?></h5>
                    <p class="card-text"><strong>Genre:</strong> <?php echo htmlspecialchars($movie['genre']); ?></p>
                    <p class="card-text"><strong>Duration:</strong> <?php echo $movie['duration']; ?> mins</p>
                    <p class="card-text"><strong>Release Date:</strong> <?php echo $movie['release_date']; ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Available Showtimes</h4>
                </div>
                <div class="card-body">
                    <?php if ($showtimes->num_rows > 0): ?>
                        <?php 
                        $currentDate = '';
                        while ($showtime = $showtimes->fetch_assoc()): 
                            $showDate = date('Y-m-d', strtotime($showtime['show_date']));
                            if ($currentDate != $showDate) {
                                if ($currentDate != '') {
                                    echo '</div>'; // Close previous date's container
                                }
                                $currentDate = $showDate;
                                echo '<h5 class="mt-3 mb-2"><span class="date-badge">' . date('D, M d, Y', strtotime($showtime['show_date'])) . '</span></h5>';
                                echo '<div class="showtime-list mb-4">';
                            }
                        ?>
                            <div class="showtime-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="time-badge"><?php echo date('h:i A', strtotime($showtime['show_time'])); ?></span>
                                        <span class="theater-name"><?php echo htmlspecialchars($showtime['theater_name']); ?></span>
                                    </div>
                                    <a href="seat_booking.php?movie_id=<?php echo $movie_id; ?>&showtime_id=<?php echo $showtime['show_id']; ?>" 
                                       class="btn btn-success">Select Seats</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        </div> <!-- Close the last date's container -->
                    <?php else: ?>
                        <div class="alert alert-info">
                            No showtimes available for this movie at the moment. Please check back later.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>