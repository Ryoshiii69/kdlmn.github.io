<?php
// Connect to your database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kadiliman";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if 'is_admin' column exists in the users table
$checkColumnSql = "SHOW COLUMNS FROM users LIKE 'is_admin'";
$columnResult = $conn->query($checkColumnSql);

// If the column doesn't exist, add it
if ($columnResult->num_rows == 0) {
    $addColumnSql = "ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0";
    if ($conn->query($addColumnSql) === TRUE) {
        echo "Added 'is_admin' column to users table<br>";
    } else {
        echo "Error adding column: " . $conn->error . "<br>";
    }
}

// Check if admin user exists
$adminUsername = "admin"; // Choose your admin username
$checkAdminSql = "SELECT id FROM users WHERE username = ?";
$stmt = $conn->prepare($checkAdminSql);
$stmt->bind_param("s", $adminUsername);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Admin exists, make sure they have admin privileges
    $updateSql = "UPDATE users SET is_admin = 1 WHERE username = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("s", $adminUsername);
    if ($updateStmt->execute()) {
        echo "Updated admin privileges for user: $adminUsername<br>";
    } else {
        echo "Error updating admin privileges: " . $conn->error . "<br>";
    }
    $updateStmt->close();
} else {
    // Admin doesn't exist, create new admin user
    $adminPassword = password_hash("1234", PASSWORD_DEFAULT); // Choose a strong password
    $firstname = "Admin";
    $surname = "User";
    $email = "admin@kadiliman.com";
    $branch = "Makati";
    
    $createSql = "INSERT INTO users (username, password, firstname, surname, email, branch, is_admin) 
                 VALUES (?, ?, ?, ?, ?, ?, 1)";
    $createStmt = $conn->prepare($createSql);
    $createStmt->bind_param("ssssss", $adminUsername, $adminPassword, $firstname, $surname, $email, $branch);
    
    if ($createStmt->execute()) {
        echo "Created new admin user:<br>";
        echo "Username: $adminUsername<br>";
        echo "Password: 1234<br>";
    } else {
        echo "Error creating admin user: " . $conn->error . "<br>";
    }
    $createStmt->close();
}

// Now modify your login script
echo "Now update your login script to check for admin status...<br>";

$conn->close();
echo "Done!";
?>