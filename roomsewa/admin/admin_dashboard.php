<?php
session_start();
// Redirect if not logged in as admin
if (!isset($_SESSION['admin_id'])){
    header("Location: admin_login.php");
    exit();
}

// CSRF Protection
if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}
$pageTitle = "Admin Dashboard | RoomSewa";

// Database connection
$conn = new mysqli('localhost', 'root', '', 'roomsewa');

// Get statistics
$stats = [
    'total_rooms' => $conn->query("SELECT COUNT(*) FROM rooms")->fetch_row()[0],
    'available_rooms' => $conn->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'")->fetch_row()[0],
    'booked_rooms' => $conn->query("SELECT COUNT(*) FROM rooms WHERE status = 'booked'")->fetch_row()[0],
    'total_users' => $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0],
    'total_bookings' => $conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0],
    'pending_bookings' => $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetch_row()[0]
];

// Get recent rooms
$recent_rooms = $conn->query("SELECT r.*, u.name as owner_name 
                             FROM rooms r JOIN users u ON r.owner_id = u.id 
                             ORDER BY r.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Get recent bookings
$recent_bookings = $conn->query("SELECT b.*, r.title as room_title, u1.name as renter_name, u2.name as owner_name
                                FROM bookings b
                                JOIN rooms r ON b.room_id = r.id
                                JOIN users u1 ON b.renter_id = u1.id
                                JOIN users u2 ON b.owner_id = u2.id
                                ORDER BY b.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?>Admin Dashboard | RoomSewa</title>
    <link rel="stylesheet" href="/roomsewa/assets/css/admin.css">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
           <!-- Navigation Menu -->
                    <nav aria-label="Main Navigation" id="mainNav">
                    <img src="logo.jpg" alt="Roomsewa Logo"style="width: 12%; height: 5%; align-items:left; border: 2px solid #000; border-radius: 10px;">
                    <a href="index.php">Home</a>
                    <a href="admin/admin_login.php"> Admin Login</a>
                    <a href="register_admin.php">Admin Register</a>
                    <a href="logout.php">Admin Logout</a>
                    </nav>

                    <!-- Overlay -->
                    <div id="overlay"></div>
               
                    </header>
                                 <div>
             <h1 style=" text-align:center; solid #000;color:voilet; border-radius: 10px; text-align:center;">Admin Dashboard </h1>
            </div>
            
        <div class="admin-container">
                   
            <div class="admin-main shifted">
        
                
                <main class="admin-content">
                 
                    <!-- Statistics Cards -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-home"></i></div>
                            <div class="stat-info">
                                <h3>Total Rooms</h3>
                                <p><?= $stats['total_rooms'] ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="stat-info">
                                <h3>Available</h3>
                                <p><?= $stats['available_rooms'] ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-calendar-times"></i></div>
                            <div class="stat-info">
                                <h3>Booked</h3>
                                <p><?= $stats['booked_rooms'] ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                            <div class="stat-info">
                                <h3>Total Users</h3>
                                <p><?= $stats['total_users'] ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                            <div class="stat-info">
                                <h3>Total Bookings</h3>
                                <p><?= $stats['total_bookings'] ?></p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-clock"></i></div>
                            <div class="stat-info">
                                <h3>Pending</h3>
                                <p><?= $stats['pending_bookings'] ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Rooms -->
                    <section class="recent-section">
                        <h2><i class="fas fa-home"></i> Recent Rooms</h2>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Owner</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_rooms as $room): ?>
                                    <tr>
                                        <td><?= $room['id'] ?></td>
                                        <td><?= htmlspecialchars($room['title']) ?></td>
                                        <td><?= htmlspecialchars($room['owner_name']) ?></td>
                                        <td>Rs. <?= number_format($room['price_per_month'], 2) ?></td>
                                        <td><span class="status-badge <?= $room['status'] ?>"><?= ucfirst($room['status']) ?></span></td>
                                        <td>
                                            <a href="edit_room.php?id=<?= $room['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                            <a href="delete_room.php?id=<?= $room['id'] ?>" class="btn-delete" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                    
                    <!-- Recent Bookings -->
                    <section class="recent-section">
                        <h2><i class="fas fa-calendar-check"></i> Recent Bookings</h2>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Room</th>
                                        <th>Renter</th>
                                        <th>Owner</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_bookings as $booking): ?>
                                    <tr>
                                        <td><?= $booking['id'] ?></td>
                                        <td><?= htmlspecialchars($booking['room_title']) ?></td>
                                        <td><?= htmlspecialchars($booking['renter_name']) ?></td>
                                        <td><?= htmlspecialchars($booking['owner_name']) ?></td>
                                        <td><span class="status-badge <?= $booking['status'] ?>"><?= ucfirst($booking['status']) ?></span></td>
                                        <td>
                                            <a href="view_booking.php?id=<?= $booking['id'] ?>" class="btn-view"><i class="fas fa-eye"></i></a>
                                            <a href="edit_booking.php?id=<?= $booking['id'] ?>" class="btn-edit"><i class="fas fa-edit"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </main>
            </div>
</div>
                            </body>
                            </html>
<script>
    
document.addEventListener('DOMContentLoaded', function () {
  const menuToggle = document.getElementById('menuToggle');
  const mainNav = document.getElementById('mainNav');
  const overlay = document.getElementById('overlay');
  const mainContent = document.querySelector('.main-content'); 

  menuToggle.addEventListener('click', function () {
    mainNav.classList.toggle('active');
    overlay.classList.toggle('active');
    mainContent.classList.toggle('shifted');
this.textContent = mainNav.classList.contains('active') ? '✕' : '☰';
    });
     // Close when overlay clicked
  overlay.addEventListener('click', function () {
    mainNav.classList.remove('active');
    overlay.classList.remove('active');
    mainContent.classList.remove('shifted');
    menuToggle.textContent = '☰';
  });

  // Close when nav link clicked
  document.querySelectorAll('#mainNav a').forEach(link => {
    link.addEventListener('click', function () {
      mainNav.classList.remove('active');
      overlay.classList.remove('active');
       mainContent.classList.remove('shifted');
      menuToggle.textContent = '☰';
    });
  });
});
    </script>
