<?php
session_start();
require_once('includes/db_connect.php');  // Include database connection
require_once('vendor/autoload.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    // If not logged in, redirect to admin login page
    header("Location: admin_login.php");
    exit();
}

// Get admin username from session
$admin_username = $_SESSION['admin_username'];

// Add unread message count and latest messages for notification bell
$unread_sql = "SELECT COUNT(*) as count FROM contact_messages WHERE status = 'unread'";
$unread_result = $conn->query($unread_sql);
$unread_count = 0;
if ($unread_result && $row = $unread_result->fetch_assoc()) {
    $unread_count = $row['count'];
}
$latest_messages_sql = "SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5";
$latest_messages_result = $conn->query($latest_messages_sql);

// Handle form submissions for status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_reservation'])) {
        $reservation_id = $_POST['reservation_id'];
        
        // Update reservation status to confirmed
        $stmt = $conn->prepare("UPDATE reservations SET status = 'confirmed' WHERE reservation_id = ?");
        $stmt->bind_param("i", $reservation_id);
        
        if ($stmt->execute()) {
            $message = "Reservation #" . $reservation_id . " has been approved.";
            $message_type = "success";
        } else {
            $message = "Error: Could not approve reservation.";
            $message_type = "error";
        }
    } elseif (isset($_POST['refuse_reservation'])) {
        $reservation_id = $_POST['reservation_id'];
        $refusal_reason = isset($_POST['refusal_reason']) ? $_POST['refusal_reason'] : 'Reservation declined by admin';
        
        // Update reservation status to canceled
        $stmt = $conn->prepare("UPDATE reservations SET status = 'canceled', refusal_reason = ? WHERE reservation_id = ?");
        $stmt->bind_param("si", $refusal_reason, $reservation_id);
        
        if ($stmt->execute()) {
            $message = "Reservation #" . $reservation_id . " has been refused.";
            $message_type = "success";
        } else {
            $message = "Error: Could not refuse reservation.";
            $message_type = "error";
        }
    }
}

// Filter settings
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending'; // Default to pending
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Base query
$query = "SELECT r.*, u.email FROM reservations r 
          LEFT JOIN users u ON r.user_id = u.id 
          WHERE 1=1";

// Apply filters
if (!empty($status_filter)) {
    $query .= " AND r.status = '" . $conn->real_escape_string($status_filter) . "'";
}
if (!empty($date_filter)) {
    $query .= " AND r.reservation_date = '" . $conn->real_escape_string($date_filter) . "'";
}

// Add order by
$query .= " ORDER BY r.reservation_date ASC, r.start_time ASC";

// Execute query
$result = $conn->query($query);

// Check if there are any results for the current filter
$has_results = ($result && $result->num_rows > 0);

