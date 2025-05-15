<?php
// Google Pay API configuration
define('GOOGLE_PAY_MERCHANT_ID', 'your_merchant_id'); // Replace with your actual Google Pay merchant ID
define('GOOGLE_PAY_MERCHANT_NAME', 'Movie Booking'); // Your business name
define('GOOGLE_PAY_ENVIRONMENT', 'TEST'); // Use 'TEST' for testing, 'PRODUCTION' for live

// Gateway configuration (for processing Google Pay payments)
// You'll need to process Google Pay through a payment processor like Stripe or your bank
define('GOOGLE_PAY_GATEWAY', 'STRIPE'); // Options: STRIPE, DIRECT, etc.

// Currency and other settings
define('GOOGLE_PAY_CURRENCY', 'INR');
?>