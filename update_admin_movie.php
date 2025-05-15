<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

// Execute SQL to update schema
$sql_file = file_get_contents('update_movies_schema.sql');
if ($conn->multi_query($sql_file)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
        // Check if there are more result sets
    } while ($conn->more_results() && $conn->next_result());
}

// Check for errors
if ($conn->error) {
    echo "Error updating schema: " . $conn->error;
    exit();
}

echo "<div class='alert alert-success'>Movie database schema updated successfully!</div>";
echo "<script>setTimeout(function() { window.location.href = 'manage_movies.php'; }, 2000);</script>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Updating Database Schema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
    <div class="container mt-5">
        <h2>Updating Database Schema</h2>
        <p>Please wait while we update the database structure...</p>
        <div class="progress">
            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
        </div>
    </div>
</body>
</html>