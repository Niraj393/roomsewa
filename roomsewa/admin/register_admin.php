<?php
session_start();
// Prevent access if already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}
require '../includes/db_connect.php';

$errors = [];
$username = $full_name = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($username)) $errors[] = "Username is required";
    if (empty($full_name)) $errors[] = "Full name is required";
    if (empty($password)) $errors[] = "Password is required";
    if ($password !== $confirm_password) $errors[] = "Passwords don't match";

    // Check if username exists
    $stmt = $conn->prepare("SELECT admin_id FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Username already exists";
    }

    // If no errors, create admin
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $insert = $conn->prepare("INSERT INTO admins (username, password_hash, full_name) VALUES (?, ?, ?)");
        $insert->bind_param("sss", $username, $password_hash, $full_name);
        
        if ($insert->execute()) {
            $_SESSION['success_message'] = "Registration successful! Please login.";
            header("Location: admin_login.php");
            exit();
        } else {
            $errors[] = "Registration failed: " . $conn->error;
        }
    }
}
?>
<html>
<head>
    <title>Register - RoomSewa</title>
    <style>
        body {
            font-family: Arial;
          background: linear-gradient(135deg, #e91e1eff 0%, #15cfccff 100%);
            margin: 0;
            padding: 20px;
        }
        .register-box {
            width: 400px;
            margin: 50px auto;
            background: #15cfccff ;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #333;
            
        }
        h1 {
            text-align: center;
            color:blue;
            
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            background: #1d12e8ff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        .error {
            color: red;
            padding: 10px;
            background: #f30e0eff;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .success {
            color: green;
            padding: 10px;
            background: #f207acff;
            margin-bottom: 15px;
            border-radius: 4px;
        }
       
    </style>
</head>
<body>

<div class="register-box">
    <h1>RoomSewa</h1>
    <h2> Admin Registration</h2>
   <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>   
    <form method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required class="form-control">
        </div>
        
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($full_name) ?>" required class="form-control">
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required class="form-control">
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required class="form-control">
        </div>
        
        <button type="submit" class="btn btn-primary">Register</button>
    </form>
    
    <div class="login-link">
        Already have an account? <a href="admin_login.php">Login here</a>
    </div>
</div>
</body>
</html>