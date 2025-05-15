<?php
session_start();
require_once '../config.php';
require_once '../includes/helpers.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Super admin check
$stmt = $pdo->prepare("SELECT COUNT(*) FROM theater_admins WHERE user_id = ?");
$stmt->execute([$user_id]);
$isSuperAdmin = ($stmt->fetchColumn() == 0);

if (!$isSuperAdmin) {
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_admin'])) {
        $admin_user_id = (int)$_POST['user_id'];
        $theater_id = (int)$_POST['theater_id'];
        
        if ($admin_user_id && $theater_id) {
            try {
                // Check if user exists and is an admin
                $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
                $stmt->execute([$admin_user_id]);
                $role = $stmt->fetchColumn();
                
                if ($role !== 'admin') {
                    $_SESSION['error'] = "Selected user is not an admin.";
                } else {
                    // Check if assignment already exists
                    $stmt = $pdo->prepare("SELECT 1 FROM theater_admins WHERE user_id = ? AND theater_id = ?");
                    $stmt->execute([$admin_user_id, $theater_id]);
                    
                    if ($stmt->fetchColumn()) {
                        $_SESSION['error'] = "This admin is already assigned to this theater.";
                    } else {
                        // Add assignment
                        $stmt = $pdo->prepare("INSERT INTO theater_admins (user_id, theater_id) VALUES (?, ?)");
                        $stmt->execute([$admin_user_id, $theater_id]);
                        $_SESSION['success'] = "Theater admin assigned successfully.";
                    }
                }
            } catch (Exception $e) {
                $_SESSION['error'] = "Error: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Invalid user or theater selected.";
        }
    } elseif (isset($_POST['remove_admin'])) {
        $assignment_id = (int)$_POST['assignment_id'];
        
        if ($assignment_id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM theater_admins WHERE id = ?");
                $stmt->execute([$assignment_id]);
                $_SESSION['success'] = "Theater admin assignment removed successfully.";
            } catch (Exception $e) {
                $_SESSION['error'] = "Error: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Invalid assignment selected.";
        }
    }
}

// Get all admin users
$stmt = $pdo->prepare("SELECT user_id, name, email FROM users WHERE role = 'admin'");
$stmt->execute();
$admin_users = $stmt->fetchAll();

// Get all theaters
$stmt = $pdo->prepare("SELECT theater_id, name, location FROM theaters");
$stmt->execute();
$theaters = $stmt->fetchAll();

// Get all theater admin assignments
$stmt = $pdo->prepare("
    SELECT ta.id, ta.user_id, ta.theater_id, u.name as user_name, u.email, t.name as theater_name, t.location
    FROM theater_admins ta
    JOIN users u ON ta.user_id = u.user_id
    JOIN theaters t ON ta.theater_id = t.theater_id
    ORDER BY t.name, u.name
");
$stmt->execute();
$assignments = $stmt->fetchAll();

include_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Theater Admins</h2>
        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success'] ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error'] ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Assign Admin to Theater</h5>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-5">
                    <label for="user_id" class="form-label">Select Admin User</label>
                    <select name="user_id" id="user_id" class="form-select" required>
                        <option value="">-- Select Admin --</option>
                        <?php foreach ($admin_users as $user): ?>
                            <option value="<?= $user['user_id'] ?>">
                                <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="theater_id" class="form-label">Select Theater</label>
                    <select name="theater_id" id="theater_id" class="form-select" required>
                        <option value="">-- Select Theater --</option>
                        <?php foreach ($theaters as $theater): ?>
                            <option value="<?= $theater['theater_id'] ?>">
                                <?= htmlspecialchars($theater['name']) ?> (<?= htmlspecialchars($theater['location']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" name="add_admin" class="btn btn-primary w-100">Assign</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Current Theater Admin Assignments</h5>
        </div>
        <div class="card-body">
            <?php if (empty($assignments)): ?>
                <div class="alert alert-info">No theater admin assignments found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Admin</th>
                                <th>Email</th>
                                <th>Theater</th>
                                <th>Location</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($assignment['user_name']) ?></td>
                                    <td><?= htmlspecialchars($assignment['email']) ?></td>
                                    <td><?= htmlspecialchars($assignment['theater_name']) ?></td>
                                    <td><?= htmlspecialchars($assignment['location']) ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to remove this assignment?');">
                                            <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                                            <button type="submit" name="remove_admin" class="btn btn-sm btn-danger">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>