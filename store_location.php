<?php
session_start();

// Store user location in session
if (isset($_POST['lat']) && isset($_POST['lng'])) {
    $_SESSION['user_lat'] = floatval($_POST['lat']);
    $_SESSION['user_lng'] = floatval($_POST['lng']);
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Missing location data']);
}
?>