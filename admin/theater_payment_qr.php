<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Create theater_payment_qr table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS theater_payment_qr (
    id INT AUTO_INCREMENT PRIMARY KEY,
    theater_id INT NOT NULL,
    qr_image VARCHAR(255) NOT NULL,
    payment_instructions TEXT,
    upi_id VARCHAR(100),
    bank_name VARCHAR(100),
    account_number VARCHAR(50),
    ifsc_code VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (theater_id)
)");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theater_id = $_POST['theater_id'];
    $payment_instructions = $_POST['payment_instructions'];
    $upi_id = $_POST['upi_id'];
    $bank_name = $_POST['bank_name'];
    $account_number = $_POST['account_number'];
    $ifsc_code = $_POST['ifsc_code'];
    
    // Handle QR code image upload
    $qr_image = '';
    if (isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] === 0) {
        $upload_dir = '../uploads/qr_codes/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . $_FILES['qr_image']['name'];
        $upload_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['qr_image']['tmp_name'], $upload_path)) {
            $qr_image = 'uploads/qr_codes/' . $file_name;
        }
    }
    
    // Check if QR already exists for this theater
    $stmt = $conn->prepare("SELECT id FROM theater_payment_qr WHERE theater_id = ?");
    $stmt->bind_param("i", $theater_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing QR
        if (!empty($qr_image)) {
            $stmt = $conn->prepare("UPDATE theater_payment_qr SET qr_image = ?, payment_instructions = ?, upi_id = ?, bank_name = ?, account_number = ?, ifsc_code = ? WHERE theater_id = ?");
            $stmt->bind_param("ssssssi", $qr_image, $payment_instructions, $upi_id, $bank_name, $account_number, $ifsc_code, $theater_id);
        } else {
            $stmt = $conn->prepare("UPDATE theater_payment_qr SET payment_instructions = ?, upi_id = ?, bank_name = ?, account_number = ?, ifsc_code = ? WHERE theater_id = ?");
            $stmt->bind_param("sssssi", $payment_instructions, $upi_id, $bank_name, $account_number, $ifsc_code, $theater_id);
        }
    } else {
        // Insert new QR
        if (!empty($qr_image)) {
            $stmt = $conn->prepare("INSERT INTO theater_payment_qr (theater_id, qr_image, payment_instructions, upi_id, bank_name, account_number, ifsc_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssss", $theater_id, $qr_image, $payment_instructions, $upi_id, $bank_name, $account_number, $ifsc_code);
        } else {
            $message = "QR code image is required";
        }
    }
    
    if (isset($stmt) && $stmt->execute()) {
        $message = "Payment QR code updated successfully!";
    } else {
        $message = "Error updating payment QR code: " . $conn->error;
    }
}

// Get theaters
$theaters = $conn->query("SELECT theater_id, name FROM theaters ORDER BY name");

// Get existing QR codes
$qr_codes = [];
$result = $conn->query("SELECT * FROM theater_payment_qr");
while ($row = $result->fetch_assoc()) {
    $qr_codes[$row['theater_id']] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theater Payment QR Codes - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #121212; color: #f8f9fa; }
        .card { background-color: #1e1e1e; border: none; border-radius: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Theater Payment QR Codes</h2>
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-info"><?= $message ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Upload Payment QR Code</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="theater_id" class="form-label">Select Theater</label>
                                <select class="form-select bg-dark text-light" id="theater_id" name="theater_id" required>
                                    <option value="">-- Select Theater --</option>
                                    <?php while ($theater = $theaters->fetch_assoc()): ?>
                                        <option value="<?= $theater['theater_id'] ?>"><?= htmlspecialchars($theater['name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="qr_image" class="form-label">QR Code Image</label>
                                <input type="file" class="form-control bg-dark text-light" id="qr_image" name="qr_image" accept="image/*">
                                <small class="text-muted">Upload a QR code image for payments</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="upi_id" class="form-label">UPI ID</label>
                                <input type="text" class="form-control bg-dark text-light" id="upi_id" name="upi_id" placeholder="yourname@upi">
                            </div>
                            
                            <div class="mb-3">
                                <label for="bank_name" class="form-label">Bank Name</label>
                                <input type="text" class="form-control bg-dark text-light" id="bank_name" name="bank_name">
                            </div>
                            
                            <div class="mb-3">
                                <label for="account_number" class="form-label">Account Number</label>
                                <input type="text" class="form-control bg-dark text-light" id="account_number" name="account_number">
                            </div>
                            
                            <div class="mb-3">
                                <label for="ifsc_code" class="form-label">IFSC Code</label>
                                <input type="text" class="form-control bg-dark text-light" id="ifsc_code" name="ifsc_code">
                            </div>
                            
                            <div class="mb-3">
                                <label for="payment_instructions" class="form-label">Payment Instructions</label>
                                <textarea class="form-control bg-dark text-light" id="payment_instructions" name="payment_instructions" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Save QR Code</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Current QR Codes</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($qr_codes)): ?>
                            <p class="text-center">No QR codes uploaded yet.</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($qr_codes as $qr): 
                                    $theater_name = '';
                                    $theaters->data_seek(0);
                                    while ($t = $theaters->fetch_assoc()) {
                                        if ($t['theater_id'] == $qr['theater_id']) {
                                            $theater_name = $t['name'];
                                            break;
                                        }
                                    }
                                ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card bg-dark">
                                            <div class="card-header">
                                                <h6><?= htmlspecialchars($theater_name) ?></h6>
                                            </div>
                                            <div class="card-body text-center">
                                                <?php if (!empty($qr['qr_image'])): ?>
                                                    <img src="../<?= $qr['qr_image'] ?>" alt="Payment QR" class="img-fluid mb-2" style="max-height: 150px;">
                                                <?php endif; ?>
                                                <p class="small mb-1"><?= htmlspecialchars($qr['upi_id']) ?></p>
                                                <p class="small"><?= htmlspecialchars($qr['payment_instructions']) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>