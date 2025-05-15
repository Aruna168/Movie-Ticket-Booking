<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$booking_id = $_GET['booking_id']; 
$user_id = $_SESSION['user_id'];

// Get booking details
$query = "SELECT b.booking_id, b.total_price, b.seats, m.title, s.show_date, s.show_time, 
          t.name AS theater_name, t.theater_id 
          FROM bookings b 
          JOIN showtimes s ON b.showtime_id = s.show_id 
          JOIN movies m ON s.movie_id = m.movie_id 
          JOIN theaters t ON s.theater_id = t.theater_id 
          WHERE b.booking_id = ? AND b.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$booking_details = $result->fetch_assoc();

// Get theater payment QR
$query = "SELECT * FROM theater_payment_qr WHERE theater_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $booking_details['theater_id']);
$stmt->execute();
$result = $stmt->get_result();
$payment_qr = $result->fetch_assoc();

// Get user details for payment
$query = "SELECT name, email FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_details = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Movie Booking</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/payment.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card bg-dark text-light border-secondary">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Complete Your Payment</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Booking Details</h5>
                                <p><strong>Movie:</strong> <?= htmlspecialchars($booking_details['title']) ?></p>
                                <p><strong>Theater:</strong> <?= htmlspecialchars($booking_details['theater_name']) ?></p>
                                <p><strong>Date & Time:</strong> <?= date('M d, Y', strtotime($booking_details['show_date'])) ?> at <?= date('h:i A', strtotime($booking_details['show_time'])) ?></p>
                                <p><strong>Seats:</strong> <?= htmlspecialchars($booking_details['seats']) ?></p>
                                <p><strong>Amount:</strong> ₹<?= number_format($booking_details['total_price'], 2) ?></p>
                            </div>
                            <div class="col-md-6 text-center">
                                <?php if ($payment_qr && !empty($payment_qr['qr_image'])): ?>
                                    <h5>Scan QR to Pay</h5>
                                    <img src="<?= $payment_qr['qr_image'] ?>" alt="Payment QR" class="img-fluid mb-3" style="max-height: 200px;">
                                    <?php if (!empty($payment_qr['upi_id'])): ?>
                                        <p class="mb-1"><strong>UPI ID:</strong> <?= htmlspecialchars($payment_qr['upi_id']) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($payment_qr['payment_instructions'])): ?>
                                        <p class="small text-muted"><?= htmlspecialchars($payment_qr['payment_instructions']) ?></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        No payment QR code available for this theater. Please contact the theater directly.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <form id="payment-form" action="payment_process.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
                            <input type="hidden" name="payment_method" value="qr_code" id="payment_method">
                            
                            <div class="mb-3">
                                <label for="transaction_id" class="form-label">Transaction ID/Reference Number</label>
                                <input type="text" class="form-control bg-dark text-light" id="transaction_id" name="transaction_id" required>
                                <small class="text-muted">Enter the transaction ID or reference number from your payment app</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="payment_screenshot" class="form-label">Payment Screenshot (Optional)</label>
                                <input type="file" class="form-control bg-dark text-light" id="payment_screenshot" name="payment_screenshot" accept="image/*">
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg">Confirm Payment</button>
                            </div>
                        </form>
                        
                        <div class="mt-4">
                            <p class="text-center text-muted">Your booking will be confirmed after payment verification</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>