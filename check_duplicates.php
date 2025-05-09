<?php
// check_duplicates.php
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kadiliman";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => "Connection failed: " . $conn->connect_error
    ]));
}

// Check if username exists
if (isset($_POST['check_username'])) {
    $username = $_POST['check_username'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        echo json_encode([
            'exists' => true,
            'message' => "Username '$username' is already taken"
        ]);
    } else {
        echo json_encode([
            'exists' => false
        ]);
    }
}

// Check if email exists
if (isset($_POST['check_email'])) {
    $email = $_POST['check_email'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        echo json_encode([
            'exists' => true,
            'message' => "Email '$email' is already registered"
        ]);
    } else {
        echo json_encode([
            'exists' => false
        ]);
    }
}

$conn->close();
?>