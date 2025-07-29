<?php
$pageTitle = "About RoomSewa | Find Your Perfect Space";
require 'includes/header.php';
?>

 <div>
    <form method="GET" action="search.php" style="display: flex; float:right; align-items: center;">
        <input 
            type="text" 
            name="search" 
            placeholder="Search rooms..." 
            required 
            style="padding: 8px; font-size: 16px; border: 1px solid #ccc; float:right; border-radius: 4px 0 0 4px;"
        >
        <button 
            type="submit" 
            style="padding: 8px 12px; background: #05e0fdff; border: none; float:right; color: white; border-radius: 0 4px 4px 0; cursor: pointer;">
            🔍
        </button>
    </form>
</div>

  <div class="hero-board">
    <div class="hero-text active">
      <p>Discover affordable rooms across Nepal</p>
    </div>
    <div class="hero-text">
      <p>List your property and find perfect tenants</p>
    </div>
    <div class="hero-text">
      <p>Verified listings with transparent pricing</p>
    </div>
    
    <div class="btn-container">
      <a href="register.php" class="btn">Get Started</a>
      <a href="dashboard.php" class="btn secondary">Browse Rooms</a>
    </div>
  </div>

  <section class="features">
    <h2>RoomSewa Features</h2>
    <div class="features-grid">
      <div class="feature-card">
        <i class="fas fa-check-circle"></i>
        <h3>Verified Listings</h3>
        <p>All rooms are personally verified by our team</p>
      </div>
      <div class="feature-card">
        <i class="fas fa-map-marker-alt"></i>
        <h3>Location Based</h3>
        <p>Find rooms in your preferred locations</p>
      </div>
      <div class="feature-card">
        <i class="fas fa-rupee-sign"></i>
        <h3>Fair Pricing</h3>
        <p>No hidden charges or broker fees</p>
      </div>
      <div class="feature-card">
        <i class="fas fa-calendar-check"></i>
        <h3>Easy Booking</h3>
        <p>Simple online reservation process</p>
      </div>
      <div class="feature-card">
        <i class="fas fa-images"></i>
        <h3>High Quality Photos</h3>
        <p>View rooms in detail before visiting</p>
      </div>
      <div class="feature-card">
        <i class="fas fa-phone-alt"></i>
        <h3>Owner Contacts</h3>
        <p>Direct Phone Numbers for quick inquiries</p>
      </div>
      <div class="feature-card">
        <i class="fas fa-star"></i>
        <h3>Ratings & Reviews</h3>
        <p>See what others say about properties</p>
      </div>
      <div class="feature-card">
        <i class="fas fa-bell"></i>
        <h3>Get Notification</h3>
        <p>Get notified about new listings</p>
      </div>
    </div>
  </section>
  <div style="float: right; margin-right: 20px;">
    <a href="admin/admin_login.php" style="text-decoration: underline; color: #fd0505ff; font-weight: bold;">Admin Login</a>
</div>
  <script src="/roomsewa/assets/js/main.js"></script>
<?php include 'includes/footer.php'; ?>

