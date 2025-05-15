<?php
session_start();
require_once('db_connect.php');

// Initialize variables
$search_term = $_GET['q'] ?? '';
$filter_genre = $_GET['genre'] ?? '';
$filter_language = $_GET['language'] ?? '';
$filter_location = $_GET['location'] ?? '';
$filter_theater = $_GET['theater'] ?? '';
$movies = [];
$theaters = [];

// Function to calculate distance between two points
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // Radius of the earth in km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $earthRadius * $c; // Distance in km
    return $distance;
}

// Get all available genres for filter
$genres = [];
$genre_result = $conn->query("SELECT DISTINCT genre FROM movies WHERE genre IS NOT NULL ORDER BY genre");
while ($row = $genre_result->fetch_assoc()) {
    if (!empty($row['genre'])) {
        $genres[] = $row['genre'];
    }
}

// Get all available languages for filter
$languages = [];
$language_result = $conn->query("SELECT DISTINCT language FROM movies WHERE language IS NOT NULL ORDER BY language");
while ($row = $language_result->fetch_assoc()) {
    if (!empty($row['language'])) {
        $languages[] = $row['language'];
    }
}

// Get all available cities for filter
$cities = [];
$city_result = $conn->query("SELECT DISTINCT city FROM theaters WHERE city IS NOT NULL ORDER BY city");
while ($row = $city_result->fetch_assoc()) {
    if (!empty($row['city'])) {
        $cities[] = $row['city'];
    }
}

// Get all theaters for filter
$all_theaters = [];
$theater_result = $conn->query("SELECT theater_id, name FROM theaters ORDER BY name");
while ($row = $theater_result->fetch_assoc()) {
    $all_theaters[] = $row;
}

