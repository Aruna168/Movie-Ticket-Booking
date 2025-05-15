<?php
session_start();
require_once('db_connect.php');

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = $_GET['booking_id'] ?? 0;

if (!$booking_id) {
    header('Location: user_dashboard.php');
    exit();
}

// Get the structure of the bookings table
$columns = [];
$result = $conn->query("SHOW COLUMNS FROM bookings");
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

// Build the query based on available columns
$sql = "SELECT b.*, m.title, m.image, m.genre, m.duration";

// Add showtime-related joins if showtime_id exists
if (in_array('showtime_id', $columns)) {
    $sql .= ", s.show_date, s.show_time, t.name AS theater_name, t.location
              FROM bookings b
              JOIN showtimes s ON b.showtime_id = s.show_id
              JOIN movies m ON b.movie_id = m.movie_id
              JOIN theaters t ON s.theater_id = t.theater_id";
} else if (in_array('show_id', $columns)) {
    $sql .= ", s.show_date, s.show_time, t.name AS theater_name, t.location
              FROM bookings b
              JOIN showtimes s ON b.show_id = s.show_id
              JOIN movies m ON b.movie_id = m.movie_id
              JOIN theaters t ON s.theater_id = t.theater_id";
} else {
    $sql .= " FROM bookings b
              JOIN movies m ON b.movie_id = m.movie_id";
}

$sql .= " WHERE b.booking_id = ? AND b.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    header('Location: user_dashboard.php');
    exit();
}

// Get seat information if seats table exists
$seats = [];
$seat_types = [];

$result = $conn->query("SHOW TABLES LIKE 'seats'");
if ($result->num_rows > 0) {
    $stmt = $conn->prepare("SELECT seat_number, seat_type FROM seats WHERE booking_id = ? ORDER BY seat_number");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $seats[] = $row['seat_number'];
        $seat_types[] = $row['seat_type'];
    }
    $stmt->close();
}

// Get payment information if payments table exists
$payment = null;
$result = $conn->query("SHOW TABLES LIKE 'payments'");
if ($result->num_rows > 0) {
    $stmt = $conn->prepare("SELECT * FROM payments WHERE booking_id = ? LIMIT 1");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();
    $stmt->close();
}

// Generate QR code data
$qr_data = json_encode([
    'booking_id' => $booking_id,
    'movie' => $booking['title'],
    'theater' => $booking['theater_name'] ?? 'Theater',
    'date' => $booking['show_date'] ?? date('Y-m-d'),
    'time' => $booking['show_time'] ?? '12:00:00',
    'seats' => implode(', ', $seats)
]);
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qr_data);

// Get the total amount from the appropriate column
$total_amount = 0;
if (in_array('total_amount', $columns) && isset($booking['total_amount'])) {
    $total_amount = $booking['total_amount'];
} else if (isset($payment['amount'])) {
    $total_amount = $payment['amount'];
}

// Get the booking status
$booking_status = "Confirmed";
if (in_array('status', $columns) && isset($booking['status'])) {
    $booking_status = $booking['status'];
} else if (isset($payment['status'])) {
    $booking_status = $payment['status'];
}

