<?php
session_start();
require 'C:\xampp\htdocs\KADILIMAN\phpmailer\PHPMailer-master\src\Exception.php';
require 'C:\xampp\htdocs\KADILIMAN\phpmailer\PHPMailer-master\src\PHPMailer.php';
require 'C:\xampp\htdocs\KADILIMAN\phpmailer\PHPMailer-master\src\SMTP.php';

// Add this at the top of your existing file with other requires
// Note: Update this path to wherever your Composer autoload file is located
require 'C:\xampp\htdocs\KADILIMAN\vendor\autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use RobThree\Auth\TwoFactorAuth;

// Function to generate TOTP for admin tokens
function generateAdminTOTP($secret = null, $interval = 60) {
    // Create a new TwoFactorAuth instance with 60-second interval (instead of default 30)
    $tfa = new TwoFactorAuth('Kadiliman', 6, 30, 'sha1', $interval);
    
    // Generate a new secret if one is not provided
    if ($secret === null) {
        $secret = $tfa->createSecret(160); // 160 bits
    }
    
    // Get the current token based on time
    $token = $tfa->getCode($secret);
    
    return [
        'secret' => $secret,
        'token' => $token,
        // Calculate seconds remaining until next token
        'expires_in' => $interval - (time() % $interval)
    ];
}

// Database connection function
function getConnection() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "kadiliman";
    
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        return ['success' => false, 'conn' => null, 'message' => 'Connection failed: ' . $conn->connect_error];
    }
    
    return ['success' => true, 'conn' => $conn];
}

// Generate admin registration token - MODIFIED to generate 6-character tokens
// with enhanced token invalidation
function generateAdminToken($createdBy, $expiryDays = 7) {
    $db = getConnection();
    if (!$db['success']) {
        error_log("Database connection error in generateAdminToken: " . $db['message']);
        return ['success' => false, 'message' => $db['message']];
    }
    $conn = $db['conn'];

    try {
        // Start transaction for atomic operations
        $conn->begin_transaction();
        
        // Mark ALL previous tokens for this creator as used
        $stmt = $conn->prepare("UPDATE admin_tokens SET is_used = 1 WHERE created_by = ? AND is_used = 0");
        $stmt->bind_param("s", $createdBy);
        $stmt->execute();
        
        // Log how many tokens were invalidated
        $invalidatedCount = $stmt->affected_rows;
        error_log("Invalidated $invalidatedCount previous tokens for creator: $createdBy");
        $stmt->close();

        // Generate a new token
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $specialChars = '!@#$%^&*()-_=+';
        
        $token = $letters[rand(0, strlen($letters) - 1)];
        $token .= $numbers[rand(0, strlen($numbers) - 1)];
        $token .= $specialChars[rand(0, strlen($specialChars) - 1)];
        
        $allChars = $letters . $numbers . $specialChars;
        for ($i = 0; $i < 3; $i++) {
            $token .= $allChars[rand(0, strlen($allChars) - 1)];
        }
        
        $token = str_shuffle($token);
        $expiryDate = date('Y-m-d H:i:s', strtotime("+$expiryDays days"));

        // Insert the new token
        $stmt = $conn->prepare("INSERT INTO admin_tokens (token, created_by, expiry_date) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $token, $createdBy, $expiryDate);
        
        if ($stmt->execute()) {
            $insertId = $conn->insert_id;
            error_log("New token generated successfully. ID: $insertId, Token: $token");
            
            // Commit all changes
            $conn->commit();
            
            $stmt->close();
            $conn->close();
            return [
                'success' => true, 
                'token' => $token, 
                'id' => $insertId,
                'invalidated_count' => $invalidatedCount
            ];
        } else {
            // Rollback if token creation failed
            $conn->rollback();
            
            error_log("Execute error in generateAdminToken: " . $stmt->error);
            $stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Failed to generate token: ' . $stmt->error];
        }
    } catch (Exception $e) {
        // Rollback on any exception
        if ($conn->connect_errno === 0) {
            $conn->rollback();
        }
        
        error_log("Exception in generateAdminToken: " . $e->getMessage());
        if (isset($stmt) && $stmt) {
            $stmt->close();
        }
        $conn->close();
        return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
    }
}


// Add this to your existing database setup or create a migration script
function addTOTPColumnsToDatabase() {
    $db = getConnection();
    if (!$db['success']) {
        return ['success' => false, 'message' => $db['message']];
    }
    $conn = $db['conn'];
    
    // Add TOTP secret column to admin table if it doesn't exist
    $sql = "SHOW COLUMNS FROM admin LIKE 'totp_secret'";
    $result = $conn->query($sql);
    if ($result->num_rows === 0) {
        $alterSql = "ALTER TABLE admin ADD COLUMN totp_secret VARCHAR(255) NULL AFTER password";
        if (!$conn->query($alterSql)) {
            $conn->close();
            return ['success' => false, 'message' => 'Failed to add TOTP column: ' . $conn->error];
        }
    }
    
    $conn->close();
    return ['success' => true, 'message' => 'TOTP columns added successfully'];
}

// You can call this function during setup or first run
// addTOTPColumnsToDatabase();

// New endpoint to get current token value (for AJAX updates)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get-current-token') {
    header('Content-Type: application/json');
    
    // Session check - only admins can access this
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }
    
    $db = getConnection();
    if (!$db['success']) {
        echo json_encode(['success' => false, 'message' => $db['message']]);
        exit;
    }
    $conn = $db['conn'];
    
    // Get admin token secret from the database
    $adminId = $_SESSION['admin_id'];
    $stmt = $conn->prepare("SELECT totp_secret FROM admin WHERE id = ?");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        $secret = $admin['totp_secret'];
        
        // If admin doesn't have a secret yet, create one
        if (empty($secret)) {
            $totpData = generateAdminTOTP();
            $secret = $totpData['secret'];
            
            // Save the secret to the admin record
            $updateStmt = $conn->prepare("UPDATE admin SET totp_secret = ? WHERE id = ?");
            $updateStmt->bind_param("si", $secret, $adminId);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            $totpData = generateAdminTOTP($secret);
        }
        
        echo json_encode([
            'success' => true, 
            'token' => $totpData['token'],
            'expires_in' => $totpData['expires_in']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Admin not found']);
    }
    
    $stmt->close();
    $conn->close();
    exit;
}

