<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    if (isAjaxRequest()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    redirect('login');
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $user_id = (int)($_POST['user_id'] ?? 0);

        // Validate input
        if ($user_id <= 0) {
            if (isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
                exit;
            }
            setFlashMessage('error', 'Invalid user ID');
            redirect('admin/users');
        }

        // Check if user exists
        $user = getUserById($user_id);
        if (!$user) {
            if (isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            setFlashMessage('error', 'User not found');
            redirect('admin/users');
        }

        // Don't allow deleting the last admin
        global $conn;
        $stmt = $conn->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
        $stmt->execute();
        $admin_count = $stmt->get_result()->fetch_assoc()['admin_count'];

        if ($user['role'] === 'admin' && $admin_count <= 1) {
            if (isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete the last admin user']);
                exit;
            }
            setFlashMessage('error', 'Cannot delete the last admin user');
            redirect('admin/users');
        }

        // Delete user using database function
        if (deleteUser($user_id)) {
            if (isAjaxRequest()) {
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
                exit;
            }
            setFlashMessage('success', 'User deleted successfully');
        } else {
            if (isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
                exit;
            }
            setFlashMessage('error', 'Failed to delete user');
        }
    } else {
        if (isAjaxRequest()) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        setFlashMessage('error', 'Invalid request');
    }
}

// For non-AJAX requests, redirect back
redirect('admin/users');

// Helper function to check if request is AJAX
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}
?> 