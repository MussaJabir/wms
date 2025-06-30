<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Set JSON response header
header('Content-Type: application/json');

// Get zone ID from query parameter
$zone_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($zone_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Invalid zone ID',
        'message' => 'Zone ID must be a positive integer'
    ]);
    exit();
}

try {
    // Get zone data
    $zone = getZoneById($zone_id);

    if (!$zone) {
        http_response_code(404);
        echo json_encode([
            'error' => 'Zone not found',
            'message' => "No zone found with ID: {$zone_id}"
        ]);
        exit();
    }

    // Ensure all fields are properly formatted
    $response = [
        'id' => (int)$zone['id'],
        'name' => $zone['name'] ?? '',
        'description' => $zone['description'] ?? '',
        'price' => number_format((float)($zone['price'] ?? 0), 2, '.', ''),
        'created_at' => $zone['created_at'] ?? ''
    ];

    // Return JSON response
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => 'Failed to retrieve zone data'
    ]);
    error_log("Zone fetch error: " . $e->getMessage());
}
?> 