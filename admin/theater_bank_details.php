<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

$theater_id = $_GET['id'] ?? 0;

// Validate theater exists
$stmt = $conn->prepare("SELECT theater_id, name, location FROM theaters WHERE theater_id = ?");
$stmt->bind_param("i", $theater_id);
$stmt->execute();
$result = $stmt->get_result();
$theater = $result->fetch_assoc();

if (!$theater) {
    header("Location: ../manage_theaters.php");
    exit();
}

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_name = $_POST['account_name'];
    $account_number = $_POST['account_number'];
    $bank_name = $_POST['bank_name'];
    $ifsc_code = $_POST['ifsc_code'];
    $upi_id = $_POST['upi_id'] ?? null;
    $gstin = $_POST['gstin'] ?? null;
    $pan = $_POST['pan'] ?? null;
    
    // Check if bank details already exist
    $stmt = $conn->prepare("SELECT detail_id FROM theater_bank_details WHERE theater_id = ?");
    $stmt->bind_param("i", $theater_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE theater_bank_details SET 
                               account_name = ?, account_number = ?, bank_name = ?, 
                               ifsc_code = ?, upi_id = ?, gstin = ?, pan = ? 
                               WHERE theater_id = ?");
        $stmt->bind_param("sssssssi", $account_name, $account_number, $bank_name, 
                         $ifsc_code, $upi_id, $gstin, $pan, $theater_id);
        
        if ($stmt->execute()) {
            $success_message = "Bank details updated successfully!";
        } else {
            $error_message = "Error updating bank details: " . $conn->error;
        }
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO theater_bank_details 
                              (theater_id, account_name, account_number, bank_name, ifsc_code, upi_id, gstin, pan) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $theater_id, $account_name, $account_number, $bank_name, 
                         $ifsc_code, $upi_id, $gstin, $pan);
        
        if ($stmt->execute()) {
            $success_message = "Bank details added successfully!";
        } else {
            $error_message = "Error adding bank details: " . $conn->error;
        }
    }
}

// Get existing bank details if any
$stmt = $conn->prepare("SELECT * FROM theater_bank_details WHERE theater_id = ?");
$stmt->bind_param("i", $theater_id);
$stmt->execute();
$result = $stmt->get_result();
$bank_details = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theater Bank Details - Admin</title>
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
                            <a class="nav-link" href="manage_theater_payouts.php">
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
                    <h1 class="h2">Theater Bank Details</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="../manage_theaters.php" class="btn btn-sm btn-outline-secondary">
                            Back to Theaters
                        </a>
                    </div>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?= $success_message ?></div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?= $error_message ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5><?= htmlspecialchars($theater['name']) ?> - <?= htmlspecialchars($theater['location']) ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="account_name" class="form-label">Account Holder Name</label>
                                    <input type="text" class="form-control" id="account_name" name="account_name" 
                                           value="<?= $bank_details['account_name'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="account_number" class="form-label">Account Number</label>
                                    <input type="text" class="form-control" id="account_number" name="account_number" 
                                           value="<?= $bank_details['account_number'] ?? '' ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="bank_name" class="form-label">Bank Name</label>
                                    <input type="text" class="form-control" id="bank_name" name="bank_name" 
                                           value="<?= $bank_details['bank_name'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="ifsc_code" class="form-label">IFSC Code</label>
                                    <input type="text" class="form-control" id="ifsc_code" name="ifsc_code" 
                                           value="<?= $bank_details['ifsc_code'] ?? '' ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="upi_id" class="form-label">UPI ID</label>
                                    <input type="text" class="form-control" id="upi_id" name="upi_id" 
                                           value="<?= $bank_details['upi_id'] ?? '' ?>">
                                    <small class="text-muted">Optional - For UPI payments</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="gstin" class="form-label">GSTIN</label>
                                    <input type="text" class="form-control" id="gstin" name="gstin" 
                                           value="<?= $bank_details['gstin'] ?? '' ?>">
                                    <small class="text-muted">Optional - For tax purposes</small>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="pan" class="form-label">PAN Number</label>
                                    <input type="text" class="form-control" id="pan" name="pan" 
                                           value="<?= $bank_details['pan'] ?? '' ?>">
                                    <small class="text-muted">Optional - For tax purposes</small>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Save Bank Details</button>
                                <a href="../manage_theaters.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>Payment Information</h5>
                    </div>
                    <div class="card-body">
                        <p>This information is used to process payments to the theater for ticket sales. Please ensure all details are accurate to avoid payment delays.</p>
                        
                        <h6 class="mt-3">Payment Schedule</h6>
                        <p>Payments to theaters are processed on the following schedule:</p>
                        <ul>
                            <li>Weekly payments for theaters with high volume (>100 tickets/week)</li>
                            <li>Bi-weekly payments for theaters with medium volume (50-100 tickets/week)</li>
                            <li>Monthly payments for theaters with low volume (<50 tickets/week)</li>
                        </ul>
                        
                        <h6 class="mt-3">Revenue Share</h6>
                        <p>The current revenue share for <?= htmlspecialchars($theater['name']) ?> is:</p>
                        
                        <?php
                        // Get theater's revenue share
                        $stmt = $conn->prepare("SELECT revenue_share FROM theaters WHERE theater_id = ?");
                        $stmt->bind_param("i", $theater_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $revenue_data = $result->fetch_assoc();
                        $revenue_share = $revenue_data['revenue_share'] ?? 70;
                        ?>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h3><?= $revenue_share ?>%</h3>
                                        <p class="mb-0">Theater Share</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h3>20%</h3>
                                        <p class="mb-0">Distributor Share</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h3>10%</h3>
                                        <p class="mb-0">Platform Fee</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <p class="mt-3">
                            <a href="../manage_theaters.php?action=edit&id=<?= $theater_id ?>" class="btn btn-sm btn-outline-primary">
                                Adjust Revenue Share
                            </a>
                        </p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>