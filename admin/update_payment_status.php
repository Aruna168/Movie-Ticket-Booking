<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    header('Location: manage_payments.php');
    exit;
}

$payment_id = $_GET['id'];
$status = $_GET['status'];
$user_id = $_SESSION['user_id'];

// Validate status
$valid_statuses = ['completed', 'pending', 'failed', 'refunded'];
if (!in_array($status, $valid_statuses)) {
    header('Location: manage_payments.php?error=invalid_status');
    exit;
}

// Get payment details to check theater access
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
    header('Location: manage_payments.php?error=payment_not_found');
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
    header('Location: manage_payments.php?error=access_denied');
    exit;
}

// Update payment status
$stmt = $conn->prepare("UPDATE payments SET payment_status = ? WHERE payment_id = ?");
$stmt->bind_param("si", $status, $payment_id);
$stmt->execute();

// If payment is completed, update booking status
if ($status === 'completed') {
    $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'confirmed' WHERE booking_id = ?");
    $stmt->bind_param("i", $payment['booking_id']);
    $stmt->execute();
} elseif ($status === 'failed' || $status === 'refunded') {
    $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE booking_id = ?");
    $stmt->bind_param("i", $payment['booking_id']);
    $stmt->execute();
}

header('Location: manage_payments.php?success=status_updated');
exit;