<?php
require_once('db_connect.php');

// Check if uploads/posters directory exists
if (!file_exists('uploads/posters')) {
    echo "Creating uploads/posters directory...<br>";
    mkdir('uploads/posters', 0777, true);
    echo "Directory created.<br>";
}

// Get all movies
$result = $conn->query("SELECT movie_id, title, image FROM movies");

echo "<h2>Movie Images Check</h2>";
echo "<table border='1'>";
echo "<tr><th>Movie ID</th><th>Title</th><th>Image Path</th><th>Status</th></tr>";

while ($movie = $result->fetch_assoc()) {
    $imagePath = "uploads/posters/" . $movie['image'];
    $status = file_exists($imagePath) ? "Found" : "Missing";
    
    echo "<tr>";
    echo "<td>" . $movie['movie_id'] . "</td>";
    echo "<td>" . htmlspecialchars($movie['title']) . "</td>";
    echo "<td>" . htmlspecialchars($imagePath) . "</td>";
    echo "<td>" . $status . "</td>";
    echo "</tr>";
}

echo "</table>";
?>