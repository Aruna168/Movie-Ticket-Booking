<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

// Add theater
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_theater'])) {
    $name = $_POST['name'];
    $location = $_POST['location'];
    $total_seats = $_POST['total_seats'];

    if (!empty($name) && !empty($location) && is_numeric($total_seats)) {
        $stmt = $conn->prepare("INSERT INTO theaters (name, location, total_seats) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $name, $location, $total_seats);
        $stmt->execute();
        $stmt->close();
    }
}

// Delete theater
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM theaters WHERE theater_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
}

// Fetch theaters
$theaters = [];
$result = $conn->query("SELECT * FROM theaters ORDER BY theater_id DESC");
while ($row = $result->fetch_assoc()) {
    $theaters[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Theaters</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
<div class="container mt-5">
    <h2 class="mb-4">Manage Theaters</h2>

    <!-- Add Theater Form -->
    <form method="POST" class="mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="name" placeholder="Theater Name" required>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control" name="location" placeholder="Location" required>
            </div>
            <div class="col-md-3">
                <input type="number" class="form-control" name="total_seats" placeholder="Total Seats" required>
            </div>
            <div class="col-md-1">
                <button type="submit" name="add_theater" class="btn btn-success w-100">Add</button>
            </div>
        </div>
    </form>

    <!-- Theater List -->
    <?php if (!empty($theaters)): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-dark">
                <thead class="table-light text-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Location</th>
                        <th>Total Seats</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($theaters as $theater): ?>
                    <tr>
                        <td><?= $theater['theater_id'] ?></td>
                        <td><?= htmlspecialchars($theater['name']) ?></td>
                        <td><?= htmlspecialchars($theater['location']) ?></td>
                        <td><?= $theater['total_seats'] ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this theater?');" class="d-inline">
                                <input type="hidden" name="delete_id" value="<?= $theater['theater_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                            <!-- <a href="edit_theater.php?id=<?= $theater['theater_id'] ?>" class="btn btn-sm btn-primary">Edit</a> -->
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted">No theaters found.</p>
    <?php endif; ?>
</div>
</body>
</html>
