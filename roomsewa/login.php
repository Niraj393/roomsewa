<?php
session_start();

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "roomsewa";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$login_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $login_error = "Invalid request.";
    } else {
        $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
        $pass = $_POST["password"];

        // Rate limiting (3 attempts)
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] > 3) {
            $login_error = "Too many attempts. Try again later.";
        } else {
            $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $name, $hashed_password, $role);
                $stmt->fetch();

                if (password_verify($pass, $hashed_password)) {
                    // Successful login
                    session_regenerate_id(true);
                    $_SESSION["user_id"] = $id;
                    $_SESSION["role"] = $role;
                    $_SESSION["name"] = $name;
                    $_SESSION['login_attempts'] = 0; // Reset attempts

                    // Handle booking redirect if exists
                    if (isset($_SESSION['booking_redirect']) && $_SESSION['booking_redirect']) {
                        $room_id = $_SESSION['room_to_book'];
                        unset($_SESSION['booking_redirect']);
                        unset($_SESSION['room_to_book']);
                        
                        if ($role === 'renter') {
                            header("Location: payment.php?room_id=$room_id");
                            exit();
                        } else {
                            $_SESSION['error'] = "Please use a renter account to book rooms";
                            header("Location: dashboard.php");
                            exit();
                        }
                    }

                    // Normal role-based redirect
                    header("Location: " . ($role === "owner" ? "owner_dashboard.php" : "renter_dashboard.php"));
                    exit();
                } else {
                    $login_error = "❌ Incorrect password.";
                }
            } else {
                $login_error = "❌ Email not found.";
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/main.css" />
    <title>Login - RoomSewa</title>
    <style>
        :root {
            --primary: #150ee8ff;
            --error: #580505ff;
            --accent: #f207acff;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e91e1eff 0%, #15cfccff 100%);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            color: var(--primary);
            margin: 0;
            font-size: 1.8rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .form-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(21, 14, 232, 0.1);
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-login:hover {
            background: #1a52d8;
            transform: translateY(-2px);
        }
        .btn-login:active {
            transform: translateY(0);
        }
        .error-message {
            color: var(--error);
            text-align: center;
            margin-bottom: 1rem;
            padding: 10px;
            background-color: rgba(88, 5, 5, 0.1);
            border-radius: 6px;
            animation: shake 0.5s;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .footer-links {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }
        .footer-links a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        .footer-links a:hover {
            color: #d6069d;
            text-decoration: underline;
        }
        .password-container {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #777;
        }
    </style>
</head>
<body>
    <button class="menu-toggle" id="menuToggle">☰</button>

<!-- Navigation Menu -->
<nav aria-label="Main Navigation" id="mainNav">
<img src="logo.jpg" alt="Roomsewa Logo"style="width: 82%; height: 18%; border: 2px solid #000; border-radius: 10px;">
 <a href="index.php">Home</a>
  <a href="login.php">Login</a>
  <a href="dashboard.php">Available Rooms</a>
  <a href="renter_dashboard.php">Renter renter_dashboard</a>
  <a href="register.php">Register</a>
  <a href="search.php">Search Rooms</a>
  <a href="about.php">About Us</a>
  <a href="contact.php">Contact</a>
  <a href="admin/admin_login.php"> Admin Dashboard</a>
  <a href="feedback.php">Feedback And Rating</a>
</nav>
    <div class="login-container">
        <div class="login-header">
            <h1>RoomSewa</h1>
            <p>Find your perfect space</p>
        </div>

        <?php if ($login_error): ?>
            <div class="error-message"><?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>
            
            <div class="form-group password-container">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <span class="toggle-password" onclick="togglePassword()">
                   
                </span>
            </div>
            
            <button type="submit" class="btn-login">Login</button>
        </form>

        <div class="footer-links">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
            <p><a href="forgot_password.php">Forgot password?</a></p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const icon = document.querySelector('.toggle-password i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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