<?php
// booking.php - Handles room booking process
session_start();
$conn = new mysqli("localhost", "root", "", "roomsewa");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in as renter
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'renter') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$booking_success = false;
$error_msg = "";

// Process booking when form is submitted
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_room'])) {
    
    $room_id = intval($_POST['room_id']);
    $move_in_date = $_POST['move_in_date'];
    
    // Basic validation
    if(empty($move_in_date)) {
        $error_msg = "Please select move-in date";
    } else {
        // Check room availability
        $check_sql = "SELECT id, owner_id, price_per_month FROM rooms WHERE id=$room_id AND status='available'";
        $result = mysqli_query($conn, $check_sql);
        
        if(mysqli_num_rows($result) == 1) {
            $room = mysqli_fetch_assoc($result);
            
            // Create booking record
            $booking_date = date('Y-m-d H:i:s');
            $insert_sql = "INSERT INTO bookings 
                          (room_id, renter_id, owner_id, booking_date, move_in_date, monthly_rate, status)
                          VALUES ($room_id, $user_id, {$room['owner_id']}, '$booking_date', '$move_in_date', {$room['price_per_month']}, 'pending')";
            
            if(mysqli_query($conn, $insert_sql)) {
                // Update room status
                mysqli_query($conn, "UPDATE rooms SET status='booked' WHERE id=$room_id");
                $booking_id = mysqli_insert_id($conn);
                header("Location: payment.php?booking_id=$booking_id");
                 exit();
            } else {
                $error_msg = "Booking failed: " . mysqli_error($conn);
            }
        } else {
            $error_msg = "Room not available for booking";
        }
    }
}

// Get booking history
$bookings = mysqli_query($conn, "SELECT 
    b.id, b.booking_date, b.move_in_date, b.status, b.monthly_rate,
    r.title, r.location, r.image,
    u.name as owner_name
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN users u ON b.owner_id = u.id
    WHERE b.renter_id = $user_id
    ORDER BY b.booking_date DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Bookings</title>
    <style>
        body { font-family: Arial; margin: 0; padding: 20px; background: linear-gradient(135deg, #e91e1eff 0%, #15cfccff 100%); }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { background: #3a5f8d; color: white; padding: 20px; border-radius: 5px; }
        .booking-card { 
            background: white; 
            padding: 20px; 
            margin: 15px 0; 
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .booking-card img { width: 150px; float: left; margin-right: 20px; }
        .status-pending { color: #e67e22; }
        .status-confirmed { color: #27ae60; }
        .status-rejected { color: #e74c3c; }
        .form-group { margin-bottom: 15px; }
        input, button { padding: 8px; }
        button { background: #3a5f8d; color: white; border: none; cursor: pointer; }
        .error { color: red; padding: 10px; background: #ffeeee; }
        .success { color: green; padding: 10px; background: #eeffee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>My Bookings</h1>
            <p>Welcome, <?php echo $_SESSION['name']; ?></p>
        </div>

        <?php if($booking_success): ?>
            <div class="success">
                Booking successful! Owner will contact you soon.
            </div>
        <?php elseif(!empty($error_msg)): ?>
            <div class="error">
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <h2>Booking History</h2>
        
        <?php if(mysqli_num_rows($bookings) > 0): ?>
            <?php while($booking = mysqli_fetch_assoc($bookings)): ?>
                <div class="booking-card">
                    <?php if(!empty($booking['image'])): ?>
                        <img src="<?php echo $booking['image']; ?>" alt="Room Image">
                    <?php endif; ?>
                    
                    <h3><?php echo $booking['title']; ?></h3>
                    <p>Location: <?php echo $booking['location']; ?></p>
                    <p>Owner: <?php echo $booking['owner_name']; ?></p>
                    <p>Rent: Rs. <?php echo $booking['monthly_rate']; ?>/month</p>
                    <p>Move-in Date: <?php echo date('M d, Y', strtotime($booking['move_in_date'])); ?></p>
                    <p>Status: 
                        <span class="status-<?php echo $booking['status']; ?>">
                            <?php echo ucfirst($booking['status']); ?>
                        </span>
                    </p>
                    <div style="clear:both;"></div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>You haven't made any bookings yet.</p>
        <?php endif; ?>
        
        <p><a href="renter_dashboard.php">← Back to Available Rooms</a></p>
    </div>
</body>
</html>