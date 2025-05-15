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

// Fix the database entries - remove "uploads/posters/" prefix if it exists
$result = $conn->query("SELECT movie_id, image FROM movies");
$updated = 0;

echo "<h2>Fixing Database Entries</h2>";
echo "<table border='1'>";
echo "<tr><th>Movie ID</th><th>Original Path</th><th>New Path</th><th>Status</th></tr>";

while ($movie = $result->fetch_assoc()) {
    $original_path = $movie['image'];
    $new_path = $original_path;
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

// Download sample movie posters if none exist
echo "<h2>Downloading Sample Movie Posters</h2>";
$result = $conn->query("SELECT movie_id, title, image FROM movies");

while ($movie = $result->fetch_assoc()) {
    $image_path = 'uploads/posters/' . $movie['image'];
    
    // If the image doesn't exist, download a sample poster
    if (!file_exists($image_path)) {
        // Sample movie poster URLs (replace with your own if needed)
        $sample_posters = [
            'https://m.media-amazon.com/images/M/MV5BMDBmYTZjNjUtN2M1MS00MTQ2LTk2ODgtNzc2M2QyZGE5NTVjXkEyXkFqcGdeQXVyNzAwMjU2MTY@._V1_.jpg',
            'https://m.media-amazon.com/images/M/MV5BNzMwOGZhMzItNGY4ZS00YWQzLTkwNDYtNzRmODZkYjU1YTUyXkEyXkFqcGdeQXVyMTA3MDk2NDg2._V1_.jpg',
            'https://m.media-amazon.com/images/M/MV5BMTU0MjAwMDkxNV5BMl5BanBnXkFtZTgwMTA4ODIxNjM@._V1_.jpg',
            'https://m.media-amazon.com/images/M/MV5BMTc5MDE2ODcwNV5BMl5BanBnXkFtZTgwMzI2NzQ2NzM@._V1_.jpg',
            'https://m.media-amazon.com/images/M/MV5BYTdiZGY3OWMtODk5MC00YzA0LWE4OGEtNDgxNGE0MzJkNTgzXkEyXkFqcGdeQXVyMTUzMTg2ODkz._V1_.jpg'
        ];
        
        // Select a random poster
        $random_poster = $sample_posters[array_rand($sample_posters)];
        
        // Try to download the poster
        $poster_content = @file_get_contents($random_poster);
        
        if ($poster_content) {
            file_put_contents($image_path, $poster_content);
            echo "Downloaded sample poster for: " . htmlspecialchars($movie['title']) . "<br>";
        } else {
            echo "Failed to download poster for: " . htmlspecialchars($movie['title']) . "<br>";
        }
    }
}

echo "<p>Fix complete! <a href='user_dashboard.php'>Return to dashboard</a></p>";
?>