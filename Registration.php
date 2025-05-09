<?php
require 'C:\xampp\htdocs\KADILIMAN\phpmailer\PHPMailer-master\src\Exception.php';
require 'C:\xampp\htdocs\KADILIMAN\phpmailer\PHPMailer-master\src\PHPMailer.php';
require 'C:\xampp\htdocs\KADILIMAN\phpmailer\PHPMailer-master\src\SMTP.php';
require_once 'C:\xampp\htdocs\KADILIMAN\login_security.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

// Ensure no output is sent before headers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    header('Content-Type: application/json');

    // Validate required fields
    if (empty($_POST['signup-username']) || empty($_POST['signup-email']) || empty($_POST['signup-firstname']) || empty($_POST['signup-surname']) || empty($_POST['signup-branch']) || empty($_POST['signup-password'])) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "kadiliman";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
        exit;
    }

    // Check if the username or email already exists
    $signupUsername = $_POST['signup-username'];
    $signupEmail = $_POST['signup-email'];

    $checkQuery = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $checkQuery->bind_param("ss", $signupUsername, $signupEmail);
    $checkQuery->execute();
    $result = $checkQuery->get_result();

    if ($result->num_rows > 0) {
        $existingUser = $result->fetch_assoc();
        if ($existingUser['username'] === $signupUsername) {
            echo json_encode(['success' => false, 'field' => 'username', 'message' => 'The username is already taken.']);
        } elseif ($existingUser['email'] === $signupEmail) {
            echo json_encode(['success' => false, 'field' => 'email', 'message' => 'The email is already taken.']);
        }
        $checkQuery->close();
        $conn->close();
        exit;
    }

    // Generate OTP
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_expiry'] = time() + 300; // Set OTP expiry time to 5 minutes from now
    $_SESSION['signup-data'] = $_POST; // Temporarily store signup data

    // Send OTP using PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->SMTPDebug = 2; // Enables debug output for troubleshooting
        $mail->Debugoutput = 'error_log'; // Send debug output to error log
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'dado.aldrin2133@gmail.com'; // Replace with your email for SMTP authentication
        $mail->Password = 'nxpu moix tawy ieoy'; // Replace with your password for SMTP authentication
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('your_email@gmail.com', 'Kadiliman'); // Replace with your email
        $mail->addAddress($signupEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body    = "Dear user,<br><br>Your OTP code is: <b>$otp</b>.<br><br>This code is required to complete your registration. If you did not request this, please ignore this email or contact our support team for assistance.<br><br>Thank you,<br>Kadiliman Team";

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'OTP sent to your email.']);
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo); // Log the error
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP. Mailer Error: ' . $mail->ErrorInfo]);
    }

    $conn->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verifyOtp') {
    header('Content-Type: application/json');
    $enteredOtp = $_POST['otp'];

    // Check if OTP exists and hasn't expired
    if (isset($_SESSION['otp']) && isset($_SESSION['otp_expiry']) && time() < $_SESSION['otp_expiry']) {
        if ($enteredOtp == $_SESSION['otp']) {
            // Create database connection
            $servername = "localhost";
            $username = "root";
            $password = "";
            $dbname = "kadiliman";
            
            $conn = new mysqli($servername, $username, $password, $dbname);
            
            // Check connection
            if ($conn->connect_error) {
                echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
                exit;
            }
            
            // Save user data to the database
            $signupData = $_SESSION['signup-data'];
            $stmt = $conn->prepare("INSERT INTO users (username, email, firstname, surname, branch, password) VALUES (?, ?, ?, ?, ?, ?)");
            $hashedPassword = password_hash($signupData['signup-password'], PASSWORD_BCRYPT);
            $stmt->bind_param(
                "ssssss",
                $signupData['signup-username'],
                $signupData['signup-email'],
                $signupData['signup-firstname'],
                $signupData['signup-surname'],
                $signupData['signup-branch'],
                $hashedPassword
            );

            if ($stmt->execute()) {
                // Clear session data
                unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['signup-data']);
                echo json_encode(['success' => true, 'message' => 'Account successfully created!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create account: ' . $stmt->error]);
            }
            $stmt->close();
            $conn->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid OTP.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'OTP has expired.']);
    }
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
    <link rel="stylesheet" href="css/registration.css">
    <link rel="icon" href="img/EYE LOGO.png" type="image/x-icon">
    <title>Login / Sign Up</title>
  </head>
  <body>
    
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
            <img src="img/eye-removebg-preview.png" alt="Logo" height="40">
          </a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav center-nav">
              <li class="nav-item">
                <a class="nav-link" aria-current="page" href="Dashboard.php">Dashboard</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="Features.php">Features</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="contact.php">Contacts</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="branches.php">Branches</a>
              </li>
            </ul>
          </div>
        </div>
      </nav>
      
      <div class="container">
        <div class="login-container">
            <div class="tab-selector">
                <button class="tab-btn active" id="signin-tab" onclick="switchTab('signin')">Sign-in</button>
                <button class="tab-btn" id="signup-tab" onclick="switchTab('signup')">Sign-Up</button>
            </div>
            
            <!-- Sign In Form -->
            <form id="signin-form" action="/KADILIMAN/register/login.php" method="POST">
                <div class="login-form-container" id="signin-container">
                    <div class="form-section">
                        <div class="form-group">
                            <label class="form-label">Username:</label>
                            <input type="text" class="form-control" id="signin-username" name="signin-username">
                            <span id="signin-username-error" style="color: red; font-size: 12px;"></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password:</label>
                            <input type="password" class="form-control" id="signin-password" name="signin-password">
                            <span id="signin-password-error" style="color: red; font-size: 12px;"></span>
                        </div>
                        <button class="submit-btn" type="submit">Sign-in</button>
                        <div class="help-text">
                            <a href="#">Need Help?</a>
                        </div>
                    </div>
                    <div class="cafe-image" style="background-image: url('img/LOGO-removebg-preview.png')"></div>
                </div>
            </form>
            
            <!-- Sign Up Form -->
            <form id="signup-form">
                <div class="login-form-container" id="signup-container" style="display: none;">
                    <div class="form-section">
                        <div class="form-group">
                            <label class="form-label">Username:</label>
                            <input type="text" class="form-control" id="signup-username" name="signup-username">
                            <span id="username-error" style="color: red; font-size: 12px;"></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email:</label>
                            <input type="email" class="form-control" id="signup-email" name="signup-email">
                            <span id="email-error" style="color: red; font-size: 12px;"></span>
                        </div>
                        <div class="name-row">
                            <div class="name-field">
                                <label class="form-label">First Name:</label>
                                <input type="text" class="form-control" id="signup-firstname" name="signup-firstname">
                            </div>
                            <div class="name-field">
                                <label class="form-label">Surname:</label>
                                <input type="text" class="form-control" id="signup-surname" name="signup-surname">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Branch:</label>
                            <input type="text" class="form-control" id="signup-branch" name="signup-branch">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password:</label>
                            <input type="password" class="form-control" id="signup-password" name="signup-password" oninput="updatePasswordFeedback()">
                            <div id="password-feedback" style="margin-top: 10px;"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm Password:</label>
                            <input type="password" class="form-control" id="signup-confirm-password" name="signup-confirm-password">
                            <div id="confirm-password-feedback" style="margin-top: 10px; color: red; font-size: 12px;"></div>
                        </div>
                        <button class="submit-btn" type="submit" onclick="return validateConfirmPassword()">Sign-Up</button>
                    </div>
                </div>
            </form>
        </div>
      </div>

      <!-- Registration Success Modal -->
      <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content" style="background-color: #333; color: white;">
                  <div class="modal-header">
                      <h5 class="modal-title" id="successModalLabel">Successful</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="background-color: white;"></button>
                  </div>
                  <div class="modal-body">
                      Successfully logged in
                  </div>
              </div>
          </div>
      </div>

      <!-- Sign-Up Success Modal -->
      <div class="modal fade" id="signupSuccessModal" tabindex="-1" aria-labelledby="signupSuccessModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content" style="background-color: #333; color: white;">
                  <div class="modal-header">
                      <h5 class="modal-title" id="signupSuccessModalLabel">Account Created</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="background-color: white;"></button>
                  </div>
                  <div class="modal-body">
                      Your account has been successfully created! Redirecting to the sign-in page...
                  </div>
              </div>
          </div>
      </div>

      <!-- OTP Modal -->
      <div class="modal fade" id="otpModal" tabindex="-1" aria-labelledby="otpModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content" style="background-color: #333; color: white;">
                  <div class="modal-header">
                      <h5 class="modal-title" id="otpModalLabel">Enter OTP</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="background-color: white;"></button>
                  </div>
                  <div class="modal-body">
                      <p>An OTP has been sent to your email. Please enter it below:</p>
                      <input type="text" id="otp-input" class="form-control" placeholder="Enter OTP">
                      <span id="otp-error" style="color: red; font-size: 12px;"></span>
                  </div>
                  <div class="modal-footer">
                      <button type="button" class="btn btn-primary" onclick="verifyOtp()">Verify OTP</button>
                  </div>
              </div>
          </div>
      </div>

      <!-- Error Modal -->
      <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content" style="background-color: #333; color: white;">
                  <div class="modal-header">
                      <h5 class="modal-title" id="errorModalLabel">Error</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="background-color: white;"></button>
                  </div>
                  <div class="modal-body">
                      <span id="error-message"></span>
                  </div>
              </div>
          </div>
      </div>

    <!-- JavaScript for tab switching -->
    <script>
    function switchTab(tabName) {
        // Hide all containers
        document.getElementById('signin-container').style.display = 'none';
        document.getElementById('signup-container').style.display = 'none';
        
        // Show selected container
        document.getElementById(tabName + '-container').style.display = 'flex';
        
        // Update active tab button
        document.getElementById('signin-tab').classList.remove('active');
        document.getElementById('signup-tab').classList.remove('active');
        document.getElementById(tabName + '-tab').classList.add('active');
    }

    function updatePasswordFeedback() {
        const password = document.getElementById('signup-password').value;
        const feedback = validatePassword(password);
        document.getElementById('password-feedback').innerHTML = feedback;
    }

    function validateFormFields() {
        const requiredFields = [
            'signup-username', 'signup-email', 'signup-firstname', 'signup-surname', 'signup-branch', 'signup-password'
        ];
        let isValid = true;

        requiredFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            const errorElement = document.createElement('div');
            errorElement.className = 'error-message';
            errorElement.style.color = 'red';
            errorElement.style.fontSize = '12px';
            errorElement.textContent = 'This is required';

            // Remove existing error message
            if (field.nextElementSibling && field.nextElementSibling.className === 'error-message') {
                field.nextElementSibling.remove();
            }

            if (!field.value.trim()) {
                field.parentNode.appendChild(errorElement);
                isValid = false;
            }
        });

        return isValid;
    }

    function validateConfirmPassword() {
        const password = document.getElementById('signup-password').value;
        const confirmPassword = document.getElementById('signup-confirm-password').value;
        const feedback = document.getElementById('confirm-password-feedback');

        if (password !== confirmPassword) {
            feedback.textContent = 'Passwords do not match.';
            return false; // Prevent form submission
        } else {
            feedback.textContent = ''; // Clear feedback if passwords match
            return true;
        }
    }

    document.querySelector('form').addEventListener('submit', function(event) {
    event.preventDefault();

    const formData = new FormData(this);
    
    // Store the current page URL before login
    let returnTo = '';
    
    // First priority: Check URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const paramRedirect = urlParams.get('redirect');
    
    // Second priority: Use document.referrer (where user came from)
    const referrer = document.referrer;
    const referrerPage = referrer ? referrer.split('/').pop() : '';
    
    // Third priority: Use localStorage if we stored the page earlier
    const storedPage = localStorage.getItem('lastVisitedPage');
    
    // Check if user is coming from homepage
    const isFromHomepage = referrerPage === '' || referrerPage === 'Homepage.php' || referrerPage === 'Homepage.php' || referrer.endsWith('/');
    
    // Determine which page to redirect to after login
    if (isFromHomepage) {
        // If coming from homepage, always redirect to Dashboard
        returnTo = 'Dashboard.php';
    } else if (paramRedirect) {
        // Priority 1: Use explicit redirect parameter
        returnTo = paramRedirect;
    } else if (referrerPage && referrerPage !== 'login.php') {
        // Priority 2: Use referrer if it's not the login page itself
        returnTo = referrerPage;
    } else if (storedPage) {
        // Priority 3: Use previously stored page
        returnTo = storedPage;
    } else {
        // Default fallback
        returnTo = 'Dashboard.php';
    }
    
    // Save return URL in form data to send to the server
    formData.append('returnTo', returnTo);

    fetch('/KADILIMAN/register/login.php', {
        method: 'POST',
        body: formData
    })
    .then((response) => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then((data) => {
        if (data.success) {
            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
            
            // Clear the stored page since we're about to redirect
            localStorage.removeItem('lastVisitedPage');
            
            // Auto close modal after 1.5 seconds and redirect
            setTimeout(() => {
                successModal.hide();
                // Use the server's redirect suggestion if available, otherwise use our calculated returnTo
                window.location.href = data.redirectUrl || returnTo;
            }, 1500);
        } else {
            // Show error in modal if showModal is true
            if (data.showModal) {
                const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                document.getElementById('error-message').textContent = data.message || 'An error occurred. Please try again.';
                errorModal.show();
                
                // Don't auto-close modal for database errors
                if (!data.message.includes('Database') && !data.message.includes('contact support')) {
                    setTimeout(() => {
                        errorModal.hide();
                    }, 3000);
                }
            }
        }
    })
    .catch((error) => {
        // Empty catch block
    });
});


// Add this script to all your pages to track user location
//document.addEventListener('DOMContentLoaded', function() {
    // Don't store login page as return destination
    //if (!window.location.pathname.includes('login.php')) {
        //localStorage.setItem('lastVisitedPage', window.location.pathname.split('/').pop() || 'Dashboard.php');
   // }
//});
    </script>

    <script>
      document.getElementById('signup-form').addEventListener('submit', function (event) {
        event.preventDefault();

        // Clear previous error messages
        document.getElementById('username-error').textContent = '';
        document.getElementById('email-error').textContent = '';

        const formData = new FormData(this);

        fetch('', {
            method: 'POST',
            body: formData,
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    const otpModal = new bootstrap.Modal(document.getElementById('otpModal'));
                    otpModal.show();
                } else {
                    if (data.field === 'username') {
                        document.getElementById('username-error').textContent = data.message;
                    } else if (data.field === 'email') {
                        document.getElementById('email-error').textContent = data.message;
                    }
                }
            })
            .catch((error) => {
                // Empty catch block
            });
      });

      function verifyOtp() {
    const otp = document.getElementById('otp-input').value.trim();
    const otpError = document.getElementById('otp-error');

    otpError.textContent = ''; // Clear previous error

    if (!otp) {
        otpError.textContent = 'OTP is required.';
        return;
    }

    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `otp=${otp}&action=verifyOtp`,
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Hide the OTP modal first
                const otpModal = bootstrap.Modal.getInstance(document.getElementById('otpModal'));
                otpModal.hide();
                
                // Show success modal
                const signupSuccessModal = new bootstrap.Modal(document.getElementById('signupSuccessModal'));
                signupSuccessModal.show();

                // After delay, hide success modal and switch to sign-in tab
                setTimeout(() => {
                    signupSuccessModal.hide();
                    switchTab('signin'); // Switch to sign-in tab
                }, 1500);
            } else {
                otpError.textContent = data.message;
            }
        })
        .catch((error) => {
            // Empty catch block
        });
}

      function switchTab(tabName) {
        document.getElementById('signin-container').style.display = 'none';
        document.getElementById('signup-container').style.display = 'none';

        document.getElementById(tabName + '-container').style.display = 'flex';

        document.getElementById('signin-tab').classList.remove('active');
        document.getElementById('signup-tab').classList.remove('active');
        document.getElementById(tabName + '-tab').classList.add('active');
      }

      // Updated login form handler
