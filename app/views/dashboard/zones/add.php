<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    redirect('login');
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');

        // Validate input
        if (empty($name)) {
            setFlashMessage('error', 'Zone name is required');
            redirect('admin/zones');
        }

        if (strlen($name) < 2) {
            setFlashMessage('error', 'Zone name must be at least 2 characters long');
            redirect('admin/zones');
        }

        // Check if zone name already exists
        global $conn;
        $stmt = $conn->prepare("SELECT id FROM zones WHERE LOWER(name) = LOWER(?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            setFlashMessage('error', 'Zone name already exists');
            redirect('admin/zones');
        }

        // Create zone using database function
        if (createZone($name, $description)) {
            setFlashMessage('success', 'Zone added successfully');
        } else {
            setFlashMessage('error', 'Failed to add zone. Please try again.');
        }
    } else {
        setFlashMessage('error', 'Invalid request');
    }
}

redirect('admin/zones');
?>
