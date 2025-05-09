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

// Get unread message count
$unread_sql = "SELECT COUNT(*) as count FROM contact_messages WHERE status = 'unread'";
$unread_result = $conn->query($unread_sql);
$unread_count = $unread_result->fetch_assoc()['count'];

// Get latest messages for dropdown
$latest_messages_sql = "SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5";
$latest_messages_result = $conn->query($latest_messages_sql);

// Date filtering
$current_month = date('m');
$current_year = date('Y');

$filter_month = isset($_GET['month']) ? $_GET['month'] : $current_month;
$filter_year = isset($_GET['year']) ? $_GET['year'] : $current_year;
$filter_username = isset($_GET['username']) ? $_GET['username'] : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';

// Base SQL for transactions
$base_sql = "SELECT bt.*, u.firstname, u.surname 
             FROM balance_transactions bt 
             LEFT JOIN users u ON bt.user_id = u.id 
             WHERE 1=1";

$where_clauses = [];
$params = [];
$param_types = "";

// Add filters to SQL query
if (!empty($filter_month) && !empty($filter_year)) {
    $where_clauses[] = "MONTH(transaction_date) = ? AND YEAR(transaction_date) = ?";
    $params[] = $filter_month;
    $params[] = $filter_year;
    $param_types .= "ii";
}

if (!empty($filter_username)) {
    $where_clauses[] = "bt.username LIKE ?";
    $params[] = "%$filter_username%";
    $param_types .= "s";
}

if (!empty($filter_type)) {
    $where_clauses[] = "transaction_type = ?";
    $params[] = $filter_type;
    $param_types .= "s";
}

// Combine where clauses
if (!empty($where_clauses)) {
    $base_sql .= " AND " . implode(" AND ", $where_clauses);
}

// Order by most recent
$base_sql .= " ORDER BY transaction_date DESC";

// Prepare and execute query
$stmt = $conn->prepare($base_sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$transactions = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
}

// Calculate monthly income with proper promo handling
$monthly_income_sql = "SELECT 
                          SUM(CASE 
                              WHEN transaction_type = 'purchase' AND standard_change = 1 THEN 20 
                              WHEN transaction_type = 'purchase' AND standard_change = 2 THEN 40
                              WHEN transaction_type = 'purchase' AND standard_change = 3 THEN 50 -- Apply promo rate
                              WHEN transaction_type = 'purchase' AND standard_change = 5 THEN 100
                              WHEN transaction_type = 'purchase' AND standard_change = 6 AND TIME(transaction_date) BETWEEN '00:00:00' AND '06:00:00' THEN 60 -- Midnight promo
                              WHEN transaction_type = 'purchase' AND standard_change > 0 THEN standard_change * 20
                              ELSE 0 
                          END) as standard_income,
                          SUM(CASE 
                              WHEN transaction_type = 'purchase' AND premium_change = 1 THEN 30
                              WHEN transaction_type = 'purchase' AND premium_change = 2 THEN 60
                              WHEN transaction_type = 'purchase' AND premium_change = 3 THEN 90
                              WHEN transaction_type = 'purchase' AND premium_change = 5 THEN 150
                              WHEN transaction_type = 'purchase' AND premium_change = 6 AND TIME(transaction_date) BETWEEN '00:00:00' AND '06:00:00' THEN 100 -- VIP Midnight promo
                              WHEN transaction_type = 'purchase' AND premium_change > 0 THEN premium_change * 30
                              ELSE 0 
                          END) as premium_income
                      FROM balance_transactions 
                      WHERE MONTH(transaction_date) = ? AND YEAR(transaction_date) = ?";

$monthly_stmt = $conn->prepare($monthly_income_sql);
$monthly_stmt->bind_param("ii", $filter_month, $filter_year);
$monthly_stmt->execute();
$monthly_result = $monthly_stmt->get_result();
$monthly_income = $monthly_result->fetch_assoc();

