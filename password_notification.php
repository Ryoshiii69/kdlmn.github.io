<?php
require 'C:\xampp\htdocs\KADILIMAN\phpmailer\PHPMailer-master\src\Exception.php';
require 'C:\xampp\htdocs\KADILIMAN\phpmailer\PHPMailer-master\src\PHPMailer.php';
require 'C:\xampp\htdocs\KADILIMAN\phpmailer\PHPMailer-master\src\SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send password change notification email to user
 * 
 * @param int $user_id ID of the user who changed password
 * @return bool Whether the email was sent successfully
 */
function sendPasswordChangeNotification($user_id) {
    // Database connection
    $servername = "localhost";
    $db_username = "root";
    $password = "";
    $dbname = "kadiliman";
    
    // Create connection
    $conn = new mysqli($servername, $db_username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        return false;
    }
    
    // Get user information
    $stmt = $conn->prepare("SELECT firstname, email, password_changes FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // User not found
        $stmt->close();
        $conn->close();
        return false;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    // Check if user wants password change notifications
    if (!$user['password_changes']) {
        return false; // User has disabled password change notifications
    }
    
    // Current date and time
    $datetime = date('F j, Y \a\t g:i a');
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $device_info = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown device';
    
    // Send email using PHPMailer
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'dado.aldrin2133@gmail.com';
        $mail->Password = 'nxpu moix tawy ieoy';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('noreply@kadiliman.com', 'Kadiliman Security');
        $mail->addAddress($user['email']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Changed - Kadiliman Account Security';
        
        // Email body with password change details
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='text-align: center; margin-bottom: 20px;'>
                <img src='https://your-website.com/img/eye-removebg-preview.png' alt='Kadiliman Logo' style='height: 60px;'>
            </div>
            
            <h2 style='color: #333;'>Password Changed</h2>
            
            <p>Hello {$user['firstname']},</p>
            
            <p>We're confirming that you recently changed your password for your Kadiliman account.</p>
            
            <div style='background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <p><strong>🕒 Time:</strong> {$datetime}</p>
                <p><strong>🖥️ Device:</strong> {$device_info}</p>
                <p><strong>🌐 IP Address:</strong> {$ip_address}</p>
            </div>
            
            <p>If this was you, no further action is needed.</p>
            
            <p>If you did NOT change your password, please contact our support team immediately and secure your account by:</p>
            <ol>
                <li>Changing your password immediately</li>
                <li>Enabling two-factor authentication if not already enabled</li>
                <li>Reviewing your recent account activity</li>
            </ol>
            
            <p>Stay secure,<br>
            The <strong>Kadiliman</strong> Team</p>
            
            <div style='margin-top: 30px; font-size: 12px; color: #666; text-align: center;'>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>You received this email because you have enabled password change notifications in your account settings.</p>
            </div>
        </div>
        ";
        
        // Plain text version
        $mail->AltBody = "
Hello {$user['firstname']},

We're confirming that you recently changed your password for your Kadiliman account.

Time: {$datetime}
Device: {$device_info}
IP Address: {$ip_address}

If this was you, no further action is needed.

If you did NOT change your password, please contact our support team immediately and secure your account by:
1. Changing your password immediately
2. Enabling two-factor authentication if not already enabled
3. Reviewing your recent account activity

Stay secure,
The Kadiliman Team

This is an automated message. Please do not reply to this email.
You received this email because you have enabled password change notifications in your account settings.
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Failed to send password change notification: " . $mail->ErrorInfo);
        return false;
    }
}
?>