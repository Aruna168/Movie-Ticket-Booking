<?php
require_once('db_connect.php');

// Check if the uploads directory exists
if (!file_exists('uploads')) {
    echo "The uploads directory does not exist.<br>";
    mkdir('uploads', 0777, true);
    echo "Created uploads directory.<br>";
}

// Check if the uploads/posters directory exists
if (!file_exists('uploads/posters')) {
    echo "The uploads/posters directory does not exist.<br>";
    mkdir('uploads/posters', 0777, true);
    echo "Created uploads/posters directory.<br>";
}

// Get all movies
$result = $conn->query("SELECT movie_id, title, image FROM movies");

echo "<h2>Movie Images Debug</h2>";
echo "<table border='1'>";
echo "<tr><th>Movie ID</th><th>Title</th><th>Image Column Value</th><th>uploads/ Path</th><th>uploads/posters/ Path</th></tr>";

while ($movie = $result->fetch_assoc()) {
    $imageName = $movie['image'];
    $uploadsPath = "uploads/" . $imageName;
    $postersPath = "uploads/posters/" . $imageName;
    
    $uploadsExists = file_exists($uploadsPath) ? "Exists" : "Missing";
    $postersExists = file_exists($postersPath) ? "Exists" : "Missing";
    
    echo "<tr>";
    echo "<td>" . $movie['movie_id'] . "</td>";
    echo "<td>" . htmlspecialchars($movie['title']) . "</td>";
    echo "<td>" . htmlspecialchars($imageName) . "</td>";
    echo "<td>" . $uploadsExists . "</td>";
    echo "<td>" . $postersExists . "</td>";
    echo "</tr>";
}

echo "</table>";

// Copy images from uploads to uploads/posters if needed
echo "<h2>Copying Images</h2>";
$result = $conn->query("SELECT image FROM movies");
$copied = 0;

while ($movie = $result->fetch_assoc()) {
    $imageName = $movie['image'];
    $uploadsPath = "uploads/" . $imageName;
    $postersPath = "uploads/posters/" . $imageName;
    
    if (file_exists($uploadsPath) && !file_exists($postersPath) && !empty($imageName)) {
        if (copy($uploadsPath, $postersPath)) {
            echo "Copied: " . htmlspecialchars($imageName) . " to uploads/posters/<br>";
            $copied++;
        } else {
            echo "Failed to copy: " . htmlspecialchars($imageName) . "<br>";
        }
    }
}

echo "<p>Copied $copied images from uploads to uploads/posters</p>";

// Create a default image if needed
if (!file_exists('uploads/posters/default.jpg')) {
    // Create a simple default image
    $image = imagecreatetruecolor(300, 450);
    $bg = imagecolorallocate($image, 200, 200, 200);
    $text_color = imagecolorallocate($image, 50, 50, 50);
    
    imagefilledrectangle($image, 0, 0, 300, 450, $bg);
    imagestring($image, 5, 100, 200, 'No Image', $text_color);
    
    imagejpeg($image, 'uploads/posters/default.jpg');
    imagedestroy($image);
    
    echo "<p>Created default poster image at uploads/posters/default.jpg</p>";
}

echo "<p>Debug complete. <a href='user_dashboard.php'>Return to dashboard</a></p>";
?>