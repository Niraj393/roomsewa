<?php
require 'includes/header.php';
?>
<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "roomsewa";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
require 'notification_functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'renter') {
    header("Location: login.php");
    exit();
}

// Get search parameters
$search = $_GET['search'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$location = $_GET['location'] ?? '';
$sort = $_GET['sort'] ?? 'relevance';

// Base query
$query = "SELECT r.*, u.name as owner_name FROM rooms r 
          JOIN users u ON r.owner_id = u.id 
          WHERE r.status = 'available'";

// Add filters
if (!empty($search)) {
    $query .= " AND (r.title LIKE '%" . $conn->real_escape_string($search) . "%' 
              OR r.description LIKE '%" . $conn->real_escape_string($search) . "%')";
}

if (!empty($location)) {
    $query .= " AND r.location LIKE '%" . $conn->real_escape_string($location) . "%'";
}

if (!empty($max_price) && is_numeric($max_price)) {
    $query .= " AND r.price_per_month <= " . (float)$max_price;
}

// Add sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY r.price_per_month ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY r.price_per_month DESC";
        break;
    case 'newest':
        $query .= " ORDER BY r.id DESC";
        break;
    default: // relevance
        if (!empty($search) || !empty($location)) {
            $query .= " ORDER BY 
                CASE 
                    WHEN r.title LIKE '%" . $conn->real_escape_string($search) . "%' THEN 1
                    WHEN r.description LIKE '%" . $conn->real_escape_string($search) . "%' THEN 2
                    ELSE 3
                END, r.price_per_month ASC";
        }
}

$rooms = $conn->query($query);
$unread_count = $notifications->getUnreadCount($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Rooms - RoomSewa</title>
    <style>
        /* Reuse styles from renter_dashboard.php */
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --light: #f8f9fa;
            --dark: #212529;
        }
        
        body {
            background-color: #f1f3ff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .search-box {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .search-form input, .search-form select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        
        .search-form button {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        
        .search-form button:hover {
            background: var(--secondary);
        }
        
        .sort-options {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        .sort-options a {
            padding: 8px 15px;
            background: #e9ecef;
            border-radius: 20px;
            text-decoration: none;
            color: var(--dark);
            font-size: 14px;
        }
        
        .sort-options a.active {
            background: var(--primary);
            color: white;
        }
        
        /* Reuse room cards styles from renter_dashboard.php */
        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .room-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .room-card:hover {
            transform: translateY(-5px);
        }
        
        /* ... (include all other styles from renter_dashboard.php) ... */
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="welcome-message">
                <h1>Search Rooms</h1>
                <p>Find your perfect accommodation</p>
            </div>
            <a href="notifications.php" class="notification-badge">
                <span class="notification-icon">🔔</span>
                <?php if ($unread_count > 0): ?>
                    <span class="badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
        </header>
        
        <div class="search-box">
            <form method="GET" action="search.php" class="search-form">
                <input type="text" name="search" placeholder="Search by name or features" value="<?php echo htmlspecialchars($search); ?>">
                <input type="number" name="max_price" placeholder="Max price (Rs.)" value="<?php echo htmlspecialchars($max_price); ?>">
                <input type="text" name="location" placeholder="Location" value="<?php echo htmlspecialchars($location); ?>">
                <button type="submit">Search</button>
            </form>
            
            <div class="sort-options">
                <span>Sort by:</span>
                <a href="?search=<?= urlencode($search) ?>&max_price=<?= $max_price ?>&location=<?= urlencode($location) ?>&sort=relevance" class="<?= $sort === 'relevance' ? 'active' : '' ?>">Relevance</a>
                <a href="?search=<?= urlencode($search) ?>&max_price=<?= $max_price ?>&location=<?= urlencode($location) ?>&sort=price_low" class="<?= $sort === 'price_low' ? 'active' : '' ?>">Price (Low)</a>
                <a href="?search=<?= urlencode($search) ?>&max_price=<?= $max_price ?>&location=<?= urlencode($location) ?>&sort=price_high" class="<?= $sort === 'price_high' ? 'active' : '' ?>">Price (High)</a>
                <a href="?search=<?= urlencode($search) ?>&max_price=<?= $max_price ?>&location=<?= urlencode($location) ?>&sort=newest" class="<?= $sort === 'newest' ? 'active' : '' ?>">Newest</a>
            </div>
        </div>
        
        <h2>Search Results</h2>
        
        <?php if ($rooms->num_rows > 0): ?>
            <div class="rooms-grid">
                <?php while ($room = $rooms->fetch_assoc()): ?>
                    <div class="room-card">
                        <?php if (!empty($room['image'])): ?>
                            <img src="<?php echo htmlspecialchars($room['image']); ?>" alt="Room image" class="room-image">
                        <?php endif; ?>
                        
                        <div class="room-details">
                            <h3 class="room-title"><?php echo htmlspecialchars($room['title']); ?></h3>
                            <p class="room-info"><strong>Location:</strong> <?php echo htmlspecialchars($room['location']); ?></p>
                            <p class="room-info"><strong>Owner:</strong> <?php echo htmlspecialchars($room['owner_name']); ?></p>
                            <p class="room-price">Rs. <?php echo number_format($room['price_per_month'], 2); ?>/month</p>
                            
                            <form method="POST" action="renter_dashboard.php">
                                <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                <button type="submit" name="book_room" class="btn">Book Now</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No rooms found matching your criteria. Try adjusting your search filters.</p>
        <?php endif; ?>
        
        <a href="dashboard.php" class="logout-link">← Back to Dashboard</a>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>