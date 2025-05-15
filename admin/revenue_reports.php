<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // Default to first day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Default to today
$theater_id = $_GET['theater_id'] ?? '';

// Get theaters for filter dropdown
$theaters_result = $conn->query("SELECT theater_id, name FROM theaters ORDER BY name");

// Build query for revenue summary
$summary_query = "SELECT 
                  SUM(rd.theater_amount) AS total_theater_amount,
                  SUM(rd.distributor_amount) AS total_distributor_amount,
                  SUM(rd.platform_amount) AS total_platform_amount,
                  SUM(rd.tax_amount) AS total_tax_amount,
                  COUNT(DISTINCT p.payment_id) AS total_transactions,
                  SUM(p.amount) AS total_revenue
                  FROM revenue_distribution rd
                  JOIN payments p ON rd.payment_id = p.payment_id
                  WHERE p.payment_status = 'Paid Successfully'
                  AND DATE(p.payment_date) BETWEEN ? AND ?";

$params = [$date_from, $date_to];
$types = "ss";

if ($theater_id) {
    $summary_query .= " AND rd.theater_id = ?";
    $params[] = $theater_id;
    $types .= "i";
}

$stmt = $conn->prepare($summary_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$summary_result = $stmt->get_result();
$summary = $summary_result->fetch_assoc();

// Build query for theater-wise revenue
$theater_query = "SELECT 
                 t.theater_id, t.name, t.location,
                 COUNT(DISTINCT p.payment_id) AS transactions,
                 SUM(p.amount) AS total_amount,
                 SUM(rd.theater_amount) AS theater_amount,
                 SUM(rd.platform_amount) AS platform_amount
                 FROM theaters t
                 LEFT JOIN revenue_distribution rd ON t.theater_id = rd.theater_id
                 LEFT JOIN payments p ON rd.payment_id = p.payment_id AND p.payment_status = 'Paid Successfully'
                 AND DATE(p.payment_date) BETWEEN ? AND ?
                 GROUP BY t.theater_id
                 ORDER BY total_amount DESC";

$stmt = $conn->prepare($theater_query);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$theater_result = $stmt->get_result();

// Build query for daily revenue chart
$daily_query = "SELECT 
               DATE(p.payment_date) AS date,
               SUM(p.amount) AS daily_revenue,
               SUM(rd.theater_amount) AS theater_amount,
               SUM(rd.platform_amount) AS platform_amount
               FROM payments p
               JOIN revenue_distribution rd ON p.payment_id = rd.payment_id
               WHERE p.payment_status = 'Paid Successfully'
               AND DATE(p.payment_date) BETWEEN ? AND ?";

if ($theater_id) {
    $daily_query .= " AND rd.theater_id = ?";
    $params = [$date_from, $date_to, $theater_id];
    $types = "ssi";
} else {
    $params = [$date_from, $date_to];
    $types = "ss";
}

$daily_query .= " GROUP BY DATE(p.payment_date) ORDER BY date";

$stmt = $conn->prepare($daily_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$daily_result = $stmt->get_result();

// Prepare data for chart
$dates = [];
$revenues = [];
$theater_amounts = [];
$platform_amounts = [];

while ($row = $daily_result->fetch_assoc()) {
    $dates[] = date('M d', strtotime($row['date']));
    $revenues[] = $row['daily_revenue'];
    $theater_amounts[] = $row['theater_amount'];
    $platform_amounts[] = $row['platform_amount'];
}

$chart_dates = json_encode($dates);
$chart_revenues = json_encode($revenues);
$chart_theater = json_encode($theater_amounts);
$chart_platform = json_encode($platform_amounts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Reports - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../admin_dashboard.php">
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../manage_movies.php">
                                Manage Movies
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../manage_theaters.php">
                                Manage Theaters
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../manage_payments.php">
                                Manage Payments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_theater_payouts.php">
                                Theater Payouts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="revenue_reports.php">
                                Revenue Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Revenue Reports</h1>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Filter Reports</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="date_from" class="form-label">Date From</label>
                                <input type="date" name="date_from" id="date_from" class="form-control" value="<?= $date_from ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="date_to" class="form-label">Date To</label>
                                <input type="date" name="date_to" id="date_to" class="form-control" value="<?= $date_to ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="theater_id" class="form-label">Theater</label>
                                <select name="theater_id" id="theater_id" class="form-select">
                                    <option value="">All Theaters</option>
                                    <?php while ($theater = $theaters_result->fetch_assoc()): ?>
                                        <option value="<?= $theater['theater_id'] ?>" <?= ($theater_id == $theater['theater_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($theater['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">Apply</button>
                                <a href="revenue_reports.php" class="btn btn-secondary">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Revenue Summary -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Revenue</h5>
                                <h2 class="card-text">₹<?= number_format($summary['total_revenue'] ?? 0, 2) ?></h2>
                                <p class="card-text"><?= $summary['total_transactions'] ?? 0 ?> transactions</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Theater Share</h5>
                                <h2 class="card-text">₹<?= number_format($summary['total_theater_amount'] ?? 0, 2) ?></h2>
                                <p class="card-text"><?= ($summary['total_revenue'] > 0) ? round(($summary['total_theater_amount'] / $summary['total_revenue']) * 100, 1) : 0 ?>% of revenue</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Platform Revenue</h5>
                                <h2 class="card-text">₹<?= number_format($summary['total_platform_amount'] ?? 0, 2) ?></h2>
                                <p class="card-text"><?= ($summary['total_revenue'] > 0) ? round(($summary['total_platform_amount'] / $summary['total_revenue']) * 100, 1) : 0 ?>% of revenue</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <h5 class="card-title">Distributor Share</h5>
                                <h2 class="card-text">₹<?= number_format($summary['total_distributor_amount'] ?? 0, 2) ?></h2>
                                <p class="card-text"><?= ($summary['total_revenue'] > 0) ? round(($summary['total_distributor_amount'] / $summary['total_revenue']) * 100, 1) : 0 ?>% of revenue</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Chart -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Daily Revenue Trend</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart" height="100"></canvas>
                    </div>
                </div>

                <!-- Theater-wise Revenue -->
                <div class="card">
                    <div class="card-header">
                        <h5>Theater-wise Revenue</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Theater</th>
                                        <th>Location</th>
                                        <th>Transactions</th>
                                        <th>Total Revenue</th>
                                        <th>Theater Share</th>
                                        <th>Platform Share</th>
                                        <th>Share %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $theater_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['name']) ?></td>
                                            <td><?= htmlspecialchars($row['location']) ?></td>
                                            <td><?= $row['transactions'] ?? 0 ?></td>
                                            <td>₹<?= number_format($row['total_amount'] ?? 0, 2) ?></td>
                                            <td>₹<?= number_format($row['theater_amount'] ?? 0, 2) ?></td>
                                            <td>₹<?= number_format($row['platform_amount'] ?? 0, 2) ?></td>
                                            <td>
                                                <?php if ($row['total_amount'] > 0): ?>
                                                    <?= round(($row['theater_amount'] / $row['total_amount']) * 100, 1) ?>%
                                                <?php else: ?>
                                                    0%
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php if ($theater_result->num_rows === 0): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No revenue data found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize revenue chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            
            const revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= $chart_dates ?>,
                    datasets: [
                        {
                            label: 'Total Revenue',
                            data: <?= $chart_revenues ?>,
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 2,
                            tension: 0.1
                        },
                        {
                            label: 'Theater Share',
                            data: <?= $chart_theater ?>,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 2,
                            tension: 0.1
                        },
                        {
                            label: 'Platform Share',
                            data: <?= $chart_platform ?>,
                            backgroundColor: 'rgba(255, 159, 64, 0.2)',
                            borderColor: 'rgba(255, 159, 64, 1)',
                            borderWidth: 2,
                            tension: 0.1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₹' + value;
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ₹' + context.raw;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>