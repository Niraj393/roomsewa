<?php
// db_connect.php

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
?>
