<?php
require_once('db_connect.php');

// Create directories if they don't exist
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
    echo "Created uploads directory<br>";
}

if (!file_exists('uploads/posters')) {
    mkdir('uploads/posters', 0777, true);
    echo "Created uploads/posters directory<br>";
}

// Create a simple default image file
$default_image_content = file_get_contents('https://via.placeholder.com/300x450/cccccc/333333?text=No+Image');
if ($default_image_content) {
    file_put_contents('uploads/posters/default.jpg', $default_image_content);
    echo "Created default image<br>";
}

// Update the database to fix image paths
$result = $conn->query("SELECT movie_id, image FROM movies");
$updated = 0;

echo "<h2>Updating Movie Images</h2>";
echo "<table border='1'>";
echo "<tr><th>Movie ID</th><th>Original Path</th><th>New Path</th><th>Status</th></tr>";

while ($movie = $result->fetch_assoc()) {
    $original_path = $movie['image'];
    $status = "No change needed";
    
    // If the path starts with "uploads/posters/", remove that prefix
    if (strpos($original_path, 'uploads/posters/') === 0) {
        $new_path = substr($original_path, strlen('uploads/posters/'));
        
        // Update the database
        $update_stmt = $conn->prepare("UPDATE movies SET image = ? WHERE movie_id = ?");
        $update_stmt->bind_param("si", $new_path, $movie['movie_id']);
        
        if ($update_stmt->execute()) {
            $status = "Updated";
            $updated++;
        } else {
            $status = "Failed: " . $conn->error;
        }
        
        $update_stmt->close();
    } else {
        $new_path = $original_path;
    }
    
    echo "<tr>";
    echo "<td>" . $movie['movie_id'] . "</td>";
    echo "<td>" . htmlspecialchars($original_path) . "</td>";
    echo "<td>" . htmlspecialchars($new_path) . "</td>";
    echo "<td>" . $status . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "<p>Updated $updated movie records</p>";

// Create placeholder images for all movies
echo "<h2>Creating Placeholder Images</h2>";
$result = $conn->query("SELECT movie_id, title, image FROM movies");

while ($movie = $result->fetch_assoc()) {
    $image_path = 'uploads/posters/' . $movie['image'];
    
    // If the image doesn't exist, create a placeholder
    if (!file_exists($image_path)) {
        // Try to get a placeholder from placeholder.com with the movie title
        $title = urlencode($movie['title']);
        $placeholder_content = file_get_contents("https://via.placeholder.com/300x450/cccccc/333333?text=$title");
        
        if ($placeholder_content) {
            file_put_contents($image_path, $placeholder_content);
            echo "Created placeholder for: " . htmlspecialchars($movie['title']) . "<br>";
        } else {
            // If failed to get placeholder, copy the default image
            if (file_exists('uploads/posters/default.jpg')) {
                copy('uploads/posters/default.jpg', $image_path);
                echo "Copied default image for: " . htmlspecialchars($movie['title']) . "<br>";
            }
        }
    }
}

echo "<p>Fix complete! <a href='user_dashboard.php'>Return to dashboard</a></p>";
?>