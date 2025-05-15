# Movie Booking System with Payment Integration

This project is a movie booking system with integrated payment processing using Stripe and Razorpay.

## Payment Integration Setup

### Prerequisites
- PHP 7.4 or higher
- Composer (for installing dependencies)
- Stripe and/or Razorpay account

### Installation Steps

1. Install the required dependencies:
   ```
   composer install
   ```

2. Update the API keys in the configuration files:
   - For Stripe: Edit `stripe-config.php` with your Stripe API keys
   - For Razorpay: Edit `razorpay-config.php` with your Razorpay API keys

3. Set up the database tables:
   - Import the `payment_tables.sql` file into your MySQL database

4. Configure webhooks:
   - Stripe: Point your Stripe webhook to `https://your-domain.com/webhook_handler.php`
   - Razorpay: Point your Razorpay webhook to `https://your-domain.com/webhook_handler.php`

### Testing Payments

For testing purposes, you can use the following test cards:

#### Stripe Test Cards
- Successful payment: 4242 4242 4242 4242
- Failed payment: 4000 0000 0000 0002

#### Razorpay Test Cards
- Successful payment: 4111 1111 1111 1111
- Failed payment: 5104 0600 0000 0008

## Features
- Real-time payment processing
- Multiple payment gateway options
- Webhook handling for payment status updates
- Admin dashboard for payment management