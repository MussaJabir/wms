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
        $request_id = (int)($_POST['request_id'] ?? 0);
        $collector_id = (int)($_POST['collector_id'] ?? 0);

        // Validate input
        if ($request_id <= 0 || $collector_id <= 0) {
            if (isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => 'Invalid request or collector ID']);
                exit;
            }
            setFlashMessage('error', 'Invalid request or collector ID');
            redirect('admin/dashboard');
        }

        // Check if request exists and is pending
        global $conn;
        $stmt = $conn->prepare("SELECT id, status, client_id FROM requests WHERE id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();

        if (!$request) {
            if (isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => 'Request not found']);
                exit;
            }
            setFlashMessage('error', 'Request not found');
            redirect('admin/dashboard');
        }

        if ($request['status'] !== 'pending') {
            if (isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => 'Request is not pending']);
                exit;
            }
            setFlashMessage('error', 'Request is not pending');
            redirect('admin/dashboard');
        }

        // Check if collector exists and is a collector
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE id = ? AND role = 'collector'");
        $stmt->bind_param("i", $collector_id);
        $stmt->execute();
        $collector = $stmt->get_result()->fetch_assoc();

        if (!$collector) {
            if (isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => 'Collector not found or invalid role']);
                exit;
            }
            setFlashMessage('error', 'Collector not found or invalid role');
            redirect('admin/dashboard');
        }

        // Assign collector to request using database function
        if (assignCollector($request_id, $collector_id)) {
            if (isAjaxRequest()) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Collector ' . htmlspecialchars($collector['name']) . ' assigned successfully'
                ]);
                exit;
            }
            setFlashMessage('success', 'Collector assigned successfully');
        } else {
            if (isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => 'Failed to assign collector']);
                exit;
            }
            setFlashMessage('error', 'Failed to assign collector');
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
redirect('admin/dashboard');

// Helper function to check if request is AJAX
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}
?> 