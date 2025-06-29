<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get user ID from URL
$path_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$user_id = end($path_parts);

if (!is_numeric($user_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

// Get user data using database function
$user = getUserById((int)$user_id);

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($user);
?> 