<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    // Redirect to login page if not logged in
    header("Location: Registration.php");
    exit();
}

// Include the database connection file
require_once 'db_connection.php';

// Check if there's a transaction ID in the URL and a pending transaction in the session
if (isset($_GET['transaction_id']) && isset($_SESSION['pending_transaction'])) {
    $transactionId = $_GET['transaction_id'];
    $pendingTransaction = $_SESSION['pending_transaction'];
    
    // Verify the transaction ID matches
    if ($transactionId === $pendingTransaction['transaction_id']) {
        
        // Get transaction details
        $userId = $pendingTransaction['user_id'];
        $username = $pendingTransaction['username']; // Get username from the session data
        $pcType = $pendingTransaction['pc_type'];
        $hours = $pendingTransaction['hours'];
        $amount = $pendingTransaction['amount'];
        $bonusMinutes = $pendingTransaction['bonus_minutes'];
        
        // Convert bonus minutes to hours (as decimal)
        $totalHours = $hours + ($bonusMinutes / 60);
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // 1. Update user balance based on PC type
            if ($pcType === 'standard') {
                // Update standard balance
                $updateBalanceQuery = "
                    UPDATE user_balance
                    SET standard_balance = standard_balance + ?,
                        last_updated = CURRENT_TIMESTAMP
                    WHERE user_id = ?
                ";
                $stmt = $conn->prepare($updateBalanceQuery);
                $stmt->bind_param("di", $totalHours, $userId);
                $stmt->execute();
                
                // Record the transaction
                $transactionType = 'purchase';
                $description = "Top-up of {$hours} hours to standard PC";
                
                $recordTransactionQuery = "
                    INSERT INTO balance_transactions 
                    (user_id, username, transaction_type, standard_change, premium_change, description)
                    VALUES (?, ?, ?, ?, 0.00, ?)
                ";
                $stmt = $conn->prepare($recordTransactionQuery);
                $stmt->bind_param("issds", $userId, $username, $transactionType, $totalHours, $description);
                $stmt->execute();
                
            } else if ($pcType === 'premium') {
                // Update premium balance
                $updateBalanceQuery = "
                    UPDATE user_balance
                    SET premium_balance = premium_balance + ?,
                        last_updated = CURRENT_TIMESTAMP
                    WHERE user_id = ?
                ";
                $stmt = $conn->prepare($updateBalanceQuery);
                $stmt->bind_param("di", $totalHours, $userId);
                $stmt->execute();
                
                // Record the transaction
                $transactionType = 'purchase';
                $description = "Top-up of {$hours} hours to premium PC";
                
                $recordTransactionQuery = "
                    INSERT INTO balance_transactions 
                    (user_id, username, transaction_type, standard_change, premium_change, description)
                    VALUES (?, ?, ?, 0.00, ?, ?)
                ";
                $stmt = $conn->prepare($recordTransactionQuery);
                $stmt->bind_param("issds", $userId, $username, $transactionType, $totalHours, $description);
                $stmt->execute();
            }
            
            // 2. Commit the transaction
            $conn->commit();
            
            // 3. Clear the pending transaction from the session
            unset($_SESSION['pending_transaction']);
            
            // Set success message
            $successMessage = "Your payment was successful! Your {$pcType} PC time has been topped up with {$hours} hour(s)";
            if ($bonusMinutes > 0) {
                $successMessage .= " plus {$bonusMinutes} bonus minutes";
            }
            $successMessage .= ".";
            
        } catch (Exception $e) {
            // If there's an error, roll back the transaction
            $conn->rollback();
            $errorMessage = "An error occurred: " . $e->getMessage();
        }
    } else {
        $errorMessage = "Invalid transaction ID.";
    }
} else {
    $errorMessage = "No transaction information found.";
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en" data-bss-forced-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Payment Successful - PC Top-up</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Titillium+Web:400,600,700">
    <link rel="stylesheet" href="css/topup.css">
    <link rel="icon" href="img/EYE LOGO.png" type="image/x-icon">
    <style>
        .success-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            background-color: rgba(26, 26, 26, 0.7);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        
        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .error-icon {
            font-size: 80px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        .transaction-details {
            background-color: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            text-align: left;
        }
        
        .transaction-detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .transaction-detail-label {
            color: #9d9d9d;
        }
        
        .transaction-detail-value {
            color: #ffffff;
            font-weight: bold;
        }
    </style>
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
            <?php if (isset($_SESSION['username'])): ?>
                <!-- Dropdown button when user is logged in -->
                <div class="ms-auto dropdown">
                  <button class="btn btn-sign-in dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php echo $_SESSION['username']; ?>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                    <li><a class="dropdown-item" href="/KADILIMAN/register/logout.php">Log Out</a></li>
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
    
    <div class="page-container">
        <h1 class="section-title">Payment Status</h1>
        
        <div class="success-container">
            <?php if (isset($successMessage)): ?>
                <div class="success-icon">✓</div>
                <h2 class="mb-4">Payment Successful!</h2>
                <p class="lead mb-4"><?php echo $successMessage; ?></p>
                
                <?php if (isset($pendingTransaction)): ?>
                <div class="transaction-details">
                    <h5 class="mb-4 text-center">Transaction Details</h5>
                    
                    <div class="transaction-detail-row">
                        <span class="transaction-detail-label">PC Type:</span>
                        <span class="transaction-detail-value"><?php echo ucfirst($pcType); ?></span>
                    </div>
                    
                    <div class="transaction-detail-row">
                        <span class="transaction-detail-label">Time Added:</span>
                        <span class="transaction-detail-value">
                            <?php echo $hours; ?> Hour<?php echo $hours > 1 ? 's' : ''; ?>
                            <?php if ($bonusMinutes > 0): ?>
                                + <?php echo $bonusMinutes; ?> minutes
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="transaction-detail-row">
                        <span class="transaction-detail-label">Amount Paid:</span>
                        <span class="transaction-detail-value">₱<?php echo number_format($amount, 2); ?></span>
                    </div>
                    
                    <div class="transaction-detail-row">
                        <span class="transaction-detail-label">Transaction ID:</span>
                        <span class="transaction-detail-value"><?php echo $transactionId; ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="error-icon">✗</div>
                <h2 class="mb-4">Payment Failed</h2>
                <p class="lead mb-4"><?php echo $errorMessage; ?></p>
            <?php endif; ?>
            
            <div class="mt-5">
                <a href="Dashboard.php" class="btn btn-outline-light me-3">Go to Dashboard</a>
                <a href="topup.php" class="btn btn-primary">Make Another Top-up</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/js/bootstrap.bundle.min.js"></script>
</body>

</html>