// Admin login processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'admin-login') {
    header('Content-Type: application/json');
    
    $db = getConnection();
    if (!$db['success']) {
        echo json_encode(['success' => false, 'message' => $db['message']]);
        exit;
    }
    $conn = $db['conn'];
    
    $adminUsername = $_POST['admin-username'];
    $adminPassword = $_POST['admin-password'];
    
    // Validate username format (must start with "admin")
    if (strpos($adminUsername, "admin") !== 0) {
        echo json_encode(['success' => false, 'message' => 'Admin username must start with "admin"']);
        exit;
    }
    
    // Check if admin exists
    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->bind_param("s", $adminUsername);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($adminPassword, $admin['password'])) {
            // Store admin session
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['firstname'] . ' ' . $admin['surname'];
            $_SESSION['is_admin'] = true;
            
            echo json_encode(['success' => true, 'message' => 'Login successful', 'redirectUrl' => 'admin.php']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Admin not found']);
    }
    
    $stmt->close();
    $conn->close();
    exit;
}

// Verify admin token before registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify-admin-token') {
    header('Content-Type: application/json');
    
    $adminToken = trim($_POST['admin-token']);
    
    if (empty($adminToken)) {
        echo json_encode(['success' => false, 'message' => 'Admin token is required']);
        exit;
    }
    
    $db = getConnection();
    if (!$db['success']) {
        echo json_encode(['success' => false, 'message' => $db['message']]);
        exit;
    }
    $conn = $db['conn'];
    
    // Check if the token exists, is unused, and not expired
    $stmt = $conn->prepare("SELECT * FROM admin_tokens WHERE token = ? AND is_used = 0 AND expiry_date > NOW()");
    $stmt->bind_param("s", $adminToken);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // Token is valid
        $token = $result->fetch_assoc();
        
        // Store token ID in session for later use
        $_SESSION['admin_token_id'] = $token['id'];
        $_SESSION['admin_token'] = $token['token']; // Store the actual token
        
        echo json_encode(['success' => true, 'message' => 'Token verification successful']);
    } else {
        // More detailed error messages
        $stmt = $conn->prepare("SELECT * FROM admin_tokens WHERE token = ?");
        $stmt->bind_param("s", $adminToken);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $message = 'Invalid admin token';
        } else {
            $token = $result->fetch_assoc();
            if ($token['is_used'] == 1) {
                $message = 'This token has already been used';
            } else if (strtotime($token['expiry_date']) < time()) {
                $message = 'This token has expired';
            } else {
                $message = 'Invalid admin token';
            }
        }
        
        echo json_encode(['success' => false, 'message' => $message]);
    }
    
    $stmt->close();
    $conn->close();
    exit;
}

