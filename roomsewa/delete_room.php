<?php
session_start();

// Database connection
$conn = new mysqli('localhost', 'root', '', 'roomsewa');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Security checks
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: login.php");
    exit;
}

// Check if room ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Room ID not specified.";
    header("Location: owner_dashboard.php");
    exit;
}

$room_id = (int)$_GET['id'];
$owner_id = $_SESSION['user_id'];

// Verify the room belongs to the owner before deleting
$stmt = $conn->prepare("SELECT id, image FROM rooms WHERE id = ? AND owner_id = ?");
$stmt->bind_param("ii", $room_id, $owner_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Room not found or you don't have permission to delete it.";
    header("Location: owner_dashboard.php");
    exit;
}

$room = $result->fetch_assoc();

// Delete the room
$delete_stmt = $conn->prepare("DELETE FROM rooms WHERE id = ? AND owner_id = ?");
$delete_stmt->bind_param("ii", $room_id, $owner_id);

if ($delete_stmt->execute()) {
    // Delete the associated image file
    if (!empty($room['image']) && file_exists($room['image'])) {
        unlink($room['image']);
    }
    
    $_SESSION['success'] = "Room deleted successfully.";
} else {
    $_SESSION['error'] = "Error deleting room: " . $conn->error;
}

header("Location: owner_dashboard.php");
exit;
?>