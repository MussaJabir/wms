<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Handle login
if (isPost() && isset($_POST['login'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            setFlashMessage('error', 'Please fill in all fields');
        } else if (login($email, $password)) {
            $user = getCurrentUser();
            redirect($user['role']);
        } else {
            setFlashMessage('error', 'Invalid email or password');
        }
    }
}

// Set page data
$page_title = 'Login - Waste Management System';
$layout = false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Template CSS -->
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
        
        .system-title {
            text-align: center;
            color: white;
            margin-bottom: 2rem;
        }
        
        .system-title h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .system-icons {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .system-icons i {
            font-size: 2rem;
            color: #3498db;
            background: rgba(255, 255, 255, 0.9);
            padding: 1rem;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(15px);
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
                        <!-- System Title and Icons -->
                        <div class="system-title">
                            <h1>Waste Management System</h1>
                            <div class="system-icons">
                                <i class='bx bx-recycle'></i>
                                <i class='bx bx-trash'></i>
                                <i class='bx bx-leaf'></i>
                                <i class='bx bx-world'></i>
                            </div>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-body p-4">
                                <div class="text-center mt-2">
                                    <h5 class="text-primary">Welcome Back!</h5>
                                    <p class="text-muted">Sign in to continue to WMS.</p>
                                </div>
                                
                                <div class="p-2 mt-4">
                                    <form action="" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                placeholder="Enter your email" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Password</label>
                                            <div class="position-relative auth-pass-inputgroup">
                                                <input type="password" class="form-control pe-5" id="password" name="password" 
                                                    placeholder="Enter password" required>
                                                <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted" type="button" id="password-addon">
                                                    <i class="ri-eye-fill align-middle"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4">
                                            <button class="btn btn-success w-100" type="submit" name="login">Sign In</button>
                                        </div>
                                        
                                        <div class="mt-4 text-center">
                                            <p class="mb-0">Don't have an account? <a href="<?php echo url('register'); ?>" class="fw-semibold text-primary text-decoration-underline">Sign up</a></p>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JS -->
    <script src="<?php echo asset('js/jquery.min.js'); ?>"></script>
    <script src="<?php echo asset('js/bootstrap.bundle.min.js'); ?>"></script>
    <script src="<?php echo asset('js/app.js'); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        $(document).ready(function() {
            // Debug: Check for flash messages
            <?php debugFlashMessage(); ?>
            
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
            
            // Password visibility toggle
            $('#password-addon').on('click', function() {
                var passwordInput = $('#password');
                var icon = $(this).find('i');
                
                if (passwordInput.attr('type') === 'password') {
                    passwordInput.attr('type', 'text');
                    icon.removeClass('ri-eye-fill').addClass('ri-eye-off-fill');
                } else {
                    passwordInput.attr('type', 'password');
                    icon.removeClass('ri-eye-off-fill').addClass('ri-eye-fill');
                }
            });
            
            // Form validation with SweetAlert
            $('form').on('submit', function(e) {
                var email = $('#email').val().trim();
                var password = $('#password').val().trim();
                
                if (!email || !password) {
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
            });
        });
    </script>
</body>
</html> 