// Admin registration processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'admin-signup') {
    header('Content-Type: application/json');

    // Check if token was verified and stored in session
    if (!isset($_SESSION['admin_token_id']) || !isset($_SESSION['admin_token'])) {
        echo json_encode(['success' => false, 'message' => 'Please verify your admin token first']);
        exit;
    }

    // Validate required fields
    $requiredFields = [
        'admin-signup-username',
        'admin-signup-email',
        'admin-signup-firstname',
        'admin-signup-surname',
        'admin-signup-password',
        'admin-signup-confirm-password'
    ];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }
    }
    
    // Validate password match
    if ($_POST['admin-signup-password'] !== $_POST['admin-signup-confirm-password']) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit;
    }
    
    // Validate username format
    $adminUsername = $_POST['admin-signup-username'];
    if (strpos($adminUsername, "admin") !== 0) {
        echo json_encode(['success' => false, 'field' => 'username', 'message' => 'Admin username must start with "admin"']);
        exit;
    }

    $db = getConnection();
    if (!$db['success']) {
        echo json_encode(['success' => false, 'message' => $db['message']]);
        exit;
    }
    $conn = $db['conn'];

    // Check if admin username or email already exists
    $adminEmail = $_POST['admin-signup-email'];
    $checkQuery = $conn->prepare("SELECT * FROM admin WHERE username = ? OR email = ?");
    $checkQuery->bind_param("ss", $adminUsername, $adminEmail);
    $checkQuery->execute();
    $result = $checkQuery->get_result();

    if ($result->num_rows > 0) {
        $existingAdmin = $result->fetch_assoc();
        if ($existingAdmin['username'] === $adminUsername) {
            echo json_encode(['success' => false, 'field' => 'username', 'message' => 'The admin username is already taken.']);
        } elseif ($existingAdmin['email'] === $adminEmail) {
            echo json_encode(['success' => false, 'field' => 'email', 'message' => 'The email is already registered.']);
        }
        $checkQuery->close();
        $conn->close();
        exit;
    }

    // Generate OTP
    $otp = rand(100000, 999999);
    $_SESSION['admin_otp'] = $otp;
    $_SESSION['admin_otp_expiry'] = time() + 300; // OTP valid for 5 minutes
    $_SESSION['admin_signup_data'] = $_POST; // Fixed variable name for consistency

    // Send OTP via email
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = 'error_log';
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'dado.aldrin2133@gmail.com'; // Your SMTP email
        $mail->Password = 'nxpu moix tawy ieoy'; // Your SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('dado.aldrin2133@gmail.com', 'Kadiliman Admin');
        $mail->addAddress($adminEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Kadiliman Admin OTP Verification';
        $mail->Body = "Dear Admin,<br><br>Your OTP code for admin registration is: <b>$otp</b>.<br><br>This code will expire in 5 minutes. If you did not request this, please ignore this email.<br><br>Regards,<br>Kadiliman Team";

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'OTP sent to your email.']);
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP. Please try again later.']);
    }

    $conn->close();
    exit;
}

