<?php
// Site configuration
define('SITE_URL', 'http://localhost/wms');
define('BASE_PATH', dirname(dirname(__DIR__)));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Mu$$@JbR_2025!xR7wZp');
define('DB_NAME', 'waste_management');

// Session configuration
define('SESSION_NAME', 'wms_session');
define('SESSION_LIFETIME', 7200); // 2 hours

// Routes configuration
$routes = [
    // Auth routes
    '/' => 'auth/login.php',
    '/login' => 'auth/login.php',
    '/register' => 'auth/register.php',
    '/logout' => 'auth/logout.php',
    
    // Role-specific dashboards
    '/admin' => 'dashboard/admin.php',
    '/admin/dashboard' => 'dashboard/admin.php',
    '/collector' => 'dashboard/collector.php',
    '/collector/dashboard' => 'dashboard/collector.php',
    '/client' => 'dashboard/client.php',
    '/client/dashboard' => 'dashboard/client.php',
    
    // Common routes
    '/profile' => 'dashboard/profile.php',
    '/admin/profile' => 'dashboard/profile.php',
    '/profile/update' => 'dashboard/profile/update.php',
    '/profile/change-password' => 'dashboard/profile/change-password.php',
    '/profile/deactivate' => 'dashboard/profile/deactivate.php',
    '/payments' => 'dashboard/payments.php',
    '/reports' => 'dashboard/reports.php',
    
    // Additional admin routes
    '/users' => 'dashboard/users.php',
    '/admin/users' => 'dashboard/users.php',
    '/admin/users/add' => 'dashboard/users/add.php',
    '/admin/users/edit' => 'dashboard/users/edit.php',
    '/admin/users/delete' => 'dashboard/users/delete.php',
    '/admin/users/get' => 'dashboard/users/get.php',
    '/zones' => 'dashboard/zones.php',
    '/admin/zones' => 'dashboard/zones.php',
    '/admin/zones/add' => 'dashboard/zones/add.php',
    '/admin/zones/edit' => 'dashboard/zones/edit.php',
    '/admin/zones/delete' => 'dashboard/zones/delete.php',
    '/admin/zones/assign' => 'dashboard/zones/assign.php',
    '/admin/zones/get' => 'dashboard/zones/get.php',
    '/admin/zones/collectors' => 'dashboard/zones/collectors.php',
    '/admin/requests/assign' => 'dashboard/requests/assign.php',
    '/admin/payments/update' => 'dashboard/payments/update.php',
    '/admin/reports' => 'dashboard/reports.php',
    
    // Additional collector routes
    '/assignments' => 'dashboard/assignments.php',
    '/collector/profile' => 'dashboard/collector/collector_profile.php',
    
    // Additional client routes
    '/requests' => 'dashboard/requests.php',
    '/client_profile' => 'dashboard/client_profile.php',
    
    // Admin-specific routes for viewing all data
    '/admin/requests' => 'dashboard/requests.php',
    '/admin/payments' => 'dashboard/admin_payments.php'
];

// Function to get route
function getRoute($path) {
    global $routes;
    
    // Remove trailing slashes for consistency
    $path = rtrim($path, '/');
    
    // If path is empty, treat as root
    if (empty($path)) {
        $path = '/';
    }
    
    return isset($routes[$path]) ? $routes[$path] : '404.php';
}

// Function to get base URL with optional path
function baseUrl($path = '') {
    return SITE_URL . '/' . ltrim($path, '/');
}
?> 