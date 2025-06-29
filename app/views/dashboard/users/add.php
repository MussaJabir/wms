<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    redirect('login');
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log the request
    error_log("User add POST request received");
    error_log("POST data: " . print_r($_POST, true));
    
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        error_log("CSRF token verified successfully");
        
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = sanitize($_POST['role'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');

        error_log("Sanitized data - Name: $name, Email: $email, Role: $role");

        // Validate input
        if (empty($name) || empty($email) || empty($password) || empty($role)) {
            error_log("Validation failed - missing required fields");
            setFlashMessage('error', 'Please fill in all required fields');
            redirect('admin/users');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("Validation failed - invalid email format");
            setFlashMessage('error', 'Invalid email format');
            redirect('admin/users');
        }

        if (strlen($password) < 6) {
            error_log("Validation failed - password too short");
            setFlashMessage('error', 'Password must be at least 6 characters long');
            redirect('admin/users');
        }

        if (!in_array($role, ['admin', 'collector', 'client'])) {
            error_log("Validation failed - invalid role");
            setFlashMessage('error', 'Invalid role selected');
            redirect('admin/users');
        }

        error_log("All validation passed, attempting to create user");

        // Create user using database function
        $result = createUser($name, $email, $password, $role, $phone, $address);
        
        error_log("createUser result: " . ($result ? 'true' : 'false'));
        
        if ($result === true) {
            error_log("User created successfully");
            setFlashMessage('success', 'User added successfully');
        } else {
            error_log("User creation failed");
            // Check if it's a duplicate email error
            global $conn;
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                error_log("Duplicate email detected");
                setFlashMessage('error', 'Email already registered. Please use a different email address.');
            } else {
                error_log("Unknown error in user creation");
                setFlashMessage('error', 'Failed to add user. Please try again.');
            }
        }
    } else {
        error_log("CSRF token verification failed");
        setFlashMessage('error', 'Invalid request');
    }
} else {
    error_log("Non-POST request to user add page");
}

redirect('admin/users');
?> 