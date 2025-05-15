<?php
require_once('db_connect.php');

// Check if the bookings table exists
$result = $conn->query("SHOW TABLES LIKE 'bookings'");
if ($result->num_rows > 0) {
    echo "The bookings table already exists. Adding the selected_seats column if it doesn't exist.<br>";
    
    // Check if selected_seats column exists
    $result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'selected_seats'");
    if ($result->num_rows == 0) {
        // Add the selected_seats column
        $sql = "ALTER TABLE bookings ADD COLUMN selected_seats TEXT AFTER showtime_id";
        if ($conn->query($sql) === TRUE) {
            echo "Column selected_seats added successfully.";
        } else {
            echo "Error adding column: " . $conn->error;
        }
    } else {
        echo "Column selected_seats already exists.";
    }
} else {
    // Create the bookings table with the selected_seats column
    $sql = "CREATE TABLE bookings (
        booking_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        movie_id INT(11) NOT NULL,
        showtime_id INT(11) NOT NULL,
        selected_seats TEXT,
        booking_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        total_price DECIMAL(10,2) NOT NULL,
        payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
        booking_status ENUM('confirmed', 'cancelled') DEFAULT 'confirmed'
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table bookings created successfully with selected_seats column.";
    } else {
        echo "Error creating table: " . $conn->error;
    }
}
?>