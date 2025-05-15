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
    echo "Invalid booking ID.";
    exit();
}

// Fetch booking details
$stmt = $conn->prepare("
    SELECT b.*, m.title, m.image, m.genre, m.duration, 
           st.show_date, st.show_time, t.name AS theater_name, t.location,
           GROUP_CONCAT(s.seat_number ORDER BY s.seat_number SEPARATOR ', ') as seat_numbers,
           GROUP_CONCAT(s.seat_type ORDER BY s.seat_number SEPARATOR ', ') as seat_types,
           p.amount, p.payment_method, p.status AS payment_status
    FROM bookings b
    JOIN showtimes st ON b.showtime_id = st.show_id
    JOIN movies m ON b.movie_id = m.movie_id
    JOIN theaters t ON st.theater_id = t.theater_id
    LEFT JOIN seats s ON s.booking_id = b.booking_id
    LEFT JOIN payments p ON p.booking_id = b.booking_id
    WHERE b.booking_id = ? AND b.user_id = ?
    GROUP BY b.booking_id
");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    echo "Booking not found or you don't have permission to view it.";
    exit();
}

// Get individual seats for display
$seats = explode(', ', $booking['seat_numbers']);
$seat_types = explode(', ', $booking['seat_types']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Movie Ticket - <?php echo htmlspecialchars($booking['title']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="ticket.css">
    <style>
        body {
            background-color: #f4f6f8;
            font-family: 'Poppins', sans-serif;
        }
        
        .ticket-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .ticket-header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .ticket-body {
            padding: 30px;
        }
        
        .ticket-info {
            display: flex;
            margin-bottom: 20px;
        }
        
        .ticket-poster {
            width: 200px;
            border-radius: 10px;
            margin-right: 20px;
        }
        
        .ticket-details h3 {
            margin-top: 0;
            color: #333;
        }
        
        .ticket-details p {
            margin: 5px 0;
            color: #666;
        }
        
        .ticket-footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px dashed #ddd;
        }
        
        .ticket-qr {
            width: 150px;
            height: 150px;
            margin: 0 auto 15px;
            background-color: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .seat-badge {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        .seat-badge.vip {
            background-color: #ff9800;
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
        
        .actions {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="ticket-container">
            <div class="ticket-header">
                <h2>Movie Ticket</h2>
                <p>Booking ID: <?php echo $booking['booking_id']; ?></p>
            </div>
            
            <div class="ticket-body">
                <div class="ticket-info">
                    <img src="uploads/<?php echo $booking['image']; ?>" alt="Movie Poster" class="ticket-poster">
                    <div class="ticket-details">
                        <h3><?php echo htmlspecialchars($booking['title']); ?></h3>
                        <p><strong>Genre:</strong> <?php echo htmlspecialchars($booking['genre']); ?></p>
                        <p><strong>Duration:</strong> <?php echo $booking['duration']; ?> mins</p>
                        <p><strong>Theater:</strong> <?php echo htmlspecialchars($booking['theater_name']); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($booking['location']); ?></p>
                        <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($booking['show_date'])); ?></p>
                        <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($booking['show_time'])); ?></p>
                        <p><strong>Seats:</strong></p>
                        <div class="seat-list">
                            <?php 
                            for ($i = 0; $i < count($seats); $i++) {
                                $seat_class = ($seat_types[$i] == 'vip') ? 'seat-badge vip' : 'seat-badge';
                                echo "<span class=\"$seat_class\">" . $seats[$i] . "</span>";
                            }
                            ?>
                        </div>
                        <p><strong>Amount Paid:</strong> $<?php echo number_format($booking['amount'], 2); ?></p>
                        <p><strong>Payment Method:</strong> <?php echo ucfirst($booking['payment_method'] ?? 'Credit Card'); ?></p>
                        <p><strong>Payment Status:</strong> 
                            <span class="payment-status <?php echo strtolower($booking['payment_status']); ?>">
                                <?php echo ucfirst($booking['payment_status']); ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="ticket-footer">
                <div class="ticket-qr">
                    <!-- QR code would be generated here -->
                    <svg width="100" height="100" viewBox="0 0 100 100">
                        <rect x="10" y="10" width="80" height="80" fill="none" stroke="#333" stroke-width="2" />
                        <text x="50" y="55" text-anchor="middle" font-size="12">QR Code</text>
                    </svg>
                </div>
                <p>Please show this ticket at the entrance.</p>
                <div class="actions">
                    <a href="user_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                    <button onclick="window.print()" class="btn btn-outline-secondary">Print Ticket</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>