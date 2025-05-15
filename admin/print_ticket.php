<?php
session_start();
require_once '../config.php';
require_once '../includes/helpers.php';

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
$stmt = $pdo->prepare("
    SELECT b.*, 
           u.name as user_name, 
           u.email as user_email,
           m.title as movie_title, 
           m.image as movie_image,
           m.duration,
           t.name as theater_name,
           t.location as theater_location,
           s.show_date, 
           s.show_time,
           p.payment_status
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN movies m ON b.movie_id = m.movie_id
    JOIN showtimes s ON b.showtime_id = s.show_id
    JOIN theaters t ON s.theater_id = t.theater_id
    LEFT JOIN payments p ON b.booking_id = p.booking_id
    WHERE b.booking_id = ?
");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: manage_bookings.php');
    exit;
}

// Check if admin has access to this theater's bookings
$stmt = $pdo->prepare("
    SELECT t.theater_id FROM bookings b
    JOIN showtimes s ON b.showtime_id = s.show_id
    JOIN theaters t ON s.theater_id = t.theater_id
    WHERE b.booking_id = ?
");
$stmt->execute([$booking_id]);
$theater_id = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT 1 FROM theater_admins WHERE user_id = ? AND theater_id = ?");
$stmt->execute([$user_id, $theater_id]);
$hasAccess = $stmt->fetchColumn();

// Super admin check
$stmt = $pdo->prepare("SELECT COUNT(*) FROM theater_admins WHERE user_id = ?");
$stmt->execute([$user_id]);
$isSuperAdmin = ($stmt->fetchColumn() == 0);

if (!$isSuperAdmin && !$hasAccess) {
    header('Location: manage_bookings.php');
    exit;
}

// Generate QR code data
$qrData = json_encode([
    'booking_id' => $booking['booking_id'],
    'movie' => $booking['movie_title'],
    'theater' => $booking['theater_name'],
    'date' => $booking['show_date'],
    'time' => $booking['show_time'],
    'seats' => $booking['seats'] ?? '',
    'user' => $booking['user_name']
]);
$qrCodeUrl = 'https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=' . urlencode($qrData);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Ticket - <?= htmlspecialchars($booking['movie_title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
        }
        .ticket-container {
            max-width: 800px;
            margin: 20px auto;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .ticket-header {
            background-color: #343a40;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .ticket-body {
            padding: 20px;
        }
        .ticket-info {
            border-bottom: 1px dashed #dee2e6;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .movie-poster {
            max-height: 200px;
            border-radius: 5px;
        }
        .ticket-footer {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: center;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .qr-code {
            text-align: center;
            margin-top: 20px;
        }
        .seat-info {
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            margin-top: 15px;
        }
        .print-button {
            margin: 20px auto;
            text-align: center;
        }
        @media print {
            .print-button {
                display: none;
            }
            body {
                background-color: white;
            }
            .ticket-container {
                box-shadow: none;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="ticket-header">
            <h2>Movie Ticket</h2>
            <p class="mb-0">Booking ID: <?= htmlspecialchars($booking['booking_id']) ?></p>
        </div>
        
        <div class="ticket-body">
            <div class="row ticket-info">
                <div class="col-md-8">
                    <h3><?= htmlspecialchars($booking['movie_title']) ?></h3>
                    <p><strong>Date:</strong> <?= htmlspecialchars(date('l, F j, Y', strtotime($booking['show_date']))) ?></p>
                    <p><strong>Time:</strong> <?= htmlspecialchars(date('g:i A', strtotime($booking['show_time']))) ?></p>
                    <p><strong>Duration:</strong> <?= htmlspecialchars($booking['duration'] ?? 'N/A') ?> minutes</p>
                    <p><strong>Theater:</strong> <?= htmlspecialchars($booking['theater_name']) ?></p>
                    <p><strong>Location:</strong> <?= htmlspecialchars($booking['theater_location']) ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <?php if (!empty($booking['movie_image'])): ?>
                        <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($booking['movie_image']) ?>" 
                             alt="<?= htmlspecialchars($booking['movie_title']) ?>" 
                             class="img-fluid movie-poster">
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-8">
                    <h4>Customer Information</h4>
                    <p><strong>Name:</strong> <?= htmlspecialchars($booking['user_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($booking['user_email']) ?></p>
                    
                    <div class="seat-info">
                        <h5>Seat Information</h5>
                        <p><strong>Seats:</strong> <?= htmlspecialchars($booking['seats'] ?? 'Not specified') ?></p>
                        <p><strong>Amount Paid:</strong> <?= formatCurrency($booking['total_price']) ?></p>
                        <p>
                            <strong>Status:</strong> 
                            <span class="badge <?= ($booking['payment_status'] === 'completed') ? 'bg-success' : 'bg-warning text-dark' ?>">
                                <?= htmlspecialchars(ucfirst($booking['payment_status'] ?? 'pending')) ?>
                            </span>
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="qr-code">
                        <img src="<?= $qrCodeUrl ?>" alt="Ticket QR Code">
                        <p class="mt-2 small">Scan for verification</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="ticket-footer">
            <p class="mb-0">Please arrive at least 15 minutes before the show. This ticket is non-refundable.</p>
            <p class="mb-0">© <?= date('Y') ?> <?= SITE_NAME ?> - All rights reserved.</p>
        </div>
    </div>
    
    <div class="print-button">
        <button class="btn btn-primary" onclick="window.print()">Print Ticket</button>
        <a href="view_booking.php?id=<?= $booking_id ?>" class="btn btn-secondary">Back</a>
    </div>
</body>
</html>