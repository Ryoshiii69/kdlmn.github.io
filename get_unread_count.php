<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kadiliman";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Get unread message count
$sql = "SELECT COUNT(*) as count FROM contact_messages WHERE status = 'unread'";
$result = $conn->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    header('Content-Type: application/json');
    echo json_encode(['count' => $row['count']]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to get count']);
}

$conn->close();
?> 