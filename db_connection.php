<?php
// Database connection configuration
$host = 'localhost';     // Database host
$username = 'root';      // Database username
$password = '';          // Database password
$database = 'kadiliman'; // Database name

// Create database connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");
?>