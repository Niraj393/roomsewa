<?php
require 'includes/header.php';
?>
<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'roomsewa');

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Verify booking ID
if(empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$booking_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Get booking details
$stmt = $conn->prepare("
    SELECT b.*, r.title, r.price_per_month 
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.id = ? AND b.renter_id = ?
");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if(!$booking) {
    header("Location: renter_dashboard.php");
    exit();
}
?>

    <div class="success-container">
        <h1>Payment Successful!</h1>
        <div class="booking-details">
            <p>Booking ID: <?php echo $booking_id; ?></p>
            <p>Room: <?php echo htmlspecialchars($booking['title']); ?></p>
            <p>Amount: Rs. <?php echo number_format($booking['price_per_month'], 2); ?></p>
        </div>
        <a href="renter_dashboard.php" class="btn">Return to Dashboard</a>
    </div>
<?php include 'includes/footer.php'; ?>