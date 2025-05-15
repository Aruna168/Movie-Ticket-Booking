<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

// Process payout action if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'initiate_payout') {
        $theater_id = $_POST['theater_id'];
        $amount = $_POST['amount'];
        $payout_method = $_POST['payout_method'];
        
        // Insert new payout record
        $query = "INSERT INTO theater_payouts (theater_id, amount, status, payout_method, created_at) 
                  VALUES (?, ?, 'Processing', ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ids", $theater_id, $amount, $payout_method);
        $stmt->execute();
        
        // Redirect to avoid form resubmission
        header("Location: manage_theater_payouts.php?success=Payout initiated successfully");
        exit();
    } 
    elseif ($_POST['action'] === 'update_status') {
        $payout_id = $_POST['payout_id'];
        $status = $_POST['status'];
        $transaction_id = $_POST['transaction_id'] ?? null;
        
        // Update payout status
        $query = "UPDATE theater_payouts SET status = ?, transaction_id = ?, payout_date = NOW() WHERE payout_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $status, $transaction_id, $payout_id);
        $stmt->execute();
        
        // Redirect to avoid form resubmission
        header("Location: manage_theater_payouts.php?success=Payout status updated");
        exit();
    }
}

// Get filter parameters
$theater_id = $_GET['theater_id'] ?? '';
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with filters
$query = "SELECT p.*, t.name AS theater_name, t.location, 
          (SELECT SUM(theater_amount) FROM revenue_distribution WHERE theater_id = p.theater_id) AS total_earnings,
          (SELECT SUM(amount) FROM theater_payouts WHERE theater_id = p.theater_id AND status = 'Completed') AS total_paid
          FROM theater_payouts p
          JOIN theaters t ON p.theater_id = t.theater_id
          WHERE 1=1";

$params = [];
$types = "";

if ($theater_id) {
    $query .= " AND p.theater_id = ?";
    $params[] = $theater_id;
    $types .= "i";
}

if ($status) {
    $query .= " AND p.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($date_from) {
    $query .= " AND DATE(p.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to) {
    $query .= " AND DATE(p.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$query .= " ORDER BY p.created_at DESC";

// Execute query with parameters if any
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $payouts_result = $stmt->get_result();
} else {
    $payouts_result = $conn->query($query);
}

// Get theaters for filter dropdown
$theaters_result = $conn->query("SELECT theater_id, name FROM theaters ORDER BY name");

// Get pending payouts summary
$pending_query = "SELECT t.theater_id, t.name, t.location, 
                 SUM(rd.theater_amount) AS total_earnings,
                 COALESCE((SELECT SUM(amount) FROM theater_payouts WHERE theater_id = t.theater_id AND status = 'Completed'), 0) AS total_paid
                 FROM theaters t
                 LEFT JOIN revenue_distribution rd ON t.theater_id = rd.theater_id
                 GROUP BY t.theater_id
                 HAVING total_earnings > total_paid
                 ORDER BY (total_earnings - total_paid) DESC";
