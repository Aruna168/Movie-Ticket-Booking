<?php
// This file handles webhooks from payment gateways (Stripe and Razorpay)
include 'db_connect.php';

// Get the raw POST data
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$event = null;

// Determine which payment gateway sent the webhook
if (isset($_SERVER['HTTP_STRIPE_SIGNATURE'])) {
    // Handle Stripe webhook
    include 'stripe-config.php';
    require_once 'vendor/autoload.php';
    
    try {
        \Stripe\Stripe::setApiKey(STRIPE_API_KEY);
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sig_header, STRIPE_WEBHOOK_SECRET
        );
        
        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                handleSuccessfulPayment(
                    $paymentIntent->metadata->booking_id ?? null,
                    'stripe',
                    $paymentIntent->id,
                    $paymentIntent->amount / 100 // Convert from cents to actual currency
                );
                break;
                
            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                handleFailedPayment(
                    $paymentIntent->metadata->booking_id ?? null,
                    'stripe',
                    $paymentIntent->id
                );
                break;
        }
        
        http_response_code(200);
        echo json_encode(['status' => 'success']);
        
    } catch(\UnexpectedValueException $e) {
        // Invalid payload
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payload']);
        exit();
    } catch(\Stripe\Exception\SignatureVerificationException $e) {
        // Invalid signature
        http_response_code(400);
        echo json_encode(['error' => 'Invalid signature']);
        exit();
    }
} elseif (isset($_SERVER['HTTP_X_RAZORPAY_SIGNATURE'])) {
    // Handle Razorpay webhook
    include 'razorpay-config.php';
    require_once 'vendor/autoload.php';
    
    $data = json_decode($payload, true);
    $razorpay_signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'];
    
    try {
        $api = new Razorpay\Api\Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
        $api->utility->verifyWebhookSignature($payload, $razorpay_signature, RAZORPAY_WEBHOOK_SECRET);
        
        // Handle the event
        if ($data['event'] === 'payment.authorized') {
            $payment = $data['payload']['payment']['entity'];
            handleSuccessfulPayment(
                $payment['notes']['booking_id'] ?? null,
                'razorpay',
                $payment['id'],
                $payment['amount'] / 100 // Convert from paise to rupees
            );
        } elseif ($data['event'] === 'payment.failed') {
            $payment = $data['payload']['payment']['entity'];
            handleFailedPayment(
                $payment['notes']['booking_id'] ?? null,
                'razorpay',
                $payment['id']
            );
        }
        
        http_response_code(200);
        echo json_encode(['status' => 'success']);
        
    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
} else {
    // Unknown webhook source
    http_response_code(400);
    echo json_encode(['error' => 'Unknown webhook source']);
    exit();
}

// Function to handle successful payments
function handleSuccessfulPayment($booking_id, $payment_method, $transaction_id, $amount) {
    global $conn;
    
    if (!$booking_id) return;
    
    // Get booking details
    $query = "SELECT user_id FROM bookings WHERE booking_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    
    if (!$booking) return;
    
    // Update booking status
    $status = 'Paid Successfully';
    $query = "UPDATE bookings SET booking_status = ? WHERE booking_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $status, $booking_id);
    $stmt->execute();
    
    // Record payment in database (if not already recorded)
    $query = "SELECT payment_id FROM payments WHERE transaction_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $transaction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $query = "INSERT INTO payments (booking_id, user_id, payment_method, amount, payment_status, transaction_id, payment_date) 
                  VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iisdss", $booking_id, $booking['user_id'], $payment_method, $amount, $status, $transaction_id);
        $stmt->execute();
    }
}

// Function to handle failed payments
function handleFailedPayment($booking_id, $payment_method, $transaction_id) {
    global $conn;
    
    if (!$booking_id) return;
    
    // Get booking details
    $query = "SELECT user_id FROM bookings WHERE booking_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    
    if (!$booking) return;
    
    // Update booking status
    $status = 'Payment Failed';
    $query = "UPDATE bookings SET booking_status = ? WHERE booking_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $status, $booking_id);
    $stmt->execute();
    
    // Record failed payment
    $query = "INSERT INTO payments (booking_id, user_id, payment_method, amount, payment_status, transaction_id, payment_date) 
              VALUES (?, ?, ?, 0, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisss", $booking_id, $booking['user_id'], $payment_method, $status, $transaction_id);
    $stmt->execute();
}