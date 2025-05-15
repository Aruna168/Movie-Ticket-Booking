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
           t.name as theater_name,
           t.location as theater_location,
           s.show_date, 
           s.show_time,
           p.payment_id,
           p.payment_method,
           p.payment_status,
           p.amount as payment_amount,
           p.transaction_id,
           p.payment_date
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
    SELECT 1 FROM theater_admins 
    WHERE user_id = ? AND theater_id = (
        SELECT t.theater_id FROM bookings b
        JOIN showtimes s ON b.showtime_id = s.show_id
        JOIN theaters t ON s.theater_id = t.theater_id
        WHERE b.booking_id = ?
    )
");
$stmt->execute([$user_id, $booking_id]);
$hasAccess = $stmt->fetchColumn();

// Super admin check
$stmt = $pdo->prepare("SELECT COUNT(*) FROM theater_admins WHERE user_id = ?");
$stmt->execute([$user_id]);
$isSuperAdmin = ($stmt->fetchColumn() == 0);

if (!$isSuperAdmin && !$hasAccess) {
    header('Location: manage_bookings.php');
    exit;
}

include_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Booking Details</h2>
        <a href="manage_bookings.php" class="btn btn-secondary">Back to Bookings</a>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Booking #<?= htmlspecialchars($booking['booking_id']) ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Customer Information</h5>
                    <p><strong>Name:</strong> <?= htmlspecialchars($booking['user_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($booking['user_email']) ?></p>
                    <p><strong>Booking Date:</strong> <?= htmlspecialchars(date('Y-m-d H:i', strtotime($booking['booking_date'] ?? $booking['created_at']))) ?></p>
                </div>
                <div class="col-md-6">
                    <h5>Movie Information</h5>
                    <div class="d-flex">
                        <?php if (!empty($booking['movie_image'])): ?>
                            <div class="me-3">
                                <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($booking['movie_image']) ?>" 
                                     alt="<?= htmlspecialchars($booking['movie_title']) ?>" 
                                     class="img-thumbnail" style="width: 100px;">
                            </div>
                        <?php endif; ?>
                        <div>
                            <p><strong>Movie:</strong> <?= htmlspecialchars($booking['movie_title']) ?></p>
                            <p><strong>Theater:</strong> <?= htmlspecialchars($booking['theater_name']) ?></p>
                            <p><strong>Location:</strong> <?= htmlspecialchars($booking['theater_location']) ?></p>
                            <p><strong>Show Date:</strong> <?= htmlspecialchars(date('Y-m-d', strtotime($booking['show_date']))) ?></p>
                            <p><strong>Show Time:</strong> <?= htmlspecialchars(date('H:i', strtotime($booking['show_time']))) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <hr>
            
            <div class="row">
                <div class="col-md-6">
                    <h5>Booking Details</h5>
                    <p><strong>Seats:</strong> <?= htmlspecialchars($booking['seats'] ?? 'Not specified') ?></p>
                    <p><strong>Total Amount:</strong> <?= formatCurrency($booking['total_price']) ?></p>
                </div>
                <div class="col-md-6">
                    <h5>Payment Information</h5>
                    <p>
                        <strong>Status:</strong> 
                        <span class="badge <?= ($booking['payment_status'] === 'completed') ? 'bg-success' : 'bg-warning' ?>">
                            <?= htmlspecialchars(ucfirst($booking['payment_status'] ?? 'pending')) ?>
                        </span>
                    </p>
                    <?php if (!empty($booking['payment_method'])): ?>
                        <p><strong>Method:</strong> <?= htmlspecialchars(ucfirst($booking['payment_method'])) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($booking['payment_amount'])): ?>
                        <p><strong>Amount Paid:</strong> <?= formatCurrency($booking['payment_amount']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($booking['transaction_id'])): ?>
                        <p><strong>Transaction ID:</strong> <?= htmlspecialchars($booking['transaction_id']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($booking['payment_date'])): ?>
                        <p><strong>Payment Date:</strong> <?= htmlspecialchars(date('Y-m-d H:i', strtotime($booking['payment_date']))) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($booking['payment_status'] !== 'completed'): ?>
                <div class="mt-4">
                    <h5>Actions</h5>
                    <form action="update_payment_status.php" method="post" class="d-inline">
                        <input type="hidden" name="booking_id" value="<?= $booking['booking_id'] ?>">
                        <input type="hidden" name="status" value="completed">
                        <button type="submit" class="btn btn-success" onclick="return confirm('Mark this payment as completed?')">
                            Mark as Paid
                        </button>
                    </form>
                    
                    <form action="cancel_booking.php" method="post" class="d-inline ms-2">
                        <input type="hidden" name="booking_id" value="<?= $booking['booking_id'] ?>">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this booking?')">
                            Cancel Booking
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Ticket Preview</h5>
        </div>
        <div class="card-body">
            <div class="ticket-container p-4 border rounded">
                <div class="row">
                    <div class="col-md-8">
                        <h4><?= htmlspecialchars($booking['movie_title']) ?></h4>
                        <p class="mb-1"><strong>Date:</strong> <?= htmlspecialchars(date('F j, Y', strtotime($booking['show_date']))) ?></p>
                        <p class="mb-1"><strong>Time:</strong> <?= htmlspecialchars(date('g:i A', strtotime($booking['show_time']))) ?></p>
                        <p class="mb-1"><strong>Theater:</strong> <?= htmlspecialchars($booking['theater_name']) ?></p>
                        <p class="mb-1"><strong>Seats:</strong> <?= htmlspecialchars($booking['seats'] ?? 'Not specified') ?></p>
                        <p class="mb-3"><strong>Booking ID:</strong> <?= htmlspecialchars($booking['booking_id']) ?></p>
                        
                        <div class="mt-3">
                            <a href="print_ticket.php?id=<?= $booking['booking_id'] ?>" class="btn btn-primary" target="_blank">
                                <i class="fas fa-print"></i> Print Ticket
                            </a>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <?php if (!empty($booking['movie_image'])): ?>
                            <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($booking['movie_image']) ?>" 
                                 alt="<?= htmlspecialchars($booking['movie_title']) ?>" 
                                 class="img-fluid rounded" style="max-height: 200px;">
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>