// Calculate total all-time income with proper promo handling
$total_income_sql = "SELECT 
                        SUM(CASE 
                            WHEN transaction_type = 'purchase' AND standard_change = 1 THEN 20 
                            WHEN transaction_type = 'purchase' AND standard_change = 2 THEN 40
                            WHEN transaction_type = 'purchase' AND standard_change = 3 THEN 50 -- Apply promo rate
                            WHEN transaction_type = 'purchase' AND standard_change = 5 THEN 100
                            WHEN transaction_type = 'purchase' AND standard_change = 6 AND TIME(transaction_date) BETWEEN '00:00:00' AND '06:00:00' THEN 60 -- Midnight promo
                            WHEN transaction_type = 'purchase' AND standard_change > 0 THEN standard_change * 20
                            ELSE 0 
                        END) as standard_income,
                        SUM(CASE 
                            WHEN transaction_type = 'purchase' AND premium_change = 1 THEN 30
                            WHEN transaction_type = 'purchase' AND premium_change = 2 THEN 60
                            WHEN transaction_type = 'purchase' AND premium_change = 3 THEN 90
                            WHEN transaction_type = 'purchase' AND premium_change = 5 THEN 150
                            WHEN transaction_type = 'purchase' AND premium_change = 6 AND TIME(transaction_date) BETWEEN '00:00:00' AND '06:00:00' THEN 100 -- VIP Midnight promo
                            WHEN transaction_type = 'purchase' AND premium_change > 0 THEN premium_change * 30
                            ELSE 0 
                        END) as premium_income
                     FROM balance_transactions";
                     
$total_stmt = $conn->prepare($total_income_sql);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_income = $total_result->fetch_assoc();

// Calculate current month transactions count
$monthly_count_sql = "SELECT COUNT(*) as count FROM balance_transactions 
                     WHERE MONTH(transaction_date) = ? AND YEAR(transaction_date) = ?";
$monthly_count_stmt = $conn->prepare($monthly_count_sql);
$monthly_count_stmt->bind_param("ii", $filter_month, $filter_year);
$monthly_count_stmt->execute();
$monthly_count_result = $monthly_count_stmt->get_result();
$monthly_count = $monthly_count_result->fetch_assoc()['count'];

// Get top 5 users by transaction volume
$top_users_sql = "SELECT username, COUNT(*) as transaction_count, 
                  SUM(CASE WHEN standard_change > 0 THEN standard_change * 20 ELSE 0 END) as standard_spend,
                  SUM(CASE WHEN premium_change > 0 THEN premium_change * 30 ELSE 0 END) as premium_spend
                  FROM balance_transactions 
                  GROUP BY username 
                  ORDER BY transaction_count DESC 
                  LIMIT 5";
$top_users_result = $conn->query($top_users_sql);
$top_users = [];

if ($top_users_result && $top_users_result->num_rows > 0) {
    while ($row = $top_users_result->fetch_assoc()) {
        if (!empty($row['username'])) { // Skip empty usernames
            $top_users[] = $row;
        }
    }
}

// Get list of unique usernames for filter dropdown
$usernames_sql = "SELECT DISTINCT username FROM balance_transactions WHERE username != '' ORDER BY username";
$usernames_result = $conn->query($usernames_sql);
$usernames = [];

if ($usernames_result && $usernames_result->num_rows > 0) {
    while ($row = $usernames_result->fetch_assoc()) {
        $usernames[] = $row['username'];
    }
}

// Helper function to convert decimal hours to HH:MM format
function convertToHoursMins($decimal) {
    $hours = floor($decimal);
    $minutes = round(($decimal - $hours) * 60);
    return sprintf('%02d:%02d', $hours, $minutes);
}

// Helper function to format numbers with commas
function formatNumber($number) {
    return number_format($number, 2);
}

// Helper function to get the month name
function getMonthName($month_number) {
    $dateObj = DateTime::createFromFormat('!m', $month_number);
    return $dateObj->format('F');
}

