<?php
session_start();
include 'db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = '';

// Fetch admin name securely
$sql = "SELECT name FROM users WHERE user_id = ? AND role = 'admin'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($admin_name);
$stmt->fetch();
$stmt->close();

// Closing database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Movie Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <script defer src="js/live_booking.js"></script>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .container-fluid {
            flex-grow: 1;
            overflow: auto;
        }
        .welcome-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            max-width: 60%;
            margin: 80px auto 30px;
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .dashboard-content {
            font-size: 18px;
            max-width: 600px;
            margin: 20px auto;
            text-align: center;
        }
        .navbar .container-fluid {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .hamburger-icon {
            font-size: 24px;
            cursor: pointer;
            margin-right: 15px;
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            margin-left: 10px;
        }
        .hamburger-menu {
            position: absolute;
            top: 60px;
            left: 0;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 15px;
            border-radius: 10px;
            display: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 200px;
            z-index: 1000;
        }
        .hamburger-menu a {
            display: block;
            color: white;
            padding: 10px;
            text-decoration: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        .hamburger-menu a:last-child {
            border-bottom: none;
        }
        .hamburger-menu a:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .live-booking-status {
            background-color: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 30px;
            margin-top: 40px;
            border-radius: 10px;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
            max-width: 900px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
        }
        .live-booking-status h3 {
            font-size: 24px;
            margin-bottom: 20px;
        }
        .live-booking-status select,
        .live-booking-status input {
            margin-bottom: 10px;
            width: 100%;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            background: #333;
            color: white;
            text-align: center;
            padding: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <div class="hamburger-icon" id="hamburgerIcon">☰</div>
                <a class="navbar-brand ms-3" href="#">Admin Panel</a>
            </div>
            <div class="profile-section d-flex align-items-center">
                <span id="adminName" class="me-2"><?php echo htmlspecialchars($admin_name, ENT_QUOTES, 'UTF-8'); ?></span>
                <div class="profile-icon" id="profileIcon">👤</div>
            </div>
        </div>
    </nav>

    <div class="hamburger-menu" id="menu">
        <a href="edit_movie.php">Manage Movies</a>
        <a href="manage_theaters.php">Manage Theaters</a>
        <a href="manage_bookings.php">Manage Bookings</a>
        <a href="manage_showtimes.php">Manage Showtimes</a>
        <a href="manage_payments.php">Manage Payments</a>
        <a href="reports.php">Generate Reports</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="welcome-card">
        <h1 class="welcome-text">Welcome, <?php echo htmlspecialchars($admin_name, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p>Welcome to the Movie Booking Admin Dashboard, <b><?php echo htmlspecialchars($admin_name, ENT_QUOTES, 'UTF-8'); ?></b>. 
        Manage movies, theaters, bookings, and showtimes with ease. Stay updated with reports and analytics to optimize performance.</p>
    </div>

    <div class="live-booking-status">
        <h3>Live Booking Status</h3>
        <select id="theaterSelect" class="form-select mb-3">
            <option value="">Select Theater</option>
        </select>
        <select id="showtimeSelect" class="form-select mb-3">
            <option value="">Select Showtime</option>
        </select>
        <input type="text" id="searchBar" class="form-control mb-3" placeholder="Search by location or showtime">
        <div class="seat-container" id="seatContainer"></div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 Movie Ticket Booking. All Rights Reserved.</p>
    </footer>

    <script>
        document.getElementById('profileIcon').addEventListener('click', function() {
            window.location.href = 'admin_profile.php';
        });

        document.getElementById('hamburgerIcon').addEventListener('click', function() {
            let menu = document.getElementById('menu');
            menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
        });
    </script>
</body>
</html>