document.getElementById('signin-form').addEventListener('submit', function (event) {
    event.preventDefault();

    // Prevent multiple submissions
    if (this.submitting) {
        return;
    }
    this.submitting = true;

    // Clear previous error messages
    document.getElementById('signin-username-error').textContent = '';
    document.getElementById('signin-password-error').textContent = '';

    let isValid = true;

    // Validate username
    const username = document.getElementById('signin-username').value.trim();
    if (!username) {
        const usernameError = document.getElementById('signin-username-error');
        usernameError.textContent = '';
        isValid = false;
    }

    // Validate password
    const password = document.getElementById('signin-password').value.trim();
    if (!password) {
        const passwordError = document.getElementById('signin-password-error');
        passwordError.textContent = '';
        isValid = false;
    }

    // If the form is valid, proceed with the fetch request
    if (isValid) {
        const formData = new FormData(this);
        formData.append('action', 'login');
        formData.append('trackSecurity', 'true'); // Flag to enable security tracking

        // Show loading state
        const submitButton = this.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = 'Signing in...';

        // Close any existing modals
        const existingModals = document.querySelectorAll('.modal');
        existingModals.forEach(modal => {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        });

        fetch('/KADILIMAN/register/registerUser.php', {
            method: 'POST',
            body: formData,
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then((data) => {
                if (data.success) {
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();

                    setTimeout(() => {
                        successModal.hide();
                        window.location.href = data.redirectUrl || 'Dashboard.php';
                    }, 1500);
                } else {
                    // Show error message in modal
                    if (data.showModal) {
                        const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                        document.getElementById('error-message').textContent = data.message || 'An error occurred. Please try again.';
                        errorModal.show();
                        
                        // Don't auto-close for server-related errors
                        if (!data.message.includes('service') && !data.message.includes('XAMPP')) {
                            setTimeout(() => {
                                errorModal.hide();
                            }, 3000);
                        }
                    }
                }
            })
            .catch((error) => {
                console.error('Error:', error);
            })
            .finally(() => {
                // Reset button state and form submission flag
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
                this.submitting = false;
            });
    } else {
        this.submitting = false;
    }
});
    </script>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    <script src="register/passwordValidation.js"></script>
  </body>
</html>