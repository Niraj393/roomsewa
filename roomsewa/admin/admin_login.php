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
if (isset($_SESSION['admin_id'])) {
    header("Location:admin_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - RoomSewa</title>
        <style>
            :root {
                --primary: #150ee8ff;
                --error: #580505ff;
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
                background:#15cfccff;  
                border-radius: 12px;
                box-shadow: 0 10px 25px rgba(238, 231, 39, 0.1);
                width: 100%;
                max-width: 400px;
                padding: 2rem;
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
                transition: border-color 0.3s;
            }
            .form-group input:focus {
                border-color: var(--primary);
                outline: none;
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
                transition: background 0.3s;
            }
            .btn-login:hover {
                background: #1a52d8;
            }
            .error-message {
                color: var(--error);
                text-align: center;
                margin-bottom: 1rem;
                font-size: 0.9rem;
            }
            .footer-links {
                text-align: center;
                margin-top: 1.5rem;
                font-size: 0.9rem;
            }
            .footer-links a {
                color: var(--primary);
                text-decoration: none;
            }
            .footer-links a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="login-header">
                <h1>RoomSewa</h1>
                <h2>Admin Login</h2>
            </div>

            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">Invalid username or password</div>
        <?php endif; ?>

            <form method="POST" action="authenticate_admin.php">
               
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-login">Login</button>
            </form>

            <div class="footer-links">
                Don't have an account? <a href="register_admin.php">Register here</a>
            </div>
        </div>
    </body>
</html>