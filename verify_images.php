<?php
require_once('db_connect.php');

// Check if directories exist
echo "<h2>Directory Check</h2>";
$directories = ['uploads', 'uploads/posters'];
foreach ($directories as $dir) {
    if (file_exists($dir)) {
        echo "$dir directory exists<br>";
    } else {
        echo "$dir directory does NOT exist<br>";
    }
}

// Get all movies
$result = $conn->query("SELECT movie_id, title, image FROM movies");

echo "<h2>Movie Images Verification</h2>";
echo "<table border='1'>";
echo "<tr><th>Movie ID</th><th>Title</th><th>Image Path in DB</th><th>Full Path</th><th>Status</th><th>File Size</th></tr>";

while ($movie = $result->fetch_assoc()) {
    $image_path = $movie['image'];
    $full_path = "uploads/posters/" . $image_path;
    
    // Remove any double path if it exists
    if (strpos($image_path, 'uploads/posters/') === 0) {
        $image_path = substr($image_path, strlen('uploads/posters/'));
        $full_path = "uploads/posters/" . $image_path;
    }
    
    $status = file_exists($full_path) ? "Exists" : "Missing";
    $file_size = file_exists($full_path) ? filesize($full_path) . " bytes" : "N/A";
    
    echo "<tr>";
    echo "<td>" . $movie['movie_id'] . "</td>";
    echo "<td>" . htmlspecialchars($movie['title']) . "</td>";
    echo "<td>" . htmlspecialchars($movie['image']) . "</td>";
    echo "<td>" . htmlspecialchars($full_path) . "</td>";
    echo "<td>" . $status . "</td>";
    echo "<td>" . $file_size . "</td>";
    echo "</tr>";
}

echo "</table>";

// List all files in uploads/posters directory
echo "<h2>Files in uploads/posters directory</h2>";
if (file_exists('uploads/posters')) {
    $files = scandir('uploads/posters');
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>" . htmlspecialchars($file) . " - " . filesize('uploads/posters/' . $file) . " bytes</li>";
        }
    }
    echo "</ul>";
} else {
    echo "uploads/posters directory does not exist";
}
?>