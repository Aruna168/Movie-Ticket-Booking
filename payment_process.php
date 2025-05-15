<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['booking_id'])) {
    header("Location: login.php");
    exit();
}

$booking_id = $_POST['booking_id'];
$user_id = $_SESSION['user_id'];
$transaction_id = $_POST['transaction_id'] ?? '';

// Get booking details
$query = "SELECT b.booking_id, b.total_price, b.showtime_id, m.title, m.movie_id, 
          s.show_date, s.show_time, t.theater_id, t.name AS theater_name
          FROM bookings b 
          JOIN showtimes s ON b.showtime_id = s.show_id 
          JOIN movies m ON s.movie_id = m.movie_id 
          JOIN theaters t ON s.theater_id = t.theater_id 
          WHERE b.booking_id = ? AND b.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$booking_details = $result->fetch_assoc();

// Handle payment screenshot if provided
$screenshot_path = '';
if (isset($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] === 0) {
    $upload_dir = 'uploads/payment_screenshots/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_name = time() . '_' . $_FILES['payment_screenshot']['name'];
    $upload_path = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['payment_screenshot']['tmp_name'], $upload_path)) {
        $screenshot_path = $upload_path;
    }
}

// Record payment in database
$payment_status = 'pending'; // QR payments need verification
$payment_method = 'qr_code';
$amount = $booking_details['total_price'];

$query = "INSERT INTO payments (booking_id, user_id, payment_method, amount, payment_status, transaction_id, screenshot_path, payment_date) 
          VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($query);
$stmt->bind_param("iisdssss", $booking_id, $user_id, $payment_method, $amount, $payment_status, $transaction_id, $screenshot_path);

if ($stmt->execute()) {
    // Update booking status
    $query = "UPDATE bookings SET booking_status = 'payment_pending' WHERE booking_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    
    // Redirect to confirmation page
    header("Location: booking_confirmation.php?booking_id=$booking_id&status=pending");
    exit();
} else {
    // Handle error
    header("Location: payment.php?booking_id=$booking_id&error=1");
    exit();
}