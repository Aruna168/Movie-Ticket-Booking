<?php
session_start();
require_once 'db_connect.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get payment method statistics
$payment_methods = [];
$stmt = $conn->prepare("SELECT payment_method, COUNT(*) as count FROM payments GROUP BY payment_method");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $payment_methods[$row['payment_method']] = $row['count'];
}
$stmt->close();

// Use payments table for revenue data
$query = "SELECT b.booking_id, u.name as user_name, m.title as movie_title, 
          t.name as theater_name, p.amount as total_price, p.payment_status,
          p.payment_date as booking_date
          FROM bookings b
          JOIN users u ON b.user_id = u.user_id
          JOIN movies m ON b.movie_id = m.movie_id
          JOIN showtimes s ON b.showtime_id = s.show_id
          JOIN theaters t ON s.theater_id = t.theater_id
          JOIN payments p ON b.booking_id = p.booking_id
          WHERE p.amount > 0";

// Execute query
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

// Process results
$bookings = [];
$totalRevenue = 0;
$movieCount = [];
$theaterRevenue = [];

while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
    $totalRevenue += $row['total_price'];
    
    // Movie statistics
    $movieTitle = $row['movie_title'];
    if (!isset($movieCount[$movieTitle])) {
        $movieCount[$movieTitle] = 0;
    }
    $movieCount[$movieTitle]++;
    
    // Theater revenue
    $theaterName = $row['theater_name'];
    if (!isset($theaterRevenue[$theaterName])) {
        $theaterRevenue[$theaterName] = 0;
    }
    $theaterRevenue[$theaterName] += $row['total_price'];
}

// Sort data
arsort($movieCount);
arsort($theaterRevenue);

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color:rgb(41, 39, 39);
            color: #f8f9fa;
        }
        .card {
            background-color: #1e1e1e;
            border: none;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .card-header {
            background-color: rgba(255, 249, 249, 0.78);
            border-bottom: 1px solid #2d2d2d;
        }
        .chart-container { 
            width: 100%; 
            height: 300px;
            margin: 20px 0; 
            background-color: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 10px;
        }
        .table {
            color: #f8f9fa;
        }
        .table-dark {
            background-color: #1e1e1e;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Revenue Reports</h2>
        <a href="admin/index.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Bookings</h5>
                    <p class="card-text display-4"><?= count($bookings) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Revenue</h5>
                    <p class="card-text display-4">₹<?= number_format($totalRevenue, 2) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h5 class="card-title">Avg. Booking Value</h5>
                    <p class="card-text display-4">₹<?= count($bookings) > 0 ? number_format($totalRevenue / count($bookings), 2) : '0.00' ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Bookings by Movie</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="movieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Revenue by Theater</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="theaterChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Methods Chart -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Payment Methods</h5>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="paymentMethodChart"></canvas>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Booking Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-striped">
                    <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>User</th>
                        <th>Movie</th>
                        <th>Theater</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td><?= $b['booking_id'] ?></td>
                            <td><?= htmlspecialchars($b['user_name']) ?></td>
                            <td><?= htmlspecialchars($b['movie_title']) ?></td>
                            <td><?= htmlspecialchars($b['theater_name']) ?></td>
                            <td>₹<?= number_format($b['total_price'], 2) ?></td>
                            <td>
                                <span class="badge <?= ($b['payment_status'] === 'completed') ? 'bg-success' : 'bg-warning text-dark' ?>">
                                    <?= htmlspecialchars(ucfirst($b['payment_status'] ?? 'pending')) ?>
                                </span>
                            </td>
                            <td><?= date('Y-m-d H:i', strtotime($b['booking_date'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No bookings found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Movie Chart
    const movieCtx = document.getElementById('movieChart').getContext('2d');
    new Chart(movieCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys(array_slice($movieCount, 0, 5))) ?>,
            datasets: [{
                label: 'Number of Bookings',
                data: <?= json_encode(array_values(array_slice($movieCount, 0, 5))) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#f8f9fa'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#f8f9fa'
                    }
                }
            }
        }
    });

    // Theater Chart
    const theaterCtx = document.getElementById('theaterChart').getContext('2d');
    new Chart(theaterCtx, {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_keys($theaterRevenue)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($theaterRevenue)) ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    
    // Payment Methods Chart
    const paymentMethodCtx = document.getElementById('paymentMethodChart').getContext('2d');
    new Chart(paymentMethodCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_map('ucfirst', array_keys($payment_methods))) ?>,
            datasets: [{
                data: <?= json_encode(array_values($payment_methods)) ?>,
                backgroundColor: [
                    'rgba(255, 159, 64, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 99, 132, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
</script>
</body>
</html>