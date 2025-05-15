<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movies - Movie Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <script defer src="js/dashboard.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="logo">Movie Booking</div>
        <ul class="nav-links">
            <li><a href="user_dashboard.php">Home</a></li>
            <li><a href="movies.php" class="active">Movies</a></li>
            <li><a href="book_ticket.php">Book Tickets</a></li>
            <li><a href="my_bookings.php">My Bookings</a></li>
            <li><a href="offers.php">Offers</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <section class="movies">
        <h2>Now Showing</h2>
        <div class="filters">
            <label for="genre">Genre:</label>
            <select id="genre" onchange="filterMovies()">
                <option value="all">All</option>
                <option value="action">Action</option>
                <option value="comedy">Comedy</option>
                <option value="drama">Drama</option>
                <option value="horror">Horror</option>
            </select>
        </div>
        <div class="movie-container" id="movieList">
            <?php
                include 'db_connect.php';
                $result = $conn->query("SELECT * FROM movies");
                while ($row = $result->fetch_assoc()) {
                    echo "<div class='movie-card' data-genre='" . strtolower($row['genre']) . "'>
                            <img src='assets/images/" . $row['image'] . "' alt='" . $row['title'] . "'>
                            <h3>" . $row['title'] . "</h3>
                            <p>" . $row['genre'] . " | " . $row['duration'] . " mins</p>
                            <p>Rating: " . $row['rating'] . "⭐</p>
                            <p>Price: $" . $row['price'] . "</p>
                            <button class='book-btn' onclick=\"window.location.href='book_ticket.php?id=" . $row['id'] . "'\">Book Now</button>
                          </div>";
                }
                $conn->close();
            ?>
        </div>
    </section>

    <footer class="footer">
        <p>&copy; 2025 Movie Ticket Booking. All Rights Reserved.</p>
    </footer>
</body>
</html>
