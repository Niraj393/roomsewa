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

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Mark as read if requested
if (isset($_GET['mark_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $_GET['mark_read'], $_SESSION['user_id']);
    $stmt->execute();
}

// Get all notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$notifications = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Notifications</title>
    <style>
        .notification {
            padding: 15px;
            margin: 10px 0;
            background: #f9f9f9;
            border-left: 3px solid #4CAF50;
        }
        .unread {
            border-left-color: #f44336;
            background: #fff;
        }
        .time {
            color: #777;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <h1>My Notifications</h1>
    
    <?php while ($note = $notifications->fetch_assoc()): ?>
        <div class="notification <?= $note['is_read'] ? '' : 'unread' ?>">
            <p><?= htmlspecialchars($note['message']) ?></p>
            <?php if ($note['link']): ?>
                <p><a href="<?= htmlspecialchars($note['link']) ?>">View Details</a></p>
            <?php endif; ?>
            <p class="time">
                <?= date('M j, Y g:i a', strtotime($note['created_at'])) ?>
                <?php if (!$note['is_read']): ?>
                    | <a href="?mark_read=<?= $note['id'] ?>">Mark as read</a>
                <?php endif; ?>
            </p>
        </div>
    <?php endwhile; ?>
    
    <p><a href="renter_dashboard.php">Back to Dashboard</a></p>
</body>
</html>