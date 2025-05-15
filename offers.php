<?php
session_start();
require_once('db_connect.php');

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : 0;

// Fetch user data if logged in
$user = null;
if ($is_logged_in) {
    $stmt = $conn->prepare("SELECT name, email, profile_pic FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

// Create offers table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS offers (
    offer_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    discount_percent INT NOT NULL,
    promo_code VARCHAR(20) NOT NULL,
    valid_from DATE NOT NULL,
    valid_to DATE NOT NULL,
    image VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1
)");

// Check if there are any offers
$result = $conn->query("SELECT COUNT(*) as count FROM offers WHERE is_active = 1");
$offer_count = $result->fetch_assoc()['count'];

// If no offers exist, add some sample offers
if ($offer_count == 0) {
    $sample_offers = [
        [
            'title' => 'Weekend Special',
            'description' => 'Get 20% off on all movie tickets booked for weekend shows. Use promo code WEEKEND20 at checkout.',
            'discount_percent' => 20,
            'promo_code' => 'WEEKEND20',
            'valid_from' => date('Y-m-d'),
            'valid_to' => date('Y-m-d', strtotime('+30 days')),
            'image' => 'weekend_special.jpg'
        ],
        [
            'title' => 'Family Pack',
            'description' => 'Book 4 or more tickets and get 15% discount. Perfect for family outings!',
            'discount_percent' => 15,
            'promo_code' => 'FAMILY15',
            'valid_from' => date('Y-m-d'),
            'valid_to' => date('Y-m-d', strtotime('+60 days')),
            'image' => 'family_pack.jpg'
        ],
        [
            'title' => 'Student Discount',
            'description' => '10% off for students with valid ID. Applicable on all shows from Monday to Thursday.',
            'discount_percent' => 10,
            'promo_code' => 'STUDENT10',
            'valid_from' => date('Y-m-d'),
            'valid_to' => date('Y-m-d', strtotime('+90 days')),
            'image' => 'student_discount.jpg'
        ]
    ];
    
    foreach ($sample_offers as $offer) {
        $stmt = $conn->prepare("INSERT INTO offers (title, description, discount_percent, promo_code, valid_from, valid_to, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissss", $offer['title'], $offer['description'], $offer['discount_percent'], $offer['promo_code'], $offer['valid_from'], $offer['valid_to'], $offer['image']);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch active offers
$offers = [];
$result = $conn->query("SELECT * FROM offers WHERE is_active = 1 AND valid_to >= CURDATE() ORDER BY discount_percent DESC");
while ($row = $result->fetch_assoc()) {
    $offers[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Offers - Movie Booking</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/animations.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #584528;
            padding: 15px;
            color: white;
        }
        
        .logo {
            font-size: 1.5em;
            font-weight: bold;
            text-decoration: none;
            color: white;
        }
        
        .nav-links .btn {
            text-decoration: none;
            color: white;
            background: #ff4500;
            padding: 10px 15px;
            border-radius: 5px;
            margin-left: 10px;
        }
        
        .nav-links .btn:hover {
            background: #e63e00;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            position: relative;
            cursor: pointer;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
            border: 2px solid #ff4500;
        }
        
        .user-name {
            color: white;
            font-weight: bold;
        }
        
        .dropdown-menu {
            position: absolute;
            top: 50px;
            right: 0;
            background: white;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            padding: 10px;
            min-width: 200px;
            display: none;
            z-index: 100;
        }
        
        .dropdown-menu.active {
            display: block;
        }
        
        .dropdown-menu a {
            display: flex;
            align-items: center;
            padding: 8px 10px;
            text-decoration: none;
            color: #333;
            transition: background 0.3s;
        }
        
        .dropdown-menu a i {
            margin-right: 10px;
            font-size: 1.2em;
        }
        
        .dropdown-menu a:hover {
            background: #f4f4f4;
        }
        
        .dropdown-menu .divider {
            height: 1px;
            background: #ddd;
            margin: 8px 0;
        }
        
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('assets/images/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding: 60px 20px;
        }
        
        .hero-title {
            font-size: 2.5em;
            margin-bottom: 15px;
        }
        
        .hero-subtitle {
            font-size: 1.2em;
            margin-bottom: 30px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .offers-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .section-title {
            font-size: 2em;
            color: #584528;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: #ff4500;
        }
        
        .offers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .offer-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .offer-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        
        .offer-image {
            height: 200px;
            width: 100%;
            object-fit: cover;
            border-bottom: 3px solid #ff4500;
        }
        
        .offer-content {
            padding: 20px;
        }
        
        .offer-title {
            font-size: 1.5em;
            margin-bottom: 10px;
            color: #333;
        }
        
        .offer-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .offer-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }
        
        .offer-discount {
            font-size: 1.8em;
            font-weight: bold;
            color: #ff4500;
        }
        
        .offer-validity {
            color: #777;
            font-size: 0.9em;
        }
        
        .promo-code {
            background: #f8f9fa;
            border: 2px dashed #ddd;
            padding: 10px;
            text-align: center;
            margin-top: 15px;
            position: relative;
        }
        
        .code {
            font-family: monospace;
            font-size: 1.2em;
            font-weight: bold;
            letter-spacing: 2px;
            color: #333;
        }
        
        .copy-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #007bff;
            cursor: pointer;
        }
        
        .how-to-use {
            margin-top: 60px;
            background: #f8f9fa;
            padding: 40px;
            border-radius: 10px;
        }
        
        .steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .step {
            text-align: center;
            padding: 20px;
        }
        
        .step-icon {
            font-size: 3em;
            color: #ff4500;
            margin-bottom: 15px;
        }
        
        .step-title {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        .step-description {
            color: #666;
            line-height: 1.5;
        }
        
        .footer {
            background: #3c3323;
            color: white;
            text-align: center;
            padding: 15px;
            margin-top: 60px;
        }
        
        @media (max-width: 768px) {
            .offers-grid {
                grid-template-columns: 1fr;
            }
            
            .hero-title {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <a href="index.html" class="logo">Movie Booking</a>
        <div class="nav-links">
            <?php if ($is_logged_in): ?>
                <div class="user-profile">
                    <div class="user-dropdown-toggle" id="userDropdownToggle">
                        <img src="<?php echo $user['profile_pic'] ?? 'uploads/default-profile.png'; ?>" alt="User" class="user-avatar">
                        <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu" id="userMenu">
                        <a href="user_profile.php"><i class="bi bi-person-circle"></i> My Profile</a>
                        <a href="user_dashboard.php#bookingsTab"><i class="bi bi-ticket-perforated"></i> My Bookings</a>
                        <div class="divider"></div>
                        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.html" class="btn">Login</a>
                <a href="register.html" class="btn">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <h1 class="hero-title">Exclusive Offers & Discounts</h1>
        <p class="hero-subtitle">Save big on your movie tickets with our special promotions and discount offers. Use promo codes during checkout to avail these exciting deals!</p>
    </section>

    <!-- Offers Section -->
    <div class="offers-container">
        <h2 class="section-title">Current Offers</h2>
        
        <?php if (empty($offers)): ?>
            <div style="text-align: center; padding: 40px;">
                <p>No active offers at the moment. Please check back later!</p>
            </div>
        <?php else: ?>
            <div class="offers-grid">
                <?php foreach ($offers as $offer): ?>
                    <div class="offer-card">
                        <img src="uploads/<?php echo !empty($offer['image']) ? $offer['image'] : 'offer_default.jpg'; ?>" alt="<?php echo htmlspecialchars($offer['title']); ?>" class="offer-image">
                        <div class="offer-content">
                            <h3 class="offer-title"><?php echo htmlspecialchars($offer['title']); ?></h3>
                            <p class="offer-description"><?php echo htmlspecialchars($offer['description']); ?></p>
                            
                            <div class="offer-details">
                                <div class="offer-discount"><?php echo $offer['discount_percent']; ?>% OFF</div>
                                <div class="offer-validity">
                                    Valid till: <?php echo date('M d, Y', strtotime($offer['valid_to'])); ?>
                                </div>
                            </div>
                            
                            <div class="promo-code">
                                <span class="code"><?php echo htmlspecialchars($offer['promo_code']); ?></span>
                                <button class="copy-btn" data-code="<?php echo htmlspecialchars($offer['promo_code']); ?>">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- How to Use Section -->
        <div class="how-to-use">
            <h2 class="section-title">How to Use Promo Codes</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-icon"><i class="bi bi-search"></i></div>
                    <h3 class="step-title">Find a Movie</h3>
                    <p class="step-description">Browse our collection of movies and select the one you want to watch.</p>
                </div>
                
                <div class="step">
                    <div class="step-icon"><i class="bi bi-calendar-check"></i></div>
                    <h3 class="step-title">Select Showtime</h3>
                    <p class="step-description">Choose your preferred date, time, and theater location.</p>
                </div>
                
                <div class="step">
                    <div class="step-icon"><i class="bi bi-clipboard-check"></i></div>
                    <h3 class="step-title">Apply Promo Code</h3>
                    <p class="step-description">Enter the promo code during checkout to get your discount.</p>
                </div>
                
                <div class="step">
                    <div class="step-icon"><i class="bi bi-ticket-perforated"></i></div>
                    <h3 class="step-title">Enjoy the Show</h3>
                    <p class="step-description">Receive your discounted tickets and enjoy the movie!</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2025 Movie Ticket Booking. All Rights Reserved.</p>
    </footer>

    <script>
        // User dropdown menu
        const userDropdownToggle = document.getElementById('userDropdownToggle');
        const userMenu = document.getElementById('userMenu');
        
        if (userDropdownToggle) {
            userDropdownToggle.addEventListener('click', () => {
                userMenu.classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (event) => {
                if (!userDropdownToggle.contains(event.target) && !userMenu.contains(event.target)) {
                    userMenu.classList.remove('active');
                }
            });
        }
        
        // Copy promo code functionality
        document.querySelectorAll('.copy-btn').forEach(button => {
            button.addEventListener('click', function() {
                const code = this.getAttribute('data-code');
                navigator.clipboard.writeText(code).then(() => {
                    // Change button icon temporarily
                    const icon = this.querySelector('i');
                    icon.classList.remove('bi-clipboard');
                    icon.classList.add('bi-clipboard-check');
                    
                    // Show tooltip or alert
                    alert('Promo code copied: ' + code);
                    
                    // Reset icon after 2 seconds
                    setTimeout(() => {
                        icon.classList.remove('bi-clipboard-check');
                        icon.classList.add('bi-clipboard');
                    }, 2000);
                });
            });
        });
    </script>
</body>
</html>