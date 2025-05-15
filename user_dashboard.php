<?php
require_once('session_config.php');
require_once('db_connect.php');

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT name, email, profile_pic FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Fetch movies from database
$movies = [];
$result = $conn->query("SELECT * FROM movies ORDER BY release_date DESC LIMIT 8");
while ($row = $result->fetch_assoc()) {
    $movies[] = $row;
}

// Check which date column exists in the bookings table
$result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'booking_date'");
$has_booking_date = $result->num_rows > 0;

$result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'booked_at'");
$has_booked_at = $result->num_rows > 0;

// Fetch user's booking history using the appropriate date column
$bookings = [];
if ($has_booking_date) {
    $stmt = $conn->prepare("
        SELECT b.*, m.title, m.image, s.show_date, s.show_time, t.name AS theater_name
        FROM bookings b
        JOIN showtimes s ON b.showtime_id = s.show_id
        JOIN movies m ON b.movie_id = m.movie_id
        JOIN theaters t ON s.theater_id = t.theater_id
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC
        LIMIT 5
    ");
} elseif ($has_booked_at) {
    $stmt = $conn->prepare("
        SELECT b.*, m.title, m.image, s.show_date, s.show_time, t.name AS theater_name
        FROM bookings b
        JOIN showtimes s ON b.showtime_id = s.show_id
        JOIN movies m ON b.movie_id = m.movie_id
        JOIN theaters t ON s.theater_id = t.theater_id
        WHERE b.user_id = ?
        ORDER BY b.booked_at DESC
        LIMIT 5
    ");
} else {
    // If neither column exists, order by booking_id
    $stmt = $conn->prepare("
        SELECT b.*, m.title, m.image, s.show_date, s.show_time, t.name AS theater_name
        FROM bookings b
        JOIN showtimes s ON b.showtime_id = s.show_id
        JOIN movies m ON b.movie_id = m.movie_id
        JOIN theaters t ON s.theater_id = t.theater_id
        WHERE b.user_id = ?
        ORDER BY b.booking_id DESC
        LIMIT 5
    ");
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$stmt->close();

// Get filter options from database
$filter_options = [];
$result = $conn->query("SHOW TABLES LIKE 'filter_options'");
if ($result->num_rows > 0) {
    $result = $conn->query("SELECT * FROM filter_options ORDER BY filter_type, display_order, option_value");
    while ($row = $result->fetch_assoc()) {
        $filter_type = $row['filter_type'];
        if (!isset($filter_options[$filter_type])) {
            $filter_options[$filter_type] = [];
        }
        $filter_options[$filter_type][] = $row['option_value'];
    }
}

// If no genre filters in database, get them from movies
if (!isset($filter_options['genre']) || empty($filter_options['genre'])) {
    $filter_options['genre'] = [];
    $result = $conn->query("SELECT DISTINCT genre FROM movies");
    while ($row = $result->fetch_assoc()) {
        // Split genres if they contain commas
        $genreList = explode(',', $row['genre']);
        foreach ($genreList as $g) {
            $g = trim($g);
            if (!empty($g) && !in_array($g, $filter_options['genre'])) {
                $filter_options['genre'][] = $g;
            }
        }
    }
    sort($filter_options['genre']);
}


// Default language options if not in database
if (!isset($filter_options['language']) || empty($filter_options['language'])) {
    $filter_options['language'] = ['English', 'Hindi', 'Tamil', 'Telugu'];
}

// Default format options if not in database
if (!isset($filter_options['format']) || empty($filter_options['format'])) {
    $filter_options['format'] = ['2D', '3D', 'IMAX', '4DX'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Movie Booking</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/animations.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <script defer src="js/animations.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #584528;
            padding: 15px;
            color: white;
        }
        .logo {
            font-size: 1.5em;
            font-weight: bold;
        }
        .search-bar {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        .search-bar input {
            padding: 10px;
            width: 250px;
            border: none;
            border-radius: 5px;
        }
        .search-bar button {
            background: #ff4500;
            color: white;
            border: none;
            padding: 10px 12px;
            margin-left: 5px;
            cursor: pointer;
            border-radius: 5px;
        }
        .search-bar button:hover {
            background: #e63e00;
        }
        .nav-links .btn {
            text-decoration: none;
            color: white;
            background: #ff4500;
            padding: 10px 15px;
            border-radius: 5px;
            margin-left: 10px;
        }
        .nav-links .btn:hover {
            background: #e63e00;
        }
        .filters {
            display: flex;
            justify-content: center;
            gap: 15px;
            padding: 15px;
            background: #ddd;
            flex-wrap: wrap;
        }
        .filters label, .filters select {
            font-size: 1.2em;
            color: #333;
        }
        .hero {
            text-align: center;
            padding: 60px;
            color: white;
            background: url('assets/images/hero-bg.jpg') no-repeat center center/cover;
            position: relative;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .movies, .offers, .authentication, .bookings {
            padding: 20px;
            text-align: center;
        }
        
        .section-title {
            font-size: 2em;
            color: #584528;
            margin-bottom: 20px;
            position: relative;
            display: inline-block;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: #ff4500;
        }
        
        .movie-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        
        .movie-card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            width: 220px;
            margin-bottom: 20px;
        }
        
        .movie-card:hover {
            transform: scale(1.05);
        }
        
        .movie-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .movie-card h3 {
            font-size: 1.2em;
            margin: 10px 0;
            color: #333;
        }
        
        .movie-card p {
            color: #666;
            margin: 5px 0;
        }
        
        .book-btn {
            background: #ff4500;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .book-btn:hover {
            background: #e63e00;
        }
        
        .footer {
            background: #3c3323;
            color: white;
            text-align: center;
            padding: 15px;
            margin-top: 30px;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            position: relative;
            cursor: pointer;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
            border: 2px solid #ff4500;
        }
        
        .user-name {
            color: white;
            font-weight: bold;
        }
        
        .dropdown-menu {
            position: absolute;
            top: 50px;
            right: 0;
            background: white;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            padding: 10px;
            min-width: 200px;
            display: none;
            z-index: 100;
        }
        
        .dropdown-menu.active {
            display: block;
        }
        
        .dropdown-menu a {
            display: flex;
            align-items: center;
            padding: 8px 10px;
            text-decoration: none;
            color: #333;
            transition: background 0.3s;
        }
        
        .dropdown-menu a i {
            margin-right: 10px;
            font-size: 1.2em;
        }
        
        .dropdown-menu a:hover {
            background: #f4f4f4;
        }
        
        .dropdown-menu .divider {
            height: 1px;
            background: #ddd;
            margin: 8px 0;
        }
        
        .tab-container {
            margin-top: 20px;
        }
        
        .tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            background: #ddd;
            border: none;
            cursor: pointer;
            margin: 0 5px;
            border-radius: 5px 5px 0 0;
            font-weight: bold;
            transition: background 0.3s;
        }
        
        .tab.active {
            background: #ff4500;
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .booking-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            text-align: left;
        }
        
        .booking-poster {
            width: 100px;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 15px;
        }
        
        .booking-details h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .booking-details p {
            margin: 5px 0;
            color: #666;
        }
        
        .booking-actions {
            margin-top: 10px;
        }
        
        .booking-actions a {
            display: inline-block;
            padding: 5px 10px;
            background: #ff4500;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
            font-size: 0.9em;
        }
        
        .booking-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .status-confirmed {
            background: #4CAF50;
            color: white;
        }
        
        .status-pending {
            background: #FFC107;
            color: #333;
        }
        
        .status-cancelled {
            background: #F44336;
            color: white;
        }
        
        .welcome-message {
            font-size: 2.5em;
            margin-bottom: 15px;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .welcome-subtitle {
            font-size: 1.2em;
            margin-bottom: 25px;
            color: white;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }
        
        .cta-button {
            display: inline-block;
            padding: 12px 25px;
            background: #ff4500;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 1.1em;
            transition: background 0.3s, transform 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .cta-button:hover {
            background: #e63e00;
            transform: scale(1.05);
        }
        
        .user-dropdown-toggle {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .user-dropdown-toggle i {
            margin-left: 5px;
            transition: transform 0.3s;
        }
        
        .user-dropdown-toggle.active i {
            transform: rotate(180deg);
        }
    </style>
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="logo">Movie Booking</div>
        <div class="search-bar">
            <input type="text" id="movieSearch" placeholder="Search movies...">
            <button type="button" id="searchButton">🔍</button>
        </div>
        <div class="user-profile">
            <div class="user-dropdown-toggle" id="userDropdownToggle">
                <img src="<?php echo $user['profile_pic'] ?? 'uploads/default-profile.png'; ?>" alt="User" class="user-avatar">
                <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="dropdown-menu" id="userMenu">
                <a href="user_profile.php"><i class="bi bi-person-circle"></i> My Profile</a>
                <a href="#" id="bookingsTabLink"><i class="bi bi-ticket-perforated"></i> My Bookings</a>
                <div class="divider"></div>
                <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
        </div>
    </nav>

    
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="welcome-message">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h1>
            <p class="welcome-subtitle">Discover the latest movies and book your tickets with ease.</p>
            <button class="cta-button" id="exploreMoviesBtn">Explore Movies</button>
        </div>
    </section>

    <!-- Tab Navigation -->
    <div class="tab-container">
        <div class="tabs">
            <button class="tab active" data-tab="movies">Movies</button>
            <button class="tab" data-tab="bookings">My Bookings</button>
        </div>
        
        <!-- Movies Tab Content -->
        <div class="tab-content active" id="moviesTab">
            <!-- Filter Section -->
            <section class="filters">
                <label for="genre">Genre:</label>
                <select id="genre">
                    <option value="all">All</option>
                    <?php foreach ($filter_options['genre'] as $genre): ?>
                        <option value="<?php echo htmlspecialchars($genre); ?>"><?php echo htmlspecialchars($genre); ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="language">Language:</label>
                <select id="language">
                    <option value="all">All</option>
                    <?php foreach ($filter_options['language'] as $language): ?>
                        <option value="<?php echo htmlspecialchars($language); ?>"><?php echo htmlspecialchars($language); ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="format">Format:</label>
                <select id="format">
                    <option value="all">All</option>
                    <?php foreach ($filter_options['format'] as $format): ?>
                        <option value="<?php echo htmlspecialchars($format); ?>"><?php echo htmlspecialchars($format); ?></option>
                    <?php endforeach; ?>
                </select>
            </section>

<!-- Now Showing -->
<section class="movies">
    <h2 class="section-title">Now Showing</h2>
    <div class="movie-container">
        <?php foreach ($movies as $movie): ?>
            <div class="movie-card" data-genre="<?php echo htmlspecialchars($movie['genre']); ?>">
                <img src="uploads/posters/<?php echo htmlspecialchars($movie['image']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                <p><?php echo htmlspecialchars($movie['genre']); ?> | <?php echo $movie['duration']; ?> mins</p>
                <p>Release: <?php echo !empty($movie['release_date']) ? date('M d, Y', strtotime($movie['release_date'])) : 'Coming Soon'; ?></p>
                <a href="book_movie.php?movie_id=<?php echo $movie['movie_id']; ?>" class="book-btn">Select Seats</a>
            </div>
        <?php endforeach; ?>
    </div>
</section>


            <!-- Exclusive Offers -->
            <section class="offers">
                <h2 class="section-title">Exclusive Offers</h2>
                <p>Get special discounts and cashback offers on movie tickets.</p>
                <a href="offers.php" class="cta-button">View Offers</a>
            </section>
        </div>
        
        <!-- Bookings Tab Content -->
        <div class="tab-content" id="bookingsTab">
            <section class="bookings">
                <h2 class="section-title">My Bookings</h2>
                
                <?php if (empty($bookings)): ?>
                    <div class="no-bookings">
                        <p>You haven't made any bookings yet.</p>
                        <button class="cta-button" id="browseMoviesBtn">Browse Movies</button>
                    </div>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <div class="booking-card">
                            <img src="uploads/posters/<?php echo htmlspecialchars($booking['image']); ?>" alt="<?php echo htmlspecialchars($booking['title']); ?>" class="booking-poster">
                            <div class="booking-details">
                                <h4><?php echo htmlspecialchars($booking['title']); ?></h4>
                                <p><strong>Theater:</strong> <?php echo htmlspecialchars($booking['theater_name']); ?></p>
                                <p><strong>Date & Time:</strong> <?php echo date('M d, Y', strtotime($booking['show_date'])); ?> at <?php echo date('h:i A', strtotime($booking['show_time'])); ?></p>
                                
                                <?php
                                // Get seats for this booking
                                $seats = [];
                                $stmt = $conn->prepare("SELECT seat_number FROM seats WHERE booking_id = ?");
                                $stmt->bind_param("i", $booking['booking_id']);
                                $stmt->execute();
                                $seat_result = $stmt->get_result();
                                while ($seat = $seat_result->fetch_assoc()) {
                                    $seats[] = $seat['seat_number'];
                                }
                                $stmt->close();
                                ?>
                                
                                <p><strong>Seats:</strong> <?php echo !empty($seats) ? implode(', ', $seats) : 'N/A'; ?></p>
                                <p><strong>Amount:</strong> ₹<?php echo number_format($booking['total_amount'], 2); ?></p>
                                
                                <div class="booking-actions">
                                    <a href="booking_confirmation.php?booking_id=<?php echo $booking['booking_id']; ?>">View Ticket</a>
                                    <span class="booking-status status-<?php echo strtolower($booking['status'] ?? 'confirmed'); ?>">
                                        <?php echo ucfirst($booking['status'] ?? 'Confirmed'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2025 Movie Ticket Booking. All Rights Reserved.</p>
    </footer>

    <script>
        // User dropdown menu
        const userDropdownToggle = document.getElementById('userDropdownToggle');
        const userMenu = document.getElementById('userMenu');
        
        userDropdownToggle.addEventListener('click', () => {
            userMenu.classList.toggle('active');
            userDropdownToggle.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (event) => {
            if (!userDropdownToggle.contains(event.target) && !userMenu.contains(event.target)) {
                userMenu.classList.remove('active');
                userDropdownToggle.classList.remove('active');
            }
        });
        
        // Tab switching
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.getAttribute('data-tab');
                
                // Remove active class from all tabs and contents
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                tab.classList.add('active');
                document.getElementById(tabId + 'Tab').classList.add('active');
            });
        });
        
        // Bookings tab link in dropdown
        document.getElementById('bookingsTabLink').addEventListener('click', (e) => {
            e.preventDefault();
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            document.querySelector('.tab[data-tab="bookings"]').classList.add('active');
            document.getElementById('bookingsTab').classList.add('active');
            userMenu.classList.remove('active');
            userDropdownToggle.classList.remove('active');
        });
        
        // Browse movies button
        document.getElementById('browseMoviesBtn')?.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            document.querySelector('.tab[data-tab="movies"]').classList.add('active');
            document.getElementById('moviesTab').classList.add('active');
        });
        
        // Explore movies button
        document.getElementById('exploreMoviesBtn').addEventListener('click', () => {
            document.querySelector('.movies').scrollIntoView({ behavior: 'smooth' });
        });
        
        // Movie search functionality
        document.getElementById('searchButton').addEventListener('click', () => {
            const searchTerm = document.getElementById('movieSearch').value.toLowerCase();
            const movieCards = document.querySelectorAll('.movie-card');
            
            movieCards.forEach(card => {
                const title = card.querySelector('h3').textContent.toLowerCase();
                const genre = card.getAttribute('data-genre').toLowerCase();
                
                if (title.includes(searchTerm) || genre.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
        
        // Genre filter
        document.getElementById('genre').addEventListener('change', function() {
            const selectedGenre = this.value.toLowerCase();
            const movieCards = document.querySelectorAll('.movie-card');
            
            movieCards.forEach(card => {
                if (selectedGenre === 'all') {
                    card.style.display = 'block';
                } else {
                    const genre = card.getAttribute('data-genre').toLowerCase();
                    if (genre.includes(selectedGenre)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        });
    </script>
</body>
</html>