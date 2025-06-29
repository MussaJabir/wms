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

        global $conn;
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Remove all existing assignments for this zone
            $stmt = $conn->prepare("DELETE FROM zone_collectors WHERE zone_id = ?");
            $stmt->bind_param("i", $zone_id);
            $stmt->execute();
            
            // Add new assignments
            $success_count = 0;
            foreach ($collector_ids as $collector_id) {
                $collector_id = (int)$collector_id;
                
                // Check if collector exists and is a collector
                $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'collector'");
                $stmt->bind_param("i", $collector_id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    // Assign collector to zone
                    if (assignCollectorToZone($zone_id, $collector_id)) {
                        $success_count++;
                    }
                }
            }
            
            $conn->commit();
            
            if ($success_count > 0) {
                setFlashMessage('success', "Successfully assigned {$success_count} collector(s) to zone");
            } else {
                setFlashMessage('error', 'No valid collectors were assigned');
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            setFlashMessage('error', 'Failed to assign collectors. Please try again.');
        }
    } else {
        setFlashMessage('error', 'Invalid request');
    }
}

redirect('admin/zones');
?> 