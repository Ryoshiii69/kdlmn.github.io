<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    header("Location: admin_login.php");
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

// Get unread message count
$unread_sql = "SELECT COUNT(*) as count FROM contact_messages WHERE status = 'unread'";
$unread_result = $conn->query($unread_sql);
$unread_count = 0;
if ($unread_result && $row = $unread_result->fetch_assoc()) {
    $unread_count = $row['count'];
}

// Get latest messages for dropdown
$latest_messages_sql = "SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5";
$latest_messages_result = $conn->query($latest_messages_sql);

// Handle message status update
if (isset($_POST['mark_read'])) {
    $message_id = $_POST['message_id'];
    $update_sql = "UPDATE contact_messages SET status = 'read' WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
}

// Handle message deletion
if (isset($_POST['delete_message'])) {
    $message_id = $_POST['message_id'];
    $delete_sql = "DELETE FROM contact_messages WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
}

// Get all messages
$messages_sql = "SELECT * FROM contact_messages ORDER BY created_at DESC";
$messages_result = $conn->query($messages_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact Messages - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: radial-gradient(#1a1a1a 0%, #000000 100%);
            color: white;
            min-height: 100vh;
            padding-top: 67px;
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
        .card {
            background-color: rgba(26, 26, 26, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .card-header {
            background-color: rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: #ff6b00;
        }
        .message-item {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px;
        }
        .message-item:last-child {
            border-bottom: none;
        }
        .message-item.unread {
            background-color: rgba(255, 255, 255, 0.05);
        }
        .btn-primary {
            background-color: #ff6b00;
            border-color: #ff6b00;
        }
        .btn-primary:hover {
            background-color: #e06000;
            border-color: #e06000;
        }
        .dropdown-menu {
            max-height: 400px;
            overflow-y: auto;
        }
        .dropdown-item {
            white-space: normal;
            padding: 0.5rem 1rem;
        }
        .dropdown-item .small {
            color: #aaa;
        }
        .badge {
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
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
                        <a class="nav-link" href="admin_request.php">Request</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_messages.php">Messages</a>
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

    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-envelope me-2"></i>Contact Messages</h4>
            </div>
            <div class="card-body">
                <?php if ($messages_result && $messages_result->num_rows > 0): ?>
                    <?php while ($message = $messages_result->fetch_assoc()): ?>
                        <div class="message-item <?php echo $message['status'] == 'unread' ? 'unread' : ''; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($message['subject']); ?></h5>
                                    <p class="mb-1"><strong>From:</strong> <?php echo htmlspecialchars($message['full_name']); ?> (<?php echo htmlspecialchars($message['email']); ?>)</p>
                                    <p class="mb-1"><strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></p>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                </div>
                                <div class="d-flex gap-2">
                                    <?php if ($message['status'] == 'unread'): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                            <button type="submit" name="mark_read" class="btn btn-sm btn-primary">
                                                <i class="fas fa-check"></i> Mark as Read
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                        <button type="submit" name="delete_message" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center mb-0">No messages found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to update notification count
        function updateNotificationCount() {
            fetch('get_unread_count.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.querySelector('.badge');
                    if (badge) {
                        if (data.count > 0) {
                            badge.textContent = data.count;
                            badge.style.display = 'block';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Update count every 30 seconds
        setInterval(updateNotificationCount, 30000);

        // Initial update
        document.addEventListener('DOMContentLoaded', function() {
            updateNotificationCount();
        });

        // Function to mark message as read
        function markAsRead(messageId) {
            fetch('mark_message_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message_id=' + messageId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the message status in the UI
                    const messageItem = document.querySelector(`[data-message-id="${messageId}"]`);
                    if (messageItem) {
                        messageItem.classList.remove('unread');
                        // Remove the mark as read button
                        const markReadBtn = messageItem.querySelector('.mark-read-btn');
                        if (markReadBtn) {
                            markReadBtn.remove();
                        }
                    }
                    // Update notification count
                    updateNotificationCount();
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html> 