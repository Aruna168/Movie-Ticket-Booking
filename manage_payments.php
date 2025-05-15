<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

$status_filter = $_GET['status'] ?? '';

$query = "SELECT p.*, u.name AS user_name, b.booking_id, m.title AS movie_title
          FROM payments p
          JOIN bookings b ON p.booking_id = b.booking_id
          JOIN users u ON p.user_id = u.user_id
          JOIN showtimes s ON b.show_id = s.show_id
          JOIN movies m ON s.movie_id = m.movie_id
          WHERE 1";

if ($status_filter) {
    $query .= " AND p.payment_status = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $status_filter);
} else {
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Payments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h2 class="mb-4">Payment Management</h2>

    <form method="GET" class="mb-3 row g-2">
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <option value="Pending" <?= ($status_filter == 'Pending') ? 'selected' : '' ?>>Pending</option>
                <option value="Received" <?= ($status_filter == 'Received') ? 'selected' : '' ?>>Received</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary" type="submit">Filter</button>
        </div>
    </form>

    <table class="table table-bordered table-hover bg-white">
        <thead class="table-light">
            <tr>
                <th>Payment ID</th>
                <th>User</th>
                <th>Booking ID</th>
                <th>Movie</th>
                <th>Method</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Paid On</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()) : ?>
            <tr>
                <td><?= $row['payment_id'] ?></td>
                <td><?= $row['user_name'] ?></td>
                <td><?= $row['booking_id'] ?></td>
                <td><?= $row['movie_title'] ?></td>
                <td><?= $row['payment_method'] ?></td>
                <td>₹<?= number_format($row['amount'], 2) ?></td>
                <td>
                    <span class="badge bg-<?= $row['payment_status'] == 'Received' ? 'success' : 'warning' ?>">
                        <?= $row['payment_status'] ?>
                    </span>
                </td>
                <td><?= $row['payment_date'] ?></td>
                <td>
                    <?php if ($row['payment_status'] == 'Pending'): ?>
                        <form method="POST" action="update_payment_status.php" class="d-inline">
                            <input type="hidden" name="payment_id" value="<?= $row['payment_id'] ?>">
                            <input type="hidden" name="action" value="receive">
                            <button type="submit" class="btn btn-success btn-sm">Mark Received</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="update_payment_status.php" class="d-inline">
                            <input type="hidden" name="payment_id" value="<?= $row['payment_id'] ?>">
                            <input type="hidden" name="action" value="resend">
                            <button type="submit" class="btn btn-outline-primary btn-sm">Resend</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
