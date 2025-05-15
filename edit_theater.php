<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

$theater_id = $_GET['id'] ?? 0;
$success_message = '';
$error_message = '';

// Fetch theater details
$stmt = $conn->prepare("SELECT * FROM theaters WHERE theater_id = ?");
$stmt->bind_param("i", $theater_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_theaters.php");
    exit();
}

$theater = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_theater'])) {
    $name = $_POST['name'];
    $location = $_POST['location'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $pincode = $_POST['pincode'];
    $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : null;
    $contact_phone = $_POST['contact_phone'];
    $contact_email = $_POST['contact_email'];
    $total_seats = $_POST['total_seats'];
    
    // Handle image upload
    $image = $theater['image']; // Keep existing image by default
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = "uploads/theaters/";
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $new_filename = 'theater_' . $theater_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $image = $new_filename;
        } else {
            $error_message = "Failed to upload image.";
        }
    }
    
    // Update theater information
    $stmt = $conn->prepare("UPDATE theaters SET name = ?, location = ?, address = ?, city = ?, 
                           state = ?, pincode = ?, latitude = ?, longitude = ?, 
                           contact_phone = ?, contact_email = ?, total_seats = ?, image = ? 
                           WHERE theater_id = ?");
    
    $stmt->bind_param("ssssssddssis", $name, $location, $address, $city, $state, $pincode, 
                     $latitude, $longitude, $contact_phone, $contact_email, $total_seats, $image, $theater_id);
    
    if ($stmt->execute()) {
        $success_message = "Theater information updated successfully!";
        
        // Refresh theater data
        $stmt = $conn->prepare("SELECT * FROM theaters WHERE theater_id = ?");
        $stmt->bind_param("i", $theater_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $theater = $result->fetch_assoc();
    } else {
        $error_message = "Error updating theater: " . $conn->error;
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Theater - <?php echo htmlspecialchars($theater['name']); ?></title>
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
        }
        .form-control, .form-select {
            background-color: #2d2d2d;
            border: 1px solid #444;
            color: #fff;
        }
        .form-control:focus, .form-select:focus {
            background-color: #2d2d2d;
            color: #fff;
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .map-container {
            height: 300px;
            border-radius: 10px;
            overflow: hidden;
        }
        .theater-image-preview {
            max-height: 200px;
            border-radius: 5px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Edit Theater</h1>
            <a href="manage_theaters.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left"></i> Back to Theaters
            </a>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <h4 class="mb-3">Basic Information</h4>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Theater Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($theater['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="location" class="form-label">Location (Area/Neighborhood)</label>
                                <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($theater['location']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Full Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($theater['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($theater['city'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="state" class="form-label">State</label>
                                    <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($theater['state'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="pincode" class="form-label">Pincode</label>
                                    <input type="text" class="form-control" id="pincode" name="pincode" value="<?php echo htmlspecialchars($theater['pincode'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="total_seats" class="form-label">Total Seats</label>
                                <input type="number" class="form-control" id="total_seats" name="total_seats" value="<?php echo $theater['total_seats']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h4 class="mb-3">Contact & Location</h4>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="contact_phone" class="form-label">Contact Phone</label>
                                    <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($theater['contact_phone'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="contact_email" class="form-label">Contact Email</label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($theater['contact_email'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="latitude" class="form-label">Latitude</label>
                                    <input type="text" class="form-control" id="latitude" name="latitude" value="<?php echo $theater['latitude'] ?? ''; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="longitude" class="form-label">Longitude</label>
                                    <input type="text" class="form-control" id="longitude" name="longitude" value="<?php echo $theater['longitude'] ?? ''; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="image" class="form-label">Theater Image</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                
                                <?php if (!empty($theater['image'])): ?>
                                    <div class="mt-2">
                                        <p>Current Image:</p>
                                        <img src="uploads/theaters/<?php echo htmlspecialchars($theater['image']); ?>" class="theater-image-preview" alt="Theater Image">
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Map Location</label>
                                <div id="map" class="map-container"></div>
                                <small class="text-muted">Click on the map to set the theater's location</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <button type="submit" name="update_theater" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize map when API is loaded
        function initMap() {
            const defaultLat = <?php echo !empty($theater['latitude']) ? $theater['latitude'] : 20.5937; ?>;
            const defaultLng = <?php echo !empty($theater['longitude']) ? $theater['longitude'] : 78.9629; ?>;
            
            const mapOptions = {
                center: { lat: defaultLat, lng: defaultLng },
                zoom: 15,
                styles: [
                    { elementType: "geometry", stylers: [{ color: "#242f3e" }] },
                    { elementType: "labels.text.stroke", stylers: [{ color: "#242f3e" }] },
                    { elementType: "labels.text.fill", stylers: [{ color: "#746855" }] },
                ]
            };
            
            const map = new google.maps.Map(document.getElementById("map"), mapOptions);
            
            // Add marker for theater location
            let marker = new google.maps.Marker({
                position: { lat: defaultLat, lng: defaultLng },
                map: map,
                draggable: true,
                title: "Theater Location"
            });
            
            // Update coordinates when marker is dragged
            google.maps.event.addListener(marker, 'dragend', function(event) {
                document.getElementById("latitude").value = event.latLng.lat();
                document.getElementById("longitude").value = event.latLng.lng();
            });
            
            // Set marker when clicking on map
            google.maps.event.addListener(map, 'click', function(event) {
                marker.setPosition(event.latLng);
                document.getElementById("latitude").value = event.latLng.lat();
                document.getElementById("longitude").value = event.latLng.lng();
            });
        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>
</body>
</html>