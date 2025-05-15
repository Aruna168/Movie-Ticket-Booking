<?php
/**
 * Helper functions for the Movie Booking System
 */

/**
 * Ensure the theater_admins table exists
 */
function ensureTheaterAdminsTable($pdo) {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS theater_admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            theater_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (user_id, theater_id)
        )";
        $pdo->exec($sql);
    } catch (PDOException $e) {
        // Silently fail - we'll handle errors elsewhere
    }
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

/**
 * Format time
 */
function formatTime($time) {
    return date('g:i A', strtotime($time));
}

/**
 * Get file extension
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Generate a random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}
?>