<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Add this near the top of your file, after other require statements
require_once 'password_notification.php';

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['firstname'])) {
    $redirect = urlencode($_SERVER['PHP_SELF']);
    header("Location: Registration.php?redirect=$redirect");
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
    die("Connection failed: " . $conn->connect_error);
}

// Enable error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Initialize variables for success/error messages
$success_message = "";
$error_message = "";

// Get current user's information from database
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
    $firstname = $user_data['firstname'];
    $surname = $user_data['surname'];
    $email = $user_data['email'];
    $username = $user_data['username'];
} else {
    header("Location: Registration.php");
    exit();
}

// Fetch the current state of login alerts from the database
$login_alerts_state = $user_data['login_alerts'] ? 'checked' : '';

// Handle personal information update
if (isset($_POST['update_personal_info'])) {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        $new_firstname = $_POST['firstName'];
        $new_surname = $_POST['surName'];
        $new_email = $_POST['email'];
        
        $update_sql = "UPDATE users SET firstname = ?, surname = ?, email = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssi", $new_firstname, $new_surname, $new_email, $user_id);
        
        if ($update_stmt->execute()) {
            // Commit the transaction
            $conn->commit();
            
            // Update session data
            $_SESSION['firstname'] = $new_firstname;
            $success_message = "Personal information updated successfully!";
            
            // Refresh user data
            $firstname = $new_firstname;
            $surname = $new_surname;
            $email = $new_email;
        } else {
            // Rollback on failure
            $conn->rollback();
            $error_message = "Error updating information: " . $conn->error;
        }
    } catch (Exception $e) {
        // Rollback on exception
        $conn->rollback();
        $error_message = "Exception occurred: " . $e->getMessage();
    }
}

