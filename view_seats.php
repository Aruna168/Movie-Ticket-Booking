<?php
session_start();
require_once('db_connect.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$booking_id) {
    header('Location: user_dashboard.php');
    exit();
}

// Get booking details
$stmt = $conn->prepare("
    SELECT b.*, m.title as movie_title, t.name as theater_name, 
           s.show_date, s.show_time
    FROM bookings b
    JOIN movies m ON b.movie_id = m.movie_id
    JOIN showtimes s ON b.showtime_id = s.show_id
    JOIN theaters t ON s.theater_id = t.theater_id
    WHERE b.booking_id = ? AND b.user_id = ?
");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: user_dashboard.php');
    exit();
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
$theater_layout = [
    'rows' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'],
    'cols' => range(1, 10)
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seat Details - <?php echo htmlspecialchars($booking['movie_title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
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
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
        }
        
        .seat-row {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .row-label {
            width: 30px;
            text-align: center;
            font-weight: bold;
        }
        
        .seat {
            width: 30px;
            height: 30px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: default;
            margin: 2px;
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
        
        .seat-legend {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            margin: 0 10px;
        }
        
        .legend-box {
            width: 20px;
            height: 20px;
            border-radius: 5px;
            margin-right: 5px;
        }
        
        .legend-box.available {
            background-color: #a0d2eb;
        }
        
        .legend-box.booked {
            background-color: #f44336;
        }
        
        .legend-box.selected {
            background-color: #4CAF50;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Seat Details</h2>
            <a href="user_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <div class="card bg-dark text-light">
            <div class="card-header bg-primary">
                <h5 class="mb-0">Booking #<?php echo $booking_id; ?> - Seats</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Booking Information</h5>
                        <p><strong>Movie:</strong> <?php echo htmlspecialchars($booking['movie_title']); ?></p>
                        <p><strong>Theater:</strong> <?php echo htmlspecialchars($booking['theater_name']); ?></p>
                        <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($booking['show_date'])); ?></p>
                        <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($booking['show_time'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5>Seat Information</h5>
                        <p><strong>Selected Seats:</strong> <?php echo htmlspecialchars($booking['seats'] ?? 'Not specified'); ?></p>
                        <p><strong>Total Amount:</strong> ₹<?php echo number_format($booking['total_price'], 2); ?></p>
                    </div>
                </div>
                
                <hr>
                
                <h4 class="text-center mb-3">Seat Map</h4>
                
                <div class="seat-map-container">
                    <div class="screen">SCREEN</div>
                    <div class="seat-grid">
                        <?php foreach ($theater_layout['rows'] as $row): ?>
                            <div class="seat-row">
                                <div class="row-label"><?php echo $row; ?></div>
                                <?php foreach ($theater_layout['cols'] as $col): ?>
                                    <?php
                                    $seat_id = $row . $col;
                                    $seat_class = 'seat available';
                                    
                                    if (in_array($seat_id, $other_booked_seats)) {
                                        $seat_class = 'seat booked';
                                    } elseif (in_array($seat_id, $selected_seats)) {
                                        $seat_class = 'seat selected';
                                    }
                                    ?>
                                    <div class="<?php echo $seat_class; ?>" data-seat="<?php echo $seat_id; ?>"><?php echo $col; ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="seat-legend mt-4">
                        <div class="legend-item">
                            <div class="legend-box available"></div>
                            <span>Available</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box booked"></div>
                            <span>Booked</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box selected"></div>
                            <span>Your Selection</span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 text-center">
                    <p class="text-muted">This seat map shows your selected seats and other booked seats for this showtime.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>