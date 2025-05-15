<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$payment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

if (!$payment_id || !in_array($status, ['completed', 'pending', 'failed', 'refunded'])) {
    $_SESSION['error'] = "Invalid payment ID or status.";
    header('Location: manage_payments.php');
    exit;
}

// Get payment details
$stmt = $conn->prepare("
    SELECT p.*, b.showtime_id, s.theater_id
    FROM payments p
    JOIN bookings b ON p.booking_id = b.booking_id
    JOIN showtimes s ON b.showtime_id = s.show_id
    WHERE p.payment_id = ?
");
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();
$payment = $result->fetch_assoc();
$stmt->close();

if (!$payment) {
    $_SESSION['error'] = "Payment not found.";
    header('Location: manage_payments.php');
    exit;
}

// Check if admin has access to this theater
$theater_id = $payment['theater_id'];
$stmt = $conn->prepare("SELECT 1 FROM theater_admins WHERE user_id = ? AND theater_id = ?");
$stmt->bind_param("ii", $user_id, $theater_id);
$stmt->execute();
$result = $stmt->get_result();
$hasAccess = $result->num_rows > 0;
$stmt->close();

// Super admin check
$stmt = $conn->prepare("SELECT COUNT(*) FROM theater_admins WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$isSuperAdmin = ($result->fetch_row()[0] == 0);
$stmt->close();

if (!$isSuperAdmin && !$hasAccess) {
    $_SESSION['error'] = "You don't have permission to update this payment.";
    header('Location: manage_payments.php');
    exit;
}

// Update payment status
$stmt = $conn->prepare("UPDATE payments SET payment_status = ? WHERE payment_id = ?");
$stmt->bind_param("si", $status, $payment_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Payment status updated successfully.";
} else {
    $_SESSION['error'] = "Error updating payment status: " . $conn->error;
}
$stmt->close();

header('Location: manage_payments.php');
exit;