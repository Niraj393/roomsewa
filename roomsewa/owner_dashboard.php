<?php
$pageTitle = "Owner Dashboard | RoomSewa";
require 'includes/header.php';

session_start();
$conn = new mysqli('localhost', 'root', '', 'roomsewa');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Notification functions
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
// Get notifications for owner
$notifications = getUnreadNotifications($conn, $_SESSION['user_id'], 'owner');
// Security checks
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: login.php");
    exit();
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle room upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_room'])) {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    // Input sanitization
    $title = htmlspecialchars(trim($_POST['title']));
    $location = htmlspecialchars(trim($_POST['location']));
    $description = htmlspecialchars(trim($_POST['description']));
    $price = floatval($_POST['price']);
    $status = 'available';
    $owner_id = $_SESSION['user_id'];

    // File upload handling
    if (isset($_FILES["image"])) {
        $allowed = ['image/jpeg', 'image/png'];
        $fileType = $_FILES["image"]["type"];
        
        if (in_array($fileType, $allowed)) {
            $targetDir = "uploads/";
            $imageName = uniqid() . '_' . basename($_FILES["image"]["name"]);
            $targetFilePath = $targetDir . $imageName;
            
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
                $stmt = $conn->prepare("INSERT INTO rooms (owner_id, title, location, description, price_per_month, image, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssdss", $owner_id, $title, $location, $description, $price, $targetFilePath, $status);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Room added successfully!";
                } else {
                    $_SESSION['error'] = "Error adding room: " . $conn->error;
                }
            }
        } else {
            $_SESSION['error'] = "Only JPG and PNG files are allowed.";
        }
    }
}

// Fetch owner's rooms with pagination
$owner_id = $_SESSION['user_id'];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Get total count
$total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM rooms WHERE owner_id = ?");
$total_stmt->bind_param("i", $owner_id);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_rooms = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rooms / $limit);

// Get paginated rooms
$stmt = $conn->prepare("SELECT * FROM rooms WHERE owner_id = ? ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $owner_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="owner-dashboard">
    <div class="dashboard-header">
        <h1>Owner Dashboard</h1>
        <div class="user-info">
            <span>Welcome, <?= htmlspecialchars($_SESSION['name']) ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert success">
            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error">
            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-content">
  <div class="notification-dropdown">
    <button class="notification-btn">
        <i class="fas fa-bell"></i>
        <?php if (count($notifications) > 0): ?>
            <span class="notification-badge"><?= count($notifications) ?></span>
        <?php endif; ?>
    </button>
    <div class="notification-content">
        <div class="notification-header">
            <h4>Notifications</h4>
            <a href="all_notifications.php" class="view-all">View All</a>
        </div>
        <div class="notification-items">
            <?php if (count($notifications) > 0): ?>
                <?php foreach (array_slice($notifications, 0, 5) as $notification): ?>
                    <a href="<?= htmlspecialchars($notification['link']) ?>" class="notification-item">
                        <div class="notification-text">
                            <p><?= htmlspecialchars($notification['message']) ?></p>
                            <small><?= date('M d, h:i A', strtotime($notification['created_at'])) ?></small>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="notification-item empty">
                    <p>No new notifications</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
        <!-- Add Room Form -->
        <section class="add-room-section">
            <h2><i class="fas fa-plus-circle"></i> Add New Room</h2>
            <form method="POST" enctype="multipart/form-data" class="room-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title">Room Title</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price (Rs/month)</label>
                        <input type="number" step="0.01" id="price" name="price" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Room Image</label>
                        <input type="file" id="image" name="image" accept="image/*" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4" required></textarea>
                    </div>
                </div>
                
                <button type="submit" name="upload_room" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Add Room
                </button>
            </form>
        </section>

        <!-- Owner's Rooms Listing -->
        <section class="rooms-section">
            <h2><i class="fas fa-home"></i> Your Rooms (<?= $total_rooms ?>)</h2>
            
            <?php if ($result->num_rows > 0): ?>
                <div class="rooms-grid">
                    <?php while ($room = $result->fetch_assoc()): ?>
                        <div class="room-card <?= $room['status'] === 'available' ? 'available' : 'booked' ?>">
                            <div class="room-image">
                                <?php if (!empty($room['image'])): ?>
                                    <img src="<?= htmlspecialchars($room['image']) ?>" alt="<?= htmlspecialchars($room['title']) ?>">
                                <?php else: ?>
                                    <div class="no-image"><i class="fas fa-home"></i></div>
                                <?php endif; ?>
                                <span class="room-status"><?= ucfirst($room['status']) ?></span>
                                <span class="room-price">Rs. <?= number_format($room['price_per_month'], 2) ?></span>
                            </div>
                            
                            <div class="room-details">
                                <h3><?= htmlspecialchars($room['title']) ?></h3>
                                <p class="location"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($room['location']) ?></p>
                                <p class="description"><?= htmlspecialchars(substr($room['description'], 0, 100)) ?><?= strlen($room['description']) > 100 ? '...' : '' ?></p>
                                
                                <div class="room-actions">
                                    <a href="edit_room.php?id=<?= $room['id'] ?>" class="btn btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="delete_room.php?id=<?= $room['id'] ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this room?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
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
        </section>
    </div>
</div>
<script>
// Better notification handling
document.addEventListener('DOMContentLoaded', function() {
    const notificationBtn = document.querySelector('.notification-btn');
    const notificationDropdown = document.querySelector('.notification-content');
    
    // Toggle dropdown on click
    notificationBtn?.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationDropdown.style.display = 
            notificationDropdown.style.display === 'block' ? 'none' : 'block';
        
        // Mark as read when opened
        if (notificationDropdown.style.display === 'block') {
            fetch('mark_notifications_read.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelectorAll('.notification-badge').forEach(el => el.remove());
                    }
                });
        }
    });
    
    // Close when clicking outside
    document.addEventListener('click', function() {
        notificationDropdown.style.display = 'none';
    });
    
    // Prevent dropdown from closing when clicking inside
    notificationDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });
});
</script>
<?php include 'includes/footer.php'; ?>
