<?php
// Place this in includes/admin_token_handler.php

/**
 * Admin Token Handler
 * 
 * Handles the creation, verification, and storage of admin tokens
 * Only stores tokens in the database when they are successfully used for registration
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once('db_connect.php');

/**
 * Generate a new admin token
 * 
 * This function generates a temporary token and stores it in the session
 * The token is NOT stored in the database until it is used
 */
function generateAdminToken($created_by = 'system', $expiry_days = 1) {
    // Generate a random token
    $token = bin2hex(random_bytes(16)); // 32 character hex string
    
    // Calculate expiry time (1 day from now by default)
    $expiry_date = date('Y-m-d H:i:s', strtotime("+{$expiry_days} day"));
    
    // Store token data in session (not in database yet)
    $_SESSION['temp_admin_token'] = [
        'token' => $token,
        'created_by' => $created_by,
        'created_at' => date('Y-m-d H:i:s'),
        'expiry_date' => $expiry_date,
        'is_used' => false
    ];
    
    return [
        'success' => true,
        'token' => $token,
        'expiry_date' => $expiry_date
    ];
}

/**
 * Check if a token has already been used
 * 
 * Returns true if the token is found in the database and is marked as used
 */
function isTokenUsed($token) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT is_used FROM admin_tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Token not found in database, so it's not used
        return false;
    }
    
    $token_data = $result->fetch_assoc();
    return $token_data['is_used'] == 1;
}

/**
 * Verify an admin token
 * 
 * Checks if the token exists in the session or in the database
 * If found in database, it must not be used and not expired
 */
function verifyAdminToken($token) {
    global $conn;
    
    // First check if the token has already been used
    if (isTokenUsed($token)) {
        return [
            'success' => false,
            'message' => 'This token has already been used and cannot be reused'
        ];
    }
    
    // Check if the token exists in the session
    if (isset($_SESSION['temp_admin_token']) && $_SESSION['temp_admin_token']['token'] === $token) {
        // Check if token has expired
        $temp_token = $_SESSION['temp_admin_token'];
        if (strtotime($temp_token['expiry_date']) < time()) {
            return [
                'success' => false,
                'message' => 'Token has expired'
            ];
        }
        
        // Mark token as verified in the session
        $_SESSION['admin_token_verified'] = true;
        
        return [
            'success' => true,
            'message' => 'Token verified'
        ];
    }
    
    // If not in session, check if it's in the database (for previously issued tokens)
    $stmt = $conn->prepare("SELECT * FROM admin_tokens WHERE token = ? AND is_used = 0 AND expiry_date > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'Invalid or expired token'
        ];
    }
    
    // Token found in database, mark as verified in the session
    $_SESSION['admin_token_verified'] = true;
    
    // Store the verified token info in session for later use during commit
    $token_data = $result->fetch_assoc();
    $_SESSION['temp_admin_token'] = [
        'token' => $token_data['token'],
        'created_by' => $token_data['created_by'],
        'created_at' => $token_data['created_at'],
        'expiry_date' => $token_data['expiry_date'],
        'is_used' => false
    ];
    
    return [
        'success' => true,
        'message' => 'Token verified'
    ];
}

/**
 * Commit a verified token to the database
 * 
 * This should only be called when the admin registration is successful
 */
function commitAdminToken($used_by) {
    global $conn;

    // Check if there's a verified token in the session
    if (!isset($_SESSION['admin_token_verified']) || $_SESSION['admin_token_verified'] !== true) {
        return [
            'success' => false,
            'message' => 'No verified token found'
        ];
    }

    // Check if temp token exists in session
    if (!isset($_SESSION['temp_admin_token'])) {
        return [
            'success' => false,
            'message' => 'No temporary token found'
        ];
    }

    $temp_token = $_SESSION['temp_admin_token'];

    // Insert token into database only if it is used
    $stmt = $conn->prepare("INSERT INTO admin_tokens (token, created_by, expiry_date, used_by, used_at, is_used) VALUES (?, ?, ?, ?, NOW(), 1)");
    $stmt->bind_param("sssss", 
        $temp_token['token'], 
        $temp_token['created_by'],
        $temp_token['expiry_date'], 
        $used_by
    );

    if ($stmt->execute()) {
        // Clear the session token data
        unset($_SESSION['temp_admin_token']);
        unset($_SESSION['admin_token_verified']);

        return [
            'success' => true,
            'message' => 'Token committed successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to commit token: ' . $conn->error
        ];
    }
}
    
    // If token doesn't exist in the database, insert it as used
    $stmt = $conn->prepare("INSERT INTO admin_tokens (token, created_by, created_at, expiry_date, used_by, used_at, is_used) VALUES (?, ?, ?, ?, ?, NOW(), 1)");
    $stmt->bind_param("sssss", 
        $temp_token['token'], 
        $temp_token['created_by'],
        $temp_token['created_at'],
        $temp_token['expiry_date'], 
        $used_by
    );

    if ($stmt->execute()) {
        // Clear the session token data
        unset($_SESSION['temp_admin_token']);
        unset($_SESSION['admin_token_verified']);

        return [
            'success' => true,
            'message' => 'Token committed successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to commit token: ' . $conn->error
        ];
    }
}

/**
 * Handle admin token API requests
 */
function handleAdminTokenRequest() {
    // Check if this is an API request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    
    // Generate token
    if (isset($_POST['action']) && $_POST['action'] === 'generate-admin-token') {
        $created_by = isset($_POST['created_by']) ? $_POST['created_by'] : 'system';
        $expiry_days = isset($_POST['expiry_days']) ? (int)$_POST['expiry_days'] : 1;
        
        $result = generateAdminToken($created_by, $expiry_days);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    // Verify token
    if (isset($_POST['action']) && $_POST['action'] === 'verify-admin-token') {
        $token = isset($_POST['admin-token']) ? $_POST['admin-token'] : '';
        
        $result = verifyAdminToken($token);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}

// Auto-run handler when this file is included
handleAdminTokenRequest();
?>