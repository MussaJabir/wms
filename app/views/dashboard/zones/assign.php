<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    redirect('login');
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $zone_id = (int)($_POST['zone_id'] ?? 0);
        $collector_ids = $_POST['collector_ids'] ?? [];

        // Validate input
        if ($zone_id <= 0) {
            setFlashMessage('error', 'Invalid zone ID');
            redirect('admin/zones');
        }

        if (empty($collector_ids)) {
            setFlashMessage('error', 'Please select at least one collector');
            redirect('admin/zones');
        }

        // Check if zone exists
        $zone = getZoneById($zone_id);
        if (!$zone) {
            setFlashMessage('error', 'Zone not found');
            redirect('admin/zones');
        }

        // Assign collectors using database function
        $success_count = assignMultipleCollectorsToZone($zone_id, $collector_ids);
        
        if ($success_count !== false && $success_count > 0) {
            setFlashMessage('success', "Successfully assigned {$success_count} collector(s) to zone");
        } elseif ($success_count === 0) {
            setFlashMessage('error', 'No valid collectors were assigned');
        } else {
            setFlashMessage('error', 'Failed to assign collectors. Please try again.');
        }
    } else {
        setFlashMessage('error', 'Invalid request');
    }
}

redirect('admin/zones');
?> 