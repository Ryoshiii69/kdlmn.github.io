<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $enteredOtp = $_POST['otp'];

    if (!isset($_SESSION['otp']) || $enteredOtp != $_SESSION['otp']) {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP.']);
        exit;
    }

    // OTP is correct, proceed with account creation
    $signupData = $_SESSION['signup-data'];
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "kadiliman";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO users (username, email, firstname, surname, branch, password) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "ssssss",
        $signupData['signup-username'],
        $signupData['signup-email'],
        $signupData['signup-firstname'],
        $signupData['signup-surname'],
        $signupData['signup-branch'],
        password_hash($signupData['signup-password'], PASSWORD_DEFAULT)
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Account created successfully.']);
        unset($_SESSION['otp'], $_SESSION['signup-data']); // Clear session data
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create account.']);
    }

    $stmt->close();
    $conn->close();
    exit;
}
?>