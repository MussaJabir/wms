<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Handle registration
if (isPost() && isset($_POST['register'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $address = sanitize($_POST['address'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');

        // Validate input
        if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($address) || empty($phone)) {
            setFlashMessage('error', 'Please fill in all fields');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlashMessage('error', 'Invalid email format');
        } elseif (strlen($password) < 6) {
            setFlashMessage('error', 'Password must be at least 6 characters long');
        } elseif ($password !== $confirm_password) {
            setFlashMessage('error', 'Passwords do not match');
        } elseif (emailExists($email)) {
            setFlashMessage('error', 'Email already registered');
        } else {
            // Register user
            $data = [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'phone' => $phone,
                'address' => $address
            ];
            if (registerUser($data)) {
                setFlashMessage('success', 'Registration successful! You can now login.');
                debugFlashMessage(); // Debug: Check if flash message is set
                redirect('login');
            } else {
                setFlashMessage('error', 'Registration failed. Please try again.');
            }
        }
    } else {
        setFlashMessage('error', 'Invalid request');
    }
}

$page_title = 'Register - Waste Management System';
$layout = false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="<?php echo asset('css/bootstrap.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset('css/app.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset('css/icons.min.css'); ?>" rel="stylesheet">
    
    <style>
        body {
            background: url('<?php echo asset('images/wallpaper.jpg'); ?>') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }
        
        .auth-page-wrapper {
            background: rgba(0, 0, 0, 0.4);
            min-height: 100vh;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        
        .auth-one-bg {
            display: none;
        }
        
        .bg-overlay {
            background: rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body class="auth-bg">
    <div class="auth-page-wrapper pt-5">
        <div class="auth-one-bg-position auth-one-bg" id="auth-particles">
            <div class="bg-overlay"></div>
            <div class="shape">
                <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1440 120">
                    <path d="M 0,36 C 144,53.6 432,123.2 720,124 C 1008,124.8 1296,56.8 1440,40L1440 140L0 140z"></path>
                </svg>
            </div>
        </div>
        <div class="auth-page-content">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card mt-4">
                            <div class="card-body p-4">
                                <div class="text-center mt-2">
                                    <h5 class="text-primary">Create Account</h5>
                                    <p class="text-muted">Register as a client</p>
                                </div>
                                <div class="p-2 mt-4">
                                    <form method="POST" action="">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email address</label>
                                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="Enter your phone number" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="address" class="form-label">Address</label>
                                            <textarea class="form-control" id="address" name="address" placeholder="Enter your complete address" rows="2" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Password</label>
                                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                                            <div class="form-text">Password must be at least 6 characters long</div>
                                        </div>
                                        <div class="mb-4">
                                            <label for="confirm_password" class="form-label">Confirm Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100" name="register">Register</button>
                                    </form>
                                </div>
                                <div class="mt-4 text-center">
                                    <p class="mb-0">Already have an account? <a href="<?php echo url('login'); ?>" class="fw-semibold text-primary text-decoration-underline">Login</a></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="<?php echo asset('js/jquery.min.js'); ?>"></script>
    <script src="<?php echo asset('js/bootstrap.bundle.min.js'); ?>"></script>
    <script src="<?php echo asset('js/app.js'); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        $(document).ready(function() {
            // Flash Messages with SweetAlert
            <?php 
            $flash = getFlashMessage(); 
            if ($flash): 
            ?>
                Swal.fire({
                    title: "<?= $flash['type'] === 'success' ? 'Success!' : 'Error!' ?>",
                    text: "<?= addslashes($flash['message']) ?>",
                    icon: "<?= $flash['type'] === 'error' ? 'error' : $flash['type'] ?>",
                    confirmButtonText: 'OK',
                    confirmButtonColor: "<?= $flash['type'] === 'error' ? '#d33' : '#3085d6' ?>",
                    timer: <?= $flash['type'] === 'error' ? 'null' : '3000' ?>,
                    timerProgressBar: <?= $flash['type'] === 'error' ? 'false' : 'true' ?>
                });
            <?php endif; ?>
            
            // Form validation with SweetAlert
            $('form').on('submit', function(e) {
                var name = $('#name').val().trim();
                var email = $('#email').val().trim();
                var phone = $('#phone').val().trim();
                var address = $('#address').val().trim();
                var password = $('#password').val().trim();
                var confirmPassword = $('#confirm_password').val().trim();
                
                if (!name || !email || !phone || !address || !password || !confirmPassword) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Validation Error!',
                        text: 'Please fill in all required fields.',
                        icon: 'warning',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#ffc107'
                    });
                    return false;
                }
                
                // Email validation
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Invalid Email!',
                        text: 'Please enter a valid email address.',
                        icon: 'warning',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#ffc107'
                    });
                    return false;
                }
                
                // Password length validation
                if (password.length < 6) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Password Too Short!',
                        text: 'Password must be at least 6 characters long.',
                        icon: 'warning',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#ffc107'
                    });
                    return false;
                }
                
                // Password confirmation validation
                if (password !== confirmPassword) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Passwords Do Not Match!',
                        text: 'Please make sure your passwords match.',
                        icon: 'warning',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#ffc107'
                    });
                    return false;
                }
            });
        });
    </script>
</body>
</html> 