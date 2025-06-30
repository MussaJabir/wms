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
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);

        // Validate input
        if ($zone_id <= 0) {
            setFlashMessage('error', 'Invalid zone ID');
            redirect('admin/zones');
        }

        if (empty($name)) {
            setFlashMessage('error', 'Zone name is required');
            redirect('admin/zones');
        }

        if (strlen($name) < 2) {
            setFlashMessage('error', 'Zone name must be at least 2 characters long');
            redirect('admin/zones');
        }

        if ($price < 0) {
            setFlashMessage('error', 'Price cannot be negative');
            redirect('admin/zones');
        }

        // Check if zone exists
        $zone = getZoneById($zone_id);
        if (!$zone) {
            setFlashMessage('error', 'Zone not found');
            redirect('admin/zones');
        }

        // Check if zone name already exists for other zones
        if (zoneNameExists($name, $zone_id)) {
            setFlashMessage('error', 'Zone name already exists');
            redirect('admin/zones');
        }

        // Update zone using database function
        if (updateZone($zone_id, $name, $description, $price)) {
            setFlashMessage('success', 'Zone updated successfully with price ' . formatCurrency($price));
        } else {
            setFlashMessage('error', 'Failed to update zone');
        }
    } else {
        setFlashMessage('error', 'Invalid request');
    }
}

redirect('admin/zones');
?> 