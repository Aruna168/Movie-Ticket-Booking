<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

// Handle Add Movie
if (isset($_POST['add_movie'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $rating = $_POST['rating'];
    $image = $_FILES['image']['name'];
    $target = "uploads/" . basename($image);

    $sql = "INSERT INTO movies (title, description, rating, image) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssds", $title, $description, $rating, $image);
    $stmt->execute();

    move_uploaded_file($_FILES['image']['tmp_name'], $target);
    header("Location: manage_movies.php");
    exit();
}

// Handle Delete Movie
if (isset($_GET['delete'])) {
    $movie_id = $_GET['delete'];
    $conn->query("DELETE FROM movies WHERE movie_id = $movie_id");
    header("Location: manage_movies.php");
    exit();
}

// Fetch movies
$result = $conn->query("SELECT * FROM movies ORDER BY movie_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Movies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">
<div class="container my-5">
    <h2 class="text-center mb-4">Manage Movies</h2>

    <!-- Add Movie Form -->
    <form action="manage_movies.php" method="POST" enctype="multipart/form-data" class="bg-secondary p-4 rounded">
        <h4>Add New Movie</h4>
        <div class="mb-3">
            <label>Movie Title</label>
            <input type="text" name="title" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control" required></textarea>
        </div>
        <div class="mb-3">
            <label>Rating (out of 10)</label>
            <input type="number" step="0.1" name="rating" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Upload Poster</label>
            <input type="file" name="image" class="form-control" required>
        </div>
        <button type="submit" name="add_movie" class="btn btn-light">Add Movie</button>
    </form>

    <!-- Movie Table -->
    <div class="mt-5">
        <h4>Existing Movies</h4>
        <table class="table table-dark table-bordered">
            <thead>
                <tr>
                    <th>Poster</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Rating</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($movie = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><img src="uploads/<?php echo $movie['image']; ?>" width="80" height="100" alt="Movie Poster"></td>
                        <td><?php echo htmlspecialchars($movie['title']); ?></td>
                        <td><?php echo htmlspecialchars($movie['description']); ?></td>
                        <td><?php echo $movie['rating']; ?>/10</td>
                        <td>
                            <a href="edit_movie.php?id=<?php echo $movie['movie_id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="manage_movies.php?delete=<?php echo $movie['movie_id']; ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('Are you sure you want to delete this movie?');">Delete</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
