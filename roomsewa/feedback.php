<?php
session_start();
$conn = new mysqli("localhost", "root", "", "roomsewa");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_title = trim($_POST['review_title']);
    $message = trim($_POST['message']);
    $rating = (int)$_POST['rating'];
    
    if ($rating < 1 || $rating > 5) {
        $error = "Please select a rating between 1 and 5 stars";
    }
    elseif (empty($review_title)) {
        $error = "Please provide a review title";
    }
    elseif (empty($message)) {
        $error = "Please write your review message";
    }
    else {
        $stmt = $conn->prepare("INSERT INTO feedback (user_id, review_title, message, rating) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $_SESSION['user_id'], $review_title, $message, $rating);
        
        if ($stmt->execute()) {
            $success = "Thank you for your review!";
        } else {
            $error = "Error submitting review: " . $conn->error;
        }
    }
}

$pageTitle = "Feedback | RoomSewa";
require 'includes/header.php';
?>

<div class="simple-container">
    <div class="card">
        <h2><i class="fas fa-comment-alt"></i> Send Feedback</h2>
        
        <?php if (!empty($success)): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php elseif (!empty($error)): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="review_title">Review Title</label>
                <input type="text" id="review_title" name="review_title" required>
            </div>
                 <div style="display: flex; justify-content: center; align-items: center; width: 100%;">
                     <p style="margin-bottom: 10px; font-weight: bold; font-size: 1.1rem;">Rate Us :</p>
                   <div>  
                    <div class="stars">
                    <input type="radio" id="star5" name="rating" value="5">
                    <label for="star5">&#9733;</label>
                    
                    <input type="radio" id="star4" name="rating" value="4">
                    <label for="star4">&#9733;</label>
                    
                    <input type="radio" id="star3" name="rating" value="3">
                    <label for="star3">&#9733;</label>
                    
                    <input type="radio" id="star2" name="rating" value="2">
                    <label for="star2">&#9733;</label>
                    
                    <input type="radio" id="star1" name="rating" value="1">
                    <label for="star1">&#9733;</label>
                 </div>
        </div>
                </div>
            
            <div class="form-group">
                <label for="message">Your Feedback</label>
                <textarea id="message" name="message" rows="5" required></textarea>
            </div>
            
            <button type="submit" class="btn primary">
                <i class="fas fa-paper-plane"></i> Submit
            </button>
        </form>
    </div>
</div>

<?php require 'includes/footer.php'; ?>