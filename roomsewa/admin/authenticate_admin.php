<?php
session_start();
$servername = "localhost";
$username = "root";        // your MySQL username
$password = "";            // your MySQL password
$dbname = "roomsewa";   // your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validate input
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Username and password are required";
        header("Location: admin_login.php");
        exit();
    }

    // Prepare SQL statement
    $stmt = $conn->prepare("SELECT admin_id, username, password_hash FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        
        if (password_verify($password, $admin['password_hash'])) {
            // Set session variables
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['logged_in'] = true;

            // Update last login
            $update = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE admin_id = ?");
            $update->bind_param("i", $admin['admin_id']);
            $update->execute();

            // Redirect to dashboard
            header("Location: admin_dashboard.php");
            exit();
        }
    }

    // If authentication fails
    $_SESSION['error'] = "Invalid username or password";
    header("Location: admin_login.php");
    exit();
} else {
    // If not POST request
    header("Location: admin_login.php");
    exit();
}
?>