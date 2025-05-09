<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    // If not logged in, redirect to admin login page
    header("Location: admin_login.php");
    exit();
}

// Get admin username from session
$admin_username = $_SESSION['admin_username'];

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

// Initialize variables for messages
$success_message = "";
$error_message = "";

// Handle user actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle user deletion
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        try {
            $conn->begin_transaction();
            
            $delete_sql = "DELETE FROM users WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $user_id);
            
            if ($delete_stmt->execute()) {
                $conn->commit();
                $success_message = "User deleted successfully!";
            } else {
                $conn->rollback();
                $error_message = "Error deleting user: " . $conn->error;
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Exception occurred: " . $e->getMessage();
        }
    }
    
    // Handle account locking/unlocking
    if (isset($_POST['toggle_lock'])) {
        $user_id = $_POST['user_id'];
        $current_status = $_POST['current_status'];
        $new_status = $current_status ? 0 : 1; // Toggle status
        
        try {
            $conn->begin_transaction();
            
            $lock_sql = "UPDATE users SET account_active = ? WHERE id = ?";
            $lock_stmt = $conn->prepare($lock_sql);
            $lock_stmt->bind_param("ii", $new_status, $user_id);
            
            if ($lock_stmt->execute()) {
                $conn->commit();
                $status_text = $new_status ? "unlocked" : "locked";
                $success_message = "User account {$status_text} successfully!";
            } else {
                $conn->rollback();
                $error_message = "Error changing account status: " . $conn->error;
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Exception occurred: " . $e->getMessage();
        }
    }
    
    // Handle password reset
    if (isset($_POST['reset_password'])) {
        $user_id = $_POST['user_id'];
        $new_password = password_hash("Kadiliman2025", PASSWORD_DEFAULT); // Default password
        
        try {
            $conn->begin_transaction();
            
            $reset_sql = "UPDATE users SET password = ? WHERE id = ?";
            $reset_stmt = $conn->prepare($reset_sql);
            $reset_stmt->bind_param("si", $new_password, $user_id);
            
            if ($reset_stmt->execute()) {
                $conn->commit();
                $success_message = "User password reset successfully to 'Kadiliman2025'!";
            } else {
                $conn->rollback();
                $error_message = "Error resetting password: " . $conn->error;
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Exception occurred: " . $e->getMessage();
        }
    }
    
    // Handle balance update
    if (isset($_POST['update_balance'])) {
        $user_id = $_POST['user_id'];
        $standard_balance = $_POST['standard_balance'];
        $premium_balance = $_POST['premium_balance'];
        
        try {
            $conn->begin_transaction();
            
            // Check if user has a balance record
            $check_sql = "SELECT id FROM user_balance WHERE user_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Update existing balance
                $balance_sql = "UPDATE user_balance SET standard_balance = ?, premium_balance = ? WHERE user_id = ?";
                $balance_stmt = $conn->prepare($balance_sql);
                $balance_stmt->bind_param("ddi", $standard_balance, $premium_balance, $user_id);
            } else {
                // Insert new balance record
                // First get the username
                $user_sql = "SELECT username FROM users WHERE id = ?";
                $user_stmt = $conn->prepare($user_sql);
                $user_stmt->bind_param("i", $user_id);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();
                $user_data = $user_result->fetch_assoc();
                $username = $user_data['username'];
                
                $balance_sql = "INSERT INTO user_balance (user_id, username, standard_balance, premium_balance) VALUES (?, ?, ?, ?)";
                $balance_stmt = $conn->prepare($balance_sql);
                $balance_stmt->bind_param("isdd", $user_id, $username, $standard_balance, $premium_balance);
            }
            
            if ($balance_stmt->execute()) {
                $conn->commit();
                $success_message = "User balance updated successfully!";
            } else {
                $conn->rollback();
                $error_message = "Error updating balance: " . $conn->error;
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Exception occurred: " . $e->getMessage();
        }
    }

    // Handle user information update
    if (isset($_POST['update_user_info'])) {
        $user_id = $_POST['user_id'];
        $firstname = $_POST['firstname'];
        $surname = $_POST['surname'];
        $email = $_POST['email'];
        $username = $_POST['username'];
        $branch = $_POST['branch'];
        $login_alerts = isset($_POST['login_alerts']) ? 1 : 0;
        $password_changes = isset($_POST['password_changes']) ? 1 : 0;
        
        try {
            $conn->begin_transaction();
            
            $update_sql = "UPDATE users SET firstname = ?, surname = ?, email = ?, username = ?, branch = ?, login_alerts = ?, password_changes = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sssssiis", $firstname, $surname, $email, $username, $branch, $login_alerts, $password_changes, $user_id);
            
            if ($update_stmt->execute()) {
                // If username changed, update the username in user_balance table as well
                $update_balance_sql = "UPDATE user_balance SET username = ? WHERE user_id = ?";
                $update_balance_stmt = $conn->prepare($update_balance_sql);
                $update_balance_stmt->bind_param("si", $username, $user_id);
                $update_balance_stmt->execute();
                
                $conn->commit();
                $success_message = "User information updated successfully!";
            } else {
                $conn->rollback();
                $error_message = "Error updating user: " . $conn->error;
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Exception occurred: " . $e->getMessage();
        }
    }
}


// Get all users with balances
$sql = "SELECT u.*, 
       COALESCE(ub.standard_balance, 0) as standard_balance, 
       COALESCE(ub.premium_balance, 0) as premium_balance,
       COALESCE(ub.conversions_used, 0) as conversions_used,
       COALESCE(ub.conversion_reset_time, '') as conversion_reset_time,
       CASE WHEN u.account_active IS NULL THEN 1 ELSE u.account_active END as account_active
       FROM users u 
       LEFT JOIN user_balance ub ON u.id = ub.user_id 
       ORDER BY u.id DESC";
$result = $conn->query($sql);
$users = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
function convertToHoursMins($decimal) {
    $hours = floor($decimal);
    $minutes = round(($decimal - $hours) * 60);
    return sprintf('%02d:%02d', $hours, $minutes);
}
?>

<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <title>Kadiliman Admin Panel</title>
    <style>
        body {
        background: radial-gradient(#1a1a1a 0%, #000000 100%);
        color: white;
        height: 100%;
        margin: 0;
        padding-top: 67px;
    }
    .navbar-custom {
        background-color: #000 !important;
        border-bottom: 1px solid #ffffff;
        position: fixed; /* Change from relative to fixed */
        top: 0; /* Position at the top of the viewport */
        width: 100%; /* Make it full width */
        z-index: 1030; /* Higher z-index to ensure it stays above other content */
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
      
      .navbar-custom .navbar-nav .nav-link.active {
        color: #ff6b00;
        font-weight: bold;
      }
      
      .navbar-custom .dropdown-menu {
        background-color: #222;
        border: none;
      }
      
      .navbar-custom .dropdown-item {
        color: #fff;
      }
      
      .navbar-custom .dropdown-item:hover {
        background-color: #333;
        color: #ff6b00;
      }
      
      .btn-sign-in {
        background-color: transparent;
        border: 1px solid #ffffff;
        color: #ffffff;
        transition: all 0.3s;
      }
      
      .btn-sign-in:hover {
        background-color: #ffffff;
        color: #000;
        transform: scale(1);
      }
      
      /* Make sure the toggler icon is visible against black background */
      .navbar-custom .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.55%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
      }
      
      .navbar-custom .navbar-toggler {
        border-color: rgba(255, 255, 255, 0.1);
      }
      
      .center-nav {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
      }

      @media (max-width: 992px) {
        .center-nav {
          position: relative;
          left: 0;
          transform: none;
        }
      }
      /* End Custom styles for the navbar */
        .admin-container {
            padding: 20px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .card {
            background-color: rgba(26, 26, 26, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: #ff6b00;
            font-weight: bold;
        }
        
        .table {
            color: #fff;
        }
        
        .table th {
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        .table td {
            border-color: rgba(255, 255, 255, 0.1);
            vertical-align: middle;
        }
        
        .badge-active {
            background-color: #28a745;
            color: white;
        }
        
        .badge-locked {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-primary {
            background-color: #ff6b00;
            border-color: #ff6b00;
        }
        
        .btn-primary:hover {
            background-color: #e06000;
            border-color: #e06000;
        }
        
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        
        .alert {
            border-radius: 5px;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #75b798;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ea868f;
        }
        
        .modal-content {
            background-color: #1a1a1a;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-control, .form-select {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }
        
        .form-control:focus, .form-select:focus {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: #ff6b00;
            color: #ffffff;
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 0, 0.25);
        }
        
        .form-label {
            color: #aaa;
        }
        
        .btn-outline-light {
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .btn-icon {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin-right: 5px;
        }
        
        .status-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .status-active {
            background-color: #28a745;
        }
        
        .status-locked {
            background-color: #dc3545;
        }
        
        .search-container {
            margin-bottom: 20px;
        }
        
        .search-input {
            width: 300px;
            max-width: 100%;
        }
        
        .pagination {
            margin-top: 20px;
            justify-content: center;
        }
        
        .page-link {
            background-color: rgba(26, 26, 26, 0.5);
            border-color: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }
        
        .page-link:hover {
            background-color: rgba(255, 107, 0, 0.2);
            border-color: rgba(255, 107, 0, 0.3);
            color: #ff6b00;
        }
        
        .page-item.active .page-link {
            background-color: #ff6b00;
            border-color: #ff6b00;
        }
        
        .form-check-input:checked {
            background-color: #ff6b00;
            border-color: #ff6b00;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="Homepage.html">
            <!-- Replace with your actual logo -->
            <img src="img/eye-removebg-preview.png" alt="Logo" height="40">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav center-nav">
                <li class="nav-item">
                <a class="nav-link active" aria-current="page" href="admin.php">Users</a>
                </li>
                <li class="nav-item">
                <a class="nav-link" href="admin_transactions.php">Transactions</a>
                </li>
                <li class="nav-item">
                <a class="nav-link" href="admin_request.php">Request</a>
                </li>
                <li class="nav-item">
                <a class="nav-link" href="admin_settings.php">Settings</a>
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
            <?php if (isset($_SESSION['admin_username'])): ?>
                <!-- Dropdown button when user is logged in -->
                <div class="ms-auto dropdown">
                  <button class="btn btn-sign-in dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php echo $_SESSION['admin_username']; ?>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                    <li><a class="dropdown-item" href="/Kadiliman/logout.php">Log Out</a></li>
                  </ul>
                </div>
            <?php else: ?>
                <!-- Regular button when user is not logged in -->
                <div class="ms-auto">
                  <a href="Registration.php" class="btn btn-sign-in">Sign In</a>
                </div>
            <?php endif; ?>
        </div>
        </nav>

    <!-- Main Content -->
    <div class="container admin-container">
        <!-- Success/Error Messages -->
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

        <!-- Header Section -->
        <div class="admin-header">
            <h2><i class="fas fa-users"></i> User Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-user-plus"></i> Add New User
            </button>
        </div>

        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group search-input">
                            <input type="text" class="form-control" id="searchInput" placeholder="Search users...">
                            <button class="btn btn-outline-light" type="button" id="searchButton">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="branchFilter">
                            <option value="">All Branches</option>
                            <option value="Makati">Makati</option>
                            <option value="Taguig">Taguig</option>
                            <option value="Quezon City">Quezon City</option>
                            <option value="Manila">Manila</option>
                            <option value="Pasig">Pasig</option>
                            <option value="Alabang">Alabang</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="statusFilter">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="locked">Locked</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-light w-100" id="resetFilters">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-table me-2"></i> Registered Users
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Branch</th>
                                <th>Standard Balance</th>
                                <th>Premium Balance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['surname']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['branch']); ?></td>
                                    <td><?php echo convertToHoursMins($user['standard_balance']); ?></td>
                                    <td><?php echo convertToHoursMins($user['premium_balance']); ?></td>
                                    <td>
                                        <?php if ($user['account_active'] == 1): ?>
                                            <span class="badge badge-active">
                                                <span class="status-dot status-active"></span> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-locked">
                                                <span class="status-dot status-locked"></span> Locked
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-primary btn-sm btn-icon view-btn" data-id="<?php echo $user['id']; ?>" data-bs-toggle="modal" data-bs-target="#viewUserModal">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-warning btn-sm btn-icon edit-btn" data-id="<?php echo $user['id']; ?>" data-bs-toggle="modal" data-bs-target="#editUserModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-info btn-sm btn-icon balance-btn" data-id="<?php echo $user['id']; ?>" data-bs-toggle="modal" data-bs-target="#balanceModal">
                                                <i class="fas fa-coins"></i>
                                            </button>
                                            <button class="btn btn-secondary btn-sm btn-icon reset-btn" data-id="<?php echo $user['id']; ?>" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <button class="btn btn-<?php echo ($user['account_active'] == 1) ? 'warning' : 'success'; ?> btn-sm btn-icon lock-btn" data-id="<?php echo $user['id']; ?>" data-status="<?php echo $user['account_active']; ?>" data-bs-toggle="modal" data-bs-target="#lockUserModal">
                                                <i class="fas fa-<?php echo ($user['account_active'] == 1) ? 'lock' : 'unlock'; ?>"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm btn-icon delete-btn" data-id="<?php echo $user['id']; ?>" data-bs-toggle="modal" data-bs-target="#deleteUserModal">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">No users found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- View User Modal -->
    <div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewUserModalLabel">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Personal Information</h6>
                            <hr>
                            <p><strong>ID:</strong> <span id="view-id"></span></p>
                            <p><strong>Username:</strong> <span id="view-username"></span></p>
                            <p><strong>Full Name:</strong> <span id="view-fullname"></span></p>
                            <p><strong>Email:</strong> <span id="view-email"></span></p>
                            <p><strong>Branch:</strong> <span id="view-branch"></span></p>
                            <p><strong>Registration Date:</strong> <span id="view-regdate"></span></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Account Status & Balance</h6>
                            <hr>
                            <p><strong>Account Status:</strong> <span id="view-status"></span></p>
                            <p><strong>Standard Balance:</strong> ₱<span id="view-standard-balance"></span></p>
                            <p><strong>Premium Balance:</strong> ₱<span id="view-premium-balance"></span></p>
                            <p><strong>Conversions Used:</strong> <span id="view-conversions"></span></p>
                            <p><strong>Conversion Reset Time:</strong> <span id="view-reset-time"></span></p>
                            <p><strong>Notification Preferences:</strong></p>
                            <ul>
                                <li>Login Alerts: <span id="view-login-alerts"></span></li>
                                <li>Password Changes: <span id="view-password-changes"></span></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" id="edit-user-id" name="user_id">
                        <div class="mb-3">
                            <label for="edit-username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="edit-username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit-email" name="email" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="edit-firstname" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="edit-firstname" name="firstname" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit-surname" class="form-label">Surname</label>
                                <input type="text" class="form-control" id="edit-surname" name="surname" required>
                            </div>
                        </div>
                        
                        <div class="mb-3'>
                            <label for="edit-branch" class="form-label">Branch</label>
                            <select class="form-select" id="edit-branch" name="branch" required>
                                <option value="Makati">Makati</option>
                                <option value="Taguig">Taguig</option>
                                <option value="Quezon City">Quezon City</option>
                                <option value="Manila">Manila</option>
                                <option value="Pasig">Pasig</option>
                                <option value="Alabang">Alabang</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <h6>Notification Preferences</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-login-alerts" name="login_alerts">
                                <label class="form-check-label" for="edit-login-alerts">
                                    Login Alerts
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-password-changes" name="password_changes">
                                <label class="form-check-label" for="edit-password-changes">
                                    Password Change Notifications
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="update_user_info">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Balance Update Modal -->
    <div class="modal fade" id="balanceModal" tabindex="-1" aria-labelledby="balanceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="balanceModalLabel">Update User Balance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" id="balance-user-id" name="user_id">
                        <div class="mb-3">
                            <label for="standard-balance" class="form-label">Standard Balance (₱)</label>
                            <input type="number" step="0.01" class="form-control" id="standard-balance" name="standard_balance" required>
                        </div>
                        <div class="mb-3">
                            <label for="premium-balance" class="form-label">Premium Balance (₱)</label>
                            <input type="number" step="0.01" class="form-control" id="premium-balance" name="premium_balance" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="update_balance">Update Balance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetPasswordModalLabel">Reset User Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" id="reset-user-id" name="user_id">
                        <p>Are you sure you want to reset the password for <strong id="reset-username"></strong>?</p>
                        <p>The password will be reset to: <strong>Kadiliman2025</strong></p>
                        <p class="text-warning"><i class="fas fa-exclamation-triangle"></i> The user will need to change this password upon next login.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning" name="reset_password">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Lock/Unlock User Modal -->
    <div class="modal fade" id="lockUserModal" tabindex="-1" aria-labelledby="lockUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="lockUserModalLabel">Lock/Unlock User Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" id="lock-user-id" name="user_id">
                        <input type="hidden" id="current-status" name="current_status">
                        <p id="lock-message"></p>
                        <div id="lock-warning" class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <span id="lock-warning-text"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning" id="lock-btn-submit" name="toggle_lock">
                            <i class="fas fa-lock" id="lock-icon"></i> <span id="lock-btn-text"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" id="delete-user-id" name="user_id">
                        <p>Are you sure you want to delete the user <strong id="delete-username"></strong>?</p>
                        <p class="text-danger"><i class="fas fa-exclamation-triangle"></i> This action cannot be undone. All user data, including balances and transaction history, will be permanently deleted.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" name="delete_user">Delete User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="add_user.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add-username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="add-username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="add-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="add-email" name="email" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="add-firstname" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="add-firstname" name="firstname" required>
                            </div>
                            <div class="col-md-6">
                                <label for="add-surname" class="form-label">Surname</label>
                                <input type="text" class="form-control" id="add-surname" name="surname" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="add-branch" class="form-label">Branch</label>
                            <select class="form-select" id="add-branch" name="branch" required>
                                <option value="Makati">Makati</option>
                                <option value="Taguig">Taguig</option>
                                <option value="Quezon City">Quezon City</option>
                                <option value="Manila">Manila</option>
                                <option value="Pasig">Pasig</option>
                                <option value="Alabang">Alabang</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="add-password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="add-password" name="password" required>
                            <small class="form-text text-muted">Default password: Kadiliman2025</small>
                        </div>
                        <div class="mb-3">
                            <h6>Initial Balance</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="add-standard-balance" class="form-label">Standard Balance (₱)</label>
                                    <input type="number" step="0.01" class="form-control" id="add-standard-balance" name="standard_balance" value="0.00">
                                </div>
                                <div class="col-md-6">
                                    <label for="add-premium-balance" class="form-label">Premium Balance (₱)</label>
                                    <input type="number" step="0.01" class="form-control" id="add-premium-balance" name="premium_balance" value="0.00">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <h6>Notification Preferences</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="add-login-alerts" name="login_alerts" checked>
                                <label class="form-check-label" for="add-login-alerts">
                                    Login Alerts
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="add-password-changes" name="password_changes" checked>
                                <label class="form-check-label" for="add-password-changes">
                                    Password Change Notifications
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="add_user">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // View User Details
            const viewButtons = document.querySelectorAll('.view-btn');
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    const row = this.closest('tr');
                    const username = row.cells[1].textContent;
                    const fullname = row.cells[2].textContent;
                    const email = row.cells[3].textContent;
                    const branch = row.cells[4].textContent;
                    const standardBalance = parseFloat(row.cells[5].textContent.replace('₱', '').replace(',', '')).toFixed(2);
                    const premiumBalance = parseFloat(row.cells[6].textContent.replace('₱', '').replace(',', '')).toFixed(2);
                    const status = row.cells[7].querySelector('.badge').textContent.trim();
                    
                    // Find other user details from the data array
                    <?php echo "const users = " . json_encode($users) . ";\n"; ?>
                    const user = users.find(u => u.id == userId);
                    
                    document.getElementById('view-id').textContent = userId;
                    document.getElementById('view-username').textContent = username;
                    document.getElementById('view-fullname').textContent = fullname;
                    document.getElementById('view-email').textContent = email;
                    document.getElementById('view-branch').textContent = branch;
                    document.getElementById('view-standard-balance').textContent = standardBalance;
                    document.getElementById('view-premium-balance').textContent = premiumBalance;
                    document.getElementById('view-status').textContent = status;
                    
                    if (user) {
                        document.getElementById('view-regdate').textContent = user.created_at || 'Not available';
                        document.getElementById('view-conversions').textContent = user.conversions_used || '0';
                        document.getElementById('view-reset-time').textContent = user.conversion_reset_time || 'Not set';
                        document.getElementById('view-login-alerts').textContent = user.login_alerts == 1 ? 'Enabled' : 'Disabled';
                        document.getElementById('view-password-changes').textContent = user.password_changes == 1 ? 'Enabled' : 'Disabled';
                    }
                });
            });
            
            // Edit User
            const editButtons = document.querySelectorAll('.edit-btn');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    const row = this.closest('tr');
                    const username = row.cells[1].textContent;
                    const fullnameCell = row.cells[2].textContent;
                    const email = row.cells[3].textContent;
                    const branch = row.cells[4].textContent;
                    
                    // Split fullname into firstname and surname
                    const nameParts = fullnameCell.trim().split(' ');
                    const firstname = nameParts[0];
                    const surname = nameParts.slice(1).join(' ');
                    
                    // Find other user details from the data array
                    <?php echo "const users = " . json_encode($users) . ";\n"; ?>
                    const user = users.find(u => u.id == userId);
                    
                    document.getElementById('edit-user-id').value = userId;
                    document.getElementById('edit-username').value = username;
                    document.getElementById('edit-firstname').value = firstname;
                    document.getElementById('edit-surname').value = surname;
                    document.getElementById('edit-email').value = email;
                    document.getElementById('edit-branch').value = branch;
                    
                    if (user) {
                        document.getElementById('edit-login-alerts').checked = user.login_alerts == 1;
                        document.getElementById('edit-password-changes').checked = user.password_changes == 1;
                    }
                });
            });
            
            // Update Balance
            const balanceButtons = document.querySelectorAll('.balance-btn');
            balanceButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    const row = this.closest('tr');
                    const standardBalance = parseFloat(row.cells[5].textContent.replace('₱', '').replace(',', '')).toFixed(2);
                    const premiumBalance = parseFloat(row.cells[6].textContent.replace('₱', '').replace(',', '')).toFixed(2);
                    
                    document.getElementById('balance-user-id').value = userId;
                    document.getElementById('standard-balance').value = standardBalance;
                    document.getElementById('premium-balance').value = premiumBalance;
                });
            });
            
            // Reset Password
            const resetButtons = document.querySelectorAll('.reset-btn');
            resetButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    const row = this.closest('tr');
                    const username = row.cells[1].textContent;
                    
                    document.getElementById('reset-user-id').value = userId;
                    document.getElementById('reset-username').textContent = username;
                });
            });
            
            // Lock/Unlock User
            const lockButtons = document.querySelectorAll('.lock-btn');
            lockButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    const status = this.getAttribute('data-status');
                    const row = this.closest('tr');
                    const username = row.cells[1].textContent;
                    
                    document.getElementById('lock-user-id').value = userId;
                    document.getElementById('current-status').value = status;
                    
                    if (status == 1) {
                        // Currently active, going to lock
                        document.getElementById('lock-message').textContent = `Are you sure you want to lock the account for ${username}?`;
                        document.getElementById('lock-warning-text').textContent = 'This will prevent the user from logging in until the account is unlocked.';
                        document.getElementById('lock-btn-text').textContent = 'Lock Account';
                        document.getElementById('lock-icon').className = 'fas fa-lock';
                        document.getElementById('lock-btn-submit').className = 'btn btn-warning';
                    } else {
                        // Currently locked, going to unlock
                        document.getElementById('lock-message').textContent = `Are you sure you want to unlock the account for ${username}?`;
                        document.getElementById('lock-warning-text').textContent = 'This will allow the user to log in again.';
                        document.getElementById('lock-btn-text').textContent = 'Unlock Account';
                        document.getElementById('lock-icon').className = 'fas fa-unlock';
                        document.getElementById('lock-btn-submit').className = 'btn btn-success';
                    }
                });
            });
            
            // Delete User
            const deleteButtons = document.querySelectorAll('.delete-btn');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    const row = this.closest('tr');
                    const username = row.cells[1].textContent;
                    
                    document.getElementById('delete-user-id').value = userId;
                    document.getElementById('delete-username').textContent = username;
                });
            });
            
            // Search and filter functionality
            document.getElementById('searchButton').addEventListener('click', filterTable);
            document.getElementById('searchInput').addEventListener('keyup', function(event) {
                if (event.key === 'Enter') {
                    filterTable();
                }
            });
            document.getElementById('branchFilter').addEventListener('change', filterTable);
            document.getElementById('statusFilter').addEventListener('change', filterTable);
            document.getElementById('resetFilters').addEventListener('click', resetFilters);
            
            function filterTable() {
                const searchInput = document.getElementById('searchInput').value.toLowerCase();
                const branchFilter = document.getElementById('branchFilter').value.toLowerCase();
                const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
                
                const rows = document.querySelectorAll('#usersTable tbody tr');
                
                rows.forEach(row => {
                    const username = row.cells[1].textContent.toLowerCase();
                    const name = row.cells[2].textContent.toLowerCase();
                    const email = row.cells[3].textContent.toLowerCase();
                    const branch = row.cells[4].textContent.toLowerCase();
                    const status = row.cells[7].textContent.toLowerCase();
                    
                    const matchesSearch = username.includes(searchInput) || 
                                         name.includes(searchInput) || 
                                         email.includes(searchInput);
                    
                    const matchesBranch = branchFilter === '' || branch.includes(branchFilter);
                    
                    const matchesStatus = statusFilter === '' || 
                                         (statusFilter === 'active' && status.includes('active')) || 
                                         (statusFilter === 'locked' && status.includes('locked'));
                    
                    if (matchesSearch && matchesBranch && matchesStatus) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
            
            function resetFilters() {
                document.getElementById('searchInput').value = '';
                document.getElementById('branchFilter').value = '';
                document.getElementById('statusFilter').value = '';
                
                const rows = document.querySelectorAll('#usersTable tbody tr');
                rows.forEach(row => {
                    row.style.display = '';
                });
            }
        });
    </script>

    <!-- Create separate add_user.php file for handling user addition -->
    <?php
    // Close connection
    $conn->close();
    ?>
</body>
</html>