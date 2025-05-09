<?php
session_start();

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format";
        header("Location: contact.php");
        exit();
    }

    // Prepare email content
    $to = "your-email@example.com"; // Replace with your email address
    $email_subject = "Contact Form Submission: $subject";
    $email_body = "You have received a new message from your website contact form.\n\n" .
                 "Here are the details:\n\n" .
                 "Name: $name\n\n" .
                 "Email: $email\n\n" .
                 "Subject: $subject\n\n" .
                 "Message:\n$message";

    $headers = "From: $email\n";
    $headers .= "Reply-To: $email\n";

    // Send email
    if (mail($to, $email_subject, $email_body, $headers)) {
        $_SESSION['success'] = "Thank you for your message. We will get back to you soon!";
    } else {
        $_SESSION['error'] = "Sorry, something went wrong. Please try again later.";
    }

    // Redirect back to contact page
    header("Location: contact.php");
    exit();
} else {
    // If not a POST request, redirect to contact page
    header("Location: contact.php");
    exit();
}
?> 