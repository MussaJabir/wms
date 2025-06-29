<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get zone ID from URL
$path_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$zone_id = end($path_parts);

if (!is_numeric($zone_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid zone ID']);
    exit;
}

// Get collectors for this zone
$collectors = getZoneCollectors((int)$zone_id);
$collectors_data = [];

while ($collector = $collectors->fetch_assoc()) {
    $collectors_data[] = [
        'id' => $collector['id'],
        'name' => $collector['name'],
        'email' => $collector['email'],
        'phone' => $collector['phone'],
        'assigned_at' => $collector['assigned_at']
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($collectors_data);
?> 