// Debug query (comment out for production)
// echo "<pre>" . $query . "</pre>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - PC Reservation Approvals</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Titillium+Web:400,600,700">
    <link rel="icon" href="/Kadiliman/img/EYE LOGO.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        color: rgb(150, 150, 150);
        margin: 0 10px;
        transition: color 0.3s, transform 0.2s;
      }
      
      .navbar-custom .navbar-nav .nav-link:hover {
        color:rgb(255, 255, 255);
        transform: translateY(0);
      }
      
      .navbar-custom .navbar-nav .nav-link.active {
        color:rgb(255, 255, 255);
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .filter-container {
            background-color: rgba(26, 26, 26, 0.7);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .reservation-table {
            background-color: rgba(26, 26, 26, 0.7);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .status-pending {
            background-color: #ffc107;
            color: #212529;
        }
        .status-confirmed {
            background-color: #28a745;
            color: white;
        }
        .status-canceled {
            background-color: #dc3545;
            color: white;
        }
        .modal-content {
            background-color: #212529;
            color: #fff;
        }
        .btn-primary {
            background-color:rgb(60, 60, 60);
            border: none;
        }
        .btn-primary:hover {
            background-color:rgb(128, 128, 128);
        }
        
        /* Custom dropdown styling */
        .form-select {
            background-color: #343a40;
            color: #fff;
            border-color: #495057;
        }
        .form-select:focus {
            border-color: #ff6b00;
            box-shadow: 0 0 0 0.25rem rgba(255, 107, 0, 0.25);
        }
        /* Style the dropdown options */
        .form-select option {
            background-color: #343a40;
            color: #fff;
        }
        /* Custom styling for status options */
        .status-option-pending {
            color: #ffc107;
        }
        .status-option-confirmed {
            color: #28a745;
        }
        .status-option-canceled {
            color: #dc3545;
        }
        .no-reservations {
            padding: 30px;
            text-align: center;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
        }
        /* Auth token styles */
        .token-value {
            font-family: 'Courier New', monospace;
            font-size: 2rem;
            letter-spacing: 3px;
            font-weight: bold;
            color: #ff6b00;
            margin-bottom: 1rem;
        }

        .token-container {
            padding: 1rem;
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 5px;
        }

        

        .btn-copy {
            transition: all 0.3s;
        }

        .btn-copy:hover {
            background-color:rgb(128, 128, 128);
            border-color:rgb(80, 80, 80);
            color: white;
        }
        
        /* Token button styling */
        .token-button {
            background-color: #343a40;
            color: white;
            border: 1px solid #495057;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: bold;
            transition: all 0.3s;
            margin-bottom: 20px;
            width: 100%;
        }
        
        .token-button:hover {
            background-color:rgb(128, 128, 128);
            border-color:rgb(80, 80, 80);
        }
        /* Token display styling */
.token-boxes {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-bottom: 15px;
}

.token-box {
  width: 50px;
  height: 70px;
  border: 2px solid #ff6b00;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: rgba(0, 0, 0, 0.3);
}

.token-box.empty {
  border-color: #495057;
}

.token-box.loading {
  animation: pulse 1.5s infinite;
}

.token-char {
  font-size: 2rem;
  font-weight: bold;
  color: #ff6b00;
  font-family: 'Courier New', monospace;
}

@keyframes pulse {
  0% { opacity: 0.6; }
  50% { opacity: 1; }
  100% { opacity: 0.6; }
}
    </style>
</head>
<body>
    <!-- Simple Admin Navbar -->
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin.php">
                <img src="img/eye-removebg-preview.png" alt="Logo" height="40">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavDropdown">
                <ul class="navbar-nav center-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_transactions.php">Transactions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_request.php">Request</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_messages.php">Messages</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_settings.php">Settings</a>
                    </li>
                </ul>
                <?php if (isset($_SESSION['admin_username'])): ?>
                    <!-- Dropdown button when user is logged in -->
                    <div class="ms-auto d-flex align-items-center">
                        <!-- Notification Bell -->
                        <div class="dropdown me-3">
                            <button class="btn btn-link text-white position-relative" type="button" id="messageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell fa-lg"></i>
                                <?php if ($unread_count > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo $unread_count; ?>
                                    </span>
                                <?php endif; ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="messageDropdown">
                                <li><h6 class="dropdown-header">Latest Messages</h6></li>
                                <?php if ($latest_messages_result && $latest_messages_result->num_rows > 0): ?>
                                    <?php while ($message = $latest_messages_result->fetch_assoc()): ?>
                                        <li>
                                            <a class="dropdown-item <?php echo $message['status'] == 'unread' ? 'fw-bold' : ''; ?>" href="admin_messages.php">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1">
                                                        <div class="small text-gray-500"><?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></div>
                                                        <div><?php echo htmlspecialchars($message['subject']); ?></div>
                                                        <div class="small text-muted">From: <?php echo htmlspecialchars($message['full_name']); ?></div>
                                                    </div>
                                                    <?php if ($message['status'] == 'unread'): ?>
                                                        <div class="ms-2">
                                                            <span class="badge bg-danger">New</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </a>
                                        </li>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <li><span class="dropdown-item-text">No messages</span></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="admin_messages.php">View All Messages</a></li>
                            </ul>
                        </div>
                        <!-- User Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-sign-in dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown">
                                <?php echo $_SESSION['admin_username']; ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/Kadiliman/register/admin_logout.php">Log Out</a></li>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Regular button when user is not logged in -->
                    <div class="ms-auto">
                        <a href="Registration.php" class="btn btn-sign-in">Sign In</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
                
    <div class="admin-container">
        <h1 class="mb-4">PC Reservation Approvals</h1>
        
        <?php if (isset($message)): ?>
            <div class="alert alert-<?php echo $message_type == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Admin Token Button -->
        <button type="button" class="token-button" data-bs-toggle="modal" data-bs-target="#tokenModal">
            <i class="fas fa-key"></i> Show Admin Authentication Token
        </button>

        <!-- Simple Filters with Status Dropdown -->
        <div class="filter-container">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4 mb-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo $date_filter; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?> class="status-option-pending">Pending</option>
                        <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?> class="status-option-confirmed">Confirmed</option>
                        <option value="canceled" <?php echo $status_filter == 'canceled' ? 'selected' : ''; ?> class="status-option-canceled">Canceled</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </div>
            </form>
        </div>

        <!-- Reservations Table -->
        <div class="reservation-table">
            <?php if ($has_results): ?>
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>PC</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['reservation_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                                <td><?php echo $row['pc_type'] == 'P' ? 'Premium' : 'Standard'; ?> #<?php echo $row['pc_number']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['reservation_date'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $row['status']; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['status'] == 'pending'): ?>
                                        <div class="d-flex gap-2">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="reservation_id" value="<?php echo $row['reservation_id']; ?>">
                                                <button type="submit" name="approve_reservation" class="btn btn-sm btn-success">Approve</button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#refuseModal" data-reservation-id="<?php echo $row['reservation_id']; ?>">Refuse</button>
                                        </div>
                                    <?php elseif ($row['status'] == 'confirmed'): ?>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#refuseModal" data-reservation-id="<?php echo $row['reservation_id']; ?>">Cancel</button>
                                    <?php elseif ($row['status'] == 'canceled' && !empty($row['refusal_reason'])): ?>
                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#reasonModal" data-reason="<?php echo htmlspecialchars($row['refusal_reason']); ?>">View Reason</button>
                                    <?php else: ?>
                                        <span class="text-muted">No actions</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-reservations">
                    <h4>No <?php echo $status_filter; ?> reservations found for <?php echo date('F d, Y', strtotime($date_filter)); ?></h4>
                    <p>Try changing your filter settings or check another date.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Refuse Reservation Modal -->
    <div class="modal fade" id="refuseModal" tabindex="-1" aria-labelledby="refuseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="refuseModalLabel">Refuse Reservation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="reservation_id" id="refuseReservationId">
                        <div class="mb-3">
                            <label for="refusalReason" class="form-label">Refusal Reason</label>
                            <textarea class="form-control" id="refusalReason" name="refusal_reason" rows="3" placeholder="Enter reason for refusing this reservation"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="refuse_reservation" class="btn btn-danger">Refuse Reservation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Reason Modal -->
    <div class="modal fade" id="reasonModal" tabindex="-1" aria-labelledby="reasonModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reasonModalLabel">Cancellation Reason</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="cancellationReason"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Token Modal -->
<div class="modal fade" id="tokenModal" tabindex="-1" aria-labelledby="tokenModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tokenModalLabel">Admin Authentication Token</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="token-container">
    <div id="token-display-container">
        <div class="token-boxes">
        <div class="token-box"><span class="token-char"></span></div>
        <div class="token-box"><span class="token-char"></span></div>
        <div class="token-box"><span class="token-char"></span></div>
        <div class="token-box"><span class="token-char"></span></div>
        <div class="token-box"><span class="token-char"></span></div>
        <div class="token-box"><span class="token-char"></span></div>
        </div>
        <p class="text-muted text-center mt-3">This token will be valid until you generate a new one.</p>
    </div>
    <h2 class="token-value" id="dynamic-token-display" style="display: none;">Click "Generate Token" to create a new token</h2>
    </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="generate-token-btn">
          <i class="fas fa-sync-alt mr-2"></i> Generate Token
        </button>
        <button class="btn btn-primary btn-copy" onclick="copyToken()">
          <i class="fas fa-copy mr-2"></i> Copy Token
        </button>
      </div>
    </div>
  </div>
</div>

</script>            
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // Document ready event
        document.addEventListener('DOMContentLoaded', function() {
            // Refuse modal handling
            var refuseModal = document.getElementById('refuseModal');
            if (refuseModal) {
                refuseModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    var reservationId = button.getAttribute('data-reservation-id');
                    document.getElementById('refuseReservationId').value = reservationId;
                    
                    // Change modal title based on status
                    var modalTitle = button.textContent.trim() === 'Cancel' ? 'Cancel Confirmed Reservation' : 'Refuse Reservation';
                    document.getElementById('refuseModalLabel').textContent = modalTitle;
                });
            }
            // Add this to your existing JavaScript, after the document ready event
    function updateTokenBoxes(token) {
    const boxes = document.querySelectorAll('.token-box');
    const chars = document.querySelectorAll('.token-char');
    
    if (token === 'loading') {
        // Show loading state
        boxes.forEach(box => {
        box.classList.remove('empty');
        box.classList.add('loading');
        });
        chars.forEach(char => {
        char.textContent = '';
        });
    } else if (token && token !== 'Click "Generate Token" to create a new token') {
        // Display token characters
        const tokenChars = token.replace(/\s/g, '').slice(0, 6).split('');
        
        boxes.forEach((box, index) => {
        box.classList.remove('empty');
        box.classList.remove('loading');
        
        if (index < tokenChars.length) {
            chars[index].textContent = tokenChars[index];
        } else {
            chars[index].textContent = '';
        }
        });
    } else {
        // Empty state
        boxes.forEach(box => {
        box.classList.add('empty');
        box.classList.remove('loading');
        });
        chars.forEach(char => {
        char.textContent = '';
        });
    }
    }

    // Update the token display whenever the hidden token value changes
    const tokenDisplay = document.getElementById('dynamic-token-display');
    if (tokenDisplay) {
    // Create a MutationObserver to watch for changes to the token text
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
        if (mutation.type === 'characterData' || mutation.type === 'childList') {
            updateTokenBoxes(tokenDisplay.textContent);
        }
        });
    });
    
    // Start observing the token display element
    observer.observe(tokenDisplay, { characterData: true, childList: true, subtree: true });
    
    // Initial setup
    updateTokenBoxes(tokenDisplay.textContent);
    }

    // Update the updateToken function to properly handle the token display
    async function updateToken() {
    if (tokenDisplay) {
        tokenDisplay.textContent = 'loading';
        updateTokenBoxes('loading');
        
        const newToken = await fetchNewToken();
        tokenDisplay.textContent = newToken;
        updateTokenBoxes(newToken);
    } else {
        // If tokenDisplay doesn't exist, just fetch the token silently
        await fetchNewToken();
    }
    }
            // View reason modal handling
            var reasonModal = document.getElementById('reasonModal');
            if (reasonModal) {
                reasonModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    var reason = button.getAttribute('data-reason');
                    document.getElementById('cancellationReason').textContent = reason;
                });
            }
        });
        // Token refresh functionality
