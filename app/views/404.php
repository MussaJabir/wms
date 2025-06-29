<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Set page data
$page_title = '404 Not Found - Waste Management System';
$show_sidebar = false;
$show_header = false;
$show_footer = false;

// Start output buffering
ob_start();
?>

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-6 text-center">
            <h1 class="display-1 fw-bold text-primary">404</h1>
            <h2 class="mb-4">Page Not Found</h2>
            <p class="text-muted mb-4">The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
            <a href="<?php echo url('/'); ?>" class="btn btn-primary">Back to Home</a>
        </div>
    </div>
</div>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout
$layout = 'admin';
require APP_PATH . '/views/layouts/' . $layout . '.php';
?> 