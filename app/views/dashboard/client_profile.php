<?php
requireRole('client');

// Get current user
$client = getCurrentUser();

// Get client statistics
$client_stats = getClientStatistics($client['id']);

// Disable layout to prevent duplicate sidebars
$layout = false;

// Handle profile update
if (isPost() && isset($_POST['update_profile'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        
        if (empty($name) || empty($email)) {
            setFlashMessage('error', 'Name and email are required');
        } else {
            if (updateClientProfile($client['id'], $name, $email, $phone, $address)) {
                setFlashMessage('success', 'Profile updated successfully');
                $client = getCurrentUser();
            } else {
                setFlashMessage('error', 'Failed to update profile');
            }
        }
        redirect('client_profile');
        exit();
    }
}

// Handle password change
if (isPost() && isset($_POST['change_password'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            setFlashMessage('error', 'All password fields are required');
        } elseif ($new_password !== $confirm_password) {
            setFlashMessage('error', 'New passwords do not match');
        } elseif (strlen($new_password) < 6) {
            setFlashMessage('error', 'Password must be at least 6 characters long');
        } else {
            if (changePassword($client['id'], $current_password, $new_password)) {
                setFlashMessage('success', 'Password changed successfully');
            } else {
                setFlashMessage('error', 'Current password is incorrect');
            }
        }
        redirect('client_profile');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Waste Management System</title>
    
    <!-- Bootstrap CSS -->
    <link href="<?php echo asset('vendor/bootstrap/css/bootstrap.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset('css/app.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset('css/icons.min.css'); ?>" rel="stylesheet">
    <!-- BoxIcons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            padding: 20px;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .main-content {
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,0.15);
            transform: translateX(5px);
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .nav-link i {
            font-size: 1.2rem;
        }
        
        .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            background: white;
            margin-bottom: 20px;
        }
        
        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid #dee2e6;
            border-radius: 12px 12px 0 0 !important;
            padding: 20px 25px;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .btn {
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border: none;
            box-shadow: 0 2px 10px rgba(0, 123, 255, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto 20px;
        }
        
        .sidebar h4 {
            color: white;
            font-weight: 600;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar h4 i {
            margin-right: 10px;
            color: #3498db;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h4><i class='bx bx-recycle'></i> WMS Client</h4>
        
        <div class="nav-links">
            <a href="<?php echo url('client'); ?>" class="nav-link">
                <i class='bx bx-home'></i> Dashboard
            </a>
            <a href="<?php echo url('client_profile'); ?>" class="nav-link active">
                <i class='bx bx-user'></i> My Profile
            </a>
            <a href="<?php echo url('client_payments'); ?>" class="nav-link">
                <i class='bx bx-credit-card'></i> Payments
            </a>
            <a href="<?php echo url('logout'); ?>" class="nav-link text-danger mt-auto">
                <i class='bx bx-log-out'></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Flash Messages -->
            <?php $flash = getFlashMessage(); ?>
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type'] == 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">My Profile</h2>
                <a href="<?php echo url('client'); ?>" class="btn btn-outline-secondary">
                    <i class='bx bx-arrow-back'></i> Back to Dashboard
                </a>
            </div>

            <div class="row">
                <!-- Profile Information -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="avatar">
                                <?php echo strtoupper(substr($client['name'], 0, 2)); ?>
                            </div>
                            <h4 class="mb-1"><?php echo htmlspecialchars($client['name']); ?></h4>
                            <p class="text-muted mb-3">Client Account</p>
                            <p class="text-muted mb-0">
                                <i class='bx bx-envelope'></i> <?php echo htmlspecialchars($client['email']); ?>
                            </p>
                            <?php if ($client['phone'] ?? ''): ?>
                                <p class="text-muted mb-0">
                                    <i class='bx bx-phone'></i> <?php echo htmlspecialchars($client['phone']); ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($client['address'] ?? ''): ?>
                                <p class="text-muted mb-0">
                                    <i class='bx bx-map'></i> <?php echo htmlspecialchars($client['address']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Account Statistics -->
                    <div class="stats-card">
                        <h5 class="mb-3">Account Statistics</h5>
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="stats-number"><?php echo $client_stats['total_requests']; ?></div>
                                <div class="stats-label">Total Requests</div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="stats-number"><?php echo $client_stats['completed_requests']; ?></div>
                                <div class="stats-label">Completed</div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="stats-number"><?php echo $client_stats['pending_requests']; ?></div>
                                <div class="stats-label">Pending</div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="stats-number"><?php echo formatCurrency($client_stats['total_spent']); ?></div>
                                <div class="stats-label">Total Spent</div>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <small>Member since <?php echo formatDate($client['created_at'] ?? date('Y-m-d H:i:s')); ?></small>
                        </div>
                    </div>
                </div>

                <!-- Profile Forms -->
                <div class="col-md-8">
                    <!-- Update Profile -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Update Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($client['name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($client['email']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($client['phone'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($client['address'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class='bx bx-save'></i> Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <button type="submit" name="change_password" class="btn btn-warning">
                                        <i class='bx bx-key'></i> Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?php echo asset('vendor/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Password confirmation validation
            $('#confirm_password').on('input', function() {
                var newPassword = $('#new_password').val();
                var confirmPassword = $(this).val();
                
                if (newPassword !== confirmPassword) {
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                }
            });
            
            // New password validation
            $('#new_password').on('input', function() {
                var newPassword = $(this).val();
                var confirmPassword = $('#confirm_password').val();
                
                if (newPassword.length < 6) {
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                }
                
                if (confirmPassword && newPassword !== confirmPassword) {
                    $('#confirm_password').addClass('is-invalid');
                } else if (confirmPassword) {
                    $('#confirm_password').removeClass('is-invalid').addClass('is-valid');
                }
            });
        });
    </script>
</body>
</html> 