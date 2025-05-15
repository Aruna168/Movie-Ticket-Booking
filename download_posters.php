<?php
require_once('db_connect.php');

// Create posters directory if it doesn't exist
if (!file_exists('uploads/posters')) {
    mkdir('uploads/posters', 0777, true);
    echo "Created uploads/posters directory<br>";
}

// Get all movies
$result = $conn->query("SELECT movie_id, title, image FROM movies");

echo "<h2>Downloading Movie Posters</h2>";
echo "<table border='1'>";
echo "<tr><th>Movie ID</th><th>Title</th><th>Image Path</th><th>Status</th></tr>";

while ($movie = $result->fetch_assoc()) {
    $image_name = $movie['image'];
    $image_path = 'uploads/posters/' . $image_name;
    $status = "Already exists";
    
    // If the image doesn't exist, download a poster
    if (!file_exists($image_path)) {
        // Try to get a poster from OMDB API (you need an API key)
        $title = urlencode($movie['title']);
        $omdb_url = "http://www.omdbapi.com/?t=$title&apikey=YOUR_OMDB_API_KEY";
        
        // If you don't have an OMDB API key, use these sample posters instead
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
            $status = "Downloaded";
        } else {
            $status = "Download failed";
        }
    }
    
    echo "<tr>";
    echo "<td>" . $movie['movie_id'] . "</td>";
    echo "<td>" . htmlspecialchars($movie['title']) . "</td>";
    echo "<td>" . htmlspecialchars($image_path) . "</td>";
    echo "<td>" . $status . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "<p>Download complete! <a href='user_dashboard.php'>Return to dashboard</a></p>";
?>