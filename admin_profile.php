<?php
session_start();
include("includes/db.php");

$admin_id = $_SESSION['user_id']; // assuming login sets user_id in session

// Fetch admin details
$stmt = $conn->prepare("SELECT name, email, phone, profile_pic, last_login, bio, address FROM users WHERE user_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($name, $email, $phone, $profile_pic, $last_login, $bio, $address);
$stmt->fetch();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = $_POST['name'];
    $new_email = $_POST['email'];
    $new_phone = $_POST['phone'];
    $new_bio = $_POST['bio'];
    $new_address = $_POST['address'];

    // Handle profile pic upload
    if ($_FILES['profile_pic']['name']) {
        $target_dir = "uploads/";
        $new_profile_pic = basename($_FILES["profile_pic"]["name"]);
        $target_file = $target_dir . $new_profile_pic;

        // Move the uploaded file to the uploads folder
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
            // If file uploaded successfully, use the new profile pic
            $profile_pic = $new_profile_pic;
        }
    }

    // Update the database with the new information
    $update = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, profile_pic=?, bio=?, address=? WHERE user_id=?");
    $update->bind_param("ssssssi", $new_name, $new_email, $new_phone, $profile_pic, $new_bio, $new_address, $admin_id);
    $update->execute();
    $update->close();

    echo "<script>alert('Profile updated successfully'); window.location.href='admin_profile.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7fa;
        }

        .profile-container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .profile-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 10px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        input[type="file"] {
            padding: 0;
        }

        label {
            font-weight: bold;
            font-size: 16px;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #2e86de;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #1b4f72;
        }

        .last-login, .bio, .address {
            font-size: 14px;
            color: #555;
        }

        .form-group {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="profile-container">
    <h2>Admin Profile</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Profile Picture</label><br>
            <?php if (!empty($profile_pic) && $profile_pic !== 'default-avatar.jpg') : ?>
                <img src="uploads/<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Pic" class="profile-img"><br>
            <?php else : ?>
                <img src="uploads/default-avatar.jpg" alt="Default" class="profile-img"><br>
            <?php endif; ?>
            <input type="file" name="profile_pic">
        </div>

        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>

        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
        </div>

        <div class="form-group">
            <label>Bio</label>
            <textarea name="bio"><?php echo htmlspecialchars($bio); ?></textarea>
        </div>

        <div class="form-group">
            <label>Address</label>
            <textarea name="address"><?php echo htmlspecialchars($address); ?></textarea>
        </div>

        <div class="form-group">
            <label>Last Login</label>
            <input type="text" value="<?php echo htmlspecialchars($last_login); ?>" disabled>
        </div>

        <button type="submit">Update Profile</button>
    </form>
</div>

</body>
</html>