// Get the booking date
$booking_date = date('Y-m-d');
if (in_array('booking_date', $columns) && isset($booking['booking_date'])) {
    $booking_date = $booking['booking_date'];
} else if (in_array('booked_at', $columns) && isset($booking['booked_at'])) {
    $booking_date = $booking['booked_at'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - <?php echo htmlspecialchars($booking['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f8;
            font-family: Arial, sans-serif;
        }
        
        .confirmation-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .confirmation-header {
            background: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        
        .confirmation-header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .confirmation-header p {
            font-size: 1.1rem;
            margin-bottom: 0;
            opacity: 0.9;
        }
        
        .confirmation-header .check-icon {
            font-size: 5rem;
            margin-bottom: 15px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .booking-details {
            padding: 30px;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }
        
        .movie-info {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .movie-poster {
            width: 120px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .movie-details h3 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .movie-meta {
            color: #777;
            margin-bottom: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .info-item {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
        }
        
        .info-item .label {
            color: #777;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .info-item .value {
            font-weight: 500;
            font-size: 1.1rem;
        }
        
        .seat-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .seat-badge {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: 500;
        }
        
        .seat-badge.vip {
            background-color: #ff9800;
        }
        
        .seat-badge.premium {
            background-color: #9c27b0;
        }
        
        .payment-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .payment-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .payment-total {
            font-weight: bold;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
            margin-top: 10px;
            font-size: 1.1rem;
        }
        
        .payment-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        .payment-status.completed {
            background-color: #28a745;
            color: white;
        }
        
        .payment-status.pending {
            background-color: #ffc107;
            color: #333;
        }
        
        .payment-status.failed {
            background-color: #dc3545;
            color: white;
        }
        
        .qr-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .qr-code {
            width: 150px;
            height: 150px;
            margin: 0 auto 15px;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 10px;
        }
        
        .actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }
        
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background-color: #f00;
            border-radius: 50%;
            animation: confetti-fall 5s ease-in-out infinite;
        }
        
        @keyframes confetti-fall {
            0% {
                transform: translateY(-100px) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(500px) rotate(360deg);
                opacity: 0;
            }
        }
        
        /* Print styles */
        @media print {
            body {
                background-color: white;
                margin: 0;
                padding: 0;
                font-size: 12px;
            }
            
            .confirmation-container {
                max-width: 100%;
                margin: 0;
                box-shadow: none;
                border-radius: 0;
            }
            
            .confirmation-header {
                padding: 15px;
                background: #4CAF50 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .confirmation-header h1 {
                font-size: 18px;
                margin-bottom: 5px;
            }
            
            .confirmation-header p {
                font-size: 12px;
            }
            
            .confirmation-header .check-icon {
                font-size: 24px;
                margin-bottom: 5px;
                animation: none;
            }
            
            .booking-details {
                padding: 15px;
            }
            
            .section-title {
                font-size: 14px;
                margin-bottom: 10px;
                padding-bottom: 5px;
            }
            
            .movie-info {
                gap: 10px;
                margin-bottom: 15px;
            }
            
            .movie-poster {
                width: 80px;
            }
            
            .movie-details h3 {
                font-size: 16px;
            }
            
            .info-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                margin-bottom: 15px;
            }
            
            .info-item {
                padding: 10px;
            }
            
            .info-item .label {
                font-size: 10px;
            }
            
            .info-item .value {
                font-size: 12px;
            }
            
            .seat-badge {
                padding: 3px 6px;
                font-size: 10px;
            }
            
            .payment-details {
                padding: 10px;
                margin-bottom: 15px;
            }
            
            .qr-section {
                margin-bottom: 15px;
            }
            
            .qr-code {
                width: 100px;
                height: 100px;
            }
            
            .actions, .navbar, .footer, .confetti {
                display: none !important;
            }
            
            /* Compact print ticket layout */
            .print-ticket {
                border: 1px solid #ddd;
                padding: 10px;
                max-width: 350px;
                margin: 0 auto;
                page-break-inside: avoid;
            }
            
            .print-ticket-header {
                text-align: center;
                border-bottom: 1px dashed #ddd;
                padding-bottom: 10px;
                margin-bottom: 10px;
            }
            
            .print-ticket-content {
                font-size: 11px;
            }
            
            .print-ticket-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 5px;
            }
            
            .print-ticket-footer {
                text-align: center;
                border-top: 1px dashed #ddd;
                padding-top: 10px;
                margin-top: 10px;
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="confirmation-container">
            <div class="confirmation-header" id="confettiContainer">
                <div class="check-icon">✓</div>
                <h1>Booking Confirmed!</h1>
                <p>Your movie tickets have been booked successfully.</p>
            </div>
            
            <div class="booking-details">
                <div class="section-title">Movie Information</div>
                <div class="movie-info">
                    <img src="uploads/<?php echo $booking['image']; ?>" alt="<?php echo htmlspecialchars($booking['title']); ?>" class="movie-poster">
                    <div class="movie-details">
                        <h3><?php echo htmlspecialchars($booking['title']); ?></h3>
                        <div class="movie-meta">
                            <?php echo htmlspecialchars($booking['genre']); ?> | <?php echo $booking['duration']; ?> mins
                        </div>
                        <?php if (isset($booking['show_date']) && isset($booking['show_time'])): ?>
                        <div>
                            <strong>Date:</strong> <?php echo date('l, F j, Y', strtotime($booking['show_date'])); ?><br>
                            <strong>Time:</strong> <?php echo date('g:i A', strtotime($booking['show_time'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="section-title">Booking Details</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="label">Booking ID</div>
                        <div class="value"><?php echo $booking['booking_id']; ?></div>
                    </div>
                    <?php if (isset($booking['theater_name'])): ?>
                    <div class="info-item">
                        <div class="label">Theater</div>
                        <div class="value"><?php echo htmlspecialchars($booking['theater_name']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($booking['location'])): ?>
                    <div class="info-item">
                        <div class="label">Location</div>
                        <div class="value"><?php echo htmlspecialchars($booking['location']); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <div class="label">Booking Date</div>
                        <div class="value"><?php echo date('M j, Y', strtotime($booking_date)); ?></div>
                    </div>
                </div>
                
                <div class="section-title">Seat Information</div>
                <div class="seat-list">
                    <?php 
                    if (!empty($seats)) {
                        for ($i = 0; $i < count($seats); $i++) {
                            $seat_class = 'seat-badge';
                            if (isset($seat_types[$i])) {
                                if ($seat_types[$i] == 'vip') {
                                    $seat_class .= ' vip';
                                } elseif ($seat_types[$i] == 'premium') {
                                    $seat_class .= ' premium';
                                }
                            }
                            echo "<span class=\"$seat_class\">" . $seats[$i] . "</span>";
                        }
                    } else {
                        echo "<p>Seats: " . ceil($total_amount / 150) . " seat(s)</p>";
                    }
                    ?>
                </div>
                
                <div class="section-title">Payment Information</div>
                <div class="payment-details">
                    <div class="payment-item">
                        <span>Amount Paid:</span>
                        <span>₹<?php echo number_format($total_amount, 2); ?></span>
                    </div>
                    <div class="payment-item">
                        <span>Payment Method:</span>
                        <span><?php echo ucfirst($payment['payment_method'] ?? 'Credit Card'); ?></span>
                    </div>
                    <div class="payment-item">
                        <span>Payment Status:</span>
                        <span class="payment-status completed">
                            <?php echo ucfirst($booking_status); ?>
                        </span>
                    </div>
                </div>
                
                <div class="qr-section">
                    <div class="section-title">Ticket QR Code</div>
                    <img src="<?php echo $qr_code_url; ?>" alt="Ticket QR Code" class="qr-code">
                    <p>Please show this QR code at the theater entrance.</p>
                </div>
                
                <div class="actions">
                    <a href="user_dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                    <button onclick="window.print()" class="btn btn-outline-secondary">Print Ticket</button>
                    <a href="movie_details.php?id=<?php echo $booking['movie_id']; ?>" class="btn btn-outline-primary">Movie Details</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Hidden print-only ticket -->
    <div class="print-ticket" style="display: none;">
        <div class="print-ticket-header">
            <h2>MOVIE TICKET</h2>
            <p>Booking ID: <?php echo $booking['booking_id']; ?></p>
        </div>
        <div class="print-ticket-content">
            <div class="print-ticket-row">
                <strong>Movie:</strong>
                <span><?php echo htmlspecialchars($booking['title']); ?></span>
            </div>
            <?php if (isset($booking['theater_name'])): ?>
            <div class="print-ticket-row">
                <strong>Theater:</strong>
                <span><?php echo htmlspecialchars($booking['theater_name']); ?></span>
            </div>
            <?php endif; ?>
            <?php if (isset($booking['show_date']) && isset($booking['show_time'])): ?>
            <div class="print-ticket-row">
                <strong>Date & Time:</strong>
                <span><?php echo date('d/m/Y', strtotime($booking['show_date'])) . ' ' . date('g:i A', strtotime($booking['show_time'])); ?></span>
            </div>
            <?php endif; ?>
            <div class="print-ticket-row">
                <strong>Seats:</strong>
                <span><?php echo !empty($seats) ? implode(', ', $seats) : ceil($total_amount / 150) . " seat(s)"; ?></span>
            </div>
            <div class="print-ticket-row">
                <strong>Amount:</strong>
                <span>₹<?php echo number_format($total_amount, 2); ?></span>
            </div>
        </div>
        <div class="print-ticket-footer">
            <img src="<?php echo $qr_code_url; ?>" alt="Ticket QR Code" style="width: 80px; height: 80px;">
            <p>Please show this ticket at the theater entrance.</p>
            <p>Thank you for booking with us!</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Create confetti effect
        function createConfetti() {
            const container = document.getElementById('confettiContainer');
            const colors = ['#f00', '#0f0', '#00f', '#ff0', '#f0f', '#0ff'];
            
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.animationDelay = Math.random() * 5 + 's';
                confetti.style.animationDuration = Math.random() * 3 + 2 + 's';
                container.appendChild(confetti);
            }
        }
        
        // Initialize confetti
        window.onload = function() {
            createConfetti();
            
            // Show print-only ticket when printing
            window.addEventListener('beforeprint', function() {
                document.querySelector('.print-ticket').style.display = 'block';
            });
            
            window.addEventListener('afterprint', function() {
                document.querySelector('.print-ticket').style.display = 'none';
            });
        };
    </script>
</body>
</html>