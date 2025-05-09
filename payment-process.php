<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    // Redirect to login page if not logged in
    header("Location: Registration.php");
    exit();
}

// Database connection
// Include your database connection file
require_once 'db_connection.php'; // Make sure this file exists and contains the $conn variable

// Get parameters from the URL
$pcType = isset($_GET['pcType']) ? $_GET['pcType'] : 'standard';
$hours = isset($_GET['hours']) ? (int)$_GET['hours'] : 1;
$amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;

// Calculate the discount for weekend special offer (10% extra time for 3+ hours)
$bonusMinutes = 0;
$isWeekend = (date('N') >= 6); // 6 = Saturday, 7 = Sunday
if ($isWeekend && $hours >= 3) {
    $bonusMinutes = (int)($hours * 60 * 0.1); // 10% extra time in minutes
}

// Generate a unique transaction ID
$transactionId = uniqid('PCTopUp_');

// Get the current username
$username = $_SESSION['username'];

// Get the user_id from the username
if (isset($conn)) {
    $userIdQuery = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($userIdQuery);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("User not found!");
    }

    $userData = $result->fetch_assoc();
    $userId = $userData['id'];

    // Store transaction data in session for later processing
    $_SESSION['pending_transaction'] = [
        'transaction_id' => $transactionId,
        'user_id' => $userId,
        'username' => $username,  // Make sure username is stored in pending transaction
        'pc_type' => $pcType,
        'hours' => $hours,
        'amount' => $amount,
        'bonus_minutes' => $bonusMinutes,
        'timestamp' => time()
    ];
} else {
    die("Database connection error. Please contact the administrator.");
}

// In a real application, you would generate a proper QR code for your payment system
// For this example, we'll create a placeholder URL with transaction details
$paymentUrl = "kadiliman://payment?id={$transactionId}&amount={$amount}";

?>

<!DOCTYPE html>
<html data-bs-theme="light" lang="en" data-bss-forced-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Payment Processing - PC Top-up</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Titillium+Web:400,600,700">
    <link rel="stylesheet" href="css/topup.css">
    <link rel="icon" href="img/EYE LOGO.png" type="image/x-icon">
    <style>
        .payment-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            background-color: rgba(26, 26, 26, 0.7);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .qr-code-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            margin: 30px auto;
            width: 260px;
            height: 260px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .payment-steps {
            margin-top: 30px;
            padding-left: 20px;
        }
        
        .payment-steps li {
            margin-bottom: 12px;
            color: #e0e0e0;
        }
        
        .transaction-details {
            background-color: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
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
        
        .timer {
            text-align: center;
            margin-top: 20px;
            font-size: 18px;
            color: #ffcc00;
        }
        
        #confirmPaymentBtn {
            display: none; /* Initially hidden, shown in demo mode */
        }
        
        /* For demo purposes only */
        .demo-controls {
            margin-top: 20px;
            text-align: center;
            padding: 15px;
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
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
        <h1 class="section-title">Payment Processing</h1>
        
        <div class="payment-container">
            <h4 class="text-center mb-4">Scan QR Code to Complete Payment</h4>
            
            <div class="qr-code-container">
                <!-- In a real application, you would generate a proper QR code -->
                <img id="qrCodeImage" src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($paymentUrl); ?>" alt="Payment QR Code">
            </div>
            
            <div class="timer">
                <p>Transaction expires in: <span id="countdown">05:00</span></p>
            </div>
            
            <div class="transaction-details">
                <h5 class="mb-4 text-center">Transaction Details</h5>
                
                <div class="transaction-detail-row">
                    <span class="transaction-detail-label">PC Type:</span>
                    <span class="transaction-detail-value"><?php echo ucfirst($pcType); ?></span>
                </div>
                
                <div class="transaction-detail-row">
                    <span class="transaction-detail-label">Time Package:</span>
                    <span class="transaction-detail-value"><?php echo $hours; ?> Hour<?php echo $hours > 1 ? 's' : ''; ?></span>
                </div>
                
                <?php if ($bonusMinutes > 0): ?>
                <div class="transaction-detail-row">
                    <span class="transaction-detail-label">Weekend Bonus:</span>
                    <span class="transaction-detail-value">+<?php echo $bonusMinutes; ?> minutes</span>
                </div>
                <?php endif; ?>
                
                <div class="transaction-detail-row">
                    <span class="transaction-detail-label">Amount:</span>
                    <span class="transaction-detail-value">₱<?php echo number_format($amount, 2); ?></span>
                </div>
                
                <div class="transaction-detail-row">
                    <span class="transaction-detail-label">Transaction ID:</span>
                    <span class="transaction-detail-value"><?php echo $transactionId; ?></span>
                </div>
            </div>
            
            <div class="payment-steps mt-4">
                <h5>How to pay:</h5>
                <ol>
                    <li>Open your mobile banking or e-wallet app</li>
                    <li>Select "Scan QR Code" option</li>
                    <li>Scan the QR code displayed above</li>
                    <li>Confirm the payment amount</li>
                    <li>Complete the payment</li>
                </ol>
            </div>
            
            <!-- For demo purposes only - in a real application this would happen automatically after payment is detected -->
            <div class="demo-controls">
                <p class="mb-3">Demo Mode: Click the button below to simulate a successful payment</p>
                <button id="confirmPaymentBtn" class="btn btn-success btn-lg w-100">Confirm Payment</button>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/js/bootstrap.bundle.min.js"></script>
    
    <!-- Payment Processing JavaScript -->
    <script>
        // Countdown timer
        let timeLeft = 300; // 5 minutes in seconds
        const countdownEl = document.getElementById('countdown');
        
        const countdownTimer = setInterval(function() {
            const minutes = Math.floor(timeLeft / 60);
            let seconds = timeLeft % 60;
            seconds = seconds < 10 ? '0' + seconds : seconds;
            
            countdownEl.textContent = minutes + ':' + seconds;
            
            if (timeLeft <= 0) {
                clearInterval(countdownTimer);
                alert('Payment session expired. Please try again.');
                window.location.href = 'topup.php'; // Redirect back to top-up page
            }
            
            timeLeft--;
        }, 1000);
        
        // For demo purposes only - in a real application payment would be verified through a proper payment system
        // Show the demo button after 3 seconds
        setTimeout(function() {
            document.getElementById('confirmPaymentBtn').style.display = 'block';
        }, 3000);
        
        // Handle the demo payment confirmation
        document.getElementById('confirmPaymentBtn').addEventListener('click', function() {
            // Simulate payment processing
            this.disabled = true;
            this.textContent = 'Processing...';
            
            setTimeout(function() {
                // Redirect to success page
                window.location.href = 'payment-success.php?transaction_id=<?php echo $transactionId; ?>';
            }, 2000);
        });
    </script>
</body>

</html>