// admin_token.js - Improved token generation and management
// Modified admin_token.js - Remove auto-refresh and timer functionality
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const tokenDisplay = document.getElementById('dynamic-token-display');
    const generateTokenBtn = document.getElementById('generate-token-btn');
    const adminTokenInput = document.getElementById('admin-token'); // Connect to the admin token input field
    
    // Variables
    let currentToken = '';
    
    // Function to fetch a new token from the server
    async function fetchNewToken() {
        try {
            // Make an actual server request to generate a token
            const response = await fetch('admin_login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=generate-admin-token&created_by=system&expiry_days=1'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Store the token in a variable accessible to other functions
                currentToken = data.token;
                
                // If we're on the signup page and the admin token field exists, auto-fill it
                if (adminTokenInput) {
                    adminTokenInput.value = currentToken;
                }
                
                return currentToken;
            } else {
                console.error('Token generation failed:', data.message);
                return 'Error: ' + data.message;
            }
        } catch (error) {
            console.error('Error fetching token:', error);
            return 'Error: ' + error.message;
        }
    }
    
    // Function to update the token display
    async function updateToken() {
        if (tokenDisplay) {
            tokenDisplay.textContent = 'Loading...';
            const newToken = await fetchNewToken();
            tokenDisplay.textContent = newToken;
        } else {
            // If tokenDisplay doesn't exist, just fetch the token silently
            await fetchNewToken();
        }
    }
    
    // Function to get the current token
    window.getAdminToken = function() {
        return currentToken;
    };
    
    // Copy token to clipboard function
    window.copyToken = function() {
        if (!currentToken) return;
        
        navigator.clipboard.writeText(currentToken)
            .then(() => {
                // Show copy success feedback
                const copyBtn = document.querySelector('.btn-copy');
                if (copyBtn) {
                    const originalText = copyBtn.innerHTML;
                    copyBtn.innerHTML = '<i class="fas fa-check mr-2"></i> Copied!';
                    
                    // Reset button text after 2 seconds
                    setTimeout(() => {
                        copyBtn.innerHTML = originalText;
                    }, 2000);
                }
            })
            .catch(err => {
                console.error('Failed to copy: ', err);
                alert('Failed to copy token. Please try again.');
            });
    };
    
    // Add event listener to the generate button
    if (generateTokenBtn) {
        generateTokenBtn.addEventListener('click', updateToken);
    }
    
    // Add auto-verification functionality if we're on the admin signup page
    if (document.getElementById('verify-token-btn') && document.getElementById('admin-token')) {
        // Auto-connect the generated token with the verification process
        document.getElementById('admin-token').addEventListener('input', function() {
            // Enable the verify button when there's text
            document.getElementById('verify-token-btn').disabled = !this.value.trim();
        });
    }
});

