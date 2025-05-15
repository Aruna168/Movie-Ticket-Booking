<?php
// session_config.php - Handle session configuration
// This file should be included at the very beginning of each page, before any output

// Set secure session parameters before starting the session
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true
]);

// Start the session
session_start();
?>