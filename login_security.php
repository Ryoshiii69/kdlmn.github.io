<?php
require_once 'C:\xampp\htdocs\KADILIMAN\phpmailer\PHPMailer-master\src\Exception.php';
require_once 'C:\xampp\htdocs\KADILIMAN\phpmailer\PHPMailer-master\src\PHPMailer.php';
require_once 'C:\xampp\htdocs\KADILIMAN\phpmailer\PHPMailer-master\src\SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Track failed login attempts and send alert emails
 * 
 * @param string $username The username that failed login
 * @return void
 */
function trackFailedLoginAttempt($username) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Initialize the session variable if it doesn't exist
    if (!isset($_SESSION['failed_logins'][$username])) {
        $_SESSION['failed_logins'][$username] = [
            'count' => 0,
            'first_attempt' => time()
        ];
    }
    
    // Increment the failed attempt count
    $_SESSION['failed_logins'][$username]['count']++;
    
    // Get the current count
    $failedAttempts = $_SESSION['failed_logins'][$username]['count'];
    
    // Send alert email if threshold (3 attempts) is reached
    if ($failedAttempts >= 3) {
        sendLoginAlertIfEnabled($username);
        
        // Reset the counter after sending alert to avoid multiple alerts
        $_SESSION['failed_logins'][$username]['count'] = 0;
    }
}

/**
 * Reset failed login attempts for a user after successful login
 * 
 * @param string $username The username that successfully logged in
 * @return void
 */
function resetFailedLoginAttempts($username) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['failed_logins'][$username])) {
        unset($_SESSION['failed_logins'][$username]);
    }
}

/**
 * Check if user has enabled login alerts and send email if enabled
 * 
 * @param string $username The username to check and send alert for
 * @return bool True if alert was sent, false otherwise
 */
function sendLoginAlertIfEnabled($username) {
    // Connect to database
    $servername = "localhost";
    $dbusername = "root";
    $password = "";
    $dbname = "kadiliman";
    
    $conn = new mysqli($servername, $dbusername, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        return false;
    }
    
    // Get user information including preference setting
    $stmt = $conn->prepare("SELECT id, email, firstname, surname, login_alerts FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Username doesn't exist
        $stmt->close();
        $conn->close();
        return false;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    // Check if login alerts are enabled (value = 1)
    if ((int)$user['login_alerts'] !== 1) {
        return false;
    }
    
    // User has alerts enabled, send email notification
    return sendLoginAlertEmail($user['email'], $user['firstname'], $user['surname'], $username);
}

/**
 * Send login alert email using PHPMailer
 * 
 * @param string $email Recipient email address
 * @param string $firstname User's first name
 * @param string $surname User's surname
 * @param string $username Username that had multiple failed attempts
 * @return bool True if email sent successfully, false otherwise
 */
function sendLoginAlertEmail($email, $firstname, $surname, $username) {
    // Create PHP Mailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->SMTPDebug = 0; // Set to 2 for debugging
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'dado.aldrin2133@gmail.com'; // Your Gmail address
        $mail->Password = 'nxpu moix tawy ieoy'; // Your Gmail app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('your_email@gmail.com', 'Kadiliman Security');
        $mail->addAddress($email, $firstname . ' ' . $surname);
        
        // Get client information
        $clientIP = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $location = "Unknown Location"; // You could use GeoIP services for better location detection
        $currentTime = date("Y-m-d H:i:s");
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Security Alert - Multiple Failed Login Attempts';
        $mail->Body = "
            <h2>Security Alert: Multiple Failed Login Attempts</h2>
            <p>Dear {$firstname} {$surname},</p>
            
            <p>We've detected multiple failed login attempts on your Kadiliman account.</p>
            
            <h3>Details:</h3>
            <ul>
                <li><strong>Username:</strong> {$username}</li>
                <li><strong>Time:</strong> {$currentTime}</li>
                <li><strong>IP Address:</strong> {$clientIP}</li>
                <li><strong>Browser:</strong> {$userAgent}</li>
                <li><strong>Location:</strong> {$location}</li>
            </ul>
            
            <p>If this wasn't you, we recommend changing your password immediately.</p>
            
            <p>If you need any assistance, please contact our support team.</p>
            
            <p>Thank you,<br>
            Kadiliman Security Team</p>
        ";
        
        // Send email
        $mail->send();
        error_log("Login alert email sent to {$email} for username {$username}");
        return true;
    } catch (Exception $e) {
        error_log("Failed to send login alert email: " . $mail->ErrorInfo);
        return false;
    }
}
?>
