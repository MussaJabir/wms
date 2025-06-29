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
        $zone_id = (int)($_POST['zone_id'] ?? 0);

        // Validate input
        if ($zone_id <= 0) {
            if (isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => 'Invalid zone ID']);
                exit;
            }
            setFlashMessage('error', 'Invalid zone ID');
            redirect('admin/zones');
        }

        // Check if zone exists
        $zone = getZoneById($zone_id);
        if (!$zone) {
            if (isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => 'Zone not found']);
                exit;
            }
            setFlashMessage('error', 'Zone not found');
            redirect('admin/zones');
        }

        // Check if zone has pending requests
        $pending_requests = getZonePendingRequests($zone_id);
        if ($pending_requests > 0) {
            if (isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete zone with pending requests']);
                exit;
            }
            setFlashMessage('error', 'Cannot delete zone with pending requests');
            redirect('admin/zones');
        }

        // Delete zone
        if (deleteZone($zone_id)) {
            if (isAjaxRequest()) {
                echo json_encode(['success' => true, 'message' => 'Zone deleted successfully']);
                exit;
            }
            setFlashMessage('success', 'Zone deleted successfully');
        } else {
            if (isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => 'Failed to delete zone']);
                exit;
            }
            setFlashMessage('error', 'Failed to delete zone');
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
redirect('admin/zones');

// Helper function to check if request is AJAX
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}
?>
