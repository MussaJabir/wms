<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get zone ID from URL
$path_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$zone_id = end($path_parts);

if (!is_numeric($zone_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid zone ID']);
    exit();
}

// Get zone data
$zone = getZoneById((int)$zone_id);

if (!$zone) {
    http_response_code(404);
    echo json_encode(['error' => 'Zone not found']);
    exit();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($zone);
?> 