// Enhanced token verification
document.addEventListener('DOMContentLoaded', function() {
    const verifyTokenBtn = document.getElementById('verify-token-btn');
    
    if (verifyTokenBtn) {
        verifyTokenBtn.addEventListener('click', function() {
            const token = document.getElementById('admin-token').value.trim();
            document.getElementById('admin-token-error').textContent = '';
            
            if (!token) {
                document.getElementById('admin-token-error').textContent = 'Admin token is required';
                return;
            }
            
            // Create form data with consistent formatting
            const formData = new URLSearchParams();
            formData.append('action', 'verify-admin-token');
            formData.append('admin-token', token);
            
            // Send verification request with consistent headers
            fetch('admin_login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString()
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Hide token verification form
                    document.getElementById('token-verification-container').style.display = 'none';
                    
                    // Show admin signup fields
                    document.getElementById('admin-signup-fields').style.display = 'block';
                    
                    // Store token verification state in sessionStorage
                    sessionStorage.setItem('adminTokenVerified', 'true');
                } else {
                    document.getElementById('admin-token-error').textContent = data.message || 'Invalid token';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('admin-token-error').textContent = 'An error occurred during token verification: ' + error.message;
            });
        });
    }
    
    // Modified initial token generation with consistent formatting
    const generateInitialToken = document.getElementById('generate-initial-token');
    if (generateInitialToken) {
        generateInitialToken.addEventListener('click', function() {
            // Show loading state
            const button = this;
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Generating...';
            
            // Clear previous results
            const resultElement = document.getElementById('initial-token-result');
            if (resultElement) {
                resultElement.style.display = 'none';
            }
            
            // Create form data with consistent formatting
            const formData = new URLSearchParams();
            formData.append('action', 'generate-admin-token');
            formData.append('initial_setup', 'true');
            formData.append('created_by', 'initial_setup');
            formData.append('expiry_days', '1');
            
            // Send request to generate initial setup token
            fetch('admin_login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString()
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    if (resultElement && document.getElementById('initial-token-value')) {
                        document.getElementById('initial-token-value').textContent = data.token;
                        resultElement.style.display = 'block';
                    }
                    
                    // Copy token to admin token field if it exists
                    const adminTokenField = document.getElementById('admin-token');
                    if (adminTokenField) {
                        adminTokenField.value = data.token;
                        
                        // Switch to signup tab and show token verification
                        if (typeof switchTab === 'function') {
                            switchTab('signup');
                        }
                        
                        // Auto-verify after a short delay
                        setTimeout(() => {
                            const verifyBtn = document.getElementById('verify-token-btn');
                            if (verifyBtn) {
                                verifyBtn.click();
                            }
                        }, 500);
                    }
                } else {
                    console.error('Token generation failed:', data.message);
                    alert('Token generation failed: ' + (data.message || 'Unknown error'));
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
    }
});
    </script>
    <script src="js/admin_token.js"></script>
</body>
</html>