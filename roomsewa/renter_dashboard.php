<?php
require 'includes/header.php';

// Start session and connect to database
session_start();
$conn = new mysqli('localhost', 'root', '', 'roomsewa');

// Check database connection
if ($conn->connect_error) {
    die("Oops! We're having trouble connecting to our system. Please try again later.");
}

// Verify user is logged in as a renter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'renter') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Notification functions
function sendNotification($conn, $user_id, $message, $link) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $message, $link);
    return $stmt->execute();
}

function getUnreadNotifications($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Handle room booking when form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_room'])) {
    $room_id = (int)$_POST['room_id'];
    
    try {
        // Get available room details
        $room_stmt = $conn->prepare("SELECT r.*, u.name as owner_name 
                                   FROM rooms r 
                                   JOIN users u ON r.owner_id = u.id 
                                   WHERE r.id = ? AND r.status = 'available'");
        $room_stmt->bind_param("i", $room_id);
        $room_stmt->execute();
        $room = $room_stmt->get_result()->fetch_assoc();
        
        if (!$room) {
            throw new Exception("Sorry, this room is no longer available for booking.");
        }

        // Create new booking
        $booking_stmt = $conn->prepare("INSERT INTO bookings 
                                      (room_id, renter_id, owner_id, monthly_rate, status) 
                                      VALUES (?, ?, ?, ?, 'pending')");
        $booking_stmt->bind_param("iiid", $room_id, $user_id, $room['owner_id'], $room['price_per_month']);
        
        if ($booking_stmt->execute()) {
            $booking_id = $booking_stmt->insert_id;
            
            // Mark room as booked
            $conn->query("UPDATE rooms SET status = 'booked' WHERE id = $room_id");
            
            // Send notifications
            $owner_notification = "New booking request for {$room['title']} from {$_SESSION['name']}";
            sendNotification($conn, $room['owner_id'], $owner_notification, 'owner_dashboard.php');
            
            $renter_notification = "Booking request sent for {$room['title']}. Complete payment to confirm.";
            sendNotification($conn, $user_id, $renter_notification, 'payment.php?booking_id='.$booking_id);
            
            // Redirect to payment
            $_SESSION['success'] = "Booking successful! Please complete payment to confirm.";
            header("Location: payment.php?booking_id=$booking_id");
            exit();
        } else {
            throw new Exception("We couldn't process your booking. Please try again.");
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: renter_dashboard.php");
        exit();
    }
}

// Get search filters from URL
$search = isset($_GET['search']) ? trim($conn->real_escape_string($_GET['search'])) : '';
$min_price = isset($_GET['min_price']) ? max(0, (float)$_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? max($min_price, (float)$_GET['max_price']) : 100000;
$location = isset($_GET['location']) ? trim($conn->real_escape_string($_GET['location'])) : '';

// Build SQL query with filters
$query = "SELECT r.*, u.name as owner_name 
          FROM rooms r 
          JOIN users u ON r.owner_id = u.id 
          WHERE r.status = 'available' 
          AND r.price_per_month BETWEEN ? AND ?";

$params = [$min_price, $max_price];
$types = "dd";

// Add search term filter
if (!empty($search)) {
    $query .= " AND (r.title LIKE ? OR r.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

// Add location filter
if (!empty($location)) {
    $query .= " AND r.location LIKE ?";
    $params[] = "%$location%";
    $types .= "s";
}

// Add pagination
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$rooms_per_page = 6;
$offset = ($page - 1) * $rooms_per_page;
$query .= " LIMIT ? OFFSET ?";
$params[] = $rooms_per_page;
$params[] = $offset;
$types .= "ii";

// Get filtered rooms
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rooms = $stmt->get_result();

// Count total available rooms (without pagination)
$count_query = "SELECT COUNT(*) as total FROM rooms r 
                WHERE r.status = 'available' 
                AND r.price_per_month BETWEEN ? AND ?";
$count_params = [$min_price, $max_price];
$count_types = "dd";

if (!empty($search)) {
    $count_query .= " AND (r.title LIKE ? OR r.description LIKE ?)";
    $count_params[] = "%$search%";
    $count_params[] = "%$search%";
    $count_types .= "ss";
}

if (!empty($location)) {
    $count_query .= " AND r.location LIKE ?";
    $count_params[] = "%$location%";
    $count_types .= "s";
}

$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param($count_types, ...$count_params);
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_rooms = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rooms / $rooms_per_page);

// Get notifications for current user
$notifications = getUnreadNotifications($conn, $user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Your Perfect Room | RoomSewa</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Dashboard Header -->
        <header class="dashboard-header">
            <div class="welcome-message">
                <h2>Welcome back, <?= htmlspecialchars($_SESSION['name']) ?>!</h2>
                <p>Browse available rooms and find your perfect space</p>
            </div>
            
            <div class="header-actions">
                <div class="notification-dropdown">
                    <button class="notification-btn">
                        <i class="fas fa-bell"></i>
                        <?php if (count($notifications) > 0): ?>
                            <span class="notification-badge"><?= count($notifications) ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="notification-content">
                        <?php if (count($notifications) > 0): ?>
                            <?php foreach ($notifications as $notification): ?>
                                <a href="<?= htmlspecialchars($notification['link']) ?>">
                                    <div class="notification-item">
                                        <p><?= htmlspecialchars($notification['message']) ?></p>
                                        <small><?= date('M d, h:i A', strtotime($notification['created_at'])) ?></small>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="notification-item">
                                <p>No new notifications</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </header>

        <!-- Rest of your existing HTML remains exactly the same -->
        <!-- Notification Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Search Section -->
        <section class="search-section">
            <h2><i class="fas fa-search"></i> Find Rooms</h2>
            <form method="GET" class="search-form">
                <div class="search-fields">
                    <div class="form-group">
                        <input type="text" name="search" placeholder="Search by name or description" 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <div class="form-group">
                        <input type="text" name="location" placeholder="Enter location" 
                               value="<?= htmlspecialchars($location) ?>">
                    </div>
                    
                    <div class="form-group price-range">
                        <label>Price Range:</label>
                        <div class="range-inputs">
                            <input type="number" name="min_price" placeholder="Min price" 
                                   value="<?= $min_price ?>" min="0">
                            <span>to</span>
                            <input type="number" name="max_price" placeholder="Max price" 
                                   value="<?= $max_price ?>" min="0">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn search-button">
                        <i class="fas fa-search"></i> Search Rooms
                    </button>
                </div>
            </form>
        </section>

        <!-- Available Rooms Section -->
        <main class="rooms-section">
            <h2><i class="fas fa-home"></i> Available Rooms 
                <span class="results-count">(Showing <?= $rooms->num_rows ?> of <?= $total_rooms ?> rooms)</span>
            </h2>
            
            <?php if ($rooms->num_rows > 0): ?>
                <div class="rooms-grid">
                    <?php while ($room = $rooms->fetch_assoc()): ?>
                        <article class="room-card">
                            <div class="room-image">
                                <?php if (!empty($room['image'])): ?>
                                    <img src="<?= htmlspecialchars($room['image']) ?>" 
                                         alt="<?= htmlspecialchars($room['title']) ?>">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-home"></i>
                                        <span>No image available</span>
                                    </div>
                                <?php endif; ?>
                                <div class="room-badge price">
                                    Rs. <?= number_format($room['price_per_month'], 2) ?>/month
                                </div>
                            </div>
                            
                            <div class="room-info">
                                <h3><?= htmlspecialchars($room['title']) ?></h3>
                                
                                <div class="room-meta">
                                    <span class="location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?= htmlspecialchars($room['location']) ?>
                                    </span>
                                    <span class="owner">
                                        <i class="fas fa-user-tie"></i>
                                        <?= htmlspecialchars($room['owner_name']) ?>
                                    </span>
                                </div>
                                
                                <p class="room-description">
                                    <?= htmlspecialchars(substr($room['description'], 0, 120)) ?>
                                    <?= strlen($room['description']) > 120 ? '...' : '' ?>
                                </p>
                                
                                <form method="POST" class="booking-form">
                                    <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                    <button type="submit" name="book_room" class="btn book-button">
                                        <i class="fas fa-calendar-check"></i> Book This Room
                                    </button>
                                    <a href="room_details.php?id=<?= $room['id'] ?>" class="btn details-button">
                                        <i class="fas fa-info-circle"></i> View Details
                                    </a>
                                </form>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
                
                <!-- Pagination -->
                 <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?= $i ?>" class="page-link <?= $i == $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>" class="page-link">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="no-rooms">
                    <i class="fas fa-home"></i>
                    <p>You haven't listed any rooms yet.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
    // Notification handling
    document.querySelector('.notification-btn')?.addEventListener('click', function() {
        // Mark notifications as read via AJAX
        fetch('mark_notifications_read.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelectorAll('.notification-badge').forEach(el => el.remove());
                }
            });
    });
    </script>
</body>
</html>