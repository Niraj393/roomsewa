<?php
$pageTitle = "Contact Us | RoomSewa";
require 'includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);
    
    // In a real project, you'd send an email here
    $success = true; // Simulate success
}
?>

<div class="page-container">
    <div class="card">
        <h1><i class="fas fa-envelope"></i> Contact Us</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i> Thanks! We'll contact you soon.
            </div>
        <?php endif; ?>
        
        <form method="POST" class="contact-form">
            <div class="form-group">
                <label for="name"><i class="fas fa-user"></i> Full Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="message"><i class="fas fa-comment"></i> Message</label>
                <textarea id="message" name="message" rows="5" required></textarea>
            </div>
            
            <button type="submit" class="btn primary">
                <i class="fas fa-paper-plane"></i> Send Message
            </button>
        </form>
        
        <div class="contact-info">
            <h2><i class="fas fa-map-marker-alt"></i> Visit Us</h2>
            <p>Nepalgunj, Nepal</p>
            
            <h2><i class="fas fa-phone"></i> Call Us</h2>
            <p>+977 9864499368</p>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>