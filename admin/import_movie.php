<?php
require_once('../session_config.php');
require_once('../db_connect.php');
require_once('../config.php');
require_once('../includes/omdb_api.php');

// Initialize OMDB API
$omdb = new OMDbAPI(OMDB_API_KEY);

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

// Initialize variables
$search_results = [];
$movie_details = null;
$search_term = '';
$search_type = 'title';
$error_message = '';
$success_message = '';

// Check if OMDb API is enabled and configured
if (!OMDB_API_ENABLED || empty(OMDB_API_KEY)) {
    $error_message = "OMDb API is not enabled or API key is missing. Please configure it in config.php.";
}

// Handle search form submission
if (isset($_POST['search']) && !empty($_POST['search_term'])) {
    $search_term = trim($_POST['search_term']);
    $search_type = $_POST['search_type'] ?? 'title';
    
    try {
        if ($search_type === 'id') {
            // Search by IMDb ID
            $movie_details = $omdb->getMovieById($search_term, true);
            if ($movie_details['Response'] === 'True') {
                $search_results = [$movie_details];
            } else {
                $error_message = "No movie found with IMDb ID: $search_term";
            }
        } else {
            // Search by title
            $result = $omdb->searchMovies($search_term);
            if ($result['Response'] === 'True' && isset($result['Search'])) {
                $search_results = $result['Search'];
            } else {
                $error_message = "No movies found matching: $search_term";
            }
        }
    } catch (Exception $e) {
        $error_message = "API Error: " . $e->getMessage();
    }
}

// Handle movie selection for detailed view
if (isset($_GET['imdb_id']) && !empty($_GET['imdb_id'])) {
    try {
        $movie_details = $omdb->getMovieById($_GET['imdb_id'], true);
        if ($movie_details['Response'] !== 'True') {
            $error_message = "Failed to fetch movie details.";
            $movie_details = null;
        }
    } catch (Exception $e) {
        $error_message = "API Error: " . $e->getMessage();
    }
}

