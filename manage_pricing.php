<?php
session_start();
require_once('db_connect.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

// Check if movie_pricing table exists, if not create it
$result = $conn->query("SHOW TABLES LIKE 'movie_pricing'");
if ($result->num_rows == 0) {
    $conn->query("CREATE TABLE movie_pricing (
        pricing_id INT AUTO_INCREMENT PRIMARY KEY,
        movie_id INT NOT NULL,
        standard_price DECIMAL(10,2) NOT NULL DEFAULT 150.00,
        premium_price DECIMAL(10,2) NOT NULL DEFAULT 250.00,
        vip_price DECIMAL(10,2) NOT NULL DEFAULT 350.00,
        convenience_fee DECIMAL(10,2) NOT NULL DEFAULT 20.00,
        UNIQUE KEY (movie_id)
    )");
}

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movie_id = $_POST['movie_id'] ?? 0;
    $standard_price = $_POST['standard_price'] ?? 150.00;
    $premium_price = $_POST['premium_price'] ?? 250.00;
    $vip_price = $_POST['vip_price'] ?? 350.00;
    $convenience_fee = $_POST['convenience_fee'] ?? 20.00;
    
    // Validate inputs
    if (!$movie_id) {
        $error_message = "Please select a movie.";
    } else {
        // Check if pricing already exists for this movie
        $stmt = $conn->prepare("SELECT pricing_id FROM movie_pricing WHERE movie_id = ?");
        $stmt->bind_param("i", $movie_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing pricing
            $stmt = $conn->prepare("UPDATE movie_pricing 
                                   SET standard_price = ?, premium_price = ?, vip_price = ?, convenience_fee = ? 
                                   WHERE movie_id = ?");
            $stmt->bind_param("ddddi", $standard_price, $premium_price, $vip_price, $convenience_fee, $movie_id);
        } else {
            // Insert new pricing
            $stmt = $conn->prepare("INSERT INTO movie_pricing 
                                   (movie_id, standard_price, premium_price, vip_price, convenience_fee) 
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("idddd", $movie_id, $standard_price, $premium_price, $vip_price, $convenience_fee);
        }
        
        if ($stmt->execute()) {
            $success_message = "Pricing updated successfully!";
        } else {
            $error_message = "Error updating pricing: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all movies
$movies = [];
$result = $conn->query("SELECT movie_id, title FROM movies ORDER BY title");
while ($row = $result->fetch_assoc()) {
    $movies[] = $row;
}

// Fetch existing pricing data
$pricing_data = [];
$result = $conn->query("SELECT * FROM movie_pricing");
while ($row = $result->fetch_assoc()) {
    $pricing_data[$row['movie_id']] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Movie Pricing - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            margin-bottom: 30px;
        }
        
        .card-header {
            background-color: #343a40;
            color: white;
            font-weight: bold;
        }
        
        .table th {
            background-color: #f8f9fa;
        }
        
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        
        .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
        }
        
        .pricing-info {
            background-color: #e9ecef;
            border-radius: 0.25rem;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_movies.php">Movies</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_theaters.php">Theaters</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_showtimes.php">Showtimes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_pricing.php">Pricing</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_bookings.php">Bookings</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="logout.php" class="btn btn-outline-light">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Movie Pricing</h2>
            <a href="admin_dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Set Movie Pricing</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="movie_id" class="form-label">Select Movie</label>
                                <select class="form-select" id="movie_id" name="movie_id" required>
                                    <option value="">-- Select Movie --</option>
                                    <?php foreach ($movies as $movie): ?>
                                        <option value="<?php echo $movie['movie_id']; ?>"><?php echo htmlspecialchars($movie['title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="standard_price" class="form-label">Standard Seat Price (₹)</label>
                                <input type="number" class="form-control" id="standard_price" name="standard_price" min="0" step="0.01" value="150.00" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="premium_price" class="form-label">Premium Seat Price (₹)</label>
                                <input type="number" class="form-control" id="premium_price" name="premium_price" min="0" step="0.01" value="250.00" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="vip_price" class="form-label">VIP Seat Price (₹)</label>
                                <input type="number" class="form-control" id="vip_price" name="vip_price" min="0" step="0.01" value="350.00" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="convenience_fee" class="form-label">Convenience Fee (₹)</label>
                                <input type="number" class="form-control" id="convenience_fee" name="convenience_fee" min="0" step="0.01" value="20.00" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Save Pricing</button>
                        </form>
                    </div>
                </div>
                
                <div class="pricing-info">
                    <h5>Pricing Guidelines</h5>
                    <ul>
                        <li>Standard seats are the regular seats in rows A, B, G, and H</li>
                        <li>Premium seats are in rows C and F (₹100 more than standard)</li>
                        <li>VIP seats are in rows D and E (₹200 more than standard)</li>
                        <li>Convenience fee is applied once per booking</li>
                    </ul>
                </div>
            </div>
            
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Current Pricing</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
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
                                    <?php foreach ($movies as $movie): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($movie['title']); ?></td>
                                            <?php if (isset($pricing_data[$movie['movie_id']])): ?>
                                                <td>₹<?php echo number_format($pricing_data[$movie['movie_id']]['standard_price'], 2); ?></td>
                                                <td>₹<?php echo number_format($pricing_data[$movie['movie_id']]['premium_price'], 2); ?></td>
                                                <td>₹<?php echo number_format($pricing_data[$movie['movie_id']]['vip_price'], 2); ?></td>
                                                <td>₹<?php echo number_format($pricing_data[$movie['movie_id']]['convenience_fee'], 2); ?></td>
                                            <?php else: ?>
                                                <td>₹150.00</td>
                                                <td>₹250.00</td>
                                                <td>₹350.00</td>
                                                <td>₹20.00</td>
                                            <?php endif; ?>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary edit-btn" data-movie-id="<?php echo $movie['movie_id']; ?>">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
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
        // Load pricing data when movie is selected
        document.getElementById('movie_id').addEventListener('change', function() {
            const movieId = this.value;
            if (!movieId) return;
            
            // Get pricing data from the table
            const pricingData = <?php echo json_encode($pricing_data); ?>;
            
            if (pricingData[movieId]) {
                document.getElementById('standard_price').value = pricingData[movieId].standard_price;
                document.getElementById('premium_price').value = pricingData[movieId].premium_price;
                document.getElementById('vip_price').value = pricingData[movieId].vip_price;
                document.getElementById('convenience_fee').value = pricingData[movieId].convenience_fee;
            } else {
                // Default values
                document.getElementById('standard_price').value = '150.00';
                document.getElementById('premium_price').value = '250.00';
                document.getElementById('vip_price').value = '350.00';
                document.getElementById('convenience_fee').value = '20.00';
            }
        });
        
        // Edit buttons
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const movieId = this.getAttribute('data-movie-id');
                document.getElementById('movie_id').value = movieId;
                
                // Trigger change event to load pricing data
                const event = new Event('change');
                document.getElementById('movie_id').dispatchEvent(event);
                
                // Scroll to form
                document.querySelector('.card-body form').scrollIntoView({ behavior: 'smooth' });
            });
        });
    </script>
</body>
</html>