// Verify OTP for admin registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verifyAdminOtp') {
    header('Content-Type: application/json');
    $enteredOtp = $_POST['otp'];

    // Check if token was verified and OTP exists and hasn't expired
    if (!isset($_SESSION['admin_token_id'])) {
        echo json_encode(['success' => false, 'message' => 'Token verification required']);
        exit;
    }
    
    if (!isset($_SESSION['admin_otp']) || !isset($_SESSION['admin_otp_expiry']) || time() > $_SESSION['admin_otp_expiry']) {
        echo json_encode(['success' => false, 'message' => 'OTP has expired.']);
        exit;
    }
    
    if ((string)$enteredOtp !== (string)$_SESSION['admin_otp']) {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP.']);
        exit;
    }
    
    // Create database connection
    $db = getConnection();
    if (!$db['success']) {
        echo json_encode(['success' => false, 'message' => $db['message']]);
        exit;
    }
    $conn = $db['conn'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Save admin data to the database
        $signupData = $_SESSION['admin_signup_data']; // Fixed variable name
        $stmt = $conn->prepare("INSERT INTO admin (username, email, firstname, surname, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $hashedPassword = password_hash($signupData['admin-signup-password'], PASSWORD_BCRYPT);
        $stmt->bind_param(
            "sssss",
            $signupData['admin-signup-username'],
            $signupData['admin-signup-email'],
            $signupData['admin-signup-firstname'],
            $signupData['admin-signup-surname'],
            $hashedPassword
        );
        $stmt->execute();
        
        // Mark the token as used
        $tokenId = $_SESSION['admin_token_id'];
        $updateToken = $conn->prepare("UPDATE admin_tokens SET is_used = 1, used_by = ?, used_at = NOW() WHERE id = ?");
        $updateToken->bind_param("si", $signupData['admin-signup-username'], $tokenId);
        $updateToken->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Clear session data
        unset($_SESSION['admin_otp'], $_SESSION['admin_otp_expiry'], $_SESSION['admin_signup_data'], $_SESSION['admin_token_id']);
        
        echo json_encode(['success' => true, 'message' => 'Admin account successfully created!']);
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to create admin account: ' . $e->getMessage()]);
        error_log("Admin account creation error: " . $e->getMessage());
    }
    
    $conn->close();
    exit;
}

// For generating tokens (admin only) - For existing admins to create tokens for new admins
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate-admin-token') {
    header('Content-Type: application/json');
    
    // Allow bypassing admin check for initial setup - REMOVE THIS IN PRODUCTION!
    $bypassAdminCheck = isset($_POST['initial_setup']) && $_POST['initial_setup'] === 'true';
    
    // Check if user is logged in as admin (unless bypass is enabled)
    if (!$bypassAdminCheck && (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true)) {
        error_log("Unauthorized token generation attempt");
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }
    
    // Use POST provided creator name for initial setup, otherwise use session
    $createdBy = $bypassAdminCheck && isset($_POST['created_by']) 
        ? $_POST['created_by'] 
        : ($_SESSION['admin_username'] ?? 'system');
        
    $expiryDays = isset($_POST['expiry_days']) ? (int)$_POST['expiry_days'] : 7; // Default 7 days validity
    
    error_log("Generating token for creator: $createdBy with $expiryDays days expiry");
    $tokenResult = generateAdminToken($createdBy, $expiryDays);
    
    if ($tokenResult['success']) {
        echo json_encode([
            'success' => true, 
            'message' => 'Token generated successfully', 
            'token' => $tokenResult['token'],
            'expires' => date('Y-m-d H:i:s', strtotime("+$expiryDays days")),
            'id' => $tokenResult['id'] ?? null
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $tokenResult['message']]);
    }
    
    exit;
}

// DEBUG ENDPOINT: List all tokens (remove in production!)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list-tokens') {
    header('Content-Type: application/json');
    
    $db = getConnection();
    if (!$db['success']) {
        echo json_encode(['success' => false, 'message' => $db['message']]);
        exit;
    }
    $conn = $db['conn'];
    
    $query = "SELECT * FROM admin_tokens ORDER BY created_at DESC";
    $result = $conn->query($query);
    
    if ($result) {
        $tokens = [];
        while($row = $result->fetch_assoc()) {
            $tokens[] = $row;
        }
        echo json_encode(['success' => true, 'tokens' => $tokens, 'count' => count($tokens)]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to retrieve tokens']);
    }
    
    $conn->close();
    exit;
}
?>

