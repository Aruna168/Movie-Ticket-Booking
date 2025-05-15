<?php
session_start();
require_once('db_connect.php');

$theater_id = $_GET['id'] ?? 0;

// Fetch theater details
$stmt = $conn->prepare("SELECT * FROM theaters WHERE theater_id = ?");
$stmt->bind_param("i", $theater_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$theater = $result->fetch_assoc();
$stmt->close();

// Fetch current movies showing at this theater
$stmt = $conn->prepare("SELECT DISTINCT m.*, s.show_date, s.show_time, s.show_id 
                       FROM movies m 
                       JOIN showtimes s ON m.movie_id = s.movie_id 
                       WHERE s.theater_id = ? AND s.show_date >= CURDATE() 
                       ORDER BY s.show_date, s.show_time");
$stmt->bind_param("i", $theater_id);
$stmt->execute();
$movies_result = $stmt->get_result();
$movies = [];

while ($movie = $movies_result->fetch_assoc()) {
    $movie_id = $movie['movie_id'];
    
    if (!isset($movies[$movie_id])) {
        $movies[$movie_id] = [
            'details' => $movie,
            'dates' => []
        ];
    }
    
    $show_date = $movie['show_date'];
    if (!in_array($show_date, $movies[$movie_id]['dates'])) {
        $movies[$movie_id]['dates'][] = $show_date;
    }
}
$stmt->close();

// Get user's location from session if available
$user_lat = $_SESSION['user_lat'] ?? null;
$user_lng = $_SESSION['user_lng'] ?? null;
$distance = null;

// Calculate distance if we have both user and theater coordinates
if ($user_lat && $user_lng && !empty($theater['latitude']) && !empty($theater['longitude'])) {
    $earth_radius = 6371; // Radius of the earth in km
    $lat_diff = deg2rad($theater['latitude'] - $user_lat);
    $lng_diff = deg2rad($theater['longitude'] - $user_lng);
    
    $a = sin($lat_diff/2) * sin($lat_diff/2) + cos(deg2rad($user_lat)) * cos(deg2rad($theater['latitude'])) * sin($lng_diff/2) * sin($lng_diff/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $earth_radius * $c; // Distance in km
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($theater['name']); ?> - Movie Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .theater-header {
            background-size: cover;
            background-position: center;
            min-height: 300px;
            position: relative;
            color: white;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.8);
        }
        
        .theater-header-overlay {
            background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.8));
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 30px;
        }
        
        .theater-info-card {
            background-color: rgba(0,0,0,0.7);
            border-radius: 10px;
            padding: 20px;
            margin-top: -50px;
            position: relative;
            z-index: 10;
        }
        
        .movie-card {
            background-color: rgba(0,0,0,0.6);
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s;
            height: 100%;
        }
        
        .movie-card:hover {
            transform: translateY(-5px);
        }
        
        .movie-poster {
            height: 300px;
            object-fit: cover;
        }
        
        .date-badge {
            background-color: #ff5722;
            color: white;
            border-radius: 20px;
            padding: 5px 10px;
            margin-right: 5px;
            margin-bottom: 5px;
            display: inline-block;
            font-size: 0.8rem;
        }
        
        .map-container {
            height: 300px;
            border-radius: 10px;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="theater-header" style="background-image: url('<?php echo !empty($theater['image']) ? 'uploads/theaters/' . htmlspecialchars($theater['image']) : 'assets/images/neon-bg.jpg'; ?>');">
        <div class="theater-header-overlay">
            <div class="container">
                <h1 class="display-4"><?php echo htmlspecialchars($theater['name']); ?></h1>
                <p class="lead">
                    <i class="fas fa-map-marker-alt"></i> 
                    <?php echo htmlspecialchars($theater['location']); ?>
                    <?php if (!empty($theater['city'])): ?>
                        , <?php echo htmlspecialchars($theater['city']); ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8">
                <div class="theater-info-card mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Theater Information</h4>
                            <p>
                                <?php if (!empty($theater['address'])): ?>
                                    <strong><i class="fas fa-map-marked-alt"></i> Address:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($theater['address'])); ?><br>
                                    <?php if (!empty($theater['city']) || !empty($theater['state']) || !empty($theater['pincode'])): ?>
                                        <?php echo htmlspecialchars($theater['city'] ?? ''); ?>
                                        <?php echo !empty($theater['state']) ? ', ' . htmlspecialchars($theater['state']) : ''; ?>
                                        <?php echo !empty($theater['pincode']) ? ' - ' . htmlspecialchars($theater['pincode']) : ''; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </p>
                            
                            <?php if (!empty($theater['contact_phone']) || !empty($theater['contact_email'])): ?>
                                <p>
                                    <?php if (!empty($theater['contact_phone'])): ?>
                                        <strong><i class="fas fa-phone"></i> Phone:</strong> <?php echo htmlspecialchars($theater['contact_phone']); ?><br>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($theater['contact_email'])): ?>
                                        <strong><i class="fas fa-envelope"></i> Email:</strong> <?php echo htmlspecialchars($theater['contact_email']); ?>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($distance !== null): ?>
                                <p>
                                    <strong><i class="fas fa-route"></i> Distance:</strong> 
                                    <?php echo number_format($distance, 1); ?> km from your location
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <?php if (!empty($theater['facilities'])): ?>
                                <h4>Facilities</h4>
                                <ul class="list-unstyled">
                                    <?php 
                                    $facilities = json_decode($theater['facilities'], true);
                                    if (is_array($facilities)) {
                                        foreach ($facilities as $facility) {
                                            echo '<li><i class="fas fa-check-circle text-success"></i> ' . htmlspecialchars($facility) . '</li>';
                                        }
                                    }
                                    ?>
                                </ul>
                            <?php endif; ?>
                            
                            <?php if (!empty($theater['latitude']) && !empty($theater['longitude'])): ?>
                                <div class="d-grid gap-2">
                                    <a href="https://maps.google.com/?q=<?php echo $theater['latitude']; ?>,<?php echo $theater['longitude']; ?>" 
                                       target="_blank" class="btn btn-outline-light">
                                        <i class="fas fa-directions"></i> Get Directions
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <h3 class="mb-4">Now Showing</h3>
                
                <?php if (count($movies) > 0): ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php foreach ($movies as $movie_data): ?>
                            <?php $movie = $movie_data['details']; ?>
                            <div class="col">
                                <div class="movie-card">
                                    <img src="uploads/<?php echo htmlspecialchars($movie['image']); ?>" class="movie-poster w-100" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                                    <div class="card-body p-3 text-light">
                                        <h5 class="card-title"><?php echo htmlspecialchars($movie['title']); ?></h5>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($movie['genre'] ?? 'N/A'); ?></span>
                                            <span class="badge bg-warning text-dark"><?php echo $movie['rating']; ?>/10</span>
                                        </div>
                                        
                                        <p class="small mb-2">Available on:</p>
                                        <div class="mb-3">
                                            <?php foreach ($movie_data['dates'] as $date): ?>
                                                <span class="date-badge">
                                                    <?php echo date('d M', strtotime($date)); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <a href="movie_details.php?id=<?php echo $movie['movie_id']; ?>&theater=<?php echo $theater_id; ?>" class="btn btn-outline-light btn-sm w-100">Book Tickets</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No movies currently scheduled at this theater.</div>
                <?php endif; ?>
            </div>
            
            <div class="col-lg-4">
                <?php if (!empty($theater['latitude']) && !empty($theater['longitude'])): ?>
                    <div class="mb-4">
                        <h4>Location</h4>
                        <div id="map" class="map-container"></div>
                    </div>
                <?php endif; ?>
                
                <div class="card bg-dark">
                    <div class="card-header">
                        <h4>Nearby Theaters</h4>
                    </div>
                    <div class="card-body" id="nearbyTheaters">
                        <div class="text-center">
                            <div class="spinner-border text-light" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading nearby theaters...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize map when API is loaded
        function initMap() {
            <?php if (!empty($theater['latitude']) && !empty($theater['longitude'])): ?>
                const theaterLocation = { 
                    lat: <?php echo $theater['latitude']; ?>, 
                    lng: <?php echo $theater['longitude']; ?> 
                };
                
                const mapOptions = {
                    center: theaterLocation,
                    zoom: 15,
                    styles: [
                        { elementType: "geometry", stylers: [{ color: "#242f3e" }] },
                        { elementType: "labels.text.stroke", stylers: [{ color: "#242f3e" }] },
                        { elementType: "labels.text.fill", stylers: [{ color: "#746855" }] },
                    ]
                };
                
                const map = new google.maps.Map(document.getElementById("map"), mapOptions);
                
                // Add marker for theater location
                new google.maps.Marker({
                    position: theaterLocation,
                    map: map,
                    title: "<?php echo addslashes($theater['name']); ?>"
                });
            <?php endif; ?>
        }
        
        // Load nearby theaters
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($theater['latitude']) && !empty($theater['longitude'])): ?>
                fetch('get_nearby_theaters.php?theater_id=<?php echo $theater_id; ?>&lat=<?php echo $theater['latitude']; ?>&lng=<?php echo $theater['longitude']; ?>')
                    .then(response => response.json())
                    .then(data => {
                        const container = document.getElementById('nearbyTheaters');
                        
                        if (data.length === 0) {
                            container.innerHTML = '<p class="text-muted">No nearby theaters found.</p>';
                            return;
                        }
                        
                        let html = '<ul class="list-group list-group-flush bg-transparent">';
                        
                        data.forEach(theater => {
                            html += `
                                <li class="list-group-item bg-transparent text-light border-light">
                                    <a href="theater_details.php?id=${theater.theater_id}" class="text-decoration-none text-light">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">${theater.name}</h6>
                                                <small class="text-muted">${theater.location}</small>
                                            </div>
                                            <span class="badge bg-primary rounded-pill">${theater.distance.toFixed(1)} km</span>
                                        </div>
                                    </a>
                                </li>
                            `;
                        });
                        
                        html += '</ul>';
                        container.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Error fetching nearby theaters:', error);
                        document.getElementById('nearbyTheaters').innerHTML = '<p class="text-danger">Error loading nearby theaters.</p>';
                    });
            <?php else: ?>
                document.getElementById('nearbyTheaters').innerHTML = '<p class="text-muted">Location information not available for this theater.</p>';
            <?php endif; ?>
        });
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>
</body>
</html>