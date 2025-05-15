<?php
require_once('../session_config.php');
require_once('../db_connect.php');
require_once('../config.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

// Initialize variables
$movies = [];
$success_message = '';
$error_message = '';

// Handle movie deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $movie_id = $_GET['delete'];
    
    // Delete movie
    $stmt = $conn->prepare("DELETE FROM movies WHERE movie_id = ?");
    $stmt->bind_param("i", $movie_id);
    
    if ($stmt->execute()) {
        $success_message = "Movie deleted successfully!";
    } else {
        $error_message = "Error deleting movie: " . $conn->error;
    }
    $stmt->close();
}

// Fetch all movies
$result = $conn->query("SELECT * FROM movies ORDER BY title");
while ($row = $result->fetch_assoc()) {
    $movies[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Movies - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .navbar {
            background-color: #584528;
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
        
        .movie-poster {
            width: 80px;
            height: 120px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .add-options {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .add-option-card {
            flex: 1;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .add-option-card:hover {
            transform: translateY(-5px);
        }
        
        .add-option-header {
            background-color: #343a40;
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: bold;
        }
        
        .add-option-body {
            padding: 20px;
            text-align: center;
        }
        
        .add-option-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #584528;
        }
        
        .add-option-description {
            margin-bottom: 20px;
            color: #6c757d;
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
                        <a class="nav-link active" href="manage_movies.php">Movies</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="../logout.php" class="btn btn-outline-light">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Movies</h2>
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
        
        <div class="add-options">
            <div class="add-option-card">
                <div class="add-option-header">
                    Add Movie Manually
                </div>
                <div class="add-option-body">
                    <div class="add-option-icon">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <p class="add-option-description">
                        Add a movie by manually entering all details including title, genre, duration, etc.
                    </p>
                    <a href="add_movie.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add Manually
                    </a>
                </div>
            </div>
            
            <div class="add-option-card">
                <div class="add-option-header">
                    Import from OMDb
                </div>
                <div class="add-option-body">
                    <div class="add-option-icon">
                        <i class="bi bi-cloud-download"></i>
                    </div>
                    <p class="add-option-description">
                        Search and import movies directly from the OMDb API with complete details.
                    </p>
                    <a href="import_movie.php" class="btn btn-success">
                        <i class="bi bi-search"></i> Search & Import
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Movie List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Poster</th>
                                <th>Title</th>
                                <th>Genre</th>
                                <th>Duration</th>
                                <th>Release Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($movies)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No movies found. Add some movies to get started!</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($movies as $movie): ?>
                                    <tr>
                                        <td>
                                            <img src="../<?php echo $movie['image']; ?>" alt="<?php echo htmlspecialchars($movie['title'] ?? ''); ?>" class="movie-poster">
                                        </td>
                                        <td><?php echo htmlspecialchars($movie['title'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($movie['genre'] ?? ''); ?></td>
                                        <td><?php echo $movie['duration']; ?> mins</td>
                                        <td><?php echo !empty($movie['release_date']) ? date('M d, Y', strtotime($movie['release_date'])) : 'Coming Soon'; ?></td>
                                        <td>
                                            <a href="edit_movie.php?id=<?php echo $movie['movie_id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <a href="?delete=<?php echo $movie['movie_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this movie?')">
                                                <i class="bi bi-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>