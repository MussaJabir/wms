<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authentication functions
function login($email, $password) {
    global $conn;
    
    // Check if database connection exists
    if (!$conn) {
        error_log("Database connection not available in login function");
        return false;
    }
    
    // Debug: Log the login attempt
    error_log("Login attempt for email: $email");
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) {
        error_log("Prepare statement failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }
    
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        error_log("User found: " . $user['name'] . " with role: " . $user['role']);
        
        // Debug: Check if password verification works
        if (password_verify($password, $user['password'])) {
            error_log("Password verification successful for user: " . $user['email']);
            
            // Set session data
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ];
            
            // Log activity
            logUserActivity($user['id'], 'Logged in');
            
            return true;
        } else {
            // Debug: Log password verification failure
            error_log("Password verification failed for email: $email");
            error_log("Input password: $password");
            error_log("Stored hash: " . substr($user['password'], 0, 20) . "...");
        }
    } else {
        // Debug: Log user not found
        error_log("User not found for email: $email");
    }
    
    return false;
}

function logout() {
    // Log activity before destroying session
    if (isset($_SESSION['user'])) {
        logUserActivity($_SESSION['user']['id'], 'Logged out');
    }
    
    // Destroy session
    session_destroy();
    
    // Clear session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Clear all cookies
    foreach ($_COOKIE as $name => $value) {
        setcookie($name, '', time() - 3600, '/');
    }
    
    // Redirect to login page
    redirect('login');
}

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login');
    }
}

function requireRole($roles) {
    requireLogin();
    
    $roles = (array)$roles;
    if (!in_array($_SESSION['user']['role'], $roles)) {
        setFlashMessage('error', 'You do not have permission to access this page');
        redirect($_SESSION['user']['role']);
    }
}

function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

// User activity logging
function logUserActivity($user_id, $description) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO user_activities (user_id, description) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $description);
    $stmt->execute();
}

// Password reset functions
function generateResetToken($user_id) {
    global $conn;
    
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $token, $expires);
    $stmt->execute();
    
    return $token;
}

function verifyResetToken($token) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = 0 LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['user_id'];
    }
    
    return false;
}

function markResetTokenUsed($token) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
}

// Account management
function deactivateAccount($user_id) {
    global $conn;
    
    // Since we don't have a status field, we'll just log the activity
    logUserActivity($user_id, 'Account deactivation requested');
    return true;
}

// Create necessary tables
function createAuthTables() {
    global $conn;
    
    $tables = [
        "user_activities" => "CREATE TABLE IF NOT EXISTS user_activities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            description TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )",
        
        "password_resets" => "CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )"
    ];
    
    foreach ($tables as $name => $sql) {
        if (!$conn->query($sql)) {
            die("Error creating table $name: " . $conn->error);
        }
    }
}

// Initialize tables
createAuthTables();

// Check if email already exists
function emailExists($email) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

// Register user
function registerUser($data) {
    global $conn;
    
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address, role, created_at) VALUES (?, ?, ?, ?, ?, 'client', NOW())");
    $stmt->bind_param("sssss", $data['name'], $data['email'], $hashed_password, $data['phone'], $data['address']);
    
    return $stmt->execute();
}
?> 