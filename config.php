<?php
// config.php - Database and site configuration

// Check if session is already started - don't start it here
// Let individual pages handle their own session starts
// This prevents session parameter conflicts

// Rest of your config file...

// Database configuration
$db_host = 'localhost';
$db_name = 'movie_booking';
$db_user = 'root';
$db_pass = '';

// Create PDO connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Site configuration
define('SITE_NAME', 'Movie Booking System');
define('SITE_URL', 'http://localhost/movie-booking');
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('POSTER_PATH', UPLOAD_PATH . 'posters/');
define('PROFILE_PATH', UPLOAD_PATH . 'profiles/');
define('THEATER_PATH', UPLOAD_PATH . 'theaters/');

// API configurations
define('OMDB_API_ENABLED', true);
define('OMDB_API_KEY', '5c9137c5'); // Replace with your actual API key if needed

// Ensure upload directories exist
$directories = [UPLOAD_PATH, POSTER_PATH, PROFILE_PATH, THEATER_PATH];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Session configuration is already set at the top of the file
// No need to set these parameters again

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include helper functions
require_once __DIR__ . '/includes/helpers.php';

// Ensure required tables exist
if (function_exists('ensureTheaterAdminsTable')) {
    ensureTheaterAdminsTable($pdo);
}