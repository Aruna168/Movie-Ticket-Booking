<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$booking_id) {
    header('Location: manage_bookings.php');
    exit;
}

// Get booking details
$stmt = $conn->prepare("
    SELECT b.*, 
           u.name as user_name, 
           m.title as movie_title, 
           t.name as theater_name,
           s.show_date, 
           s.show_time
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN movies m ON b.movie_id = m.movie_id
    JOIN showtimes s ON b.showtime_id = s.show_id
    JOIN theaters t ON s.theater_id = t.theater_id
    WHERE b.booking_id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: manage_bookings.php');
    exit;
}

$booking = $result->fetch_assoc();
$stmt->close();

// Get all booked seats for this showtime (excluding this booking)
$stmt = $conn->prepare("
    SELECT seats FROM bookings 
    WHERE showtime_id = ? AND booking_id != ? 
    AND (booking_status IS NULL OR booking_status != 'cancelled')
");
$stmt->bind_param("ii", $booking['showtime_id'], $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$other_booked_seats = [];

while ($row = $result->fetch_assoc()) {
    if (!empty($row['seats'])) {
        $seats = explode(', ', $row['seats']);
        $other_booked_seats = array_merge($other_booked_seats, $seats);
    }
}
$stmt->close();

// Get selected seats for this booking
$selected_seats = !empty($booking['seats']) ? explode(', ', $booking['seats']) : [];

// Define theater layout
$rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
$cols = 10;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seat Details - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #121212;
            color: #f8f9fa;
        }
        .card {
            background-color: #1e1e1e;
            border: none;
            border-radius: 10px;
        }
        .seat-map-container {
            text-align: center;
            margin: 20px 0;
        }
        .screen {
            background-color: #d3d3d3;
            height: 30px;
            width: 70%;
            margin: 0 auto 30px;
            border-radius: 5px;
            color: #333;
            text-align: center;
            line-height: 30px;
            transform: perspective(200px) rotateX(-10deg);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.7);
        }
        .seat-grid {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            gap: 10px;
        }
        .seat-row {
            display: flex !important;
            align-items: center !important;
            gap: 5px;
        }
        .row-label {
            width: 30px;
            text-align: center;
            font-weight: bold;
        }
        .seat {
            width: 30px !important;
            height: 30px !important;
            border-radius: 5px;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 12px;
            margin: 2px !important;
        }
        .seat.available {
            background-color: #a0d2eb;
            color: #333;
        }
        .seat.booked {
            background-color: #f44336;
            color: white;
        }
        .seat.selected {
            background-color: #4CAF50;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Seat Details</h2>
            <a href="manage_bookings.php" class="btn btn-secondary">Back to Bookings</a>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Booking #<?= htmlspecialchars($booking['booking_id']) ?> - Seats</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Booking Information</h5>
                        <p><strong>Customer:</strong> <?= htmlspecialchars($booking['user_name']) ?></p>
                        <p><strong>Movie:</strong> <?= htmlspecialchars($booking['movie_title']) ?></p>
                        <p><strong>Theater:</strong> <?= htmlspecialchars($booking['theater_name']) ?></p>
                        <p><strong>Date:</strong> <?= htmlspecialchars(date('F j, Y', strtotime($booking['show_date']))) ?></p>
                        <p><strong>Time:</strong> <?= htmlspecialchars(date('g:i A', strtotime($booking['show_time']))) ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5>Seat Information</h5>
                        <p><strong>Selected Seats:</strong> <?= htmlspecialchars($booking['seats'] ?? 'Not specified') ?></p>
                    </div>
                </div>
                
                <hr>
                
                <h4 class="text-center mb-3">Seat Map</h4>
                
                <div class="seat-map-container">
                    <div class="screen">SCREEN</div>
                    <div class="seat-grid">
                        <?php foreach ($rows as $i => $row): ?>
                            <div class="seat-row">
                                <div class="row-label"><?= $row ?></div>
                                <?php for ($j = 1; $j <= $cols; $j++): ?>
                                    <?php 
                                    $seatId = $row . $j;
                                    $seatClass = 'seat available';
                                    
                                    if (in_array($seatId, $other_booked_seats)) {
                                        $seatClass = 'seat booked';
                                    } elseif (in_array($seatId, $selected_seats)) {
                                        $seatClass = 'seat selected';
                                    }
                                    ?>
                                    <div class="<?= $seatClass ?>"><?= $j ?></div>
                                <?php endfor; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mt-4 text-center">
                    <div class="d-flex justify-content-center">
                        <div class="me-3">
                            <span class="badge bg-primary">Available</span>
                        </div>
                        <div class="me-3">
                            <span class="badge bg-danger">Booked by Others</span>
                        </div>
                        <div>
                            <span class="badge bg-success">Customer's Selection</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>