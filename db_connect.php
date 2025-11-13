<?php
// --- Establishes the connection to your MySQL database ---

$servername = "localhost";
$username = "root";
$password = ""; // Default XAMPP password is empty
$dbname = "fitness_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection and stop the script if it fails
if ($conn->connect_error) {
  die("Connection failed: (" . $conn->connect_errno . ") " . $conn->connect_error);
}

// Set charset for proper character handling
$conn->set_charset("utf8mb4");
?>