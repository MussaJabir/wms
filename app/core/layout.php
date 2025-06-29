<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Get the layout type (default to 'admin' if not set)
$layout = $layout ?? 'admin';

// Get the page title (default to 'Dashboard' if not set)
$pageTitle = $pageTitle ?? 'Dashboard';

// Get the current page for navigation highlighting
$currentPage = $currentPage ?? '';

// Get the content that was buffered
$pageContent = $content ?? '';

// Start output buffering for the entire layout
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Waste Management System</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?= url('assets/img/favicon.ico') ?>" type="image/x-icon">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
    
    <!-- Custom styles for this template -->
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
        }

        body {
            background-color: #f8f9fc;
        }

        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, var(--primary-color) 10%, #224abe 100%);
            color: white;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem;
            font-weight: 500;
        }

        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.2);
        }

        .sidebar .nav-link i {
            margin-right: 0.5rem;
        }

        .topbar {
            height: 4.375rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            background-color: white;
        }

        .topbar .navbar-search {
            width: 25rem;
        }

        .topbar .topbar-divider {
            width: 0;
            border-right: 1px solid #e3e6f0;
            height: calc(4.375rem - 2rem);
            margin: auto 1rem;
        }

        .topbar .nav-item .nav-link {
            height: 4.375rem;
            display: flex;
            align-items: center;
            padding: 0 0.75rem;
        }

        .topbar .nav-item .nav-link .badge-counter {
            position: absolute;
            transform: scale(0.7);
            transform-origin: top right;
            right: 0.25rem;
            margin-top: -0.25rem;
        }

        .topbar .dropdown-list {
            padding: 0;
            border: none;
            overflow: hidden;
            width: 20rem !important;
        }

        .topbar .dropdown-list .dropdown-header {
            background-color: var(--primary-color);
            border: 1px solid var(--primary-color);
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            color: white;
        }

        .topbar .dropdown-list .dropdown-item {
            white-space: normal;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            border-left: 1px solid #e3e6f0;
            border-right: 1px solid #e3e6f0;
            border-bottom: 1px solid #e3e6f0;
            line-height: 1.3rem;
        }

        .topbar .dropdown-list .dropdown-item .text-truncate {
            max-width: 13.375rem;
        }

        .topbar .dropdown-list .dropdown-item:active {
            background-color: #eaecf4;
            color: #3a3b45;
        }

        .card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border: none;
        }

        .card .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }

        .dropdown-menu .dropdown-item {
            padding: 0.5rem 1rem;
        }

        .dropdown-menu .dropdown-item:active {
            background-color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-brand p-3 text-center">
                <h4 class="mb-0">WMS</h4>
                <small>Waste Management System</small>
            </div>
            <hr class="sidebar-divider">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="<?= url('admin/dashboard') ?>">
                        <i class="bx bxs-dashboard"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>" href="<?= url('admin/users') ?>">
                        <i class="bx bxs-user-detail"></i>
                        Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'zones' ? 'active' : '' ?>" href="<?= url('admin/zones') ?>">
                        <i class="bx bxs-map"></i>
                        Zones
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'reports' ? 'active' : '' ?>" href="<?= url('admin/reports') ?>">
                        <i class="bx bxs-report"></i>
                        Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>" href="<?= url('admin/profile') ?>">
                        <i class="bx bxs-user"></i>
                        Profile
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Content -->
        <div class="content">
            <!-- Topbar -->
            <nav class="topbar navbar navbar-expand navbar-light">
                <div class="container-fluid">
                    <!-- Sidebar Toggle -->
                    <button class="btn btn-link d-md-none rounded-circle me-3">
                        <i class="bx bx-menu"></i>
                    </button>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <span class="me-2 d-none d-lg-inline text-gray-600 small">
                                    <?= htmlspecialchars($_SESSION['user']['name'] ?? 'User') ?>
                                </span>
                                <i class="bx bxs-user-circle fs-4"></i>
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-end shadow">
                                <a class="dropdown-item" href="<?= url('admin/profile') ?>">
                                    <i class="bx bxs-user me-2"></i>
                                    Profile
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="<?= url('logout') ?>">
                                    <i class="bx bxs-log-out me-2"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="p-4">
                <?= $pageContent ?>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="<?= url('assets/js/scripts.js') ?>"></script>
</body>
</html>
<?php
// Output the entire layout
echo ob_get_clean(); 