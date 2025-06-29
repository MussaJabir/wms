<?php

// Require client role
requireRole('client');

// Get current user
$client = getCurrentUser();

// Get payment history
$payments = getClientPayments($client['id']);

// Disable layout to prevent duplicate sidebars
$layout = false;

// Handle payment submission
if (isPost() && isset($_POST['submit_payment'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $request_id = $_POST['request_id'] ?? 0;
        $amount = $_POST['amount'] ?? 0;
        $method = $_POST['method'] ?? '';
        
        if (createPayment($client['id'], $request_id, $amount, $method)) {
            setFlashMessage('success', 'Payment submitted successfully');
        } else {
            setFlashMessage('error', 'Failed to submit payment');
        }
        redirect('payments');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Waste Management System</title>
    
    <!-- Bootstrap CSS -->
    <link href="<?php echo asset('vendor/bootstrap/css/bootstrap.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset('css/app.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset('css/icons.min.css'); ?>" rel="stylesheet">
    <!-- BoxIcons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px;
            background-color: #2c3e50;
            color: white;
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
        }
        .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        .nav-link.active {
            background-color: #3498db;
            color: white;
        }
        .payment-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" style="width: 250px;">
        <h4 class="mb-4 text-white">
            <i class='bx bx-recycle'></i> WMS Client
        </h4>
        <div class="nav flex-column">
            <a href="<?php echo url('client'); ?>" class="nav-link">
                <i class='bx bx-dashboard'></i> Dashboard
            </a>
            <a href="<?php echo url('payments'); ?>" class="nav-link active">
                <i class='bx bx-credit-card'></i> Payments
            </a>
            <a href="<?php echo url('client_profile'); ?>" class="nav-link">
                <i class='bx bx-user'></i> Profile
            </a>
            <a href="<?php echo url('logout'); ?>" class="nav-link text-danger mt-auto">
                <i class='bx bx-log-out'></i> Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Flash messages will be handled by SweetAlert -->
            
            <!-- Payment History -->
            <div class="card payment-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment History</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped" id="paymentsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Request</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Transaction ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($payment = $payments->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $payment['id']; ?></td>
                                    <td>
                                        Collection at <?php echo htmlspecialchars($payment['location']); ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo formatDate($payment['pickup_date']); ?>
                                        </small>
                                    </td>
                                    <td><?php echo formatCurrency($payment['amount']); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo ucfirst($payment['method']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($payment['payment_date']); ?></td>
                                    <td><?php echo getStatusBadge($payment['status']); ?></td>
                                    <td>
                                        <?php if ($payment['transaction_id']): ?>
                                            <code><?php echo htmlspecialchars($payment['transaction_id']); ?></code>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?php echo asset('vendor/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Flash messages with SweetAlert -->
    <?php if ($flash = getFlashMessage()): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: '<?= $flash['type'] === 'error' ? 'Error' : ($flash['type'] === 'success' ? 'Success' : 'Info') ?>',
                    text: '<?= addslashes($flash['message']) ?>',
                    icon: '<?= $flash['type'] ?>',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#3085d6',
                    timer: <?= $flash['type'] === 'success' ? '3000' : '5000' ?>,
                    timerProgressBar: true
                });
            });
        </script>
    <?php endif; ?>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#paymentsTable').DataTable({
                order: [[4, 'desc']], // Sort by date
                pageLength: 10
            });
        });
    </script>
</body>
</html> 