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

## Execution of code
<img width="1917" height="893" alt="Screenshot 2025-05-31 000446" src="https://github.com/user-attachments/assets/cc48ccf4-a4b2-4500-aa96-894098798df6" />
<img width="1899" height="891" alt="Screenshot 2025-05-30 233734" src="https://github.com/user-attachments/assets/8fc054b6-aef3-44df-99b6-612539983db9" />
<img width="1907" height="886" alt="Screenshot 2025-05-28 060114" src="https://github.com/user-attachments/assets/039b3b71-5d40-47ee-bd0a-06680a22b474" />
<img width="837" height="870" alt="Screenshot 2025-05-28 155319" src="https://github.com/user-attachments/assets/0a334991-4b93-4510-a3db-6586b4b87332" />
<img width="1241" height="754" alt="Screenshot 2025-05-28 135239" src="https://github.com/user-attachments/assets/a7dc2024-d83d-4333-9263-43c7d78cc4ba" />
<img width="1190" height="519" alt="Screenshot 2025-05-28 135433" src="https://github.com/user-attachments/assets/26db0320-9322-406c-8c73-3af274c2aa0c" />
<img width="814" height="853" alt="Screenshot 2025-05-28 122617" src="https://github.com/user-attachments/assets/7254baf4-d6ea-4e8f-ae43-7dc918d81e71" />





