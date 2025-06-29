<?php
// DEPRECATED: Use app/views/layouts/admin.php instead. This file is no longer used.
if (!defined('BASE_PATH')) exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Waste Management System'; ?></title>
    
    <!-- Template CSS -->
    <link href="<?php echo asset('css/bootstrap.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset('css/app.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset('css/icons.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset('css/custom.css'); ?>" rel="stylesheet">
    
    <!-- BoxIcons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Page Specific CSS -->
    <?php if (isset($page_specific_css)): ?>
        <?php foreach ($page_specific_css as $css): ?>
            <link href="<?php echo asset($css); ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <?php if (isset($show_sidebar) && $show_sidebar): ?>
        <!-- Sidebar -->
        <div class="app-menu navbar-menu">
            <div class="navbar-brand-box">
                <a href="<?php echo url($user['role']); ?>" class="logo logo-dark">
                    <span class="logo-sm">
                        <i class='bx bx-recycle'></i>
                    </span>
                    <span class="logo-lg">
                        <i class='bx bx-recycle'></i>
                        <span>WMS</span>
                    </span>
                </a>
            </div>
            
            <div class="navbar-menu-wrapper">
                <?php include APP_PATH . '/views/includes/sidebar.php'; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="main-content">
        <?php if (isset($show_header) && $show_header): ?>
            <?php include APP_PATH . '/views/includes/header.php'; ?>
        <?php endif; ?>

        <div class="page-content">
            <div class="container-fluid">
                <?php include APP_PATH . '/views/includes/flash_messages.php'; ?>
                <?php echo $content ?? ''; ?>
            </div>
        </div>

        <?php if (isset($show_footer) && $show_footer): ?>
            <?php include APP_PATH . '/views/includes/footer.php'; ?>
        <?php endif; ?>
    </div>

    <!-- Core JS -->
    <script src="<?php echo asset('js/jquery.min.js'); ?>"></script>
    <script src="<?php echo asset('js/bootstrap.bundle.min.js'); ?>"></script>
    <script src="<?php echo asset('js/app.js'); ?>"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Initialize DataTables -->
    <script>
        $(document).ready(function() {
            $('.table').DataTable({
                pageLength: 10,
                responsive: true
            });
        });
    </script>
    
    <!-- Page Specific JS -->
    <?php if (isset($page_specific_js)): ?>
        <?php foreach ($page_specific_js as $js): ?>
            <script src="<?php echo asset($js); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html> 