// Handle movie import
if (isset($_POST['import_movie']) && isset($_POST['imdb_id'])) {
    $imdb_id = $_POST['imdb_id'];
    
    try {
        // Get movie details from OMDb
        $movie_data = $omdb->getMovieById($imdb_id, true);
        
        if ($movie_data['Response'] === 'True') {
            // Format movie data for database
            $formatted_data = $omdb->formatMovieData($movie_data);
            
            // Check if movie already exists in database
            $stmt = $conn->prepare("SELECT movie_id FROM movies WHERE imdb_id = ?");
            $stmt->bind_param("s", $imdb_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Movie exists, update it
                $row = $result->fetch_assoc();
                $movie_id = $row['movie_id'];
                
                // Download poster if available
                $poster_path = $formatted_data['image'];
                if (!empty($formatted_data['poster_url']) && $formatted_data['poster_url'] != 'N/A') {
                    $downloaded_poster = $omdb->downloadPoster($formatted_data['poster_url'], $imdb_id);
                    if ($downloaded_poster) {
                        $poster_path = $downloaded_poster;
                    }
                }
                
                // Update movie in database
                $stmt = $conn->prepare("
                    UPDATE movies 
                    SET title = ?, genre = ?, duration = ?, release_date = ?, 
                        director = ?, cast = ?, description = ?, image = ?,
                        imdb_rating = ?, language = ?, country = ?, awards = ?
                    WHERE movie_id = ?
                ");
                
                $stmt->bind_param(
                    "ssisssssdsssi",
                    $formatted_data['title'],
                    $formatted_data['genre'],
                    $formatted_data['duration'],
                    $formatted_data['release_date'],
                    $formatted_data['director'],
                    $formatted_data['cast'],
                    $formatted_data['description'],
                    $poster_path,
                    $formatted_data['imdb_rating'],
                    $formatted_data['language'],
                    $formatted_data['country'],
                    $formatted_data['awards'],
                    $movie_id
                );
                
                if ($stmt->execute()) {
                    $success_message = "Movie updated successfully! <a href='edit_movie.php?id=$movie_id'>Edit movie details</a>";
                } else {
                    $error_message = "Error updating movie: " . $conn->error;
                }
            } else {
                // New movie, insert it
                
                // Download poster if available
                $poster_path = DEFAULT_POSTER;
                if (!empty($formatted_data['poster_url']) && $formatted_data['poster_url'] != 'N/A') {
                    $downloaded_poster = $omdb->downloadPoster($formatted_data['poster_url'], $imdb_id);
                    if ($downloaded_poster) {
                        $poster_path = $downloaded_poster;
                    }
                }
                
                // Insert movie into database
                $stmt = $conn->prepare("
                    INSERT INTO movies 
                    (title, genre, duration, release_date, director, cast, description, 
                     image, imdb_id, imdb_rating, language, country, awards) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->bind_param(
                    "ssissssssdsss",
                    $formatted_data['title'],
                    $formatted_data['genre'],
                    $formatted_data['duration'],
                    $formatted_data['release_date'],
                    $formatted_data['director'],
                    $formatted_data['cast'],
                    $formatted_data['description'],
                    $poster_path,
                    $imdb_id,
                    $formatted_data['imdb_rating'],
                    $formatted_data['language'],
                    $formatted_data['country'],
                    $formatted_data['awards']
                );
                
                if ($stmt->execute()) {
                    $movie_id = $conn->insert_id;
                    $success_message = "Movie imported successfully! <a href='edit_movie.php?id=$movie_id'>Edit movie details</a>";
                } else {
                    $error_message = "Error importing movie: " . $conn->error;
                }
            }
            
            $stmt->close();
        } else {
            $error_message = "Failed to fetch movie data: " . ($movie_data['Error'] ?? 'Unknown error');
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get all theaters for the form
$theaters = [];
$result = $conn->query("SELECT theater_id, name FROM theaters ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $theaters[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Movies - Admin Dashboard</title>
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
        
        .search-results {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .movie-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            transition: transform 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .movie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .movie-poster {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }
        
        .movie-info {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .movie-title {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 1.1em;
        }
        
        .movie-year {
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .movie-actions {
            margin-top: auto;
        }
        
        .movie-details {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }
        
        .movie-poster-large {
            width: 300px;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .movie-info-large {
            flex: 1;
        }
        
        .movie-title-large {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .movie-meta {
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .movie-plot {
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .detail-row {
            margin-bottom: 10px;
        }
        
        .detail-label {
            font-weight: bold;
            min-width: 100px;
            display: inline-block;
        }
        
        .rating {
            display: inline-block;
            padding: 5px 10px;
            background-color: #ffc107;
            color: #212529;
            border-radius: 3px;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .movie-details {
                flex-direction: column;
            }
            
            .movie-poster-large {
                width: 100%;
                max-width: 300px;
                margin: 0 auto 20px;
            }
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
            <h2>Import Movies from OMDb</h2>
            <div>
                <a href="manage_movies.php" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left"></i> Back to Movies
                </a>
                <a href="add_movie.php" class="btn btn-outline-primary">
                    <i class="bi bi-plus-circle"></i> Add Movie Manually
                </a>
            </div>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Search for Movies</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="search_term" name="search_term" placeholder="Enter movie title or IMDb ID" value="<?php echo htmlspecialchars($search_term); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="search_type" name="search_type">
                            <option value="title" <?php echo $search_type === 'title' ? 'selected' : ''; ?>>Search by Title</option>
                            <option value="id" <?php echo $search_type === 'id' ? 'selected' : ''; ?>>Search by IMDb ID</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" name="search" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </form>
                
                <?php if (!empty($search_term) && empty($movie_details)): ?>
                    <div class="search-results">
                        <?php foreach ($search_results as $movie): ?>
                            <div class="movie-card">
                                <img src="<?php echo $movie['Poster'] !== 'N/A' ? $movie['Poster'] : '../' . DEFAULT_POSTER; ?>" alt="<?php echo htmlspecialchars($movie['Title']); ?>" class="movie-poster">
                                <div class="movie-info">
                                    <div class="movie-title"><?php echo htmlspecialchars($movie['Title']); ?></div>
                                    <div class="movie-year"><?php echo $movie['Year']; ?> | <?php echo $movie['Type']; ?></div>
                                    <div class="movie-actions">
                                        <a href="?imdb_id=<?php echo $movie['imdbID']; ?>" class="btn btn-sm btn-primary w-100">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($movie_details): ?>
                    <div class="movie-details">
                        <img src="<?php echo $movie_details['Poster'] !== 'N/A' ? $movie_details['Poster'] : '../' . DEFAULT_POSTER; ?>" alt="<?php echo htmlspecialchars($movie_details['Title']); ?>" class="movie-poster-large">
                        <div class="movie-info-large">
                            <h3 class="movie-title-large"><?php echo htmlspecialchars($movie_details['Title']); ?></h3>
                            <div class="movie-meta">
                                <?php echo $movie_details['Year']; ?> | 
                                <?php echo $movie_details['Rated']; ?> | 
                                <?php echo $movie_details['Runtime']; ?> | 
                                <?php echo $movie_details['Genre']; ?>
                            </div>
                            
                            <div class="movie-plot">
                                <?php echo htmlspecialchars($movie_details['Plot']); ?>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">Director:</span>
                                <?php echo htmlspecialchars($movie_details['Director']); ?>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">Cast:</span>
                                <?php echo htmlspecialchars($movie_details['Actors']); ?>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">Language:</span>
                                <?php echo htmlspecialchars($movie_details['Language']); ?>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">Country:</span>
                                <?php echo htmlspecialchars($movie_details['Country']); ?>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">Awards:</span>
                                <?php echo htmlspecialchars($movie_details['Awards']); ?>
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">IMDb Rating:</span>
                                <span class="rating"><?php echo $movie_details['imdbRating']; ?>/10</span>
                                (<?php echo $movie_details['imdbVotes']; ?> votes)
                            </div>
                            
                            <div class="detail-row">
                                <span class="detail-label">IMDb ID:</span>
                                <?php echo $movie_details['imdbID']; ?>
                            </div>
                            
                            <div class="mt-4">
                                <form method="POST">
                                    <input type="hidden" name="imdb_id" value="<?php echo $movie_details['imdbID']; ?>">
                                    <button type="submit" name="import_movie" class="btn btn-success">
                                        <i class="bi bi-cloud-download"></i> Import Movie
                                    </button>
                                    <a href="import_movie.php" class="btn btn-outline-secondary ms-2">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </a>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">How to Use</h5>
            </div>
            <div class="card-body">
                <ol>
                    <li>Search for a movie by title or IMDb ID using the search form above.</li>
                    <li>Click "View Details" on a search result to see complete movie information.</li>
                    <li>Click "Import Movie" to add the movie to your database.</li>
                    <li>After importing, you can edit additional details like showtimes and pricing.</li>
                </ol>
                
                <div class="alert alert-info">
                    <strong>Note:</strong> This feature requires a valid OMDb API key. If you don't have one, you can get a free key at 
                    <a href="http://www.omdbapi.com/apikey.aspx" target="_blank">http://www.omdbapi.com/apikey.aspx</a>.
                    Then update the key in your config.php file.
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>