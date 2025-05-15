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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_bookings.php');
    exit;
}

$booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;

if (!$booking_id) {
    $_SESSION['error'] = "Invalid booking ID.";
    header('Location: manage_bookings.php');
    exit;
}

// Check if admin has access to this theater's bookings
$stmt = $pdo->prepare("
    SELECT t.theater_id FROM bookings b
    JOIN showtimes s ON b.showtime_id = s.show_id
    JOIN theaters t ON s.theater_id = t.theater_id
    WHERE b.booking_id = ?
");
$stmt->execute([$booking_id]);
$theater_id = $stmt->fetchColumn();

if (!$theater_id) {
    $_SESSION['error'] = "Booking not found.";
    header('Location: manage_bookings.php');
    exit;
}

$stmt = $pdo->prepare("SELECT 1 FROM theater_admins WHERE user_id = ? AND theater_id = ?");
$stmt->execute([$user_id, $theater_id]);
$hasAccess = $stmt->fetchColumn();

// Super admin check
$stmt = $pdo->prepare("SELECT COUNT(*) FROM theater_admins WHERE user_id = ?");
$stmt->execute([$user_id]);
$isSuperAdmin = ($stmt->fetchColumn() == 0);

if (!$isSuperAdmin && !$hasAccess) {
    $_SESSION['error'] = "You don't have permission to cancel this booking.";
    header('Location: manage_bookings.php');
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Update payment status to 'cancelled' if exists
    $stmt = $pdo->prepare("
        UPDATE payments 
        SET payment_status = 'cancelled', 
            updated_at = NOW() 
        WHERE booking_id = ?
    ");
    $stmt->execute([$booking_id]);
    
    // Add booking_status column if it doesn't exist
    $pdo->exec("
        ALTER TABLE bookings 
        ADD COLUMN IF NOT EXISTS booking_status VARCHAR(20) NOT NULL DEFAULT 'confirmed'
    ");
    
    // Update booking status
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET booking_status = 'cancelled' 
        WHERE booking_id = ?
    ");
    $stmt->execute([$booking_id]);
    
    // Release the seats
    // This would depend on how your seat reservation system works
    // For example, if you have a seats table:
    $stmt = $pdo->prepare("DELETE FROM seats WHERE booking_id = ?");
    $stmt->execute([$booking_id]);
    
    $pdo->commit();
    
    $_SESSION['success'] = "Booking cancelled successfully.";
    header('Location: manage_bookings.php');
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Error cancelling booking: " . $e->getMessage();
    header('Location: view_booking.php?id=' . $booking_id);
    exit;
}