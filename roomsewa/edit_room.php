<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "roomsewa");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$room_id = $_GET['id'] ?? null;
if (!$room_id) {
    echo "Invalid room ID.";
    exit;
}

// Get existing room details
$sql = "SELECT * FROM rooms WHERE id = ? AND owner_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $room_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();

if (!$room) {
    echo "Room not found.";
    exit;
}

// Update room
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $location = $_POST['location'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $status = $_POST['status'];
    $image = $room['image']; // default old image

    if (!empty($_FILES["image"]["name"])) {
        $targetDir = "uploads/";
        $newImage = basename($_FILES["image"]["name"]);
        $targetFile = $targetDir . $newImage;
        move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile);
        $image = $newImage;
    }

    $update = $conn->prepare("UPDATE rooms SET title=?, location=?, description=?, price_per_month=?, status=?, image=? WHERE id=? AND owner_id=?");
    $update->bind_param("sssdssii", $title, $location, $description, $price, $status, $image, $room_id, $_SESSION['user_id']);

    if ($update->execute()) {
        header("Location: owner_dashboard.php");
        exit;
    } else {
        echo "Error updating room.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Room</title>
    <style>
        body { font-family: Arial; background: linear-gradient(135deg, #e91e1eff 0%, #15cfccff 100%);padding: 20px; }
        .form-box {
            max-width: 600px; margin: auto; background: white; padding: 20px;
            border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        input, textarea, select {
            width: 100%; padding: 10px; margin-bottom: 15px;
            border: 1px solid #ccc; border-radius: 5px;
        }
        input[type="submit"] {
            background: #0073e6; color: white; border: none; cursor: pointer;
        }
    </style>
</head>
<body>

<div class="form-box">
    <h2>Edit Room</h2>
    <form method="POST" enctype="multipart/form-data">
        <label>Title</label>
        <input type="text" name="title" value="<?= htmlspecialchars($room['title']) ?>" required>

        <label>Location</label>
        <input type="text" name="location" value="<?= htmlspecialchars($room['location']) ?>" required>

        <label>Description</label>
        <textarea name="description" required><?= htmlspecialchars($room['description']) ?></textarea>

        <label>Price Per Month</label>
        <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($room['price_per_month']) ?>" required>

        <label>Status</label>
        <select name="status">
            <option value="available" <?= $room['status'] === 'available' ? 'selected' : '' ?>>Available</option>
            <option value="booked" <?= $room['status'] === 'booked' ? 'selected' : '' ?>>Booked</option>
        </select>

        <label>Change Room Image (optional)</label>
        <input type="file" name="image">

        <input type="submit" value="Update Room">
    </form>
</div>

</body>
</html>
