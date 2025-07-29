<?php
$conn = new mysqli("localhost", "root", "", "roomsewa");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

class NotificationSystem {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Send notification to user
     * @param int $user_id Recipient ID
     * @param string $message Notification content
     * @param string $link Optional link (e.g., 'booking.php?id=5')
     * @param bool $send_email Whether to send email
     * @return bool True on success
     */
    public function send($user_id, $message, $link = '', $send_email = false) {
        // 1. Save to database
        $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $message, $link);
        $db_result = $stmt->execute();
        
        // 2. Send email if requested
        if ($send_email && $db_result) {
            $this->sendEmailNotification($user_id, $message, $link);
        }
        
        return $db_result;
    }
    
    /**
     * Get unread notifications count for user
     * @param int $user_id
     * @return int Unread count
     */
    public function getUnreadCount($user_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['count'];
    }
    
    /**
     * Mark notification as read
     * @param int $notification_id
     * @param int $user_id
     * @return bool True on success
     */
    public function markAsRead($notification_id, $user_id) {
        $stmt = $this->conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
        return $stmt->execute();
    }
    
    /**
     * Get all notifications for user
     * @param int $user_id
     * @param int $limit Max notifications to return
     * @return array Notifications
     */
    public function getAll($user_id, $limit = 50) {
        $stmt = $this->conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    private function sendEmailNotification($user_id, $message, $link) {
        // Get user email
        $stmt = $this->conn->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
            $to = $user['email'];
            $subject = "RoomSewa Notification";
            $headers = "From: notifications@roomsewa.com\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            $body = "<html><body>
                <h2>New Notification</h2>
                <p>$message</p>
                ".(!empty($link) ? "<p><a href='http://yourdomain.com/$link'>View Details</a></p>" : "")."
                <hr>
                <small>This is an automated message</small>
            </body></html>";
            
            mail($to, $subject, $body, $headers);
        }
    }
}

// Initialize notification system with your existing connection
$notifications = new NotificationSystem($conn);
?>