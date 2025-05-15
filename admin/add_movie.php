<?php
session_start();
require_once('../db_connect.php');
require_once('../config.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

// Initialize variables
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $genre = $_POST['genre'] ?? '';
    $duration = $_POST['duration'] ?? 0;
    $release_date = $_POST['release_date'] ?? '';
    $director = $_POST['director'] ?? '';
    $cast = $_POST['cast'] ?? '';
    $description = $_POST['description'] ?? '';
    $language = $_POST['language'] ?? '';
    $country = $_POST['country'] ?? '';
    
    // Validate inputs
    if (empty($title)) {
        $error_message = "Movie title is required.";
    } else {
        // Handle image upload
        $image_path = DEFAULT_POSTER;
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = "../uploads/posters/";
        // File upload handling
        if(isset($_FILES['poster']) && $_FILES['poster']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $filename = $_FILES['poster']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);

            // Validate file extension
            if(in_array(strtolower($filetype), $allowed)) {
                // Create unique filename to prevent overwriting
                $new_filename = uniqid() . '.' . $filetype;
                $upload_dir = '../uploads/posters/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'movie_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            // Check if file is an image
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array(strtolower($file_extension), $allowed_types)) {
                $error_message = "Only JPG, JPEG, PNG, and GIF files are allowed.";
            } else if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // If upload successful, update image path
                $image_path = 'uploads/posters/' . $new_filename;
                if(move_uploaded_file($_FILES['poster']['tmp_name'], $upload_path)) {
                    // Store relative path in database
                    $poster_path = 'uploads/posters/' . $new_filename;
            } else {
                $error_message = "Failed to upload image. Please try again.";
                    $errors[] = "Failed to upload image.";
                    $poster_path = '';
            }
        }
        
            } else {
                $errors[] = "Invalid file format. Allowed formats: JPG, JPEG, PNG, WEBP";
                $poster_path = '';
            }
            } else {
            $poster_path = '';
            }

        if (empty($error_message)) {
            // Insert movie into database
            $stmt = $conn->prepare("
                INSERT INTO movies 
                (title, genre, duration, release_date, director, cast, description, 
                 image, language, country) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "ssisssssss",
                $title,
                $genre,
                $duration,
                $release_date,
                $director,
                $cast,
                $description,
                $image_path,
                $language,
                $country
            );
            
            if ($stmt->execute()) {
                $movie_id = $conn->insert_id;
            $stmt = $pdo->prepare("INSERT INTO movies (title, description, duration, genre, language, release_date, poster_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $duration, $genre, $language, $release_date, $poster_path]);

            if ($stmt->rowCount() > 0) {
                $movie_id = $pdo->lastInsertId();
                $success_message = "Movie added successfully! <a href='edit_movie.php?id=$movie_id'>Edit movie details</a>";
            } else {
                $error_message = "Error adding movie: " . $conn->error;
                $error_message = "Error adding movie.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Movie - Admin Dashboard</title>
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
        
        .poster-preview {
            width: 100%;
            height: 400px;
            border-radius: 5px;
            object-fit: cover;
            background-color: #e9ecef;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #6c757d;
            font-size: 1.2em;
            margin-bottom: 15px;
        }
        
        .poster-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 5px;
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
                        <a class="nav-link" href="manage_bookings.php">Bookings</a>
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
            <h2>Add New Movie</h2>
            <div>
                <a href="manage_movies.php" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left"></i> Back to Movies
                </a>
                <a href="import_movie.php" class="btn btn-outline-success">
                    <i class="bi bi-cloud-download"></i> Import from OMDb
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
                <h5 class="mb-0">Movie Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="poster-preview" id="posterPreview">
                                <span>No image selected</span>
                            </div>
                            <div class="mb-3">
                                <label for="image" class="form-label">Movie Poster</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <label for="poster" class="form-label">Movie Poster</label>
                                <input type="file" class="form-control" id="poster" name="poster" accept="image/*">
                                <div class="form-text">Recommended size: 300x450 pixels</div>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="title" class="form-label">Movie Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="genre" class="form-label">Genre</label>
                                        <input type="text" class="form-control" id="genre" name="genre" placeholder="Action, Drama, Comedy, etc.">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="duration" class="form-label">Duration (minutes)</label>
                                        <input type="number" class="form-control" id="duration" name="duration" min="1" value="120">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="release_date" class="form-label">Release Date</label>
                                        <input type="date" class="form-control" id="release_date" name="release_date">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="language" class="form-label">Language</label>
                                        <input type="text" class="form-control" id="language" name="language" placeholder="English, Spanish, etc.">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="director" class="form-label">Director</label>
                                <input type="text" class="form-control" id="director" name="director">
                            </div>
                            
                            <div class="mb-3">
                                <label for="cast" class="form-label">Cast</label>
                                <input type="text" class="form-control" id="cast" name="cast" placeholder="Main actors separated by commas">
                            </div>
                            
                            <div class="mb-3">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="country" name="country">
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5" placeholder="Movie plot and description..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end mt-4">
                        <a href="manage_movies.php" class="btn btn-outline-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Movie</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show image preview when a file is selected
        document.getElementById('image').addEventListener('change', function() {
        document.getElementById('poster').addEventListener('change', function() {
            const preview = document.getElementById('posterPreview');
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Poster Preview">';
                };
                
                reader.readAsDataURL(this.files[0]);
            } else {
                preview.innerHTML = '<span>No image selected</span>';
            }
        });
    </script>
</body>
</html>