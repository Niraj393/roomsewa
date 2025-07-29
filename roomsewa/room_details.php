<?php
require 'includes/header.php';

// Database connection
$conn = new mysqli('localhost', 'root', '', 'roomsewa');
if ($conn->connect_error) die("Connection failed");

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get room ID from URL
$room_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch room details
$room = $conn->query("SELECT r.*, u.name as owner_name 
                     FROM rooms r JOIN users u ON r.owner_id = u.id 
                     WHERE r.id = $room_id")->fetch_assoc();

if (!$room) {
    header("Location: renter_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($room['title']) ?> | RoomSewa</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            color: #3498db;
            text-decoration: none;
        }
        .room-header {
            margin-bottom: 20px;
        }
        .room-header h1 {
            color: #333;
            margin-bottom: 5px;
        }
        .room-meta {
            color: #777;
            margin-bottom: 15px;
        }
        .room-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .no-image {
            height: 400px;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .room-details {
            display: flex;
            gap: 30px;
        }
        .main-content {
            flex: 2;
        }
        .sidebar {
            flex: 1;
        }
        .price-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .price {
            font-size: 24px;
            font-weight: bold;
            color: #2ecc71;
        }
        .book-btn {
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        .owner-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        @media (max-width: 768px) {
            .room-details {
                flex-direction: column;
            }
            .room-image, .no-image {
                height: 250px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="renter_dashboard.php" class="back-btn">← Back to listings</a>
        
        <div class="room-header">
            <h1><?= htmlspecialchars($room['title']) ?></h1>
            <div class="room-meta">
                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($room['location']) ?>
            </div>
        </div>
        
        <?php if (!empty($room['image'])): ?>
            <img src="<?= htmlspecialchars($room['image']) ?>" class="room-image" alt="Room image">
        <?php else: ?>
            <div class="no-image">
                <i class="fas fa-home fa-3x"></i>
            </div>
        <?php endif; ?>
        
        <div class="room-details">
            <div class="main-content">
                <h2>Description</h2>
                <p><?= nl2br(htmlspecialchars($room['description'])) ?></p>
            </div>
            
            <div class="sidebar">
                <div class="price-box">
                    <div class="price">Rs. <?= number_format($room['price_per_month'], 2) ?>/month</div>
                    <form method="POST" action="renter_dashboard.php">
                        <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                        <button type="submit" name="book_room" class="book-btn">
                            Book Now
                        </button>
                    </form>
                </div>
                
                <div class="owner-info">
                    <h3>Contact Owner</h3>
                    <p><strong><?= htmlspecialchars($room['owner_name']) ?></strong></p>
                    <?php if (!empty($room['owner_phone'])): ?>
                        <p><i class="fas fa-phone"></i> <?= htmlspecialchars($room['owner_phone']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="amenities">
    <h2>Amenities</h2>
    <ul>
        <li><i class="fas fa-wifi"></i> WiFi</li>
        <li><i class="fas fa-tv"></i> TV</li>
        <li><i class="fas fa-parking"></i> Parking</li>
    </ul>
  </div>
  <div class="rating">
    <h2>Rating</h2>
    <div class="stars">
        <?php for ($i = 0; $i < 5; $i++): ?>
            <i class="fas fa-star <?= $i < 3 ? 'filled' : '' ?>"></i>
        <?php endfor; ?>
        <span>(3.0)</span>
    </div>
</div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>