
<?php
$pageTitle = "About RoomSewa | Find Your Perfect Space";
require 'includes/header.php';
?>
<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'roomsewa');

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset('utf8mb4');
require 'notification_functions.php';

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'renter') {
    header("Location: login.php");
    exit();
}

$booking_id = (int)$_GET['booking_id'];
$user_id = $_SESSION['user_id'];

// Verify booking belongs to user
$booking = $conn->query("
    SELECT b.*, r.title, r.price_per_month, r.owner_id 
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.id = $booking_id AND b.renter_id = $user_id
");

if ($booking->num_rows !== 1) {
    $_SESSION['error'] = "Invalid booking request";
    header("Location: renter_dashboard.php");
    exit();
}

$booking_data = $booking->fetch_assoc();

// Process payment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = $_POST['payment_method'];
    
    // Validate payment method
    if (!in_array($payment_method, ['card', 'khalti', 'esewa'])) {
        $_SESSION['error'] = "Invalid payment method";
        header("Location: payment.php?booking_id=$booking_id");
        exit();
    }
    
    // Process different payment methods
    if ($payment_method === 'card') {
        $card_number = $_POST['card_number'];
        $expiry = $_POST['expiry'];
        $cvv = $_POST['cvv'];
        
        // Validate payment details
        if (strlen($card_number) < 16 || strlen($expiry) < 4 || strlen($cvv) < 3) {
            $_SESSION['error'] = "Invalid payment details";
            header("Location: payment.php?booking_id=$booking_id");
            exit();
        }
    }
    
    // Generate transaction ID
    $transaction_id = strtoupper($payment_method) . "_" . uniqid();
    
    // Update booking status using prepared statement
    $update_stmt = $conn->prepare("
        UPDATE bookings 
        SET payment_status = 'paid',
            status = 'confirmed',
            transaction_id = ?,
            payment_method = ?,
            payment_date = NOW()
        WHERE id = ?
    ");
    $update_stmt->bind_param("ssi", $transaction_id, $payment_method, $booking_id);
    $update_stmt->execute();
    
    // Notify owner
    $notifications->send(
        $booking_data['owner_id'],
        "Payment received for {$booking_data['title']} via " . strtoupper($payment_method),
        "owner_dashboard.php",
        true
    );
    
    // Redirect to success page
    header("Location: payment_success.php?id=$booking_id");
    exit();
}

// Set page variables for header
$pageTitle = "Complete Payment | RoomSewa";
$additionalCSS = '/assets/css/payment.css';
$additionalJS = '/assets/js/payment.js';


?>

<div class="payment-container">
    <div class="payment-card">
        <div class="payment-header">
            <h1><i class="fas fa-credit-card"></i> Complete Payment</h1>
            <p>Booking #<?php echo $booking_id; ?></p>
        </div>
        
        <div class="payment-details">
            <div class="detail-item">
                <span>Room:</span>
                <strong><?php echo htmlspecialchars($booking_data['title']); ?></strong>
            </div>
            <div class="detail-item">
                <span>Amount:</span>
                <strong class="price">Rs. <?php echo number_format($booking_data['price_per_month'], 2); ?></strong>
            </div>
        </div>
        
        <div class="payment-methods">
            <div class="method-tabs">
                <button class="tab-btn active" data-tab="card">Credit Card</button>
                <button class="tab-btn" data-tab="khalti">Khalti</button>
                <button class="tab-btn" data-tab="esewa">eSewa</button>
            </div>
            
            <div class="tab-content active" id="card-tab">
                <form method="POST" class="payment-form" id="card-form">
                    <input type="hidden" name="payment_method" value="card">
                    <div class="form-group">
                        <label for="card_number">
                            <i class="fas fa-credit-card"></i> Card Number
                        </label>
                        <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="expiry">
                                <i class="fas fa-calendar-alt"></i> Expiry Date
                            </label>
                            <input type="text" id="expiry" name="expiry" placeholder="MM/YY" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="cvv">
                                <i class="fas fa-lock"></i> CVV
                            </label>
                            <input type="text" id="cvv" name="cvv" placeholder="123" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="pay-now-btn">
                        <i class="fas fa-lock"></i> Pay Now
                    </button>
                </form>
            </div>
            
            <div class="tab-content" id="khalti-tab">
                <div class="qr-payment-method">
                    <div class="qr-code-container">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=khalti:<?php echo $booking_id ?>" alt="Khalti QR Code">
                        <p>Scan this QR code with Khalti App</p>
                    </div>
                    <p class="or-divider">- OR -</p>
                    <form method="POST" class="payment-form" id="khalti-form">
                        <input type="hidden" name="payment_method" value="khalti">
                        <div class="form-group">
                            <label for="khalti_mobile">
                                <i class="fas fa-mobile-alt"></i> Khalti Mobile Number
                            </label>
                            <input type="text" id="khalti_mobile" name="khalti_mobile" placeholder="98XXXXXXXX" required>
                        </div>
                        <div class="form-group">
                            <label for="khalti_pin">
                                <i class="fas fa-lock"></i> Khalti MPIN
                            </label>
                            <input type="password" id="khalti_pin" name="khalti_pin" placeholder="Enter MPIN" required>
                        </div>
                        <button type="submit" class="pay-now-btn khalti-btn">
                            <i class="fas fa-wallet"></i> Pay with Khalti
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="tab-content" id="esewa-tab">
                <div class="qr-payment-method">
                    <div class="qr-code-container">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=esewa:<?php echo $booking_id ?>" alt="eSewa QR Code">
                        <p>Scan this QR code with eSewa App</p>
                    </div>
                    <p class="or-divider">- OR -</p>
                    <form method="POST" class="payment-form" id="esewa-form">
                        <input type="hidden" name="payment_method" value="esewa">
                        <div class="form-group">
                            <label for="esewa_username">
                                <i class="fas fa-user"></i> eSewa Username
                            </label>
                            <input type="text" id="esewa_username" name="esewa_username" placeholder="username" required>
                        </div>
                        <div class="form-group">
                            <label for="esewa_password">
                                <i class="fas fa-lock"></i> eSewa Password
                            </label>
                            <input type="password" id="esewa_password" name="esewa_password" placeholder="Password" required>
                        </div>
                        <button type="submit" class="pay-now-btn esewa-btn">
                            <i class="fas fa-wallet"></i> Pay with eSewa
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="payment-security">
            <i class="fas fa-shield-alt"></i>
            <span>Secure payment encrypted with SSL</span>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
