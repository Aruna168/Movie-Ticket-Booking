<?php
session_start();
require_once('db_connect.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

$admin_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Booking Status - Movie Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/seat_booking.css">
    <style>
        body {
            background-color: #121212;
            color: white;
            min-height: 100vh;
        }
        
        .container {
            padding-top: 30px;
            padding-bottom: 50px;
        }
        
        .header {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #ff9800;
        }
        
        .header p {
            font-size: 1.2rem;
            color: #aaa;
        }
        
        .filters {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            flex: 1;
            min-width: 200px;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            color: #aaa;
            margin: 0;
        }
        
        .stat-card.total h3 {
            color: #2196F3;
        }
        
        .stat-card.available h3 {
            color: #4CAF50;
        }
        
        .stat-card.booked h3 {
            color: #F44336;
        }
        
        .stat-card.occupancy h3 {
            color: #FF9800;
        }
        
        .refresh-info {
            text-align: center;
            margin-top: 20px;
            color: #aaa;
        }
        
        .auto-refresh {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
        }
        
        .auto-refresh label {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Live Seat Booking Status</h1>
            <p>Monitor real-time seat bookings across all theaters</p>
        </div>
        
        <div class="filters">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="theaterSelect" class="form-label">Theater</label>
                    <select id="theaterSelect" class="form-select">
                        <option value="">Select Theater</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="showtimeSelect" class="form-label">Showtime</label>
                    <select id="showtimeSelect" class="form-select">
                        <option value="">Select Showtime</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="searchBar" class="form-label">Search</label>
                    <input type="text" id="searchBar" class="form-control" placeholder="Search by movie or location">
                </div>
            </div>
        </div>
        
        <div class="stats-container">
            <div class="stat-card total">
                <h3 id="totalSeats">0</h3>
                <p>Total Seats</p>
            </div>
            <div class="stat-card available">
                <h3 id="availableSeats">0</h3>
                <p>Available</p>
            </div>
            <div class="stat-card booked">
                <h3 id="bookedSeats">0</h3>
                <p>Booked</p>
            </div>
            <div class="stat-card occupancy">
                <h3 id="occupancyRate">0%</h3>
                <p>Occupancy Rate</p>
            </div>
        </div>
        
        <div id="seatContainer" class="mb-4">
            <!-- Seat matrix will be rendered here -->
            <div class="text-center py-5">
                <p>Select a theater and showtime to view the seat booking status</p>
            </div>
        </div>
        
        <div class="refresh-info">
            <p>Last updated: <span id="lastUpdated">-</span></p>
            <div class="auto-refresh">
                <label for="autoRefresh">Auto-refresh:</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                </div>
                <span class="ms-2">(Every 5 seconds)</span>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="admin_dashboard.php" class="btn btn-outline-light">Back to Dashboard</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/live_booking.js"></script>
    <script>
        // Additional script for the live booking status page
        document.getElementById('autoRefresh').addEventListener('change', function() {
            if (this.checked) {
                if (refreshInterval === null && currentShowId) {
                    refreshInterval = setInterval(updateSeatMatrix, 5000);
                }
            } else {
                if (refreshInterval !== null) {
                    clearInterval(refreshInterval);
                    refreshInterval = null;
                }
            }
        });
        
        function updateLastUpdated() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            document.getElementById('lastUpdated').textContent = timeString;
        }
        
        // Override the updateSeatMatrix function to also update the last updated time
        const originalUpdateSeatMatrix = updateSeatMatrix;
        updateSeatMatrix = function() {
            originalUpdateSeatMatrix();
            updateLastUpdated();
        };
    </script>
</body>
</html>