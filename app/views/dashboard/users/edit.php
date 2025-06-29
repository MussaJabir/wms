<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    redirect('login');
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = sanitize($_POST['role'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');

        // Validate input
        if (empty($name) || empty($email) || empty($role)) {
            setFlashMessage('error', 'Please fill in all required fields');
            redirect('admin/users');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlashMessage('error', 'Invalid email format');
            redirect('admin/users');
        }

        if (!in_array($role, ['admin', 'collector', 'client'])) {
            setFlashMessage('error', 'Invalid role selected');
            redirect('admin/users');
        }

        // Validate password if provided
        if (!empty($password) && strlen($password) < 6) {
            setFlashMessage('error', 'Password must be at least 6 characters long');
            redirect('admin/users');
        }

        // Update user using database function
        if (updateUser($user_id, $name, $email, $role, $phone, $address, $password)) {
            setFlashMessage('success', 'User updated successfully');
        } else {
            setFlashMessage('error', 'Failed to update user. Email might already be registered to another user.');
        }
    } else {
        setFlashMessage('error', 'Invalid request');
    }
}

redirect('admin/users'); 