<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$showtime_id = isset($_GET['showtime_id']) ? (int)$_GET['showtime_id'] : 0;

if (!$showtime_id) {
    header('Location: manage_showtimes.php');
    exit;
}

// Get showtime details
$stmt = $conn->prepare("
    SELECT s.*, 
           m.title as movie_title, 
           m.image as movie_image,
           t.name as theater_name,
           t.total_seats,
           t.theater_id
    FROM showtimes s
    JOIN movies m ON s.movie_id = m.movie_id
    JOIN theaters t ON s.theater_id = t.theater_id
    WHERE s.show_id = ?
");
$stmt->bind_param("i", $showtime_id);
$stmt->execute();
$result = $stmt->get_result();
$showtime = $result->fetch_assoc();
$stmt->close();

if (!$showtime) {
    header('Location: manage_showtimes.php');
    exit;
}

// Get booked seats
$stmt = $conn->prepare("
    SELECT b.seats
    FROM bookings b
    WHERE b.showtime_id = ? AND (b.booking_status IS NULL OR b.booking_status != 'cancelled')
");
$stmt->bind_param("i", $showtime_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$booked_seats = [];
foreach ($bookings as $booking) {
    if (!empty($booking['seats'])) {
        $seats = explode(', ', $booking['seats']);
        $booked_seats = array_merge($booked_seats, $seats);
    }
}

// Define theater layout
$rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
$cols = 10;
$vip_rows = [3, 4]; // D and E rows
$premium_rows = [2, 5]; // C and F rows
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Booking Status - Admin</title>
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
            margin-bottom: 20px;
        }
        .seat-map-container {
            margin: 20px 0;
            text-align: center;
        }
        
        .screen {
            background-color: #d3d3d3;
            height: 30px;
            width: 70%;
            margin: 0 auto 30px;
            border-radius: 5px;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
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
        
        .seat.occupied {
            background-color: #f44336;
            color: white;
        }
        
        .seat.vip {
            background-color: #ffd700;
            color: #333;
        }
        
        .seat.premium {
            background-color: #9c27b0;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Live Booking Status</h2>
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo htmlspecialchars($showtime['movie_title']); ?></h5>
                    <div>
                        <?php echo htmlspecialchars(date('F j, Y', strtotime($showtime['show_date']))); ?> at 
                        <?php echo htmlspecialchars(date('g:i A', strtotime($showtime['show_time']))); ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <?php if (!empty($showtime['movie_image'])): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($showtime['movie_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($showtime['movie_title']); ?>" 
                                 class="img-fluid rounded">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-9">
                        <h4><?php echo htmlspecialchars($showtime['movie_title']); ?></h4>
                        <p><strong>Theater:</strong> <?php echo htmlspecialchars($showtime['theater_name']); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars(date('l, F j, Y', strtotime($showtime['show_date']))); ?></p>
                        <p><strong>Time:</strong> <?php echo htmlspecialchars(date('g:i A', strtotime($showtime['show_time']))); ?></p>
                        
                        <div class="booking-stats mt-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="card-title">Total Seats</h5>
                                            <p class="card-text display-6"><?php echo count($rows) * $cols; ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-success text-white">
                                        <div class="card-body text-center">
                                            <h5 class="card-title">Booked Seats</h5>
                                            <p class="card-text display-6"><?php echo count($booked_seats); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-info text-white">
                                        <div class="card-body text-center">
                                            <h5 class="card-title">Available Seats</h5>
                                            <p class="card-text display-6"><?php echo (count($rows) * $cols) - count($booked_seats); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <h4 class="text-center mb-3">Seat Map</h4>
                
                <div class="seat-map-container">
                    <div class="screen">SCREEN</div>
                    <div class="seat-grid">
                        <?php foreach ($rows as $i => $row): ?>
                            <div class="seat-row">
                                <div class="row-label"><?php echo $row; ?></div>
                                <?php for ($j = 1; $j <= $cols; $j++): ?>
                                    <?php
                                    $seatId = $row . $j;
                                    $seatClass = 'seat available';
                                    
                                    // Set seat type
                                    if (in_array($i, $premium_rows)) {
                                        $seatClass .= ' premium';
                                    } elseif (in_array($i, $vip_rows)) {
                                        $seatClass .= ' vip';
                                    }
                                    
                                    // Check if seat is occupied
                                    if (in_array($seatId, $booked_seats)) {
                                        $seatClass = str_replace('available', 'occupied', $seatClass);
                                    }
                                    ?>
                                    <div class="<?php echo $seatClass; ?>" data-seat="<?php echo $seatId; ?>"><?php echo $j; ?></div>
                                <?php endfor; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-4 d-flex justify-content-center flex-wrap">
                        <div class="mx-2">
                            <span class="badge bg-primary">Available</span>
                        </div>
                        <div class="mx-2">
                            <span class="badge bg-danger">Occupied</span>
                        </div>
                        <div class="mx-2">
                            <span class="badge bg-warning text-dark">VIP</span>
                        </div>
                        <div class="mx-2">
                            <span class="badge bg-purple" style="background-color: #9c27b0;">Premium</span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h4>Recent Bookings</h4>
                    <div id="recent-bookings">
                        <div class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading recent bookings...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Load recent bookings
            loadRecentBookings();
            
            // Set up auto-refresh
            setInterval(function() {
                refreshSeatMap();
                loadRecentBookings();
            }, 10000); // Refresh every 10 seconds
        });
        
        function loadRecentBookings() {
            fetch('../get_recent_bookings.php?showtime_id=<?php echo $showtime_id; ?>')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('recent-bookings');
                    
                    if (data.length === 0) {
                        container.innerHTML = '<div class="alert alert-info">No bookings found for this showtime.</div>';
                        return;
                    }
                    
                    let html = '<div class="list-group">';
                    
                    data.forEach(booking => {
                        const date = new Date(booking.booking_date);
                        const formattedDate = date.toLocaleString();
                        
                        html += `
                            <div class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">${booking.user_name}</h5>
                                    <small>${formattedDate}</small>
                                </div>
                                <p class="mb-1">Seats: ${booking.seats || 'Not specified'}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small>Amount: ₹${parseFloat(booking.total_price).toFixed(2)}</small>
                                    <span class="badge ${booking.payment_status === 'completed' ? 'bg-success' : 'bg-warning text-dark'}">
                                        ${booking.payment_status ? booking.payment_status.charAt(0).toUpperCase() + booking.payment_status.slice(1) : 'Pending'}
                                    </span>
                                </div>
                            </div>
                        `;
                    });
                    
                    html += '</div>';
                    container.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error loading recent bookings:', error);
                    document.getElementById('recent-bookings').innerHTML = 
                        '<div class="alert alert-danger">Error loading recent bookings. Please try again.</div>';
                });
        }
        
        function refreshSeatMap() {
            fetch('../get_occupied_seats.php?showtime_id=<?php echo $showtime_id; ?>')
                .then(response => response.json())
                .then(data => {
                    // Reset all seats
                    document.querySelectorAll('.seat').forEach(seat => {
                        const seatClass = seat.className.replace('occupied', 'available');
                        seat.className = seatClass;
                    });
                    
                    // Mark occupied seats
                    data.forEach(seatId => {
                        const seatElement = document.querySelector(`.seat[data-seat="${seatId}"]`);
                        if (seatElement) {
                            seatElement.className = seatElement.className.replace('available', 'occupied');
                        }
                    });
                    
                    // Update stats
                    const totalSeats = <?php echo count($rows) * $cols; ?>;
                    const occupiedSeats = data.length;
                    const availableSeats = totalSeats - occupiedSeats;
                    
                    document.querySelector('.booking-stats .card:nth-child(2) .display-6').textContent = occupiedSeats;
                    document.querySelector('.booking-stats .card:nth-child(3) .display-6').textContent = availableSeats;
                })
                .catch(error => {
                    console.error('Error refreshing seat map:', error);
                });
        }
    </script>
</body>
</html>