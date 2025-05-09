<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check if message_id is provided
if (!isset($_POST['message_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Message ID not provided']);
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
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// Update message status
$message_id = $_POST['message_id'];
$sql = "UPDATE contact_messages SET status = 'read' WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $message_id);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Failed to update message status']);
}

$stmt->close();
$conn->close();
?> 