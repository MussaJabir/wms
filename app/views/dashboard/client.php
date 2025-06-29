<?php
requireRole('client');

// Get current user
$client = getCurrentUser();

// Get client's requests
$requests = getClientRequests($client['id']);

// Disable layout to prevent duplicate sidebars
$layout = false;

// Handle new request submission
if (isPost() && isset($_POST['submit_request'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $location = sanitize($_POST['location'] ?? '');
        $pickup_date = $_POST['pickup_date'] ?? '';
        $notes = sanitize($_POST['notes'] ?? '');
        
        if (empty($location) || empty($pickup_date)) {
            setFlashMessage('error', 'Please fill in all required fields');
        } else {
            if (createRequest($client['id'], $location, $pickup_date, $notes)) {
                setFlashMessage('success', 'Request submitted successfully');
            } else {
                setFlashMessage('error', 'Failed to submit request');
            }
        }
        redirect('client');
        exit();
    }
}

// Handle request cancellation
if (isPost() && isset($_POST['cancel_request'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $request_id = $_POST['request_id'] ?? 0;
        
        if (updateRequestStatus($request_id, 'cancelled')) {
            setFlashMessage('success', 'Request cancelled successfully');
        } else {
            setFlashMessage('error', 'Failed to cancel request');
        }
        redirect('client');
        exit();
    }
}

// Handle payment submission
if (isPost() && isset($_POST['submit_payment'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $request_id = $_POST['request_id'] ?? 0;
        $amount = floatval($_POST['amount'] ?? 0);
        
        if ($amount <= 0) {
            setFlashMessage('error', 'Please enter a valid payment amount');
        } else {
            if (createPayment($client['id'], $request_id, $amount)) {
                setFlashMessage('success', 'Payment submitted successfully. The collector will confirm upon service completion.');
            } else {
                setFlashMessage('error', 'Failed to submit payment. Please try again.');
            }
        }
        redirect('client');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - Waste Management System</title>
    
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
        
        .profile-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            background: white;
            overflow: hidden;
        }
        
        .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            background: white;
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
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
            background-color: #f8f9fa;
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
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
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
            
            .mobile-toggle {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                background: #2c3e50;
                border: none;
                color: white;
                padding: 10px;
                border-radius: 5px;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-toggle {
                display: none;
            }
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
            <a href="<?php echo url('client'); ?>" class="nav-link active">
                <i class='bx bx-dashboard'></i> Dashboard
            </a>
            <a href="<?php echo url('payments'); ?>" class="nav-link">
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
            <!-- Flash Messages -->
            <?php $flash = getFlashMessage(); ?>

            <!-- Profile Card -->
            <div class="card profile-card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="avatar avatar-xl">
                                <i class='bx bx-user-circle' style="font-size: 3rem;"></i>
                            </div>
                        </div>
                        <div class="col">
                            <h4 class="mb-1"><?php echo htmlspecialchars($client['name']); ?></h4>
                            <p class="text-muted mb-0">
                                <i class='bx bx-envelope'></i> <?php echo htmlspecialchars($client['email']); ?>
                                &nbsp;&nbsp;
                                <i class='bx bx-phone'></i> <?php echo htmlspecialchars($client['phone'] ?? 'Not provided'); ?>
                                &nbsp;&nbsp;
                                <i class='bx bx-map'></i> <?php echo htmlspecialchars($client['address'] ?? 'Not provided'); ?>
                            </p>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newRequestModal">
                                <i class='bx bx-plus'></i> New Request
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Requests Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">My Requests</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped" id="requestsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Location</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Collector</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($request = $requests->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $request['id']; ?></td>
                                    <td><?php echo htmlspecialchars($request['location']); ?></td>
                                    <td><?php echo formatDate($request['pickup_date']); ?></td>
                                    <td><?php echo getStatusBadge($request['status']); ?></td>
                                    <td>
                                        <?php if ($request['collector_name']): ?>
                                            <?php echo htmlspecialchars($request['collector_name']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($request['payment_status']): ?>
                                            <?php echo getStatusBadge($request['payment_status']); ?>
                                            <br>
                                            <small><?php echo formatCurrency($request['payment_amount']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">No payment</span>
                                            <br>
                                            <button type="button" class="btn btn-sm btn-success mt-1" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#paymentModal"
                                                    data-request-id="<?php echo $request['id']; ?>"
                                                    data-request-location="<?php echo htmlspecialchars($request['location']); ?>">
                                                <i class='bx bx-credit-card'></i> Pay Now
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($request['status'] == 'pending'): ?>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#cancelRequestModal"
                                                    data-request-id="<?php echo $request['id']; ?>">
                                                Cancel
                                            </button>
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

    <!-- New Request Modal -->
    <div class="modal fade" id="newRequestModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">New Waste Collection Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="location" class="form-label">Collection Location</label>
                            <textarea class="form-control" id="location" name="location" rows="2" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="pickup_date" class="form-label">Pickup Date</label>
                            <input type="date" class="form-control" id="pickup_date" name="pickup_date" 
                                   min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Additional Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="submit_request" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Cancel Request Modal -->
    <div class="modal fade" id="cancelRequestModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="request_id" id="cancelRequestId">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Cancel Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body">
                        <p>Are you sure you want to cancel this request?</p>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep It</button>
                        <button type="submit" name="cancel_request" class="btn btn-danger">Yes, Cancel It</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="request_id" id="paymentRequestId">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Make Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <h6>Request Details:</h6>
                            <p class="mb-0" id="paymentRequestLocation"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label for="amount" class="form-label">Payment Amount (PHP)</label>
                            <input type="number" class="form-control" id="amount" name="amount" 
                                   step="0.01" min="0" required>
                            <div class="form-text">Enter the amount you want to pay for this service.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="cash" value="cash" checked>
                                <label class="form-check-label" for="cash">
                                    <i class='bx bx-money'></i> Cash Payment
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="gcash" value="gcash">
                                <label class="form-check-label" for="gcash">
                                    <i class='bx bx-mobile'></i> GCash
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="bank" value="bank">
                                <label class="form-check-label" for="bank">
                                    <i class='bx bx-building-house'></i> Bank Transfer
                                </label>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class='bx bx-info-circle'></i>
                            <strong>Note:</strong> Payment will be marked as pending. The collector will confirm the payment upon service completion.
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="submit_payment" class="btn btn-success">
                            <i class='bx bx-credit-card'></i> Submit Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?php echo asset('vendor/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        $(document).ready(function() {
            // Flash Messages with SweetAlert
            <?php if ($flash): ?>
                Swal.fire({
                    title: "<?= $flash['type'] === 'success' ? 'Success!' : 'Error!' ?>",
                    text: "<?= addslashes($flash['message']) ?>",
                    icon: "<?= $flash['type'] === 'error' ? 'error' : $flash['type'] ?>",
                    confirmButtonText: 'OK',
                    confirmButtonColor: "<?= $flash['type'] === 'error' ? '#d33' : '#3085d6' ?>"
                });
            <?php endif; ?>
            
            // Debug: Check if Bootstrap and jQuery are loaded
            console.log('jQuery version:', $.fn.jquery);
            console.log('Bootstrap version:', typeof bootstrap !== 'undefined' ? 'Loaded' : 'Not loaded');
            
            // Test modal functionality
            $('[data-bs-toggle="modal"]').on('click', function() {
                console.log('Modal button clicked:', $(this).data('bs-target'));
            });
            
            // Initialize DataTable
            $('#requestsTable').DataTable({
                order: [[2, 'desc']], // Sort by date
                pageLength: 10
            });

            // Handle cancel modal data
            $('#cancelRequestModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var requestId = button.data('request-id');
                $('#cancelRequestId').val(requestId);
            });

            // Handle payment modal data
            $('#paymentModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var requestId = button.data('request-id');
                var requestLocation = button.data('request-location');
                $('#paymentRequestId').val(requestId);
                $('#paymentRequestLocation').text(requestLocation);
            });

            // Set minimum date for pickup date
            var today = new Date().toISOString().split('T')[0];
            document.getElementById('pickup_date').setAttribute('min', today);
        });
        
        // Test function to manually trigger modal
        function testModal() {
            console.log('Testing modal...');
            var modal = new bootstrap.Modal(document.getElementById('newRequestModal'));
            modal.show();
        }
    </script>
</body>
</html> 