<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="css/admin-login.css">
    <link rel="icon" href="img/EYE LOGO.png" type="image/x-icon">
    <title>Admin Login / Sign Up</title>
    <style>
        body {
            background: radial-gradient(#1a1a1a 0%, #000000 100%);
            color: white;
            height: 100%;
            margin: 0;
            padding-top: 76px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar-custom {
            background-color: #000 !important;
            border-bottom: 1px solid #ffffff;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1030;
        }
        
        .navbar-custom .navbar-brand {
            margin-right: 2rem;
        }
        
        .navbar-custom .navbar-nav .nav-link {
            color: #fff;
            margin: 0 10px;
            transition: color 0.3s, transform 0.2s;
        }
        
        .navbar-custom .navbar-nav .nav-link:hover {
            color: #ff6b00;
            transform: translateY(0);
        }

        .login-container {
            max-width: 800px;
            margin: 100px auto;
            padding: 0;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            background-color: rgba(0, 0, 0, 0.7);
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .tab-selector {
            display: flex;
            width: 100%;
            margin-bottom: 20px;
        }

        .tab-btn {
            flex: 1;
            padding: 15px 0;
            text-align: center;
            background-color: #333;
            color: white;
            border: none;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .tab-btn.active {
            background-color: #555;
        }

        .login-form-container {
            display: flex;
            width: 100%;
        }

        .form-section {
            flex: 1;
            padding: 10px 30px 40px 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .form-group {
            width: 100%;
            max-width: 300px;
            margin-bottom: 15px;
        }

        .name-row {
            display: flex;
            gap: 15px;
            width: 100%;
            max-width: 300px;
        }

        .name-field {
            flex: 1;
        }

        .cafe-image {
            flex: 1;
            background-image: url('img/LOGO-removebg-preview.png');
            background-size: cover;
            background-position: center;
            min-height: 300px;
            margin: 20px;
            border-radius: 5px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            background-color: #333;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            color: white;
        }

        .form-control:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.1);
        }

        .submit-btn {
            background-color: #ff6b00;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px 25px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 20px;
            width: 100%;
            max-width: 300px;
            font-weight: bold;
        }

        .submit-btn:hover {
            background-color: #ff8c33;
            transform: translateY(-2px);
        }

        .help-text {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            width: 100%;
            max-width: 300px;
        }

        .help-text a {
            color: #ff6b00;
            text-decoration: none;
        }

        .help-text a:hover {
            text-decoration: underline;
        }

        .admin-title {
            text-align: center;
            margin-bottom: 30px;
            color: #ff6b00;
            font-size: 1.5rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100%;
            max-width: 300px;
        }

        /* Password feedback styles */
        .password-criteria {
            font-size: 12px;
            color: #aaa;
            margin-top: 8px;
        }

        .criteria-met {
            color: #28a745;
        }

        .criteria-unmet {
            color: #dc3545;
        }

        /* Modal styling */
        .modal-content {
            background-color: #222;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-primary {
            background-color: #ff6b00;
            border: none;
        }

        .btn-primary:hover {
            background-color: #ff8c33;
        }

        /* Animation for error text */
        @keyframes shake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
            75% { transform: translateX(-5px); }
            100% { transform: translateX(0); }
        }

        .error-message {
            color: #dc3545;
            font-size: 12px;
            animation: fadeIn 0.3s ease, shake 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @media (max-width: 768px) {
            .login-form-container {
                flex-direction: column;
            }
            
            .cafe-image {
                min-height: 200px;
            }
            
            .name-row {
                flex-direction: column;
                gap: 15px;
            }
        }
        /* Hide token verification container by default */
        #token-verification-container {
            display: none;
            padding: 10px 30px 40px 30px;
            flex-direction: column;
            align-items: center;
        }

        /* Style for the signup container to match the login form layout */
        #signup-container {
            display: none; /* Hidden by default */
        }

        /* Ensure the admin signup form fields container is hidden by default */
        #admin-signup-fields {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="tab-selector">
                <button class="tab-btn active" id="signin-tab" onclick="switchTab('signin')">Admin Sign-in</button>
                <button class="tab-btn" id="signup-tab" onclick="switchTab('signup')">Admin Sign-Up</button>
            </div>
            
            <!-- Admin Sign In Form -->
            <form id="admin-signin-form">
                <div class="login-form-container" id="signin-container">
                    <div class="form-section">
                        <div class="admin-title">Administrator Login</div>
                        <div class="form-group">
                            <label class="form-label">Admin Username:</label>
                            <input type="text" class="form-control" id="admin-username" name="admin-username" placeholder="e.g. adminUsername">
                            <span id="admin-username-error" class="error-message"></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password:</label>
                            <input type="password" class="form-control" id="admin-password" name="admin-password">
                            <span id="admin-password-error" class="error-message"></span>
                        </div>
                        <button class="submit-btn" type="submit">Admin Sign-in</button>
                        <div class="help-text">
                            <a href="#">Forgot Password?</a>
                        </div>
                    </div>
                    <div class="cafe-image"></div>
                </div>
            </form>
            
            <!-- Token Verification Form (add this inside the signup container before admin-signup-form) -->
            <div class="token-verification-container" id="token-verification-container">
                <div class="admin-title">Admin Registration Token</div>
                <div class="form-group">
                    <label class="form-label">Admin Token:</label>
                    <input type="text" class="form-control" id="admin-token" name="admin-token" placeholder="Enter your admin registration token">
                    <span id="admin-token-error" class="error-message"></span>
                </div>
                <button class="submit-btn" id="verify-token-btn" type="button">Verify Token</button>
            </div>
            <!-- Admin Sign Up Form -->
            <div id="admin-signup-fields" style="display: none;">
            <form id="admin-signup-form">
                <div class="login-form-container" id="signup-container" style="display: none;">
                    <div class="form-section">
                        <div class="admin-title">New Administrator Registration</div>
                        <div class="form-group">
                            <label class="form-label">Admin Username:</label>
                            <input type="text" class="form-control" id="admin-signup-username" name="admin-signup-username" placeholder="Must start with 'admin' (e.g. adminJohn)">
                            <span id="admin-username-signup-error" class="error-message"></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email:</label>
                            <input type="email" class="form-control" id="admin-signup-email" name="admin-signup-email">
                            <span id="admin-email-error" class="error-message"></span>
                        </div>
                        <div class="name-row">
                            <div class="name-field">
                                <label class="form-label">First Name:</label>
                                <input type="text" class="form-control" id="admin-signup-firstname" name="admin-signup-firstname">
                            </div>
                            <div class="name-field">
                                <label class="form-label">Surname:</label>
                                <input type="text" class="form-control" id="admin-signup-surname" name="admin-signup-surname">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password:</label>
                            <input type="password" class="form-control" id="admin-signup-password" name="admin-signup-password" oninput="updateAdminPasswordFeedback()">
                            <div id="admin-password-feedback" class="password-criteria"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm Password:</label>
                            <input type="password" class="form-control" id="admin-signup-confirm-password" name="admin-signup-confirm-password">
                            <div id="admin-confirm-password-feedback" class="error-message"></div>
                        </div>
                        <button class="submit-btn" type="submit">Register as Admin</button>
                    </div>
                    
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- Login Success Modal -->
    <div class="modal fade" id="adminLoginSuccessModal" tabindex="-1" aria-labelledby="adminLoginSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="adminLoginSuccessModalLabel">Admin Login Successful</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Welcome back, Administrator! Redirecting to admin dashboard...
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Sign-Up Success Modal -->
    <div class="modal fade" id="adminSignupSuccessModal" tabindex="-1" aria-labelledby="adminSignupSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="adminSignupSuccessModalLabel">Admin Account Created</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Your administrator account has been successfully created! Redirecting to the admin sign-in page...
                </div>
            </div>
        </div>
    </div>

    <!-- OTP Modal -->
    <div class="modal fade" id="adminOtpModal" tabindex="-1" aria-labelledby="adminOtpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="adminOtpModalLabel">Admin Verification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>An OTP has been sent to your email. Please enter it below to verify your admin account:</p>
                    <input type="text" id="admin-otp-input" class="form-control" placeholder="Enter OTP">
                    <span id="admin-otp-error" class="error-message"></span>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="verifyAdminOtp()">Verify</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Add this to the bottom of your HTML before closing body tag -->
    <!--<div class="container mt-5">
        <div class="row">
            <div class="col-md-12 text-center">
                <hr style="border-color: rgba(255,255,255,0.1);">
                <p class="text-muted">First time setup? Generate an initial admin token:</p>
                <button id="generate-initial-token" class="btn btn-outline-light">Generate Initial Setup Token</button>
                <div id="initial-token-result" class="mt-3" style="display: none;">
                    <div class="alert alert-success">
                        <strong>Your Initial Token:</strong> 
                        <span id="initial-token-value"></span>
                        <p class="mt-2 small">This token will expire in 24 hours. Use it to create your first admin account.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>-->
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    
    <script>
    // Modified switchTab function to correctly handle visibility
function switchTab(tabName) {
    // Hide all containers
    document.getElementById('signin-container').style.display = 'none';
    document.getElementById('signup-container').style.display = 'none';
    
    // Show selected container
    document.getElementById(tabName + '-container').style.display = 'flex';
    
    // Toggle token verification and signup fields based on tab
    if (tabName === 'signup') {
        document.getElementById('token-verification-container').style.display = 'block';
        document.getElementById('admin-signup-fields').style.display = 'none';
    } else {
        // Hide both when on signin tab
        document.getElementById('token-verification-container').style.display = 'none';
        document.getElementById('admin-signup-fields').style.display = 'none';
    }
    
    // Update active tab button
    document.getElementById('signin-tab').classList.remove('active');
    document.getElementById('signup-tab').classList.remove('active');
    document.getElementById(tabName + '-tab').classList.add('active');
}

// Initial setup when the page loads
document.addEventListener('DOMContentLoaded', function() {
    // Make sure the sign-in tab is active by default
    switchTab('signin');
    
    // Initialize password feedback
    updateAdminPasswordFeedback();
});

    // Admin Login Form Submit Handler
    document.getElementById('admin-signin-form').addEventListener('submit', function(event) {
        event.preventDefault();
        
        // Clear previous error messages
        document.getElementById('admin-username-error').textContent = '';
        document.getElementById('admin-password-error').textContent = '';
        
        // Validate input fields
        const username = document.getElementById('admin-username').value.trim();
        const password = document.getElementById('admin-password').value.trim();
        let isValid = true;
        
        if (!username) {
            document.getElementById('admin-username-error').textContent = 'Admin username is required';
            isValid = false;
        } else if (username.indexOf('admin') !== 0) {
            document.getElementById('admin-username-error').textContent = 'Admin username must start with "admin"';
            isValid = false;
        }
        
        if (!password) {
            document.getElementById('admin-password-error').textContent = 'Password is required';
            isValid = false;
        }
        
        if (isValid) {
            // Create form data for submission
            const formData = new FormData();
            formData.append('admin-username', username);
            formData.append('admin-password', password);
            formData.append('action', 'admin-login');
            
            // Send login request
            fetch('admin_login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success modal
                    const successModal = new bootstrap.Modal(document.getElementById('adminLoginSuccessModal'));
                    successModal.show();
                    
                    // Redirect after delay
                    setTimeout(() => {
                        window.location.href = data.redirectUrl || 'admin.php';
                    }, 1500);
                } else {
                    // Show error message
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your login request');
            });
        }
    });

    // Admin Signup Form Submit Handler
    document.getElementById('admin-signup-form').addEventListener('submit', function(event) {
        event.preventDefault();
        
        // Clear previous error messages
        document.getElementById('admin-username-signup-error').textContent = '';
        document.getElementById('admin-email-error').textContent = '';
        document.getElementById('admin-confirm-password-feedback').textContent = '';
        
        // Validate input fields
        const username = document.getElementById('admin-signup-username').value.trim();
        const email = document.getElementById('admin-signup-email').value.trim();
        const firstname = document.getElementById('admin-signup-firstname').value.trim();
        const surname = document.getElementById('admin-signup-surname').value.trim();
        const password = document.getElementById('admin-signup-password').value;
        const confirmPassword = document.getElementById('admin-signup-confirm-password').value;
        
        let isValid = true;
        
        // Check required fields
        if (!username || !email || !firstname || !surname || !password || !confirmPassword) {
            alert('All fields are required');
            isValid = false;
        }
        
        // Check username format
        if (username.indexOf('admin') !== 0) {
            document.getElementById('admin-username-signup-error').textContent = 'Admin username must start with "admin"';
            isValid = false;
        }
        
        // Check password match
        if (password !== confirmPassword) {
            document.getElementById('admin-confirm-password-feedback').textContent = 'Passwords do not match';
            isValid = false;
        }
        
        if (isValid) {
            // Create form data for submission
            const formData = new FormData(this);
            formData.append('action', 'admin-signup');
            
            // Send signup request
            fetch('admin_login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show OTP modal for verification
                    const otpModal = new bootstrap.Modal(document.getElementById('adminOtpModal'));
                    otpModal.show();
                } else {
                    // Show field-specific errors
                    if (data.field === 'username') {
                        document.getElementById('admin-username-signup-error').textContent = data.message;
                    } else if (data.field === 'email') {
                        document.getElementById('admin-email-error').textContent = data.message;
                    } else {
                        alert(data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your signup request');
            });
        }
    });

    // Function to verify admin OTP
    function verifyAdminOtp() {
        const otp = document.getElementById('admin-otp-input').value.trim();
        document.getElementById('admin-otp-error').textContent = '';
        
        if (!otp) {
            document.getElementById('admin-otp-error').textContent = 'Please enter the OTP sent to your email';
            return;
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('action', 'verifyAdminOtp');
        formData.append('otp', otp);
        
        // Send verification request
        fetch('admin_login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hide OTP modal
                const otpModal = bootstrap.Modal.getInstance(document.getElementById('adminOtpModal'));
                otpModal.hide();
                
                // Show success modal
                const successModal = new bootstrap.Modal(document.getElementById('adminSignupSuccessModal'));
                successModal.show();
                
                // Redirect to login tab after delay
                setTimeout(() => {
                    switchTab('signin');
                }, 2000);
            } else {
                document.getElementById('admin-otp-error').textContent = data.message;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('admin-otp-error').textContent = 'An error occurred during verification';
        });
    }

    // Confirm password validation
    document.getElementById('admin-signup-confirm-password').addEventListener('input', function() {
        const password = document.getElementById('admin-signup-password').value;
        const confirmPassword = this.value;
        const feedbackElement = document.getElementById('admin-confirm-password-feedback');
        
        if (password !== confirmPassword) {
            feedbackElement.textContent = 'Passwords do not match';
        } else {
            feedbackElement.textContent = '';
        }
    });

    // Initialize password feedback on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateAdminPasswordFeedback();
    });
    // Token verification
