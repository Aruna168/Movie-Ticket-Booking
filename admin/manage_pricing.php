<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$success_message = '';
$error_message = '';

// Create movie_pricing table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS movie_pricing (
    pricing_id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT NOT NULL,
    standard_price DECIMAL(10,2) NOT NULL DEFAULT 150.00,
    premium_price DECIMAL(10,2) NOT NULL DEFAULT 250.00,
    vip_price DECIMAL(10,2) NOT NULL DEFAULT 350.00,
    convenience_fee DECIMAL(10,2) NOT NULL DEFAULT 20.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (movie_id)
)");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_pricing'])) {
        $movie_id = $_POST['movie_id'];
        $standard_price = $_POST['standard_price'];
        $premium_price = $_POST['premium_price'];
        $vip_price = $_POST['vip_price'];
        $convenience_fee = $_POST['convenience_fee'];
        
        try {
            // Check if pricing already exists for this movie
            $stmt = $conn->prepare("SELECT * FROM movie_pricing WHERE movie_id = ?");
            $stmt->bind_param("i", $movie_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update existing pricing
                $stmt = $conn->prepare("UPDATE movie_pricing SET standard_price = ?, premium_price = ?, vip_price = ?, convenience_fee = ? WHERE movie_id = ?");
                $stmt->bind_param("ddddi", $standard_price, $premium_price, $vip_price, $convenience_fee, $movie_id);
            } else {
                // Insert new pricing
                $stmt = $conn->prepare("INSERT INTO movie_pricing (movie_id, standard_price, premium_price, vip_price, convenience_fee) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("idddd", $movie_id, $standard_price, $premium_price, $vip_price, $convenience_fee);
            }
            
            if ($stmt->execute()) {
                $success_message = "Pricing updated successfully!";
            } else {
                $error_message = "Error updating pricing: " . $conn->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Get all movies
$movies = $conn->query("SELECT movie_id, title FROM movies ORDER BY title");

// Get pricing for all movies
$pricing = [];
$result = $conn->query("SELECT * FROM movie_pricing");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pricing[$row['movie_id']] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Pricing - Admin</title>
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
            <h2>Manage Pricing</h2>
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Set Movie Pricing</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="movie_id" class="form-label">Select Movie</label>
                                <select class="form-select bg-dark text-light" id="movie_id" name="movie_id" required>
                                    <option value="">-- Select Movie --</option>
                                    <?php if ($movies): while ($movie = $movies->fetch_assoc()): ?>
                                        <option value="<?php echo $movie['movie_id']; ?>"><?php echo htmlspecialchars($movie['title']); ?></option>
                                    <?php endwhile; endif; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="standard_price" class="form-label">Standard Seat Price (₹)</label>
                                <input type="number" class="form-control bg-dark text-light" id="standard_price" name="standard_price" min="0" step="0.01" value="150.00" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="premium_price" class="form-label">Premium Seat Price (₹)</label>
                                <input type="number" class="form-control bg-dark text-light" id="premium_price" name="premium_price" min="0" step="0.01" value="250.00" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="vip_price" class="form-label">VIP Seat Price (₹)</label>
                                <input type="number" class="form-control bg-dark text-light" id="vip_price" name="vip_price" min="0" step="0.01" value="350.00" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="convenience_fee" class="form-label">Convenience Fee (₹)</label>
                                <input type="number" class="form-control bg-dark text-light" id="convenience_fee" name="convenience_fee" min="0" step="0.01" value="20.00" required>
                            </div>
                            
                            <button type="submit" name="update_pricing" class="btn btn-primary">Save Pricing</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Current Pricing</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-dark table-striped">
                                <thead>
                                    <tr>
                                        <th>Movie</th>
                                        <th>Standard</th>
                                        <th>Premium</th>
                                        <th>VIP</th>
                                        <th>Fee</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($movies) {
                                        $movies->data_seek(0); // Reset movie result pointer
                                        while ($movie = $movies->fetch_assoc()):
                                            $movie_pricing = isset($pricing[$movie['movie_id']]) ? $pricing[$movie['movie_id']] : null;
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($movie['title']); ?></td>
                                            <td>₹<?php echo $movie_pricing ? number_format($movie_pricing['standard_price'], 2) : '150.00'; ?></td>
                                            <td>₹<?php echo $movie_pricing ? number_format($movie_pricing['premium_price'], 2) : '250.00'; ?></td>
                                            <td>₹<?php echo $movie_pricing ? number_format($movie_pricing['vip_price'], 2) : '350.00'; ?></td>
                                            <td>₹<?php echo $movie_pricing ? number_format($movie_pricing['convenience_fee'], 2) : '20.00'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary edit-pricing" 
                                                        data-movie-id="<?php echo $movie['movie_id']; ?>"
                                                        data-standard="<?php echo $movie_pricing ? $movie_pricing['standard_price'] : '150.00'; ?>"
                                                        data-premium="<?php echo $movie_pricing ? $movie_pricing['premium_price'] : '250.00'; ?>"
                                                        data-vip="<?php echo $movie_pricing ? $movie_pricing['vip_price'] : '350.00'; ?>"
                                                        data-fee="<?php echo $movie_pricing ? $movie_pricing['convenience_fee'] : '20.00'; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php 
                                        endwhile;
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle edit pricing buttons
            document.querySelectorAll('.edit-pricing').forEach(button => {
                button.addEventListener('click', function() {
                    const movieId = this.getAttribute('data-movie-id');
                    const standard = this.getAttribute('data-standard');
                    const premium = this.getAttribute('data-premium');
                    const vip = this.getAttribute('data-vip');
                    const fee = this.getAttribute('data-fee');
                    
                    document.getElementById('movie_id').value = movieId;
                    document.getElementById('standard_price').value = standard;
                    document.getElementById('premium_price').value = premium;
                    document.getElementById('vip_price').value = vip;
                    document.getElementById('convenience_fee').value = fee;
                    
                    // Scroll to form
                    document.querySelector('.card').scrollIntoView({ behavior: 'smooth' });
                });
            });
        });
    </script>
</body>
</html>