<?php
session_start();
require_once('db_connect.php');

$movie_id = $_GET['id'] ?? null;

if (!$movie_id) {
    header('Location: index.html');
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
    header('Location: index.html');
    exit();
}

// Fetch showtimes for this movie
$stmt = $conn->prepare("
    SELECT s.show_id, s.show_date, s.show_time, t.name AS theater_name, t.location
    FROM showtimes s
    JOIN theaters t ON s.theater_id = t.theater_id
    WHERE s.movie_id = ? AND s.show_date >= CURDATE()
    ORDER BY s.show_date, s.show_time
");
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$showtimes_result = $stmt->get_result();
$stmt->close();

// Group showtimes by date
$showtimes_by_date = [];
while ($showtime = $showtimes_result->fetch_assoc()) {
    $date = date('Y-m-d', strtotime($showtime['show_date']));
    if (!isset($showtimes_by_date[$date])) {
        $showtimes_by_date[$date] = [];
    }
    $showtimes_by_date[$date][] = $showtime;
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movie['title']); ?> - Movie Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f4f6f8;
            font-family: 'Poppins', sans-serif;
        }
        
        .movie-banner {
            height: 400px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .banner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0.8) 100%);
            display: flex;
            align-items: flex-end;
            padding: 30px;
            color: white;
        }
        
        .movie-poster {
            width: 200px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            margin-right: 30px;
        }
        
        .movie-title {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .movie-meta {
            font-size: 1.1rem;
            margin-bottom: 15px;
        }
        
        .movie-meta span {
            margin-right: 15px;
        }
        
        .rating {
            display: inline-flex;
            align-items: center;
            background-color: #ff9800;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
            margin-right: 15px;
        }
        
        .rating i {
            margin-right: 5px;
        }
        
        .movie-details {
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: -50px;
            position: relative;
            z-index: 10;
        }
        
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        
        .movie-description {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #555;
            margin-bottom: 30px;
        }
        
        .cast-crew {
            margin-bottom: 30px;
        }
        
        .cast-member {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .cast-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }
        
        .cast-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .cast-role {
            color: #777;
            font-size: 0.9rem;
        }
        
        .showtimes {
            margin-top: 30px;
        }
        
        .date-tabs {
            display: flex;
            overflow-x: auto;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }
        
        .date-tab {
            min-width: 100px;
            padding: 10px 15px;
            text-align: center;
            background-color: #f0f0f0;
            border-radius: 20px;
            margin-right: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .date-tab.active {
            background-color: #007bff;
            color: white;
        }
        
        .date-tab .day {
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .date-tab .date {
            font-size: 0.9rem;
            color: inherit;
        }
        
        .theater-card {
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .theater-name {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .theater-location {
            color: #777;
            margin-bottom: 10px;
        }
        
        .time-slots {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .time-slot {
            padding: 8px 15px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .time-slot:hover {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .trailer-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .trailer-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 10px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .action-button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .action-button i {
            margin-right: 8px;
        }
        
        .book-button {
            background-color: #007bff;
            color: white;
            border: none;
        }
        
        .book-button:hover {
            background-color: #0056b3;
        }
        
        .share-button {
            background-color: #f0f0f0;
            color: #333;
            border: none;
        }
        
        .share-button:hover {
            background-color: #ddd;
        }
        
        .favorite-button {
            background-color: #f0f0f0;
            color: #333;
            border: none;
        }
        
        .favorite-button:hover {
            background-color: #ddd;
        }
        
        .favorite-button.active {
            background-color: #ff4081;
            color: white;
        }
        
        .favorite-button.active:hover {
            background-color: #e91e63;
        }
        
        @media (max-width: 768px) {
            .movie-banner {
                height: 300px;
            }
            
            .movie-poster {
                width: 150px;
            }
            
            .movie-title {
                font-size: 2rem;
            }
            
            .movie-details {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.html">Movie Booking</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.html">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="movies.php">Movies</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="theaters.php">Theaters</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <?php if ($is_logged_in): ?>
                        <a href="user_dashboard.php" class="btn btn-outline-light me-2">Dashboard</a>
                        <a href="logout.php" class="btn btn-danger">Logout</a>
                    <?php else: ?>
                        <a href="login.html" class="btn btn-outline-light me-2">Login</a>
                        <a href="register.html" class="btn btn-primary">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Movie Banner -->
    <div class="movie-banner" style="background-image: url('uploads/posters/<?php echo $movie['image']; ?>');">
        <div class="banner-overlay">
            <img src="uploads/posters/<?php echo $movie['image']; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="movie-poster">
            <div>
                <h1 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h1>
                <div class="movie-meta">
                    <span><i class="bi bi-calendar"></i> <?php echo $movie['release_date']; ?></span>
                    <span><i class="bi bi-clock"></i> <?php echo $movie['duration']; ?> mins</span>
                    <span><i class="bi bi-tag"></i> <?php echo htmlspecialchars($movie['genre']); ?></span>
                </div>
                <div class="rating">
                    <i class="bi bi-star-fill"></i> 
                    <?php echo number_format(rand(35, 50) / 10, 1); ?>/5
                </div>
                
                <div class="action-buttons">
                    <?php if ($is_logged_in): ?>
                        <a href="#showtimes" class="action-button book-button">
                            <i class="bi bi-ticket-perforated"></i> Book Tickets
                        </a>
                    <?php else: ?>
                        <a href="login.html" class="action-button book-button">
                            <i class="bi bi-ticket-perforated"></i> Login to Book
                        </a>
                    <?php endif; ?>
                    <button class="action-button share-button">
                        <i class="bi bi-share"></i> Share
                    </button>
                    <button class="action-button favorite-button" id="favoriteBtn">
                        <i class="bi bi-heart"></i> <span>Favorite</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="movie-details">
            <!-- Synopsis -->
            <h2 class="section-title">Synopsis</h2>
            <p class="movie-description">
                <?php echo htmlspecialchars($movie['description'] ?? 'No description available for this movie.'); ?>
            </p>

            <!-- Trailer -->
            <?php if (!empty($movie['trailer_url'] ?? '')): ?>
            <h2 class="section-title">Trailer</h2>
            <div class="trailer-container">
                <iframe src="<?php echo $movie['trailer_url']; ?>" frameborder="0" allowfullscreen></iframe>
            </div>
            <?php endif; ?>

            <!-- Showtimes -->
            <h2 class="section-title" id="showtimes">Showtimes</h2>
            
            <?php if (count($showtimes_by_date) > 0): ?>
                <!-- Date Tabs -->
                <div class="date-tabs">
                    <?php 
                    $first_date = true;
                    foreach ($showtimes_by_date as $date => $shows): 
                        $date_obj = new DateTime($date);
                    ?>
                        <div class="date-tab <?php echo $first_date ? 'active' : ''; ?>" data-date="<?php echo $date; ?>">
                            <div class="day"><?php echo $date_obj->format('D'); ?></div>
                            <div class="date"><?php echo $date_obj->format('M j'); ?></div>
                        </div>
                    <?php 
                        $first_date = false;
                    endforeach; 
                    ?>
                </div>

                <!-- Showtimes by Date -->
                <?php 
                $first_date = true;
                foreach ($showtimes_by_date as $date => $shows): 
                    // Group showtimes by theater
                    $theaters = [];
                    foreach ($shows as $show) {
                        $theater_id = $show['theater_id'] ?? 0;
                        if (!isset($theaters[$theater_id])) {
                            $theaters[$theater_id] = [
                                'name' => $show['theater_name'],
                                'location' => $show['location'],
                                'showtimes' => []
                            ];
                        }
                        $theaters[$theater_id]['showtimes'][] = $show;
                    }
                ?>
                    <div class="date-content" id="date-<?php echo $date; ?>" style="<?php echo $first_date ? '' : 'display: none;'; ?>">
                        <?php foreach ($theaters as $theater): ?>
                            <div class="theater-card">
                                <div class="theater-name"><?php echo htmlspecialchars($theater['name']); ?></div>
                                <div class="theater-location"><?php echo htmlspecialchars($theater['location']); ?></div>
                                <div class="time-slots">
                                    <?php foreach ($theater['showtimes'] as $show): ?>
                                        <a href="<?php echo $is_logged_in ? 'seat_booking.php?movie_id=' . $movie_id . '&showtime_id=' . $show['show_id'] : 'login.html'; ?>" 
                                           class="time-slot">
                                            <?php echo date('g:i A', strtotime($show['show_time'])); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php 
                    $first_date = false;
                endforeach; 
                ?>
            <?php else: ?>
                <div class="alert alert-info">
                    No showtimes available for this movie at the moment. Please check back later.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Movie Booking</h5>
                    <p>Your one-stop destination for booking movie tickets online.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.html" class="text-white">Home</a></li>
                        <li><a href="movies.php" class="text-white">Movies</a></li>
                        <li><a href="theaters.php" class="text-white">Theaters</a></li>
                        <li><a href="contact.php" class="text-white">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Connect With Us</h5>
                    <div class="d-flex gap-3 fs-4">
                        <a href="#" class="text-white"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; 2023 Movie Booking. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Date tab switching
        document.querySelectorAll('.date-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                document.querySelectorAll('.date-tab').forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Hide all date content
                document.querySelectorAll('.date-content').forEach(content => {
                    content.style.display = 'none';
                });
                
                // Show selected date content
                const date = this.getAttribute('data-date');
                document.getElementById('date-' + date).style.display = 'block';
            });
        });
        
        // Favorite button toggle
        document.getElementById('favoriteBtn').addEventListener('click', function() {
            this.classList.toggle('active');
            const icon = this.querySelector('i');
            const text = this.querySelector('span');
            
            if (this.classList.contains('active')) {
                icon.classList.remove('bi-heart');
                icon.classList.add('bi-heart-fill');
                text.textContent = 'Favorited';
            } else {
                icon.classList.remove('bi-heart-fill');
                icon.classList.add('bi-heart');
                text.textContent = 'Favorite';
            }
        });
        
        // Share functionality
        document.querySelector('.share-button').addEventListener('click', function() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo htmlspecialchars($movie['title']); ?>',
                    text: 'Check out this movie: <?php echo htmlspecialchars($movie['title']); ?>',
                    url: window.location.href
                })
                .catch(error => console.log('Error sharing:', error));
            } else {
                alert('Share functionality is not supported in your browser.');
            }
        });
    </script>
</body>
</html>