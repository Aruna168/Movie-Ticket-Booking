<?php
session_start();
require_once('db_connect.php');

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$movie_id = $_POST['movie_id'] ?? null;
$showtime_id = $_POST['showtime_id'] ?? null;
$selected_seats = $_POST['selected_seats'] ?? '[]';
$total_price = $_POST['total_price'] ?? 0;

// Validate inputs
if (!$movie_id || !$showtime_id || $selected_seats === '[]') {
    echo "Invalid booking data. Please try again.";
    exit();
}

// Decode selected seats
$seats = json_decode($selected_seats);
if (!is_array($seats) || empty($seats)) {
    echo "No seats selected. Please select at least one seat.";
    exit();
}

// Begin transaction
$conn->begin_transaction();

try {
    // Check if seats table exists, if not create it
    $result = $conn->query("SHOW TABLES LIKE 'seats'");
    if ($result->num_rows == 0) {
        // Create seats table
        $conn->query("CREATE TABLE seats (
            seat_id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT NOT NULL,
            seat_number VARCHAR(10) NOT NULL,
            seat_type VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    // Use a simple INSERT statement with minimal columns to avoid column count mismatch
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, movie_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $movie_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error creating booking: " . $stmt->error);
    }
    
    $booking_id = $conn->insert_id;
    $stmt->close();
    
    // Now update the booking with additional information
    $stmt = $conn->prepare("UPDATE bookings SET total_amount = ? WHERE booking_id = ?");
    $stmt->bind_param("di", $total_price, $booking_id);
    $stmt->execute();
    $stmt->close();
    
    // Try to update with showtime_id if the column exists
    $result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'showtime_id'");
    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE bookings SET showtime_id = ? WHERE booking_id = ?");
        $stmt->bind_param("ii", $showtime_id, $booking_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Insert seat bookings
    $stmt = $conn->prepare("INSERT INTO seats (booking_id, seat_number, seat_type) VALUES (?, ?, ?)");
    
    foreach ($seats as $seat) {
        // Determine seat type (VIP, premium, or standard)
        $row = ord(substr($seat, 0, 1)) - ord('A');
        if (in_array($row, [3, 4])) {
            $seat_type = 'vip';
        } elseif (in_array($row, [2, 5])) {
            $seat_type = 'premium';
        } else {
            $seat_type = 'standard';
        }
        
        $stmt->bind_param("iss", $booking_id, $seat, $seat_type);
        
        if (!$stmt->execute()) {
            throw new Exception("Error saving seat information: " . $stmt->error);
        }
    }
    $stmt->close();
    
    // Check if payments table exists, if not create it
    $result = $conn->query("SHOW TABLES LIKE 'payments'");
    if ($result->num_rows == 0) {
        // Create payments table
        $conn->query("CREATE TABLE payments (
            payment_id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            payment_method VARCHAR(50) NOT NULL,
            status VARCHAR(20) NOT NULL
        )");
    }
    
    // Check if payments table has status column
    $result = $conn->query("SHOW COLUMNS FROM payments LIKE 'status'");
    $has_status = $result->num_rows > 0;
    
    // Create payment record
    $payment_method = 'credit_card'; // Default payment method
    
    if ($has_status) {
        $stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, status) 
                               VALUES (?, ?, ?, 'completed')");
        $stmt->bind_param("ids", $booking_id, $total_price, $payment_method);
    } else {
        $stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method) 
                               VALUES (?, ?, ?)");
        $stmt->bind_param("ids", $booking_id, $total_price, $payment_method);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Error processing payment: " . $stmt->error);
    }
    
    $stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Redirect to booking confirmation page
    header("Location: booking_confirmation.php?booking_id=$booking_id");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Display error message with more details
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Booking Error</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container py-5">
            <div class="card mx-auto" style="max-width: 500px;">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Booking Failed</h4>
                </div>
                <div class="card-body">
                    <p class="card-text">Error: ' . htmlspecialchars($e->getMessage()) . '</p>
                    <div class="alert alert-info">
                        <strong>Debug Info:</strong><br>
                        User ID: ' . $user_id . '<br>
                        Movie ID: ' . $movie_id . '<br>
                        Showtime ID: ' . $showtime_id . '<br>
                        Total Price: ' . $total_price . '<br>
                        Selected Seats: ' . htmlspecialchars($selected_seats) . '
                    </div>
                    <a href="javascript:history.back()" class="btn btn-primary">Go Back and Try Again</a>
                </div>
            </div>
        </div>
    </body>
    </html>';
}
?>