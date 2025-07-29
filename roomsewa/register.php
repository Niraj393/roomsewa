<?php
// register.php - Created by [Your Name], CSIT 7th Sem, [Your College]

// Database connection stuff
$conn = mysqli_connect("localhost","root","","roomsewa");
if(!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$error = "";
$success = "";

// When form is submitted
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get form data
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];
    $role = $_POST['role'];
    
    // Basic validation
    if(empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required!";
    }
    elseif($password != $cpassword) {
        $error = "Passwords don't match!";
    }
    elseif(strlen($password) < 6) {
        $error = "Password too short (min 6 chars)";
    }
    else {
        // Check if email exists
        $check_email = "SELECT * FROM users WHERE email='$email'";
        $result = mysqli_query($conn, $check_email);
        
        if(mysqli_num_rows($result) > 0) {
            $error = "Email already registered!";
        }
        else {
            // Hash password (basic security)
            $hashed_pw = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert into database
            $insert = "INSERT INTO users (name, email, password, role) 
                      VALUES ('$name', '$email', '$hashed_pw', '$role')";
            
            if(mysqli_query($conn, $insert)) {
                $success = "Registration successful! <a href='login.php'>Login now</a>";
                // Clear form
                $name = $email = "";
            }
            else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!-- Simple HTML Form -->
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
    <h2>Create Account</h2>
    
    <?php if($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="post" action="">
        <div class="form-group">
            <label>Full Name:</label>
            <input type="text" name="name" value="<?php echo isset($name) ? $name : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" value="<?php echo isset($email) ? $email : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label>Confirm Password:</label>
            <input type="password" name="cpassword" required>
        </div>
        
        <div class="form-group">
            <label>Register As:</label>
            <select name="role" required>
                <option value="">-- Select --</option>
                <option value="owner">Room Owner</option>
                <option value="renter">Room Renter</option>
            </select>
        </div>
        
        <button type="submit" class="btn">Register</button>
    </form>
    
    <p style="text-align: center; margin-top: 15px; color:#f207acff; ">
        Already have an account? <a href="login.php"style="color: red; text-decoration: underline; font-weight: bold;">Login here</a>
    </p>
</div>

</body>
</html>