// Update the token verification function
document.getElementById('verify-token-btn').addEventListener('click', function() {
    const token = document.getElementById('admin-token').value.trim();
    document.getElementById('admin-token-error').textContent = '';
    
    if (!token) {
        document.getElementById('admin-token-error').textContent = 'Admin token is required';
        return;
    }
    
    // Create form data
    const formData = new FormData();
    formData.append('action', 'verify-admin-token');
    formData.append('admin-token', token);
    
    // Send verification request
    fetch('admin_login.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide token verification form
            document.getElementById('token-verification-container').style.display = 'none';
            
            // Show admin signup fields
            document.getElementById('admin-signup-fields').style.display = 'block';
            
            // Store token verification state in sessionStorage
            sessionStorage.setItem('adminTokenVerified', 'true');
        } else {
            document.getElementById('admin-token-error').textContent = data.message;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('admin-token-error').textContent = 'An error occurred during token verification';
    });
});
// Initial token generation
// Fixed Initial token generation with better error handling
document.getElementById('generate-initial-token').addEventListener('click', function() {
    // Show loading state
    const button = this;
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Generating...';
    
    // Clear previous results
    document.getElementById('initial-token-result').style.display = 'none';
    
    // Send request to generate initial setup token
    fetch('admin_login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=generate-admin-token&initial_setup=true&created_by=initial_setup&expiry_days=1'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            document.getElementById('initial-token-value').textContent = data.token;
            document.getElementById('initial-token-result').style.display = 'block';
            
            // Copy token to admin token field if signup tab is active
            document.getElementById('admin-token').value = data.token;
            
            // Switch to signup tab and show token verification
            switchTab('signup');
        } else {
            console.error('Token generation failed:', data.message);
            alert('Token generation failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while generating the initial token: ' + error.message);
    })
    .finally(() => {
        // Reset button state
        button.disabled = false;
        button.textContent = originalText;
    });
});
</script>
</body>
</html>