<?php
session_start();
require_once('db_connect.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

// Initialize variables
$movie = null;
$success_message = '';
$error_message = '';

// Check if movie ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin/manage_movies.php");
    exit();
}

$movie_id = $_GET['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $genre = $_POST['genre'] ?? '';
    $duration = $_POST['duration'] ?? 0;
    $release_date = $_POST['release_date'] ?? '';
    $description = $_POST['description'] ?? '';
    $director = $_POST['director'] ?? '';
    $cast = $_POST['cast'] ?? '';
    $language = $_POST['language'] ?? '';
    $country = $_POST['country'] ?? '';
    
    // Validate inputs
    if (empty($title)) {
        $error_message = "Movie title is required.";
    } else {
        // Handle image upload
        $image = $_POST['current_image'] ?? '';
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = "uploads/posters/";
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'movie_' . $movie_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            // Check if file is an image
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array(strtolower($file_extension), $allowed_types)) {
                $error_message = "Only JPG, JPEG, PNG, and GIF files are allowed.";
            } else if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // If upload successful, update image path
                $image = $upload_dir . $new_filename;
            } else {
                $error_message = "Failed to upload image. Please try again.";
            }
        }
        
        if (empty($error_message)) {
            // Update movie in database
            $stmt = $conn->prepare("
                UPDATE movies 
                SET title = ?, genre = ?, duration = ?, release_date = ?, 
                    description = ?, image = ?, director = ?, cast = ?,
                    language = ?, country = ?
                WHERE movie_id = ?
            ");
            $stmt->bind_param("ssisssssssi", 
                $title, $genre, $duration, $release_date, 
                $description, $image, $director, $cast,
                $language, $country, $movie_id
            );
            
            if ($stmt->execute()) {
                $success_message = "Movie updated successfully!";
            } else {
                $error_message = "Error updating movie: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Fetch movie data
$stmt = $conn->prepare("SELECT * FROM movies WHERE movie_id = ?");
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin/manage_movies.php");
    exit();
}

$movie = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Movie - Admin Dashboard</title>
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
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .poster-container {
            position: relative;
            margin-bottom: 20px;
        }
        
        .poster-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s;
            border-radius: 5px;
        }
        
        .poster-container:hover .poster-overlay {
            opacity: 1;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin/admin_dashboard.php">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin/admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin/manage_movies.php">Movies</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/manage_theaters.php">Theaters</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/manage_showtimes.php">Showtimes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/manage_pricing.php">Pricing</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/manage_bookings.php">Bookings</a>
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
            <h2>Edit Movie: <?php echo htmlspecialchars($movie['title']); ?></h2>
            <div>
                <a href="admin/manage_movies.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Movies
                </a>
                <a href="admin/manage_showtimes.php?movie_id=<?php echo $movie_id; ?>" class="btn btn-outline-primary">
                    <i class="bi bi-calendar-event"></i> Manage Showtimes
                </a>
            </div>
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
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Edit Movie Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="poster-container">
                                <img src="<?php echo $movie['image']; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="movie-poster">
                                <div class="poster-overlay">
                                    <label for="image" class="btn btn-light">
                                        <i class="bi bi-camera"></i> Change Poster
                                    </label>
                                </div>
                            </div>
                            <input type="file" class="form-control d-none" id="image" name="image" accept="image/*">
                            <input type="hidden" name="current_image" value="<?php echo $movie['image']; ?>">
                            
                            <?php if (!empty($movie['imdb_id'])): ?>
                                <div class="mt-3">
                                    <div class="alert alert-info">
                                        <strong>IMDb ID:</strong> <?php echo $movie['imdb_id']; ?><br>
                                        <strong>IMDb Rating:</strong> <?php echo $movie['imdb_rating']; ?>/10
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="title" class="form-label">Movie Title</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($movie['title']); ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="genre" class="form-label">Genre</label>
                                        <input type="text" class="form-control" id="genre" name="genre" value="<?php echo htmlspecialchars($movie['genre']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="duration" class="form-label">Duration (minutes)</label>
                                        <input type="number" class="form-control" id="duration" name="duration" value="<?php echo $movie['duration']; ?>" min="1">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="release_date" class="form-label">Release Date</label>
                                        <input type="date" class="form-control" id="release_date" name="release_date" value="<?php echo $movie['release_date']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="language" class="form-label">Language</label>
                                        <input type="text" class="form-control" id="language" name="language" value="<?php echo htmlspecialchars($movie['language'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="director" class="form-label">Director</label>
                                <input type="text" class="form-control" id="director" name="director" value="<?php echo htmlspecialchars($movie['director'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="cast" class="form-label">Cast</label>
                                <input type="text" class="form-control" id="cast" name="cast" value="<?php echo htmlspecialchars($movie['cast'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlspecialchars($movie['country'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($movie['description']); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end mt-4">
                        <a href="admin/manage_movies.php" class="btn btn-outline-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show file name when a file is selected
        document.getElementById('image').addEventListener('change', function() {
            if (this.files.length > 0) {
                alert('Selected file: ' + this.files[0].name);
            }
        });
        
        // Trigger file input when clicking on the poster
        document.querySelector('.poster-overlay').addEventListener('click', function() {
            document.getElementById('image').click();
        });
    </script>
</body>
</html>