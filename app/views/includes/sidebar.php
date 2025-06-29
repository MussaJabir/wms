<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

$user = getCurrentUser();
?>

<div class="navbar-nav">
    <?php if ($user['role'] === 'admin'): ?>
        <!-- Admin Navigation -->
        <div class="nav-item">
            <a class="nav-link menu-link <?php echo isCurrentPage('admin') ? 'active' : ''; ?>" href="<?php echo url('admin'); ?>">
                <i class="bx bx-dashboard"></i> <span>Dashboard</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link menu-link <?php echo isCurrentPage('users') ? 'active' : ''; ?>" href="<?php echo url('users'); ?>">
                <i class="bx bx-user"></i> <span>Users</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link menu-link <?php echo isCurrentPage('zones') ? 'active' : ''; ?>" href="<?php echo url('zones'); ?>">
                <i class="bx bx-map-alt"></i> <span>Zones</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link menu-link <?php echo isCurrentPage('reports') ? 'active' : ''; ?>" href="<?php echo url('reports'); ?>">
                <i class="bx bx-chart"></i> <span>Reports</span>
            </a>
        </div>
    <?php elseif ($user['role'] === 'collector'): ?>
        <!-- Collector Navigation -->
        <div class="nav-item">
            <a class="nav-link menu-link <?php echo isCurrentPage('collector') ? 'active' : ''; ?>" href="<?php echo url('collector'); ?>">
                <i class="bx bx-dashboard"></i> <span>Dashboard</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link menu-link <?php echo isCurrentPage('assignments') ? 'active' : ''; ?>" href="<?php echo url('assignments'); ?>">
                <i class="bx bx-task"></i> <span>Assignments</span>
            </a>
        </div>
    <?php else: ?>
        <!-- Client Navigation -->
        <div class="nav-item">
            <a class="nav-link menu-link <?php echo isCurrentPage('client') ? 'active' : ''; ?>" href="<?php echo url('client'); ?>">
                <i class="bx bx-dashboard"></i> <span>Dashboard</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link menu-link <?php echo isCurrentPage('requests') ? 'active' : ''; ?>" href="<?php echo url('requests'); ?>">
                <i class="bx bx-list-ul"></i> <span>Requests</span>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link menu-link <?php echo isCurrentPage('payments') ? 'active' : ''; ?>" href="<?php echo url('payments'); ?>">
                <i class="bx bx-credit-card"></i> <span>Payments</span>
            </a>
        </div>
    <?php endif; ?>
    
    <!-- Common Navigation -->
    <div class="nav-item">
        <a class="nav-link menu-link <?php echo isCurrentPage('profile') ? 'active' : ''; ?>" href="<?php echo url('profile'); ?>">
            <i class="bx bx-user-circle"></i> <span>Profile</span>
        </a>
    </div>
    <div class="nav-item">
        <a class="nav-link menu-link text-danger" href="<?php echo url('logout'); ?>">
            <i class="bx bx-log-out"></i> <span>Logout</span>
        </a>
    </div>
</div>

<?php
// Helper function to check current page
function isCurrentPage($page) {
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    return $current_page === $page;
}
?> 