// Handle password change
if (isset($_POST['update_password'])) {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        $current_password = $_POST['currentPassword'];
        $new_password = $_POST['newPassword'];
        $confirm_password = $_POST['confirmPassword'];
        
        // Verify current password
        $password_sql = "SELECT password FROM users WHERE id = ?";
        $password_stmt = $conn->prepare($password_sql);
        $password_stmt->bind_param("i", $user_id);
        $password_stmt->execute();
        $password_result = $password_stmt->get_result();
        $password_data = $password_result->fetch_assoc();
        
        if (password_verify($current_password, $password_data['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $update_pass_sql = "UPDATE users SET password = ? WHERE id = ?";
                $update_pass_stmt = $conn->prepare($update_pass_sql);
                $update_pass_stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($update_pass_stmt->execute()) {
                    // Commit the transaction
                    $conn->commit();
                    $success_message = "Password updated successfully!";
                    
                    // Send password change notification email
                    sendPasswordChangeNotification($user_id);
                } else {
                    // Rollback on failure
                    $conn->rollback();
                    $error_message = "Error updating password: " . $conn->error;
                }
            } else {
                $error_message = "New passwords don't match!";
            }
        } else {
            $error_message = "Current password is incorrect!";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Exception occurred: " . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="css/Settings.css">
    <link rel="icon" href="img/EYE LOGO.png" type="image/x-icon">
    <title>Settings</title>
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
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  Branches
                </a>
                <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                    <li><a class="dropdown-item" href="branches/manila.html">Manila</a></li>
                    <li><a class="dropdown-item" href="branches/quezon-city.html">Quezon City</a></li>
                    <li><a class="dropdown-item" href="branches/makati.html">Makati</a></li>
                    <li><a class="dropdown-item" href="branches/pasig.html">Pasig</a></li>
                    <li><a class="dropdown-item" href="branches/alabang.html">Alabang</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="branches/all-locations.html">All Locations</a></li>
                </ul>
              </li>
            </ul>
            <?php if (isset($_SESSION['username'])): ?>
                <div class="ms-auto dropdown">
                  <button class="btn btn-sign-in dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php echo $_SESSION['username']; ?>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                    <li><a class="dropdown-item" href="/KADILIMAN/register/logout.php">Log Out</a></li>
                  </ul>
                </div>
            <?php else: ?>
                <div class="ms-auto">
                  <a href="Registration.php" class="btn btn-sign-in">Sign In</a>
                </div>
            <?php endif; ?>
        </div>
      </nav>
      
    <div class="profile-container">
        <div class="sidebar">
            <div class="sidebar-nav">
                <a href="#personal-info" class="sidebar-item active" data-section="personal-info">
                    <img class="sidebar-icon" src="img/profile-user%20(2).png" alt="Personal Info Icon">
                    <span>Personal Information</span>
                </a>
                <a href="#security" class="sidebar-item" data-section="security">
                    <img class="sidebar-icon" src="img/secure.png" alt="Security Icon">
                    <span>Security</span>
                </a>
                <a href="#two-factor" class="sidebar-item" data-section="two-factor">
                    <img class="sidebar-icon" src="img/authentication.png" alt="2FA Icon">
                    <span>Two-Factor Authentication</span>
                </a>
                <a href="#login-management" class="sidebar-item" data-section="login-management">
                    <img class="sidebar-icon" src="img/profile.png" alt="Login Management Icon">
                    <span>Login Management</span>
                </a>
            </div>
        </div>

        <div class="content-area">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <section id="personal-info" class="content-section">
                <h2 class="section-title">Personal Information</h2>
                <p class="section-description">This information is private and will not be shared with other users.</p>

                <div class="card mb-4">
                    <div class="username-display">Your username is used to log in and operate the computer</div>
                    <div class="username-value">
                        <span><?php echo htmlspecialchars($username ?? 'Username not set'); ?></span>
                        <span class="status-indicator status-online"></span>
                        <span class="text-success">Online</span>
                    </div>
                </div>

                <form method="post" action="">
                    <div class="row mb-3">
                        <div class="col-md-12 mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo htmlspecialchars($firstname ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="surName" class="form-label">Surname</label>
                            <input type="text" class="form-control" id="surname" name="surName" value="<?php echo htmlspecialchars($surname ?? ''); ?>">
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" name="update_personal_info" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </section>

            <section id="security" class="content-section">
                <h2 class="section-title">Security</h2>
                <p class="section-description">Manage your password and security settings to keep your account safe.</p>

                <div class="card mb-4">
                    <h5 class="mb-3">Change Password</h5>
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                        </div>
                        <div class="mb-4">
                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                        </div>
                        <div class="text-end">
                            <button type="submit" name="update_password" class="btn btn-primary">Update Password</button>
                        </div>
                    </form>
                </div>
                <div class="card">
                    <h5 class="mb-3">Security Notifications</h5>
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="loginAlerts" <?php echo $login_alerts_state; ?>>
                        <label class="form-check-label" for="loginAlerts">Receive alerts for unusual login attempts</label>
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="passwordChanges" <?php echo $user_data['password_changes'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="passwordChanges">Notify me when my password is changed</label>
                    </div>
                </div>
            </section>
            <section id="two-factor" class="content-section">
                <h2 class="section-title">Two-Factor Authentication</h2>
                <p class="section-description">Protect your account from unauthorized access by requiring a secure code when signing in.</p>

                <div class="card mb-4">
                    <div class="verification-section">
                        <div class="verification-icon">
                            <img src="img/authentication.png" alt="Email Verification" style="width: 100%; height: auto;">
                        </div>
                        <div class="verification-info">
                            <h5 class="verification-title">Email Authentication</h5>
                            <p class="verification-description">We will send a verification code to your email: <?php echo htmlspecialchars($email ?? ''); ?></p>
                        </div>
                        <button class="btn btn-primary" id="send-verification">Send</button>
                    </div>

                    <div class="mt-4" id="verification-code-form" style="display: none;">
                        <label class="form-label">Enter verification code</label>
                        <div class="d-flex gap-2 mb-3">
                            <input type="text" class="form-control text-center" maxlength="1">
                            <input type="text" class="form-control text-center" maxlength="1">
                            <input type="text" class="form-control text-center" maxlength="1">
                            <input type="text" class="form-control text-center" maxlength="1">
                            <input type="text" class="form-control text-center" maxlength="1">
                            <input type="text" class="form-control text-center" maxlength="1">
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <button class="btn btn-link text-white p-0">Resend code</button>
                            <button class="btn btn-primary">Verify</button>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h5 class="mb-3">Two-Factor Methods</h5>
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="emailAuth" checked>
                        <label class="form-check-label" for="emailAuth">Email Authentication</label>
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="appAuth">
                        <label class="form-check-label" for="appAuth">Authenticator App</label>
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="smsAuth">
                        <label class="form-check-label" for="smsAuth">SMS Authentication</label>
                    </div>
                </div>
            </section>

            <!-- Login Management Section -->
            <section id="login-management" class="content-section">
                <h2 class="section-title">Login Management</h2>
                <p class="section-description">Worried that your account or password has been compromised? You can forcibly log out from all Kadiliman branches.</p>

                <div class="card mb-4">
                    <h5 class="mb-4">Active Sessions</h5>
                    <div class="mb-3 d-flex justify-content-between align-items-center pb-3" style="border-bottom: 1px solid rgba(255,255,255,0.1)">
                        <div>
                            <div class="d-flex align-items-center">
                                <span class="status-indicator status-online"></span>
                                <strong>Makati Branch</strong>
                            </div>
                            <div class="text-muted small">Last activity: Today, 2:15 PM</div>
                        </div>
                        <button class="btn btn-sm btn-outline-danger">End Session</button>
                    </div>
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="d-flex align-items-center">
                                <span class="status-indicator status-online"></span>
                                <strong>This device</strong>
                            </div>
                            <div class="text-muted small">Last activity: Just now</div>
                        </div>
                        <button class="btn btn-sm btn-outline-danger">End Session</button>
                    </div>
                </div>

                <div class="text-center mb-4">
                    <button class="btn btn-danger btn-lg">LOG OUT EVERYWHERE</button>
                </div>

                <div class="logo-container">
                    <img src="img/eye-removebg-preview.png" class="logo-img mb-2" alt="Kadiliman Logo">
                    <h4 class="text-center text-white">KADILIMAN<span class="text-muted small"> ESPORTS CAFE</span></h4>
                </div>
            </section>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/settings.js"></script>
    <?php include('preference_handler.php'); ?>

  </body>
</html>