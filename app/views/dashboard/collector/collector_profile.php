<?php
requireRole('collector');

// Get current user from session
$current_user = getCurrentUser();

// Get complete user data from database
$collector = getUserById($current_user['id']);

// Handle profile update
if (isPost() && isset($_POST['update_profile'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        if (empty($name) || empty($email)) {
            setFlashMessage('error', 'Name and email are required');
        } else {
            if (updateUser($collector['id'], $name, $email, 'collector', $phone, $address)) {
                setFlashMessage('success', 'Profile updated successfully');
                // Refresh user data
                $collector = getCurrentUser();
            } else {
                setFlashMessage('error', 'Failed to update profile');
            }
        }
        redirect('collector/profile');
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
            setFlashMessage('error', 'New password must be at least 6 characters');
        } else {
            if (changePassword($collector['id'], $current_password, $new_password)) {
                setFlashMessage('success', 'Password changed successfully');
            } else {
                setFlashMessage('error', 'Current password is incorrect');
            }
        }
        redirect('collector/profile');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collector Profile - Waste Management System</title>
    
    <!-- jQuery (load first) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- BoxIcons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
            text-decoration: none;
        }
        .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
            text-decoration: none;
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
        .profile-avatar {
            width: 100px;
            height: 100px;
            background-color: #3498db;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            margin: 0 auto 20px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h4 class="mb-4 text-white d-flex align-items-center gap-2">
            <i class='bx bx-recycle'></i> WMS Collector
        </h4>
        <div class="nav flex-column">
            <a href="<?= url('collector') ?>" class="nav-link">
                <i class='bx bx-dashboard'></i> Dashboard
            </a>
            <a href="<?= url('collector/profile') ?>" class="nav-link active">
                <i class='bx bx-user'></i> Profile
            </a>
            <a href="<?= url('logout') ?>" class="nav-link text-danger mt-auto">
                <i class='bx bx-log-out'></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Flash Messages -->
            <?php $flash = getFlashMessage(); ?>
            <script>
            $(function() {
                <?php if ($flash): ?>
                    Swal.fire({
                        title: "<?= $flash['type'] === 'success' ? 'Success!' : 'Error!' ?>",
                        text: "<?= addslashes($flash['message']) ?>",
                        icon: "<?= $flash['type'] === 'error' ? 'error' : $flash['type'] ?>",
                        confirmButtonText: 'OK',
                        confirmButtonColor: "<?= $flash['type'] === 'error' ? '#d33' : '#3085d6' ?>"
                    });
                <?php endif; ?>
            });
            </script>

            <div class="row">
                <!-- Profile Information -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class='bx bx-user-circle'></i> Profile Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <div class="profile-avatar">
                                    <i class='bx bx-user'></i>
                                </div>
                                <h4><?= htmlspecialchars($collector['name']) ?></h4>
                                <p class="text-muted">Collector</p>
                            </div>

                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?= htmlspecialchars($collector['name']) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?= htmlspecialchars($collector['email']) ?>" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?= htmlspecialchars($collector['phone'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($collector['address'] ?? '') ?></textarea>
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
                </div>

                <!-- Change Password -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class='bx bx-lock-alt'></i> Change Password
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" 
                                           name="current_password" required>
                                </div>

                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" 
                                           name="new_password" required>
                                </div>

                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" required>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" name="change_password" class="btn btn-warning">
                                        <i class='bx bx-key'></i> Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Account Info -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class='bx bx-info-circle'></i> Account Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <small class="text-muted">Member Since:</small><br>
                                <strong><?= formatDate($collector['created_at']) ?></strong>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Last Updated:</small><br>
                                <strong><?= !empty($collector['updated_at']) ? formatDate($collector['updated_at']) : 'Never updated' ?></strong>
                            </div>
                            <div>
                                <small class="text-muted">Role:</small><br>
                                <span class="badge bg-primary">Collector</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
