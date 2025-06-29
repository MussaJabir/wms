<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Flash Messages
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Debug function for flash messages
function debugFlashMessage() {
    if (isset($_SESSION['flash'])) {
        error_log("Flash message exists: " . print_r($_SESSION['flash'], true));
    } else {
        error_log("No flash message in session");
    }
}

// Security Functions
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// Date and Time Functions
function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('M j, Y g:i A', strtotime($datetime));
}

// Currency Functions
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

// Status Functions
function getStatusBadge($status) {
    $badges = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'completed' => 'info',
        'assigned' => 'primary',
        'cancelled' => 'secondary',
        'failed' => 'danger'
    ];
    
    $color = $badges[$status] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . ucfirst($status) . '</span>';
}

// URL Functions
function url($path = '') {
    return baseUrl($path);
}

function redirect($path) {
    // Ensure session data is written before redirect
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    header('Location: ' . url($path));
    exit;
}

// Asset Functions
function asset($path) {
    return baseUrl('assets/' . ltrim($path, '/'));
}

// View Functions
function view($name, $data = []) {
    // Extract data to make variables available in view
    extract($data);
    
    // Start output buffering
    ob_start();
    
    // Include the view file
    $viewFile = APP_PATH . '/views/' . $name . '.php';
    if (!file_exists($viewFile)) {
        die("View file not found: " . $viewFile);
    }
    require $viewFile;
    $content = ob_get_clean();
    
    // Include layout if not explicitly disabled
    if (!isset($layout) || $layout !== false) {
        $layout = isset($layout) ? $layout : 'admin';
        $layoutFile = APP_PATH . '/views/layouts/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            die("Layout file not found: " . $layoutFile);
        }
        require $layoutFile;
    } else {
        echo $content;
    }
}
?> 