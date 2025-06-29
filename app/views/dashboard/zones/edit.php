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

        // Check if zone exists
        $zone = getZoneById($zone_id);
        if (!$zone) {
            setFlashMessage('error', 'Zone not found');
            redirect('admin/zones');
        }

        // Check if zone name already exists for other zones
        $stmt = $conn->prepare("SELECT id FROM zones WHERE name = ? AND id != ?");
        $stmt->bind_param("si", $name, $zone_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            setFlashMessage('error', 'Zone name already exists');
            redirect('admin/zones');
        }

        // Update zone
        $stmt = $conn->prepare("UPDATE zones SET name = ?, description = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssi", $name, $description, $zone_id);

        if ($stmt->execute()) {
            setFlashMessage('success', 'Zone updated successfully');
        } else {
            setFlashMessage('error', 'Failed to update zone');
        }
    } else {
        setFlashMessage('error', 'Invalid request');
    }
}

redirect('admin/zones');
?> 