<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin Panel' ?> - Waste Management System</title>
    
    <!-- jQuery (load first) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- BoxIcons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px;
            background-color: #2c3e50;
            color: white;
            width: 250px;
            z-index: 1000;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        .nav-link.active {
            background-color: #3498db;
            color: white;
        }
        .nav-link i {
            font-size: 1.2rem;
        }
        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .btn i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h4 class="mb-4 text-white d-flex align-items-center gap-2">
            <i class='bx bx-recycle'></i> WMS Admin
        </h4>
        <div class="nav flex-column">
            <a href="<?= url('admin/dashboard') ?>" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <i class='bx bx-dashboard'></i> Dashboard
            </a>
            <a href="<?= url('admin/users') ?>" class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>">
                <i class='bx bx-user'></i> Users
            </a>
            <a href="<?= url('admin/zones') ?>" class="nav-link <?= $currentPage === 'zones' ? 'active' : '' ?>">
                <i class='bx bx-map-alt'></i> Zones
            </a>
            <a href="<?= url('admin/reports') ?>" class="nav-link <?= $currentPage === 'reports' ? 'active' : '' ?>">
                <i class='bx bx-chart'></i> Reports
            </a>
            <a href="<?= url('admin/profile') ?>" class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>">
                <i class='bx bx-user-circle'></i> Profile
            </a>
            <a href="<?= url('logout') ?>" class="nav-link text-danger mt-auto">
                <i class='bx bx-log-out'></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?= $content ?? '' ?>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Initialize components -->
    <script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            theme: 'bootstrap-5'
        });
        
        // Initialize DataTables
        $('.datatable').DataTable({
            responsive: true
        });
    });
    </script>
</body>
</html> 