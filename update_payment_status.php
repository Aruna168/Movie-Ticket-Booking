<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = $_POST['payment_id'];
    $action = $_POST['action'];

    if ($action === 'receive') {
        $stmt = $conn->prepare("UPDATE payments SET payment_status = 'Received' WHERE payment_id = ?");
    } elseif ($action === 'resend') {
        $stmt = $conn->prepare("UPDATE payments SET payment_status = 'Pending' WHERE payment_id = ?");
    }

    if ($stmt) {
        $stmt->bind_param("i", $payment_id);
        $stmt->execute();
    }
}

header("Location: manage_payments.php");
exit();