$pending_result = $conn->query($pending_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Theater Payouts - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
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
                            <a class="nav-link active" href="manage_theater_payouts.php">
                                Theater Payouts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="payment_settings.php">
                                Payment Settings
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
                    <h1 class="h2">Manage Theater Payouts</h1>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
                <?php endif; ?>

                <!-- Pending Payouts Summary -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Pending Theater Payments</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>Theater</th>
                                        <th>Location</th>
                                        <th>Total Earnings</th>
                                        <th>Total Paid</th>
                                        <th>Balance Due</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $pending_result->fetch_assoc()): ?>
                                        <?php $balance = $row['total_earnings'] - $row['total_paid']; ?>
                                        <?php if ($balance > 0): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['name']) ?></td>
                                                <td><?= htmlspecialchars($row['location']) ?></td>
                                                <td>₹<?= number_format($row['total_earnings'], 2) ?></td>
                                                <td>₹<?= number_format($row['total_paid'], 2) ?></td>
                                                <td>₹<?= number_format($balance, 2) ?></td>
                                                <td>
                                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" 
                                                            data-bs-target="#initiatePayoutModal" 
                                                            data-theater-id="<?= $row['theater_id'] ?>"
                                                            data-theater-name="<?= htmlspecialchars($row['name']) ?>"
                                                            data-amount="<?= $balance ?>">
                                                        Initiate Payout
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endwhile; ?>
                                    <?php if ($pending_result->num_rows === 0): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No pending payments</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Filter Payouts</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
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
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="Pending" <?= ($status == 'Pending') ? 'selected' : '' ?>>Pending</option>
                                    <option value="Processing" <?= ($status == 'Processing') ? 'selected' : '' ?>>Processing</option>
                                    <option value="Completed" <?= ($status == 'Completed') ? 'selected' : '' ?>>Completed</option>
                                    <option value="Failed" <?= ($status == 'Failed') ? 'selected' : '' ?>>Failed</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">Date From</label>
                                <input type="date" name="date_from" id="date_from" class="form-control" value="<?= $date_from ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">Date To</label>
                                <input type="date" name="date_to" id="date_to" class="form-control" value="<?= $date_to ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">Filter</button>
                                <a href="manage_theater_payouts.php" class="btn btn-secondary">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Payouts List -->
                <div class="card">
                    <div class="card-header">
                        <h5>Payout History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Theater</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Transaction ID</th>
                                        <th>Created Date</th>
                                        <th>Payout Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $payouts_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $row['payout_id'] ?></td>
                                            <td><?= htmlspecialchars($row['theater_name']) ?></td>
                                            <td>₹<?= number_format($row['amount'], 2) ?></td>
                                            <td><?= htmlspecialchars($row['payout_method']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= getStatusColor($row['status']) ?>">
                                                    <?= $row['status'] ?>
                                                </span>
                                            </td>
                                            <td><?= $row['transaction_id'] ? htmlspecialchars($row['transaction_id']) : '-' ?></td>
                                            <td><?= date('Y-m-d', strtotime($row['created_at'])) ?></td>
                                            <td><?= $row['payout_date'] ? date('Y-m-d', strtotime($row['payout_date'])) : '-' ?></td>
                                            <td>
                                                <?php if ($row['status'] !== 'Completed' && $row['status'] !== 'Failed'): ?>
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                                                            data-bs-target="#updateStatusModal" 
                                                            data-payout-id="<?= $row['payout_id'] ?>"
                                                            data-theater-name="<?= htmlspecialchars($row['theater_name']) ?>"
                                                            data-amount="<?= $row['amount'] ?>">
                                                        Update Status
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php if ($payouts_result->num_rows === 0): ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No payouts found</td>
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

    <!-- Initiate Payout Modal -->
    <div class="modal fade" id="initiatePayoutModal" tabindex="-1" aria-labelledby="initiatePayoutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="initiatePayoutModalLabel">Initiate Payout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="initiate_payout">
                        <input type="hidden" name="theater_id" id="modal_theater_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Theater</label>
                            <input type="text" class="form-control" id="modal_theater_name" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount (₹)</label>
                            <input type="number" step="0.01" class="form-control" id="modal_amount" name="amount" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="payout_method" class="form-label">Payout Method</label>
                            <select class="form-select" id="payout_method" name="payout_method" required>
                                <option value="">Select Method</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="UPI">UPI</option>
                                <option value="Google Pay">Google Pay</option>
                                <option value="PayPal">PayPal</option>
                                <option value="Check">Check</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Initiate Payout</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Update Payout Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="payout_id" id="modal_payout_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Theater</label>
                            <input type="text" class="form-control" id="modal_update_theater_name" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="text" class="form-control" id="modal_update_amount" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="update_status" name="status" required>
                                <option value="Processing">Processing</option>
                                <option value="Completed">Completed</option>
                                <option value="Failed">Failed</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="transaction_id" class="form-label">Transaction ID</label>
                            <input type="text" class="form-control" id="transaction_id" name="transaction_id">
                            <small class="text-muted">Required for Completed status</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize modals with data
        document.addEventListener('DOMContentLoaded', function() {
            // Initiate Payout Modal
            const initiatePayoutModal = document.getElementById('initiatePayoutModal');
            if (initiatePayoutModal) {
                initiatePayoutModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const theaterId = button.getAttribute('data-theater-id');
                    const theaterName = button.getAttribute('data-theater-name');
                    const amount = button.getAttribute('data-amount');
                    
                    document.getElementById('modal_theater_id').value = theaterId;
                    document.getElementById('modal_theater_name').value = theaterName;
                    document.getElementById('modal_amount').value = amount;
                });
            }
            
            // Update Status Modal
            const updateStatusModal = document.getElementById('updateStatusModal');
            if (updateStatusModal) {
                updateStatusModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const payoutId = button.getAttribute('data-payout-id');
                    const theaterName = button.getAttribute('data-theater-name');
                    const amount = button.getAttribute('data-amount');
                    
                    document.getElementById('modal_payout_id').value = payoutId;
                    document.getElementById('modal_update_theater_name').value = theaterName;
                    document.getElementById('modal_update_amount').value = '₹' + parseFloat(amount).toFixed(2);
                });
            }
            
            // Validate transaction ID is required for Completed status
            const statusSelect = document.getElementById('update_status');
            if (statusSelect) {
                statusSelect.addEventListener('change', function() {
                    const transactionIdField = document.getElementById('transaction_id');
                    if (this.value === 'Completed') {
                        transactionIdField.setAttribute('required', 'required');
                    } else {
                        transactionIdField.removeAttribute('required');
                    }
                });
            }
        });
    </script>
</body>
</html>

<?php
// Helper function to get status color
function getStatusColor($status) {
    switch ($status) {
        case 'Pending':
            return 'warning';
        case 'Processing':
            return 'info';
        case 'Completed':
            return 'success';
        case 'Failed':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>