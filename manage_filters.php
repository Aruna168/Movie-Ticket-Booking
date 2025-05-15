<?php
session_start();
require_once('db_connect.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

// Create filter_options table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS filter_options (
    option_id INT AUTO_INCREMENT PRIMARY KEY,
    filter_type VARCHAR(50) NOT NULL,
    option_value VARCHAR(100) NOT NULL,
    display_order INT DEFAULT 0,
    UNIQUE KEY (filter_type, option_value)
)");

// Handle form submissions
$success_message = '';
$error_message = '';

// Add new filter option
if (isset($_POST['add_option'])) {
    $filter_type = $_POST['filter_type'] ?? '';
    $option_value = $_POST['option_value'] ?? '';
    $display_order = $_POST['display_order'] ?? 0;
    
    if (empty($filter_type) || empty($option_value)) {
        $error_message = "Filter type and option value are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO filter_options (filter_type, option_value, display_order) VALUES (?, ?, ?) 
                               ON DUPLICATE KEY UPDATE display_order = ?");
        $stmt->bind_param("ssii", $filter_type, $option_value, $display_order, $display_order);
        
        if ($stmt->execute()) {
            $success_message = "Filter option added successfully!";
        } else {
            $error_message = "Error adding filter option: " . $conn->error;
        }
        $stmt->close();
    }
}

// Delete filter option
if (isset($_POST['delete_option'])) {
    $option_id = $_POST['option_id'] ?? 0;
    
    if ($option_id) {
        $stmt = $conn->prepare("DELETE FROM filter_options WHERE option_id = ?");
        $stmt->bind_param("i", $option_id);
        
        if ($stmt->execute()) {
            $success_message = "Filter option deleted successfully!";
        } else {
            $error_message = "Error deleting filter option: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all filter options
$filter_options = [];
$result = $conn->query("SELECT * FROM filter_options ORDER BY filter_type, display_order, option_value");
while ($row = $result->fetch_assoc()) {
    $filter_options[] = $row;
}

// Group options by filter type
$grouped_options = [];
foreach ($filter_options as $option) {
    $filter_type = $option['filter_type'];
    if (!isset($grouped_options[$filter_type])) {
        $grouped_options[$filter_type] = [];
    }
    $grouped_options[$filter_type][] = $option;
}

// Get all genres from movies table for reference
$genres = [];
$result = $conn->query("SELECT DISTINCT genre FROM movies");
while ($row = $result->fetch_assoc()) {
    // Split genres if they contain commas
    $genreList = explode(',', $row['genre']);
    foreach ($genreList as $g) {
        $g = trim($g);
        if (!empty($g) && !in_array($g, $genres)) {
            $genres[] = $g;
        }
    }
}
sort($genres);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Filters - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .navbar {
            background-color: #584528;
            color: white;
        }
        
        .navbar-brand {
            font-weight: bold;
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
            background-color: #ff4500;
            border-color: #ff4500;
        }
        
        .btn-primary:hover {
            background-color: #e63e00;
            border-color: #e63e00;
        }
        
        .filter-type-section {
            margin-bottom: 30px;
        }
        
        .filter-type-header {
            background-color: #f0f0f0;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-weight: bold;
        }
        
        .option-badge {
            display: inline-block;
            background-color: #e9ecef;
            padding: 5px 10px;
            border-radius: 20px;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .option-badge .delete-btn {
            color: #dc3545;
            margin-left: 5px;
            cursor: pointer;
        }
        
        .reference-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .reference-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .reference-item {
            display: inline-block;
            background-color: #e2e3e5;
            padding: 3px 8px;
            border-radius: 3px;
            margin-right: 5px;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
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
                        <a class="nav-link" href="manage_pricing.php">Pricing</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_filters.php">Filters</a>
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
            <h2>Manage Filter Options</h2>
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
                        <h5 class="mb-0">Add Filter Option</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="filter_type" class="form-label">Filter Type</label>
                                <select class="form-select" id="filter_type" name="filter_type" required>
                                    <option value="">-- Select Filter Type --</option>
                                    <option value="genre">Genre</option>
                                    <option value="language">Language</option>
                                    <option value="format">Format</option>
                                    <option value="rating">Rating</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="option_value" class="form-label">Option Value</label>
                                <input type="text" class="form-control" id="option_value" name="option_value" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="display_order" class="form-label">Display Order</label>
                                <input type="number" class="form-control" id="display_order" name="display_order" value="0" min="0">
                                <div class="form-text">Lower numbers will be displayed first.</div>
                            </div>
                            
                            <button type="submit" name="add_option" class="btn btn-primary">Add Option</button>
                        </form>
                    </div>
                </div>
                
                <div class="reference-section">
                    <div class="reference-title">Genres from Movies Database:</div>
                    <div>
                        <?php foreach ($genres as $genre): ?>
                            <span class="reference-item"><?php echo htmlspecialchars($genre); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-primary" id="importGenresBtn">Import All Genres</button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Current Filter Options</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($grouped_options)): ?>
                            <div class="alert alert-info">No filter options have been added yet.</div>
                        <?php else: ?>
                            <?php foreach ($grouped_options as $filter_type => $options): ?>
                                <div class="filter-type-section">
                                    <div class="filter-type-header">
                                        <?php echo ucfirst(htmlspecialchars($filter_type)); ?>
                                    </div>
                                    <div>
                                        <?php foreach ($options as $option): ?>
                                            <div class="option-badge">
                                                <?php echo htmlspecialchars($option['option_value']); ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="option_id" value="<?php echo $option['option_id']; ?>">
                                                    <button type="submit" name="delete_option" class="delete-btn btn btn-link btn-sm p-0" onclick="return confirm('Are you sure you want to delete this option?')">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Filter Options Usage</h5>
                    </div>
                    <div class="card-body">
                        <p>Filter options defined here will be available in the filter dropdowns on the user dashboard and movie listing pages.</p>
                        
                        <h6 class="mt-3">Recommended Options:</h6>
                        <ul>
                            <li><strong>Genre:</strong> Action, Adventure, Comedy, Drama, Horror, Sci-Fi, Thriller, etc.</li>
                            <li><strong>Language:</strong> English, Hindi, Tamil, Telugu, etc.</li>
                            <li><strong>Format:</strong> 2D, 3D, IMAX, 4DX, etc.</li>
                            <li><strong>Rating:</strong> U, UA, A, etc. (or PG, PG-13, R, etc.)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Import all genres button
        document.getElementById('importGenresBtn').addEventListener('click', function() {
            const genres = <?php echo json_encode($genres); ?>;
            let filterType = document.getElementById('filter_type');
            let optionValue = document.getElementById('option_value');
            
            filterType.value = 'genre';
            
            if (genres.length > 0) {
                optionValue.value = genres[0];
                alert('Set to import "' + genres[0] + '". Add this genre, then click Import again for the next one.');
                
                // Remove the first genre from the array for next time
                genres.shift();
                this.dataset.genres = JSON.stringify(genres);
            } else {
                alert('All genres have been imported!');
            }
        });
    </script>
</body>
</html>