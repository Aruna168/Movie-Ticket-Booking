<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$payment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$payment_id) {
    header('Location: manage_payments.php');
    exit;
}

// Get payment details
$stmt = $conn->prepare("
    SELECT p.*, b.booking_id, b.seats, b.total_price as booking_amount,
           u.name as user_name, u.email as user_email,
           m.title as movie_title, m.image as movie_image,
           t.name as theater_name, t.location as theater_location,
           s.show_date, s.show_time, s.theater_id
    FROM payments p
    JOIN bookings b ON p.booking_id = b.booking_id
    JOIN users u ON b.user_id = u.user_id
    JOIN movies m ON b.movie_id = m.movie_id
    JOIN showtimes s ON b.showtime_id = s.show_id
    JOIN theaters t ON s.theater_id = t.theater_id
    WHERE p.payment_id = ?
");
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();
$payment = $result->fetch_assoc();
$stmt->close();

if (!$payment) {
    header('Location: manage_payments.php');
    exit;
}

// Check if admin has access to this theater
$theater_id = $payment['theater_id'];
$stmt = $conn->prepare("SELECT 1 FROM theater_admins WHERE user_id = ? AND theater_id = ?");
$stmt->bind_param("ii", $user_id, $theater_id);
$stmt->execute();
$result = $stmt->get_result();
$hasAccess = $result->num_rows > 0;
$stmt->close();

// Super admin check
$stmt = $conn->prepare("SELECT COUNT(*) FROM theater_admins WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$isSuperAdmin = ($result->fetch_row()[0] == 0);
$stmt->close();

if (!$isSuperAdmin && !$hasAccess) {
    header('Location: manage_payments.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Details - Admin</title>
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
        .card-header {
            background-color: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid #2d2d2d;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Payment Details</h2>
            <a href="manage_payments.php" class="btn btn-secondary">Back to Payments</a>
        </div>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Payment #<?= $payment['payment_id'] ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Payment Information</h5>
                        <p><strong>Payment ID:</strong> <?= $payment['payment_id'] ?></p>
                        <p><strong>Amount:</strong> ₹<?= number_format($payment['amount'], 2) ?></p>
                        <p><strong>Status:</strong> 
                            <span class="badge <?= ($payment['payment_status'] === 'completed') ? 'bg-success' : (($payment['payment_status'] === 'failed') ? 'bg-danger' : 'bg-warning text-dark') ?>">
                                <?= ucfirst($payment['payment_status']) ?>
                            </span>
                        </p>
                        <p><strong>Payment Method:</strong> <?= ucfirst($payment['payment_method'] ?? 'N/A') ?></p>
                        <p><strong>Transaction ID:</strong> <?= $payment['transaction_id'] ?? 'N/A' ?></p>
                        <p><strong>Payment Date:</strong> <?= date('Y-m-d H:i:s', strtotime($payment['payment_date'])) ?></p>
                        
                        <?php if ($payment['payment_status'] !== 'completed'): ?>
                            <div class="mt-3">
                                <a href="update_payment.php?id=<?= $payment['payment_id'] ?>&status=completed" class="btn btn-success" onclick="return confirm('Mark this payment as completed?')">
                                    Mark as Completed
                                </a>
                                
                                <?php if ($payment['payment_status'] !== 'failed'): ?>
                                    <a href="update_payment.php?id=<?= $payment['payment_id'] ?>&status=failed" class="btn btn-danger ms-2" onclick="return confirm('Mark this payment as failed?')">
                                        Mark as Failed
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($payment['payment_status'] === 'completed'): ?>
                            <div class="mt-3">
                                <a href="update_payment.php?id=<?= $payment['payment_id'] ?>&status=refunded" class="btn btn-warning" onclick="return confirm('Mark this payment as refunded?')">
                                    Mark as Refunded
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>Booking Information</h5>
                        <p><strong>Booking ID:</strong> <?= $payment['booking_id'] ?></p>
                        <p><strong>Customer:</strong> <?= htmlspecialchars($payment['user_name']) ?> (<?= htmlspecialchars($payment['user_email']) ?>)</p>
                        <p><strong>Movie:</strong> <?= htmlspecialchars($payment['movie_title']) ?></p>
                        <p><strong>Theater:</strong> <?= htmlspecialchars($payment['theater_name']) ?> (<?= htmlspecialchars($payment['theater_location']) ?>)</p>
                        <p><strong>Show Date:</strong> <?= date('Y-m-d', strtotime($payment['show_date'])) ?></p>
                        <p><strong>Show Time:</strong> <?= date('H:i', strtotime($payment['show_time'])) ?></p>
                        <p><strong>Seats:</strong> <?= htmlspecialchars($payment['seats'] ?? 'N/A') ?></p>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-12">
                        <h5>Payment Breakdown</h5>
                        <table class="table table-dark table-striped">
                            <tbody>
                                <tr>
                                    <td>Booking Amount</td>
                                    <td class="text-end">₹<?= number_format($payment['booking_amount'], 2) ?></td>
                                </tr>
                                <?php if ($payment['amount'] > $payment['booking_amount']): ?>
                                    <tr>
                                        <td>Convenience Fee</td>
                                        <td class="text-end">₹<?= number_format($payment['amount'] - $payment['booking_amount'], 2) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr class="table-primary">
                                    <th>Total Amount</th>
                                    <th class="text-end">₹<?= number_format($payment['amount'], 2) ?></th>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>