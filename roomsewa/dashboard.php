<?php
$pageTitle = "Dashboard | RoomSewa";
require 'includes/header.php';

// Start session and connect to database
session_start();
$conn = new mysqli('localhost', 'root', '', 'roomsewa');

// Check database connection
if ($conn->connect_error) {
    die("Oops! We're having trouble connecting to our system. Please try again later.");
}

// Notification functions (modified to work with both roles)
function sendNotification($conn, $user_id, $message, $link, $role = null) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $message, $link, $role);
    return $stmt->execute();
}

function getUnreadNotifications($conn, $user_id, $role) {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND (role = ? OR role IS NULL) AND is_read = 0 ORDER BY created_at DESC");
    $stmt->bind_param("is", $user_id, $role);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get user role and ID if logged in
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

// Get notifications if logged in
$notifications = array();
if ($user_id) {
    $notifications = getUnreadNotifications($conn, $user_id, $user_role);
}

// Handle room booking when form is submitted (only for renters)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_room'])) {
    if ($user_role !== 'renter') {
        $_SESSION['error'] = "You need to be logged in as a renter to book rooms.";
        header("Location: login.php");
        exit();
    }

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
            sendNotification($conn, $room['owner_id'], $owner_notification, 'owner_dashboard.php', 'owner');
            
            $renter_notification = "Booking request sent for {$room['title']}. Complete payment to confirm.";
            sendNotification($conn, $user_id, $renter_notification, 'payment.php?booking_id='.$booking_id, 'renter');
            
            // Redirect to payment
            $_SESSION['success'] = "Booking successful! Please complete payment to confirm.";
            header("Location: payment.php?booking_id=$booking_id");
            exit();
        } else {
            throw new Exception("We couldn't process your booking. Please try again.");
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: dashboard.php");
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

$params = array($min_price, $max_price);
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
$count_params = array($min_price, $max_price);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Dashboard Header (only for logged in users) -->
        <?php if ($user_id): ?>
        <header class="dashboard-header">
            <div class="welcome-message">
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
                <p><?php echo $user_role === 'owner' ? 'Manage your properties' : 'Find your perfect space'; ?></p>
            </div>
            
            <div class="header-actions">
                <?php if ($user_role === 'owner'): ?>
                    <a href="owner_dashboard.php" class="btn btn-primary">
                        <i class="fas fa-tachometer-alt"></i> Owner Dashboard
                    </a>
                <?php elseif ($user_role === 'renter'): ?>
                    <a href="renter_dashboard.php" class="btn btn-primary">
                        <i class="fas fa-tachometer-alt"></i> Renter Dashboard
                    </a>
                <?php endif; ?>
                
                <div class="notification-dropdown">
                    <button class="notification-btn">
                        <i class="fas fa-bell"></i>
                        <?php if (count($notifications) > 0): ?>
                            <span class="notification-badge"><?php echo count($notifications); ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="notification-content">
                        <?php if (count($notifications) > 0): ?>
                            <?php foreach ($notifications as $notification): ?>
                                <a href="<?php echo htmlspecialchars($notification['link']); ?>">
                                    <div class="notification-item">
                                        <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                        <small><?php echo date('M d, h:i A', strtotime($notification['created_at'])); ?></small>
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
        <?php else: ?>
        <header class="dashboard-header guest">
            <div class="welcome-message">
                <h2>Welcome to RoomSewa</h2>
                <p>Find your perfect space. Please <a href="login.php">login</a> to book rooms.</p>
            </div>
            <div class="header-actions">
                <a href="login.php" class="btn btn-primary">Login</a>
                <a href="register.php" class="btn btn-secondary">Register</a>
            </div>
        </header>
        <?php endif; ?>

        <!-- Notification Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($_SESSION['error']); ?>
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
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="form-group">
                        <input type="text" name="location" placeholder="Enter location" 
                               value="<?php echo htmlspecialchars($location); ?>">
                    </div>
                    
                    <div class="form-group price-range">
                        <label>Price Range:</label>
                        <div class="range-inputs">
                            <input type="number" name="min_price" placeholder="Min price" 
                                   value="<?php echo $min_price; ?>" min="0">
                            <span>to</span>
                            <input type="number" name="max_price" placeholder="Max price" 
                                   value="<?php echo $max_price; ?>" min="0">
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
                <span class="results-count">(Showing <?php echo $rooms->num_rows; ?> of <?php echo $total_rooms; ?> rooms)</span>
            </h2>
            
            <?php if ($rooms->num_rows > 0): ?>
                <div class="rooms-grid">
                    <?php while ($room = $rooms->fetch_assoc()): ?>
                        <article class="room-card">
                            <div class="room-image">
                                <?php if (!empty($room['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($room['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($room['title']); ?>">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-home"></i>
                                        <span>No image available</span>
                                    </div>
                                <?php endif; ?>
                                <div class="room-badge price">
                                    Rs. <?php echo number_format($room['price_per_month'], 2); ?>/month
                                </div>
                            </div>
                            
                            <div class="room-info">
                                <h3><?php echo htmlspecialchars($room['title']); ?></h3>
                                
                                <div class="room-meta">
                                    <span class="location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($room['location']); ?>
                                    </span>
                                    <span class="owner">
                                        <i class="fas fa-user-tie"></i>
                                        <?php echo htmlspecialchars($room['owner_name']); ?>
                                    </span>
                                </div>
                                
                                <p class="room-description">
                                    <?php echo htmlspecialchars(substr($room['description'], 0, 120)); ?>
                                    <?php echo strlen($room['description']) > 120 ? '...' : ''; ?>
                                </p>
                                
                                <div class="room-actions">
                                    <?php if ($user_role === 'renter'): ?>
                                        <form method="POST" class="booking-form">
                                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                            <button type="submit" name="book_room" class="btn book-button">
                                                <i class="fas fa-calendar-check"></i> Book This Room
                                            </button>
                                        </form>
                                    <?php elseif (!$user_id): ?>
                                        <a href="login.php?redirect=book&room_id=<?php echo $room['id']; ?>" class="btn book-button">
                                            <i class="fas fa-sign-in-alt"></i> Login to Book
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="room_details.php?id=<?php echo $room['id']; ?>" class="btn details-button">
                                        <i class="fas fa-info-circle"></i> View Details
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, array('page' => $page - 1))); ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, array('page' => $i))); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, array('page' => $page + 1))); ?>" class="page-link">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-rooms">
                    <i class="fas fa-home"></i>
                    <p>No rooms available matching your criteria.</p>
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