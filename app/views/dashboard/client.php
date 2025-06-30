<?php
requireRole('client');

// Get current user
$client = getCurrentUser();

// Get client's requests
$requests = getClientRequests($client['id']);

// Get all available zones for the dropdown
$zones = getAllZones();

// Disable layout to prevent duplicate sidebars
$layout = false;

// Handle new request submission
if (isPost() && isset($_POST['submit_request'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $zone_id = $_POST['zone_id'] ?? '';
        $pickup_date = $_POST['pickup_date'] ?? '';
        $notes = sanitize($_POST['notes'] ?? '');
        
        if (empty($zone_id) || empty($pickup_date)) {
            setFlashMessage('error', 'Please fill in all required fields');
        } else {
            // Get zone name for the location field
            $zone_name = '';
            if ($zones) {
                $zones->data_seek(0); // Reset pointer
                while ($zone = $zones->fetch_assoc()) {
                    if ($zone['id'] == $zone_id) {
                        $zone_name = $zone['name'];
                        break;
                    }
                }
            }
            
            if (createRequest($client['id'], $zone_name, $pickup_date, $notes)) {
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
        $payment_type = $_POST['payment_type'] ?? '';
        
        // Validate basic fields
        if ($amount <= 0) {
            setFlashMessage('error', 'Please enter a valid payment amount');
            redirect('client');
            exit();
        }
        
        if ($payment_type !== 'phone') {
            setFlashMessage('error', 'Please select phone payment method');
            redirect('client');
            exit();
        }
        
        // Prepare payment details for phone payment
        $payment_details = [];
        
        $phone_provider = $_POST['phone_provider'] ?? '';
        if (!in_array($phone_provider, ['Mpesa', 'Halopesa', 'AirtelMoney', 'MixbyYas'])) {
            setFlashMessage('error', 'Please select a valid phone payment provider');
            redirect('client');
            exit();
        }
        $payment_details['phone_provider'] = $phone_provider;
        
        // Create payment with payment method details
        if (createPayment($client['id'], $request_id, $amount, $payment_type, $payment_details)) {
            $payment_method_text = "via {$payment_details['phone_provider']}";
            setFlashMessage('success', "Payment submitted successfully {$payment_method_text}. The collector will confirm upon service completion.");
        } else {
            setFlashMessage('error', 'Failed to submit payment. Please try again.');
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
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
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

        /* Empty State Styling */
        .empty-state {
            padding: 2rem;
            max-width: 400px;
            margin: 0 auto;
        }

        .empty-state-icon {
            animation: float 6s ease-in-out infinite;
        }

        .empty-state-title {
            color: #495057;
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .empty-state-description {
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .alert-sm {
            font-size: 0.75rem;
            line-height: 1.2;
            border-radius: 6px;
        }

        .empty-state .btn-lg {
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
            transition: all 0.3s ease;
        }

        .empty-state .btn-lg:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        /* Table responsive improvements */
        .table-responsive {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 2px solid #dee2e6;
        }

        .card-header h5 {
            color: #495057;
            font-weight: 600;
        }

        .card-header i {
            color: #007bff;
            margin-right: 8px;
        }

        /* Payment Method Cards */
        .payment-method-card {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 100%;
        }

        .payment-method-card:hover {
            border-color: #007bff;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.1);
        }

        .payment-method-card input[type="radio"]:checked + label {
            color: #007bff;
        }

        .payment-fields {
            transition: all 0.3s ease;
        }

        .alert-sm {
            font-size: 0.75rem;
            line-height: 1.2;
            border-radius: 6px;
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
                    <h5 class="card-title mb-0">
                        <i class='bx bx-clipboard'></i> My Requests
                    </h5>
                </div>
                <div class="card-body">
                    <?php 
                    // Check if there are any requests to display
                    $has_requests = false;
                    $request_rows = [];
                    
                    // Store all request data first
                    while ($request = $requests->fetch_assoc()) {
                        $has_requests = true;
                        $request_rows[] = $request;
                    }
                    
                    if ($has_requests):
                    ?>
                    <div class="table-responsive">
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
                                <?php foreach ($request_rows as $request): ?>
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
                                            <?php if ($request['payment_status'] && $request['payment_status'] == 'completed'): ?>
                                                <span class="badge bg-success">
                                                    <i class='bx bx-check'></i> Paid
                                                </span>
                                                <br>
                                                <small><?php echo formatCurrency($request['payment_amount']); ?></small>
                                            <?php elseif ($request['payment_status'] && $request['payment_status'] == 'pending'): ?>
                                                <span class="badge bg-warning">
                                                    <i class='bx bx-time'></i> Payment Pending
                                                </span>
                                                <br>
                                                <small><?php echo formatCurrency($request['payment_amount']); ?></small>
                                            <?php else: ?>
                                                <?php if ($request['status'] == 'completed'): ?>
                                                    <div class="alert alert-warning alert-sm p-2 mb-1">
                                                        <small><strong>Payment Required</strong><br>
                                                        Service completed - please pay now</small>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">No payment required yet</span>
                                                    <br>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm btn-success mt-1" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#paymentModal"
                                                        data-request-id="<?php echo $request['id']; ?>"
                                                        data-request-location="<?php echo htmlspecialchars($request['location']); ?>"
                                                        data-zone-price="<?php 
                                                            // Get zone price for this request location
                                                            $zones_temp = getAllZones();
                                                            $zone_price = 150.00; // default
                                                            if ($zones_temp) {
                                                                while ($zone_temp = $zones_temp->fetch_assoc()) {
                                                                    if ($zone_temp['name'] == $request['location']) {
                                                                        $zone_price = $zone_temp['price'] ?? 150.00;
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                            echo $zone_price;
                                                        ?>">
                                                    <i class='bx bx-credit-card'></i> 
                                                    <?php echo ($request['status'] == 'completed') ? 'Pay Now' : 'Pre-pay'; ?>
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
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <!-- Modern Empty State -->
                        <div class="text-center py-5">
                            <div class="empty-state">
                                <div class="empty-state-icon mb-4">
                                    <i class='bx bx-clipboard' style="font-size: 4rem; color: #6c757d;"></i>
                                </div>
                                <h4 class="empty-state-title">No Active Requests</h4>
                                <p class="empty-state-description text-muted mb-4">
                                    You haven't made any waste collection requests yet.<br>
                                    Get started by creating your first request!
                                </p>
                                <button type="button" class="btn btn-primary btn-lg" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#newRequestModal">
                                    <i class='bx bx-plus'></i> Create New Request
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
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
                        <?php if ($zones && $zones->num_rows > 0): ?>
                            <div class="mb-3">
                                <label for="zone_id" class="form-label">Collection Zone</label>
                                <select class="form-select" id="zone_id" name="zone_id" required>
                                    <option value="">Select a collection zone</option>
                                    <?php 
                                    // Reset zones pointer and populate dropdown
                                    $zones->data_seek(0); // Reset pointer
                                    while ($zone = $zones->fetch_assoc()): 
                                    ?>
                                                                            <option value="<?php echo $zone['id']; ?>" data-price="<?php echo $zone['price'] ?? 0; ?>">
                                        <?php echo htmlspecialchars($zone['name']); ?>
                                        <?php if (!empty($zone['description'])): ?>
                                            - <?php echo htmlspecialchars($zone['description']); ?>
                                        <?php endif; ?>
                                        - <?php echo formatCurrency($zone['price'] ?? 0); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="form-text">Choose the zone where you want waste collection service.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="pickup_date" class="form-label">Pickup Date</label>
                                <input type="date" class="form-control" id="pickup_date" name="pickup_date" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Additional Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                          placeholder="Any special instructions or additional information..."></textarea>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning text-center">
                                <i class='bx bx-map-alt' style="font-size: 2rem;"></i>
                                <h5 class="mt-2">No Collection Zones Available</h5>
                                <p class="mb-0">
                                    No collection zones have been set up yet. Please contact the administrator 
                                    to create collection zones in your area.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <?php if ($zones && $zones->num_rows > 0): ?>
                            <button type="submit" name="submit_request" class="btn btn-primary">
                                <i class='bx bx-plus'></i> Submit Request
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-primary" disabled>
                                <i class='bx bx-x'></i> Cannot Submit - No Zones
                            </button>
                        <?php endif; ?>
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
                            <div class="form-text">Amount is auto-filled based on zone pricing. You can adjust if needed.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <div class="form-check payment-method-card">
                                <input class="form-check-input" type="radio" name="payment_type" id="phone_payment" value="phone" checked>
                                <label class="form-check-label" for="phone_payment">
                                    <i class='bx bx-mobile-alt text-primary'></i>
                                    <strong>Phone Payment</strong>
                                    <small class="d-block text-muted">Mobile money services</small>
                                </label>
                            </div>
                        </div>

                        <!-- Phone Payment Fields -->
                        <div id="phonePaymentFields" class="payment-fields">
                            <div class="mb-3">
                                <label for="phone_provider" class="form-label">
                                    <i class='bx bx-mobile'></i> Select Provider
                                </label>
                                <select class="form-select" id="phone_provider" name="phone_provider" required>
                                    <option value="">Choose your mobile money provider</option>
                                    <option value="Mpesa">M-Pesa</option>
                                    <option value="Halopesa">HaloPesa</option>
                                    <option value="AirtelMoney">Airtel Money</option>
                                    <option value="MixbyYas">MixbyYas</option>
                                </select>
                                <div class="form-text">Select your preferred mobile payment service</div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class='bx bx-info-circle'></i>
                                <strong>How to pay:</strong>
                                <ol class="mb-0 mt-2">
                                    <li>Submit this payment request</li>
                                    <li>You'll receive SMS with payment instructions</li>
                                    <li>Complete payment through your mobile app</li>
                                    <li>Collector will confirm payment upon service completion</li>
                                </ol>
                            </div>
                        </div>


                        
                        <div class="alert alert-info">
                            <i class='bx bx-info-circle'></i>
                            <strong>Payment Process:</strong> Payment will be marked as pending. The collector will confirm the payment upon service completion.
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo asset('js/payment-methods.js'); ?>"></script>
    
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
            
            // Initialize DataTable
            $('#requestsTable').DataTable({
                order: [[2, 'desc']], // Sort by date
                pageLength: 10,
                responsive: true
            });

            // Initialize Select2 for zone dropdown
            $('#zone_id').select2({
                theme: 'bootstrap-5',
                placeholder: 'Search and select a collection zone...',
                allowClear: true,
                dropdownParent: $('#newRequestModal'),
                width: '100%'
            });

            // Reset form and Select2 when modal opens
            $('#newRequestModal').on('show.bs.modal', function() {
                $('#zone_id').val('').trigger('change');
                $('#pickup_date').val('');
                $('#notes').val('');
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
                var zonePrice = button.data('zone-price') || 150.00;
                
                $('#paymentRequestId').val(requestId);
                $('#paymentRequestLocation').text(requestLocation);
                $('#amount').val(parseFloat(zonePrice).toFixed(2));
            });

            // Handle zone selection for new requests - show pricing
            $('#zone_id').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var zonePrice = selectedOption.data('price') || 0;
                
                // Remove existing price info
                $('#zonePriceInfo').remove();
                
                if (zonePrice > 0 && selectedOption.val()) {
                    // Format currency using PHP number format style
                    var formattedPrice = '₱' + parseFloat(zonePrice).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                    
                    // Show price info below the select
                    var priceInfo = '<div class="mt-2 alert alert-info" id="zonePriceInfo">' +
                                   '<i class="bx bx-info-circle"></i> Service price for this zone: <strong>' + 
                                   formattedPrice + '</strong></div>';
                    
                    $(this).closest('.mb-3').append(priceInfo);
                }
            });

            // Set minimum date for pickup date
            var today = new Date().toISOString().split('T')[0];
            document.getElementById('pickup_date').setAttribute('min', today);

            // Reset payment modal when closed
            $('#paymentModal').on('hidden.bs.modal', function() {
                $('#phone_provider').val('');
            });

            // Form validation before submission
            $('#paymentModal form').on('submit', function(e) {
                if (!$('#phone_provider').val()) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Validation Error',
                        text: 'Please select a phone payment provider',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });
    </script>
</body>
</html> 