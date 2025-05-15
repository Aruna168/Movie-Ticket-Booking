<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        echo "<script>alert('Please enter both email and password.'); window.location.href='login.html';</script>";
        exit();
    }

    // Fetch user from database
    $sql = "SELECT user_id, name, email, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $name, $db_email, $hashed_password, $role);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            // Set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $db_email;
            $_SESSION['role'] = $role;

            // Debugging Logs
            error_log("Login successful: $name ($role)");

            // Redirect properly
            if ($role === 'admin') {
                header("Location: admin_dashboard.php");
                exit();
            } else {
                header("Location: user_dashboard.php");
                exit();
            }
        } else {
            error_log("Login failed: Incorrect password");
            echo "<script>alert('Invalid email or password.'); window.location.href='login.html';</script>";
        }
    } else {
        error_log("Login failed: User not found");
        echo "<script>alert('User not found. Please register first.'); window.location.href='register.html';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
