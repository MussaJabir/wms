<?php
require_once __DIR__ . '/../app/config/config.php';
require_once APP_PATH . '/core/functions.php';
require_once APP_PATH . '/core/database.php';
require_once APP_PATH . '/core/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the request URI and remove any query string
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove the base path from the request URI
$base_path = parse_url(SITE_URL, PHP_URL_PATH);
if ($base_path && strpos($request_uri, $base_path) === 0) {
    $request_uri = substr($request_uri, strlen($base_path));
}

// Ensure root path is handled correctly
if (empty($request_uri) || $request_uri === '/') {
    $request_uri = '/';
}

// Get the route file path
$route_file = getRoute($request_uri);

// Remove .php extension for view function
$view_name = str_replace('.php', '', $route_file);

// Check if file exists
$file_path = APP_PATH . '/views/' . $route_file;
if (!file_exists($file_path)) {
    // Load 404 page
    http_response_code(404);
    view('404');
    exit;
}

// Load the view
view($view_name);
?> 