<?php
session_start();
require_once '../config.php';
require_once '../includes/helpers.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$showtime_id = isset($_GET['showtime_id']) ? (int)$_GET['showtime_id'] : 0;

if (!$showtime_id) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid showtime ID']);
    exit;
}

// Check if admin has access to this theater
$stmt = $pdo->prepare("
    SELECT t.theater_id FROM showtimes s
    JOIN theaters t ON s.theater_id = t.theater_id
    WHERE s.show_id = ?
");
$stmt->execute([$showtime_id]);
$theater_id = $stmt->fetchColumn();

if (!$theater_id) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Showtime not found']);
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
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get booked seats
$stmt = $pdo->prepare("
    SELECT b.seats
    FROM bookings b
    WHERE b.showtime_id = ? AND (b.booking_status IS NULL OR b.booking_status != 'cancelled')
");
$stmt->execute([$showtime_id]);
$bookings = $stmt->fetchAll();

$booked_seats = [];
foreach ($bookings as $booking) {
    if (!empty($booking['seats'])) {
        $seats = explode(', ', $booking['seats']);
        $booked_seats = array_merge($booked_seats, $seats);
    }
}

header('Content-Type: application/json');
echo json_encode($booked_seats);
exit;