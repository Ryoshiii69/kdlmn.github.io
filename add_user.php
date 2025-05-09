<?php
// add_user.php
header('Content-Type: application/json'); // Set the content type to JSON

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kadiliman";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => "Connection failed: " . $conn->connect_error
    ]));
}

// Handle adding a new user
if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $firstname = $_POST['firstname'];
    $surname = $_POST['surname'];
    $branch = $_POST['branch'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $standard_balance = $_POST['standard_balance'];
    $premium_balance = $_POST['premium_balance'];
    $login_alerts = isset($_POST['login_alerts']) ? 1 : 0;
    $password_changes = isset($_POST['password_changes']) ? 1 : 0;

    // Check if username already exists
    $check_username_sql = "SELECT COUNT(*) as count FROM users WHERE username = ?";
    $check_username_stmt = $conn->prepare($check_username_sql);
    $check_username_stmt->bind_param("s", $username);
    $check_username_stmt->execute();
    $result = $check_username_stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => "Username '$username' already exists. Please choose a different username."
        ]);
        $conn->close();
        exit;
    }
    
    // Check if email already exists
    $check_email_sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
    $check_email_stmt = $conn->prepare($check_email_sql);
    $check_email_stmt->bind_param("s", $email);
    $check_email_stmt->execute();
    $result = $check_email_stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => "Email '$email' already exists. Please use a different email address."
        ]);
        $conn->close();
        exit;
    }

    try {
        $conn->begin_transaction();

        // Insert new user into the users table
        $insert_user_sql = "INSERT INTO users (username, email, firstname, surname, branch, password, login_alerts, password_changes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_user_stmt = $conn->prepare($insert_user_sql);
        $insert_user_stmt->bind_param("ssssssii", $username, $email, $firstname, $surname, $branch, $password, $login_alerts, $password_changes);

        if ($insert_user_stmt->execute()) {
            $user_id = $conn->insert_id; // Get the ID of the newly inserted user

            // Insert initial balance for the new user
            $insert_balance_sql = "INSERT INTO user_balance (user_id, username, standard_balance, premium_balance) VALUES (?, ?, ?, ?)";
            $insert_balance_stmt = $conn->prepare($insert_balance_sql);
            $insert_balance_stmt->bind_param("isdd", $user_id, $username, $standard_balance, $premium_balance);

            if ($insert_balance_stmt->execute()) {
                $conn->commit();
                echo json_encode([
                    'success' => true,
                    'message' => "User $username added successfully!",
                    'user_id' => $user_id
                ]);
            } else {
                $conn->rollback();
                echo json_encode([
                    'success' => false,
                    'message' => "Error adding user balance: " . $conn->error
                ]);
            }
        } else {
            $conn->rollback();
            echo json_encode([
                'success' => false,
                'message' => "Error adding user: " . $conn->error
            ]);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => "Exception occurred: " . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => "Invalid request. Missing 'add_user' parameter."
    ]);
}

$conn->close();
?>