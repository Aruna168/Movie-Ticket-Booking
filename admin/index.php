<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Get upcoming showtimes
$stmt = $conn->prepare("
    SELECT s.*, m.title as movie_title, t.name as theater_name
    FROM showtimes s
    JOIN movies m ON s.movie_id = m.movie_id
    JOIN theaters t ON s.theater_id = t.theater_id
    WHERE s.show_date >= CURDATE()
    ORDER BY s.show_date, s.show_time
    LIMIT 10
");
$stmt->execute();
$result = $stmt->get_result();
$upcoming_showtimes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
            <h2>Admin Dashboard</h2>
            <a href="../logout.php" class="btn btn-danger">Logout</a>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Upcoming Showtimes</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcoming_showtimes)): ?>
                            <div class="alert alert-info">No upcoming showtimes found.</div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($upcoming_showtimes as $showtime): ?>
                                    <a href="live_booking_status.php?showtime_id=<?php echo $showtime['show_id']; ?>" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($showtime['movie_title']); ?></h5>
                                            <small>
                                                <?php echo htmlspecialchars(date('M d, Y', strtotime($showtime['show_date']))); ?>
                                            </small>
                                        </div>
                                        <p class="mb-1">
                                            <?php echo htmlspecialchars($showtime['theater_name']); ?> at 
                                            <?php echo htmlspecialchars(date('g:i A', strtotime($showtime['show_time']))); ?>
                                        </p>
                                        <small class="text-muted">Click to view live booking status</small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <a href="manage_movies.php" class="btn btn-primary w-100">
                                    <i class="fas fa-film"></i> Manage Movies
                                </a>
                            </div>
                            <div class="col-6 mb-3">
                                <a href="manage_theaters.php" class="btn btn-success w-100">
                                    <i class="fas fa-building"></i> Manage Theaters
                                </a>
                            </div>
                            <div class="col-6 mb-3">
                                <a href="manage_showtimes.php" class="btn btn-info w-100">
                                    <i class="fas fa-clock"></i> Manage Showtimes
                                </a>
                            </div>
                            <div class="col-6 mb-3">
                                <a href="manage_bookings.php" class="btn btn-warning w-100">
                                    <i class="fas fa-ticket-alt"></i> Manage Bookings
                                </a>
                            </div>
                            <div class="col-6 mb-3">
                                <a href="manage_payments.php" class="btn btn-danger w-100">
                                    <i class="fas fa-money-bill"></i> Manage Payments
                                </a>
                            </div>
                            <div class="col-6 mb-3">
                                <a href="manage_pricing.php" class="btn btn-secondary w-100">
                                    <i class="fas fa-tags"></i> Manage Pricing
                                </a>
                            </div>
                            <div class="col-6 mb-3">
                                <a href="theater_payment_qr.php" class="btn btn-primary w-100">
                                    <i class="fas fa-qrcode"></i> Payment QR Codes
                                </a>
                            </div>
                            <div class="col-6 mb-3">
                                <a href="../reports.php" class="btn btn-success w-100">
                                    <i class="fas fa-chart-bar"></i> Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>