// Search functionality
if (!empty($search_term) || !empty($filter_genre) || !empty($filter_language) || !empty($filter_location) || !empty($filter_theater)) {
    // Build the movie search query with filters
    $movie_query = "SELECT m.*, GROUP_CONCAT(DISTINCT t.name) as theaters, 
                    GROUP_CONCAT(DISTINCT t.city) as cities,
                    GROUP_CONCAT(DISTINCT s.show_date) as show_dates
                    FROM movies m
                    LEFT JOIN showtimes s ON m.movie_id = s.movie_id
                    LEFT JOIN theaters t ON s.theater_id = t.theater_id
                    WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if (!empty($search_term)) {
        $movie_query .= " AND (m.title LIKE ? OR m.description LIKE ? OR m.genre LIKE ? OR m.director LIKE ? OR m.cast LIKE ?)";
        $search_param = "%$search_term%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "sssss";
    }
    
    if (!empty($filter_genre)) {
        $movie_query .= " AND m.genre LIKE ?";
        $params[] = "%$filter_genre%";
        $types .= "s";
    }
    
    if (!empty($filter_language)) {
        $movie_query .= " AND m.language = ?";
        $params[] = $filter_language;
        $types .= "s";
    }
    
    if (!empty($filter_location)) {
        $movie_query .= " AND t.city = ?";
        $params[] = $filter_location;
        $types .= "s";
    }
    
    if (!empty($filter_theater)) {
        $movie_query .= " AND t.theater_id = ?";
        $params[] = $filter_theater;
        $types .= "i";
    }
    
    $movie_query .= " GROUP BY m.movie_id ORDER BY m.release_date DESC";
    
    $stmt = $conn->prepare($movie_query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $movies[] = $row;
    }
    
    // Search for theaters
    $theater_query = "SELECT t.*, 
                     COUNT(DISTINCT s.movie_id) as movie_count,
                     GROUP_CONCAT(DISTINCT m.title) as movies
                     FROM theaters t
                     LEFT JOIN showtimes s ON t.theater_id = s.theater_id
                     LEFT JOIN movies m ON s.movie_id = m.movie_id
                     WHERE 1=1";
    
    $theater_params = [];
    $theater_types = "";
    
    if (!empty($search_term)) {
        $theater_query .= " AND (t.name LIKE ? OR t.location LIKE ? OR t.address LIKE ? OR t.city LIKE ?)";
        $search_param = "%$search_term%";
        $theater_params[] = $search_param;
        $theater_params[] = $search_param;
        $theater_params[] = $search_param;
        $theater_params[] = $search_param;
        $theater_types .= "ssss";
    }
    
    if (!empty($filter_location)) {
        $theater_query .= " AND t.city = ?";
        $theater_params[] = $filter_location;
        $theater_types .= "s";
    }
    
    if (!empty($filter_theater)) {
        $theater_query .= " AND t.theater_id = ?";
        $theater_params[] = $filter_theater;
        $theater_types .= "i";
    }
    
    $theater_query .= " GROUP BY t.theater_id ORDER BY t.name";
    
    $stmt = $conn->prepare($theater_query);
    if (!empty($theater_params)) {
        $stmt->bind_param($theater_types, ...$theater_params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $theaters[] = $row;
    }
}

// Get user's location from session if available
$user_lat = $_SESSION['user_lat'] ?? null;
$user_lng = $_SESSION['user_lng'] ?? null;

// If we have user location, calculate distances to theaters
if ($user_lat && $user_lng && !empty($theaters)) {
    foreach ($theaters as &$theater) {
        if (!empty($theater['latitude']) && !empty($theater['longitude'])) {
            $theater['distance'] = calculateDistance($user_lat, $user_lng, $theater['latitude'], $theater['longitude']);
        } else {
            $theater['distance'] = null;
        }
    }
    
    // Sort theaters by distance
    usort($theaters, function($a, $b) {
        if ($a['distance'] === null) return 1;
        if ($b['distance'] === null) return -1;
        return $a['distance'] <=> $b['distance'];
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Movie Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .search-container {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .filter-section {
            background-color: rgba(0, 0, 0, 0.5);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .movie-card, .theater-card {
            background-color: rgba(0, 0, 0, 0.6);
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s;
            height: 100%;
        }
        
        .movie-card:hover, .theater-card:hover {
            transform: translateY(-5px);
        }
        
        .movie-poster {
            height: 300px;
            object-fit: cover;
        }
        
        .theater-image {
            height: 200px;
            object-fit: cover;
        }
        
        .result-tabs .nav-link {
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 0;
            background-color: transparent;
        }
        
        .result-tabs .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-bottom: 2px solid #ff5722;
            color: #ff5722;
        }
        
        .distance-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-5">
        <div class="search-container">
            <h2 class="text-center text-light mb-4">Search Movies & Theaters</h2>
            
            <form action="search.php" method="GET" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="text" name="q" class="form-control form-control-lg" placeholder="Search for movies, theaters..." value="<?php echo htmlspecialchars($search_term); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-lg w-100">Search</button>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-light btn-lg w-100" data-bs-toggle="collapse" data-bs-target="#filterOptions">Filters</button>
                    </div>
                    <div class="col-md-2">
                        <button type="button" id="getNearbyTheaters" class="btn btn-success btn-lg w-100">Nearby</button>
                    </div>
                </div>
                
                <div class="collapse mt-3" id="filterOptions">
                    <div class="filter-section">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label text-light">Genre</label>
                                <select name="genre" class="form-select">
                                    <option value="">All Genres</option>
                                    <?php foreach ($genres as $genre): ?>
                                        <option value="<?php echo htmlspecialchars($genre); ?>" <?php echo ($filter_genre == $genre) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($genre); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-light">Language</label>
                                <select name="language" class="form-select">
                                    <option value="">All Languages</option>
                                    <?php foreach ($languages as $language): ?>
                                        <option value="<?php echo htmlspecialchars($language); ?>" <?php echo ($filter_language == $language) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($language); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-light">Location</label>
                                <select name="location" class="form-select">
                                    <option value="">All Locations</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?php echo htmlspecialchars($city); ?>" <?php echo ($filter_location == $city) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($city); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-light">Theater</label>
                                <select name="theater" class="form-select">
                                    <option value="">All Theaters</option>
                                    <?php foreach ($all_theaters as $theater): ?>
                                        <option value="<?php echo $theater['theater_id']; ?>" <?php echo ($filter_theater == $theater['theater_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($theater['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            
            <?php if (!empty($search_term) || !empty($filter_genre) || !empty($filter_language) || !empty($filter_location) || !empty($filter_theater)): ?>
                <div class="result-summary text-light">
                    <p>Found <?php echo count($movies); ?> movies and <?php echo count($theaters); ?> theaters matching your search.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($movies) || !empty($theaters)): ?>
            <ul class="nav nav-tabs result-tabs mb-4" id="resultTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="movies-tab" data-bs-toggle="tab" data-bs-target="#movies" type="button" role="tab" aria-controls="movies" aria-selected="true">
                        Movies (<?php echo count($movies); ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="theaters-tab" data-bs-toggle="tab" data-bs-target="#theaters" type="button" role="tab" aria-controls="theaters" aria-selected="false">
                        Theaters (<?php echo count($theaters); ?>)
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="resultTabsContent">
                <div class="tab-pane fade show active" id="movies" role="tabpanel" aria-labelledby="movies-tab">
                    <?php if (!empty($movies)): ?>
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                            <?php foreach ($movies as $movie): ?>
                                <div class="col">
                                    <div class="movie-card">
                                        <img src="uploads/<?php echo htmlspecialchars($movie['image']); ?>" class="movie-poster w-100" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                                        <div class="card-body p-3 text-light">
                                            <h5 class="card-title"><?php echo htmlspecialchars($movie['title']); ?></h5>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($movie['genre'] ?? 'N/A'); ?></span>
                                                <span class="badge bg-warning text-dark"><?php echo $movie['rating']; ?>/10</span>
                                            </div>
                                            <p class="card-text small">
                                                <?php if (!empty($movie['language'])): ?>
                                                    <strong>Language:</strong> <?php echo htmlspecialchars($movie['language']); ?><br>
                                                <?php endif; ?>
                                                <?php if (!empty($movie['duration'])): ?>
                                                    <strong>Duration:</strong> <?php echo $movie['duration']; ?> mins<br>
                                                <?php endif; ?>
                                                <?php if (!empty($movie['release_date'])): ?>
                                                    <strong>Release:</strong> <?php echo date('d M Y', strtotime($movie['release_date'])); ?>
                                                <?php endif; ?>
                                            </p>
                                            <a href="movie_details.php?id=<?php echo $movie['movie_id']; ?>" class="btn btn-outline-light btn-sm w-100">View Details</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No movies found matching your search criteria.</div>
                    <?php endif; ?>
                </div>
                
                <div class="tab-pane fade" id="theaters" role="tabpanel" aria-labelledby="theaters-tab">
                    <?php if (!empty($theaters)): ?>
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                            <?php foreach ($theaters as $theater): ?>
                                <div class="col">
                                    <div class="theater-card position-relative">
                                        <?php if (!empty($theater['image'])): ?>
                                            <img src="uploads/theaters/<?php echo htmlspecialchars($theater['image']); ?>" class="theater-image w-100" alt="<?php echo htmlspecialchars($theater['name']); ?>">
                                        <?php else: ?>
                                            <div class="theater-image w-100 bg-dark d-flex align-items-center justify-content-center">
                                                <h3 class="text-light"><?php echo htmlspecialchars($theater['name']); ?></h3>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($theater['distance'])): ?>
                                            <div class="distance-badge">
                                                <i class="fas fa-map-marker-alt"></i> 
                                                <?php echo number_format($theater['distance'], 1); ?> km
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="card-body p-3 text-light">
                                            <h5 class="card-title"><?php echo htmlspecialchars($theater['name']); ?></h5>
                                            <p class="card-text small">
                                                <i class="fas fa-map-marker-alt"></i> 
                                                <?php echo htmlspecialchars($theater['location']); ?>
                                                <?php if (!empty($theater['city'])): ?>
                                                    , <?php echo htmlspecialchars($theater['city']); ?>
                                                <?php endif; ?>
                                                <?php if (!empty($theater['pincode'])): ?>
                                                    - <?php echo htmlspecialchars($theater['pincode']); ?>
                                                <?php endif; ?>
                                            </p>
                                            
                                            <?php if (!empty($theater['movies'])): ?>
                                                <p class="small text-muted">
                                                    <strong>Now Showing:</strong> 
                                                    <?php echo htmlspecialchars(substr($theater['movies'], 0, 50) . (strlen($theater['movies']) > 50 ? '...' : '')); ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex justify-content-between mt-3">
                                                <a href="theater_details.php?id=<?php echo $theater['theater_id']; ?>" class="btn btn-outline-light btn-sm">View Details</a>
                                                <?php if (!empty($theater['latitude']) && !empty($theater['longitude'])): ?>
                                                    <a href="https://maps.google.com/?q=<?php echo $theater['latitude']; ?>,<?php echo $theater['longitude']; ?>" target="_blank" class="btn btn-outline-success btn-sm">
                                                        <i class="fas fa-directions"></i> Directions
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No theaters found matching your search criteria.</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif (!empty($search_term) || !empty($filter_genre) || !empty($filter_language) || !empty($filter_location) || !empty($filter_theater)): ?>
            <div class="alert alert-info">No results found matching your search criteria.</div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
        document.getElementById('getNearbyTheaters').addEventListener('click', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    // Store user location in session via AJAX
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    fetch('store_location.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `lat=${lat}&lng=${lng}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Redirect to search with nearby parameter
                            window.location.href = 'search.php?nearby=1';
                        }
                    });
                }, function() {
                    alert('Unable to access your location. Please enable location services.');
                });
            } else {
                alert('Geolocation is not supported by your browser.');
            }
        });
    </script>
</body>
</html>