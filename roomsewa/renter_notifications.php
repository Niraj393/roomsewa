<?php
require 'includes/header.php';

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'renter') {
    header("Location: login.php");
    exit();
}

// Mark all as read when viewing full list
$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = {$_SESSION['user_id']}");

// Get all renter notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND (role = 'renter' OR role IS NULL) ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$all_notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="renter-notifications">
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-bell"></i> Your Notifications</h1>
            <a href="renter_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <div class="notification-filters">
            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="unread">Unread</button>
            <button class="filter-btn" data-filter="booking">Bookings</button>
            <button class="filter-btn" data-filter="payment">Payments</button>
        </div>
        
        <div class="notifications-list">
            <?php if (!empty($all_notifications)): ?>
                <?php foreach ($all_notifications as $notification): ?>
                    <div class="notification-card" data-type="<?= 
                        strpos(strtolower($notification['message']), 'payment') !== false ? 'payment' : 
                        (strpos(strtolower($notification['message']), 'book') !== false ? 'booking' : 'other') ?>">
                        <div class="notification-icon">
                            <?php if (strpos($notification['message'], 'approved') !== false): ?>
                                <i class="fas fa-check-circle approved"></i>
                            <?php elseif (strpos($notification['message'], 'rejected') !== false): ?>
                                <i class="fas fa-times-circle rejected"></i>
                            <?php elseif (strpos($notification['message'], 'payment') !== false): ?>
                                <i class="fas fa-rupee-sign payment"></i>
                            <?php else: ?>
                                <i class="fas fa-info-circle info"></i>
                            <?php endif; ?>
                        </div>
                        <div class="notification-content">
                            <p><?= htmlspecialchars($notification['message']) ?></p>
                            <div class="notification-meta">
                                <span class="time"><?= date('M d, Y h:i A', strtotime($notification['created_at'])) ?></span>
                                <a href="<?= htmlspecialchars($notification['link']) ?>" class="action-link">View <i class="fas fa-chevron-right"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No notifications found</h3>
                    <p>You'll see important updates here about your bookings and payments</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.renter-notifications {
    padding: 30px 0;
    background: #f9f9f9;
    min-height: 100vh;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.notification-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.filter-btn {
    padding: 8px 15px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 20px;
    cursor: pointer;
    font-size: 0.9rem;
}

.filter-btn.active {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

.notifications-list {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.notification-card {
    display: flex;
    padding: 15px;
    border-bottom: 1px solid #eee;
    transition: all 0.3s;
}

.notification-card:hover {
    background: #f8f9fa;
}

.notification-icon {
    font-size: 1.5rem;
    margin-right: 15px;
    color: #3498db;
}

.notification-icon .approved { color: #2ecc71; }
.notification-icon .rejected { color: #e74c3c; }
.notification-icon .payment { color: #27ae60; }
.notification-icon .info { color: #3498db; }

.notification-content {
    flex: 1;
}

.notification-content p {
    margin: 0 0 5px 0;
    color: #333;
}

.notification-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.time {
    font-size: 0.8rem;
    color: #777;
}

.action-link {
    color: #3498db;
    text-decoration: none;
    font-size: 0.9rem;
}

.empty-state {
    text-align: center;
    padding: 50px 20px;
    color: #777;
}

.empty-state i {
    font-size: 3rem;
    color: #ddd;
    margin-bottom: 15px;
}
</style>

<script>
// Filter notifications
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const filter = this.dataset.filter;
        document.querySelectorAll('.notification-card').forEach(card => {
            if (filter === 'all') {
                card.style.display = 'flex';
            } else if (filter === 'unread') {
                // You would need to add unread status to your data
                card.style.display = 'flex'; // Implement actual filtering logic
            } else {
                card.style.display = card.dataset.type === filter ? 'flex' : 'none';
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>