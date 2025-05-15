<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.html");
    exit();
}

// Load current payment settings
include '../stripe-config.php';
include '../razorpay-config.php';
include '../google-pay-config.php';

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_stripe'])) {
        // Update Stripe settings
        $stripe_api_key = $_POST['stripe_api_key'];
        $stripe_publishable_key = $_POST['stripe_publishable_key'];
        $stripe_webhook_secret = $_POST['stripe_webhook_secret'];
        
        $config_content = "<?php
// Stripe API configuration
define('STRIPE_API_KEY', '$stripe_api_key'); // Replace with your actual Stripe secret key
define('STRIPE_PUBLISHABLE_KEY', '$stripe_publishable_key'); // Replace with your actual Stripe publishable key
define('STRIPE_WEBHOOK_SECRET', '$stripe_webhook_secret'); // Replace with your Stripe webhook secret

// Currency and other settings
define('STRIPE_CURRENCY', 'INR');
define('WEBSITE_URL', 'http://localhost/movie-booking'); // Replace with your actual website URL
?>";
        
        file_put_contents('../stripe-config.php', $config_content);
        $success_message = "Stripe settings updated successfully!";
    } 
    elseif (isset($_POST['update_razorpay'])) {
        // Update Razorpay settings
        $razorpay_key_id = $_POST['razorpay_key_id'];
        $razorpay_key_secret = $_POST['razorpay_key_secret'];
        
        $config_content = "<?php
// Razorpay API configuration
define('RAZORPAY_KEY_ID', '$razorpay_key_id'); // Replace with your actual Razorpay key ID
define('RAZORPAY_KEY_SECRET', '$razorpay_key_secret'); // Replace with your actual Razorpay key secret

// Currency and other settings
define('RAZORPAY_CURRENCY', 'INR');
define('WEBSITE_URL', 'http://localhost/movie-booking'); // Replace with your actual website URL
?>";
        
        file_put_contents('../razorpay-config.php', $config_content);
        $success_message = "Razorpay settings updated successfully!";
    }
    elseif (isset($_POST['update_google_pay'])) {
        // Update Google Pay settings
        $google_pay_merchant_id = $_POST['google_pay_merchant_id'];
        $google_pay_merchant_name = $_POST['google_pay_merchant_name'];
        $google_pay_environment = $_POST['google_pay_environment'];
        $google_pay_gateway = $_POST['google_pay_gateway'];
        
        $config_content = "<?php
// Google Pay API configuration
define('GOOGLE_PAY_MERCHANT_ID', '$google_pay_merchant_id'); // Replace with your actual Google Pay merchant ID
define('GOOGLE_PAY_MERCHANT_NAME', '$google_pay_merchant_name'); // Your business name
define('GOOGLE_PAY_ENVIRONMENT', '$google_pay_environment'); // Use 'TEST' for testing, 'PRODUCTION' for live

// Gateway configuration (for processing Google Pay payments)
// You'll need to process Google Pay through a payment processor like Stripe or your bank
define('GOOGLE_PAY_GATEWAY', '$google_pay_gateway'); // Options: STRIPE, DIRECT, etc.

// Currency and other settings
define('GOOGLE_PAY_CURRENCY', 'INR');
?>";
        
        file_put_contents('../google-pay-config.php', $config_content);
        $success_message = "Google Pay settings updated successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Gateway Settings - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../admin_dashboard.php">
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../manage_movies.php">
                                Manage Movies
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../manage_showtimes.php">
                                Manage Showtimes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../manage_theaters.php">
                                Manage Theaters
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../manage_bookings.php">
                                Manage Bookings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../manage_payments.php">
                                Manage Payments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="payment_settings.php">
                                Payment Settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Payment Gateway Settings</h1>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?= $success_message ?></div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?= $error_message ?></div>
                <?php endif; ?>

                <div class="row">
                    <!-- Stripe Settings -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Stripe Settings</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="stripe_api_key" class="form-label">Secret Key</label>
                                        <input type="text" class="form-control" id="stripe_api_key" name="stripe_api_key" 
                                               value="<?= defined('STRIPE_API_KEY') ? STRIPE_API_KEY : '' ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="stripe_publishable_key" class="form-label">Publishable Key</label>
                                        <input type="text" class="form-control" id="stripe_publishable_key" name="stripe_publishable_key" 
                                               value="<?= defined('STRIPE_PUBLISHABLE_KEY') ? STRIPE_PUBLISHABLE_KEY : '' ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="stripe_webhook_secret" class="form-label">Webhook Secret</label>
                                        <input type="text" class="form-control" id="stripe_webhook_secret" name="stripe_webhook_secret" 
                                               value="<?= defined('STRIPE_WEBHOOK_SECRET') ? STRIPE_WEBHOOK_SECRET : '' ?>">
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="stripe_enabled" name="stripe_enabled" checked>
                                        <label class="form-check-label" for="stripe_enabled">
                                            Enable Stripe Payments
                                        </label>
                                    </div>
                                    <button type="submit" name="update_stripe" class="btn btn-primary">Save Stripe Settings</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Razorpay Settings -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Razorpay Settings</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="razorpay_key_id" class="form-label">Key ID</label>
                                        <input type="text" class="form-control" id="razorpay_key_id" name="razorpay_key_id" 
                                               value="<?= defined('RAZORPAY_KEY_ID') ? RAZORPAY_KEY_ID : '' ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="razorpay_key_secret" class="form-label">Key Secret</label>
                                        <input type="text" class="form-control" id="razorpay_key_secret" name="razorpay_key_secret" 
                                               value="<?= defined('RAZORPAY_KEY_SECRET') ? RAZORPAY_KEY_SECRET : '' ?>" required>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="razorpay_enabled" name="razorpay_enabled" checked>
                                        <label class="form-check-label" for="razorpay_enabled">
                                            Enable Razorpay Payments
                                        </label>
                                    </div>
                                    <button type="submit" name="update_razorpay" class="btn btn-primary">Save Razorpay Settings</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Google Pay Settings -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5>Google Pay Settings</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="google_pay_merchant_id" class="form-label">Merchant ID</label>
                                        <input type="text" class="form-control" id="google_pay_merchant_id" name="google_pay_merchant_id" 
                                               value="<?= defined('GOOGLE_PAY_MERCHANT_ID') ? GOOGLE_PAY_MERCHANT_ID : '' ?>" required>
                                        <small class="text-muted">Get this from your Google Pay for Business account</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="google_pay_merchant_name" class="form-label">Merchant Name</label>
                                        <input type="text" class="form-control" id="google_pay_merchant_name" name="google_pay_merchant_name" 
                                               value="<?= defined('GOOGLE_PAY_MERCHANT_NAME') ? GOOGLE_PAY_MERCHANT_NAME : 'Movie Booking' ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="google_pay_environment" class="form-label">Environment</label>
                                        <select class="form-select" id="google_pay_environment" name="google_pay_environment">
                                            <option value="TEST" <?= (defined('GOOGLE_PAY_ENVIRONMENT') && GOOGLE_PAY_ENVIRONMENT === 'TEST') ? 'selected' : '' ?>>Test</option>
                                            <option value="PRODUCTION" <?= (defined('GOOGLE_PAY_ENVIRONMENT') && GOOGLE_PAY_ENVIRONMENT === 'PRODUCTION') ? 'selected' : '' ?>>Production</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="google_pay_gateway" class="form-label">Payment Processor</label>
                                        <select class="form-select" id="google_pay_gateway" name="google_pay_gateway">
                                            <option value="STRIPE" <?= (defined('GOOGLE_PAY_GATEWAY') && GOOGLE_PAY_GATEWAY === 'STRIPE') ? 'selected' : '' ?>>Stripe</option>
                                            <option value="DIRECT" <?= (defined('GOOGLE_PAY_GATEWAY') && GOOGLE_PAY_GATEWAY === 'DIRECT') ? 'selected' : '' ?>>Direct Bank Integration</option>
                                        </select>
                                        <small class="text-muted">Select how you want to process Google Pay payments</small>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="google_pay_enabled" name="google_pay_enabled" checked>
                                        <label class="form-check-label" for="google_pay_enabled">
                                            Enable Google Pay
                                        </label>
                                    </div>
                                    <button type="submit" name="update_google_pay" class="btn btn-primary">Save Google Pay Settings</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5>How to Set Up Google Pay for Your Business</h5>
                    </div>
                    <div class="card-body">
                        <ol>
                            <li>
                                <strong>Create a Google Pay for Business Account:</strong>
                                <p>Visit <a href="https://pay.google.com/business/console" target="_blank">Google Pay for Business</a> and sign up with your Google account.</p>
                            </li>
                            <li>
                                <strong>Complete Business Verification:</strong>
                                <p>Provide your business details and complete the verification process.</p>
                            </li>
                            <li>
                                <strong>Get Your Merchant ID:</strong>
                                <p>Once verified, you'll receive a Merchant ID which you should enter in the settings above.</p>
                            </li>
                            <li>
                                <strong>Choose a Payment Processor:</strong>
                                <p>Google Pay requires a payment processor. You can use Stripe (recommended) or direct bank integration.</p>
                            </li>
                            <li>
                                <strong>Test Your Integration:</strong>
                                <p>Use the TEST environment to make sure everything works before going live.</p>
                            </li>
                        </ol>
                        <div class="alert alert-info">
                            <strong>Note:</strong> Google Pay integration requires that you have an SSL certificate installed on your website (https://).
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>