// Helper function to convert database enum to readable text
function formatTransactionType($type) {
    $types = [
        'purchase' => 'Purchase',
        'conversion' => 'Conversion',
        'usage' => 'Usage',
        'refund' => 'Refund'
    ];
    
    return isset($types[$type]) ? $types[$type] : ucfirst($type);
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
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <title>Kadiliman Admin - Transactions</title>
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
        
        .stat-card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .stat-card .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0;
            color: #ff6b00;
        }
        
        .stat-label {
            color: #aaa;
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .positive-change {
            color: #28a745;
        }
        
        .negative-change {
            color: #dc3545;
        }
        
        .neutral-change {
            color: #aaa;
        }
        
        .form-select, .form-control {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }
        
        .form-select:focus, .form-control:focus {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: #ff6b00;
            color: #ffffff;
            box-shadow: 0 0 0 0.2rem rgba(255, 107, 0, 0.25);
        }
        
        .btn-primary {
            background-color: #ff6b00;
            border-color: #ff6b00;
        }
        
        .btn-primary:hover {
            background-color: #e06000;
            border-color: #e06000;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .badge-transaction {
            padding: 0.5em 0.75em;
            border-radius: 0.25rem;
        }
        
        .badge-purchase {
            background-color: #28a745;
        }
        
        .badge-usage {
            background-color: #dc3545;
        }
        
        .badge-conversion {
            background-color: #17a2b8;
        }
        
        .badge-refund {
            background-color: #6c757d;
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
        /* Form select dropdown styling */
        .form-select {
        background-color: rgba(26, 26, 26, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #ffffff;
        }

        .form-select:focus {
        background-color: rgba(26, 26, 26, 0.9);
        border-color: #ff6b00;
        color: #ffffff;
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 0, 0.25);
        }

        /* For the dropdown list itself */
        .form-select option {
        background-color: #222;
        color: #ffffff;
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
                    <a class="nav-link" href="admin.php">Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="admin_transactions.php">Transactions</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_request.php">Request</a>
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
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
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
            <h2><i class="fas fa-exchange-alt"></i> Transaction Management</h2>
            <div>
                <button class="btn btn-primary me-2" id="exportCSVBtn">
                    <i class="fas fa-file-csv"></i> Export to CSV
                </button>
                <button class="btn btn-primary" id="printReportBtn">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card">
                <div class="card-body">
                    <p class="stat-label">Monthly Standard Income</p>
                    <p class="stat-value">₱<?php echo number_format($monthly_income['standard_income'] ?? 0, 2); ?></p>
                    <small class="text-muted"><?php echo getMonthName($filter_month) . ' ' . $filter_year; ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card">
                <div class="card-body">
                    <p class="stat-label">Monthly Premium Income</p>
                    <p class="stat-value">₱<?php echo number_format($monthly_income['premium_income'] ?? 0, 2); ?></p>
                    <small class="text-muted"><?php echo getMonthName($filter_month) . ' ' . $filter_year; ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card">
                <div class="card-body">
                    <p class="stat-label">Total All-Time Standard</p>
                    <p class="stat-value">₱<?php echo number_format($total_income['standard_income'] ?? 0, 2); ?></p>
                    <small class="text-muted">All transactions</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card">
                <div class="card-body">
                    <p class="stat-label">Total All-Time Premium</p>
                    <p class="stat-value">₱<?php echo number_format($total_income['premium_income'] ?? 0, 2); ?></p>
                    <small class="text-muted">All transactions</small>
                </div>
            </div>
        </div>
    </div>

        <div class="row mb-4">
            <!-- Transaction Charts -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-line me-2"></i> Monthly Transaction Analysis
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="transactionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Users -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-users me-2"></i> Top Users by Transactions
                    </div>
                    <div class="card-body">
                    <?php if (!empty($top_users)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Transactions</th>
                                        <th>Total Spend</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo $user['transaction_count']; ?></td>
                                            <td>₱<?php echo number_format($user['standard_spend'] + $user['premium_spend'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No user transaction data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" action="">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="month" class="form-label">Month</label>
                            <select class="form-select" id="month" name="month">
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $filter_month == $i ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $i, 10)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="year" class="form-label">Year</label>
                            <select class="form-select" id="year" name="year">
                                <?php for ($i = 2022; $i <= date('Y'); $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $filter_year == $i ? 'selected' : ''; ?>>
                                        <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="username" class="form-label">Username</label>
                            <select class="form-select" id="username" name="username">
                                <option value="">All Users</option>
                                <?php foreach ($usernames as $username): ?>
                                    <option value="<?php echo htmlspecialchars($username); ?>" <?php echo $filter_username == $username ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($username); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="type" class="form-label">Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">All Types</option>
                                <option value="purchase" <?php echo $filter_type == 'purchase' ? 'selected' : ''; ?>>Purchase</option>
                                <option value="conversion" <?php echo $filter_type == 'conversion' ? 'selected' : ''; ?>>Conversion</option>
                                <option value="usage" <?php echo $filter_type == 'usage' ? 'selected' : ''; ?>>Usage</option>
                                <option value="refund" <?php echo $filter_type == 'refund' ? 'selected' : ''; ?>>Refund</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="d-grid gap-2 w-100">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <a href="admin_transactions.php" class="btn btn-outline-light">
                                    <i class="fas fa-undo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-list me-2"></i> Transaction History
                </div>
                <div>
                    <span class="badge bg-secondary"><?php echo $monthly_count; ?> transactions in <?php echo getMonthName($filter_month) . ' ' . $filter_year; ?></span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <?php if (!empty($transactions)): ?>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Standard</th>
                                    <th>Premium</th>
                                    <th>Date</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo $transaction['transaction_id']; ?></td>
                                        <td>
                                            <?php if (!empty($transaction['username'])): ?>
                                                <?php echo htmlspecialchars($transaction['username']); ?>
                                                <?php if (!empty($transaction['firstname']) || !empty($transaction['surname'])): ?>
                                                    <small class="d-block text-muted">
                                                        <?php echo htmlspecialchars($transaction['firstname'] . ' ' . $transaction['surname']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Unknown</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                                $badgeClass = '';
                                                switch($transaction['transaction_type']) {
                                                    case 'purchase':
                                                        $badgeClass = 'badge-purchase';
                                                        break;
                                                    case 'usage':
                                                        $badgeClass = 'badge-usage';
                                                        break;
                                                    case 'conversion':
                                                        $badgeClass = 'badge-conversion';
                                                        break;
                                                    case 'refund':
                                                        $badgeClass = 'badge-refund';
                                                        break;
                                                }
                                            ?>
                                            <span class="badge badge-transaction <?php echo $badgeClass; ?>">
                                                <?php echo formatTransactionType($transaction['transaction_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($transaction['standard_change'] != 0): ?>
                                                <?php
                                                    // Calculate standard price based on promotion rates
                                                    $standard_price = 0;
                                                    $is_midnight = (date('H', strtotime($transaction['transaction_date'])) >= 0 && 
                                                                    date('H', strtotime($transaction['transaction_date'])) < 6);
                                                    
                                                    if ($transaction['transaction_type'] == 'purchase') {
                                                        $hours = abs($transaction['standard_change']);
                                                        switch($hours) {
                                                            case 1:
                                                                $standard_price = 20;
                                                                break;
                                                            case 2:
                                                                $standard_price = 40;
                                                                break;
                                                            case 3:
                                                                $standard_price = 50; // Promo rate
                                                                break;
                                                            case 5:
                                                                $standard_price = 100;
                                                                break;
                                                            case 6:
                                                                $standard_price = $is_midnight ? 60 : 120; // Midnight special or regular rate
                                                                break;
                                                            default:
                                                                $standard_price = $hours * 20; // Default hourly rate
                                                        }
                                                    } else {
                                                        // For non-purchases (usage, refund, etc.), use regular rate
                                                        $standard_price = abs($transaction['standard_change']) * 20;
                                                    }
                                                ?>
                                                <span class="<?php echo $transaction['standard_change'] > 0 ? 'positive-change' : 'negative-change'; ?>">
                                                    <?php echo ($transaction['standard_change'] > 0 ? '+' : ''); ?>₱<?php echo formatNumber($standard_price); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="neutral-change">₱0.00</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($transaction['premium_change'] != 0): ?>
                                                <?php
                                                    // Calculate premium price based on promotion rates
                                                    $premium_price = 0;
                                                    $is_midnight = (date('H', strtotime($transaction['transaction_date'])) >= 0 && 
                                                                   date('H', strtotime($transaction['transaction_date'])) < 6);
                                                    
                                                    if ($transaction['transaction_type'] == 'purchase') {
                                                        $hours = abs($transaction['premium_change']);
                                                        switch($hours) {
                                                            case 1:
                                                                $premium_price = 30;
                                                                break;
                                                            case 2:
                                                                $premium_price = 60;
                                                                break;
                                                            case 3:
                                                                $premium_price = 90;
                                                                break;
                                                            case 5:
                                                                $premium_price = 150;
                                                                break;
                                                            case 6:
                                                                $premium_price = $is_midnight ? 100 : 180; // VIP Midnight special or regular rate
                                                                break;
                                                            default:
                                                                $premium_price = $hours * 30; // Default hourly rate
                                                        }
                                                    } else {
                                                        // For non-purchases (usage, refund, etc.), use regular rate
                                                        $premium_price = abs($transaction['premium_change']) * 30;
                                                    }
                                                ?>
                                                <span class="<?php echo $transaction['premium_change'] > 0 ? 'positive-change' : 'negative-change'; ?>">
                                                    <?php echo ($transaction['premium_change'] > 0 ? '+' : ''); ?>₱<?php echo formatNumber($premium_price); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="neutral-change">₱0.00</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center my-4">
                            <p>No transactions found for the selected filters.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart JS script -->
    <script>
         // Get the context of the canvas element
    const ctx = document.getElementById('transactionChart').getContext('2d');
    
    // Create data for the chart with peso values
    const monthlyData = {
        labels: ['Standard Purchase', 'Premium Purchase', 'Standard Usage', 'Premium Usage'],
        datasets: [{
            data: [
                <?php echo $monthly_income['standard_income'] ?? 0; ?>,
                <?php echo $monthly_income['premium_income'] ?? 0; ?>,
                0, // Replace with actual usage data from your database
                0  // Replace with actual usage data from your database
            ],
            backgroundColor: [
                'rgba(40, 167, 69, 0.5)',
                'rgba(255, 107, 0, 0.5)',
                'rgba(220, 53, 69, 0.5)',
                'rgba(23, 162, 184, 0.5)'
            ],
            borderColor: [
                'rgba(40, 167, 69, 1)',
                'rgba(255, 107, 0, 1)',
                'rgba(220, 53, 69, 1)',
                'rgba(23, 162, 184, 1)'
            ],
            borderWidth: 1
        }]
    };

    // Create the chart
    const transactionChart = new Chart(ctx, {
        type: 'pie',
        data: monthlyData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#fff',
                        padding: 20
                    }
                },
                title: {
                    display: true,
                    text: 'Income Distribution for <?php echo getMonthName($filter_month) . " " . $filter_year; ?>',
                    color: '#fff',
                    font: {
                        size: 16
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed !== null) {
                                label += '₱' + context.parsed.toFixed(2);
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });

        // Save chart configuration for reinitialization
window.chartConfig = {
    type: 'pie',
    data: {
        labels: ['Standard Purchase', 'Premium Purchase', 'Standard Usage', 'Premium Usage'],
        datasets: [{
            data: [
                <?php echo $monthly_income['standard_income'] ?? 0; ?>,
                <?php echo $monthly_income['premium_income'] ?? 0; ?>,
                0, 
                0
            ],
            backgroundColor: [
                'rgba(40, 167, 69, 0.5)',
                'rgba(255, 107, 0, 0.5)',
                'rgba(220, 53, 69, 0.5)',
                'rgba(23, 162, 184, 0.5)'
            ],
            borderColor: [
                'rgba(40, 167, 69, 1)',
                'rgba(255, 107, 0, 1)',
                'rgba(220, 53, 69, 1)',
                'rgba(23, 162, 184, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: '#fff',
                    padding: 20
                }
            },
            title: {
                display: true,
                text: 'Income Distribution for <?php echo getMonthName($filter_month) . " " . $filter_year; ?>',
                color: '#fff',
                font: {
                    size: 16
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed !== null) {
                            label += '₱' + context.parsed.toFixed(2);
                        }
                        return label;
                    }
                }
            }
        }
    }
};

// Function to get month name (same as PHP function but in JavaScript)
function getMonthName(monthNumber) {
    const date = new Date();
    date.setMonth(monthNumber - 1);
    return date.toLocaleString('en-US', { month: 'long' });
}

// Add print functionality
document.getElementById('printReportBtn').addEventListener('click', function() {
    // Create a print-specific title
    const printTitle = document.createElement('div');
    printTitle.classList.add('print-only');
    printTitle.innerHTML = `
        <div style="text-align: center; margin-bottom: 20px;">
            <h1>Kadiliman Transaction Report</h1>
            <h3>${getMonthName(<?php echo $filter_month; ?>)} ${<?php echo $filter_year; ?>}</h3>
            <p>Generated on: ${new Date().toLocaleDateString()} at ${new Date().toLocaleTimeString()}</p>
        </div>
    `;
    
    // Save current body content
    const originalContent = document.body.innerHTML;
    
    // Add print-specific styling
    const printStyles = document.createElement('style');
    printStyles.innerHTML = `
        @media print {
            body {
                background: white;
                color: black;
                padding: 20px;
            }
            .navbar-custom, .admin-header button, form, .no-print, .btn, .form-select {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
            .card {
                border: 1px solid #ddd;
                break-inside: avoid;
                background-color: white !important;
            }
            .card-header {
                background-color: #f8f9fa !important;
                color: #333 !important;
            }
            .stat-value {
                color: #333 !important;
            }
            .table {
                color: black !important;
                border-color: #ddd !important;
            }
            .table td, .table th {
                border-color: #ddd !important;
            }
            .positive-change {
                color: #28a745 !important;
            }
            .negative-change {
                color: #dc3545 !important;
            }
            .badge-transaction {
                border: 1px solid #333 !important;
                color: black !important;
                background-color: transparent !important;
            }
            .badge-purchase::after { content: " (Purchase)"; }
            .badge-usage::after { content: " (Usage)"; }
            .badge-conversion::after { content: " (Conversion)"; }
            .badge-refund::after { content: " (Refund)"; }
            .chart-container {
                height: auto !important;
                max-height: 300px !important;
                page-break-before: always;
            }
            .admin-container {
                padding: 0 !important;
            }
            canvas {
                max-width: 100%;
                height: auto !important;
            }
        }
    `;
    
    // Prepare print view
    document.body.appendChild(printStyles);
    document.body.prepend(printTitle);
    
    // Trigger print dialog
    window.print();
    
    // Remove print title and restore content after print dialog is closed
    setTimeout(function() {
        document.body.innerHTML = originalContent;
        
        // Reattach event listeners after restoring content
        document.getElementById('printReportBtn').addEventListener('click', arguments.callee);
        document.getElementById('exportCSVBtn').addEventListener('click', window.exportCSVHandler);
        
        // Re-initialize chart
        const ctx = document.getElementById('transactionChart').getContext('2d');
        new Chart(ctx, window.chartConfig);
    }, 100);
});

// Export to CSV functionality
document.getElementById('exportCSVBtn').addEventListener('click', function() {
    // Create CSV content from transactions
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Transaction ID,User,Type,Standard Change (₱),Premium Change (₱),Date,Description\n";
    
    <?php foreach ($transactions as $transaction): ?>
        csvContent += "<?php echo $transaction['transaction_id']; ?>,";
        csvContent += "\"<?php echo htmlspecialchars($transaction['username']); ?>\",";
        csvContent += "\"<?php echo formatTransactionType($transaction['transaction_type']); ?>\",";
        csvContent += "<?php echo $transaction['standard_change'] * 20; ?>,";
        csvContent += "<?php echo $transaction['premium_change'] * 30; ?>,";
        csvContent += "\"<?php echo date('Y-m-d H:i:s', strtotime($transaction['transaction_date'])); ?>\",";
        csvContent += "\"<?php echo str_replace('"', '""', $transaction['description']); ?>\"\n";
    <?php endforeach; ?>
    
    // Create download link and trigger download
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "transactions_<?php echo $filter_year; ?>_<?php echo $filter_month; ?>.csv");
    
    // Add to DOM, click, and remove (important for Firefox)
    document.body.appendChild(link);
    
    // Use setTimeout to ensure the link is properly added to DOM
    setTimeout(() => {
        link.click();
        document.body.removeChild(link);
    }, 100);
});

// Save the exportCSV handler for reattaching after print
window.exportCSVHandler = function() {
    document.getElementById('exportCSVBtn').click();
};


// Save the exportCSV handler for reattaching
window.exportCSVHandler = document.getElementById('exportCSVBtn').onclick;
</script>

    <!-- Additional style for print -->
    <style>
        @media print {
            body {
                background: white;
                color: black;
                padding: 20px;
            }
            .navbar-custom, .admin-header button, .btn, .form-select, .no-print {
                display: none;
            }
            .card {
                border: 1px solid #ddd;
                break-inside: avoid;
            }
            .stat-value {
                color: #333;
            }
            .table {
                color: black;
                border-color: #ddd;
            }
            .table td, .table th {
                border-color: #ddd;
            }
            .positive-change {
                color: #28a745;
            }
            .negative-change {
                color: #dc3545;
            }
            .badge-transaction {
                border: 1px solid #333;
                background-color: transparent !important;
                color: black !important;
            }
            canvas {
                max-width: 100%;
                height: auto !important;
            }
            .chart-container {
                height: auto !important;
            }
            .admin-container {
                padding: 0;
            }
        }
    </style>
    
</body>
</html>