<?php
$servername = "localhost";
$username = "root";
$password = ""; // Default XAMPP password is empty
$dbname = "fitness_db"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: (" . $conn->connect_errno . ") " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");
?>