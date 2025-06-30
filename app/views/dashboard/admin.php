<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Set layout and page variables
$layout = 'admin';
$pageTitle = "Admin Dashboard";
$currentPage = 'dashboard';

// Check authentication
if (!isset($_SESSION['user'])) {
    redirect('login');
} elseif ($_SESSION['user']['role'] !== 'admin') {
    redirect($_SESSION['user']['role']);
}

// Get dashboard statistics
$stats = getSystemStats();
$activeRequests = getAllActiveRequests();
$pendingPayments = getAllPendingPayments();

// Start content
ob_start();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Dashboard</h1>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                <i class="bx bx-printer"></i> Print Report
            </button>
        </div>
    </div>

    <!-- Flash messages will be handled by SweetAlert -->
    <?php include APP_PATH . '/views/includes/flash_messages.php'; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Clients</h6>
                            <h4 class="mb-0"><?= number_format($stats['total_clients']) ?></h4>
                        </div>
                        <div class="text-primary">
                            <i class="bx bx-group" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Collectors</h6>
                            <h4 class="mb-0"><?= number_format($stats['total_collectors']) ?></h4>
                        </div>
                        <div class="text-success">
                            <i class="bx bx-user" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Pending Requests</h6>
                            <h4 class="mb-0"><?= number_format($stats['pending_requests']) ?></h4>
                        </div>
                        <div class="text-warning">
                            <i class="bx bx-time" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Revenue</h6>
                            <h4 class="mb-0"><?= formatCurrency($stats['total_revenue']) ?></h4>
                        </div>
                        <div class="text-info">
                            <i class="bx bx-dollar" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Requests Table -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="bx bx-clipboard"></i> Active Collection Requests
                <small class="text-muted">(Auto-assigned to zone collectors)</small>
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="activeRequestsTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Location (Zone)</th>
                            <th>Pickup Date</th>
                            <th>Status</th>
                            <th>Assigned Collector</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $has_active_requests = false;
                        $request_rows = [];
                        
                        // Store all request data first
                        while ($request = $activeRequests->fetch_assoc()) {
                            $has_active_requests = true;
                            $request_rows[] = $request;
                        }
                        
                        if ($has_active_requests):
                            foreach ($request_rows as $request):
                        ?>
                        <tr>
                            <td>
                                <strong>#<?= $request['id'] ?></strong>
                            </td>
                            <td>
                                <div>
                                    <strong><?= htmlspecialchars($request['client_name']) ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <i class="bx bx-envelope"></i> <?= htmlspecialchars($request['client_email']) ?>
                                        <?php if ($request['client_phone']): ?>
                                            <br><i class="bx bx-phone"></i> <?= htmlspecialchars($request['client_phone']) ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <i class="bx bx-map"></i> <?= htmlspecialchars($request['location']) ?>
                                </span>
                            </td>
                            <td>
                                <i class="bx bx-calendar"></i> <?= formatDate($request['pickup_date']) ?>
                            </td>
                            <td><?= getStatusBadge($request['status']) ?></td>
                            <td>
                                <?php if ($request['collector_name']): ?>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-success rounded-circle me-2 d-flex align-items-center justify-content-center">
                                            <i class="bx bx-user text-white"></i>
                                        </div>
                                        <div>
                                            <strong><?= htmlspecialchars($request['collector_name']) ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <i class="bx bx-envelope"></i> <?= htmlspecialchars($request['collector_email']) ?>
                                                <?php if ($request['collector_phone']): ?>
                                                    <br><i class="bx bx-phone"></i> <?= htmlspecialchars($request['collector_phone']) ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-warning">
                                        <i class="bx bx-exclamation-triangle"></i>
                                        <strong>No collector assigned</strong>
                                        <br>
                                        <small class="text-muted">Zone has no collectors</small>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php 
                            endforeach;
                        else:
                        ?>
                        <tr>
                            <td colspan="6" class="border-0 p-0">
                                <?php
                                $type = 'pending_requests';
                                include APP_PATH . '/views/includes/empty_state.php';
                                ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pending Payments Table -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Pending Payments</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="pendingPaymentsTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Client</th>
                            <th>Request Details</th>
                            <th>Amount</th>
                            <th>Date Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $has_pending_payments = false;
                        $payment_rows = [];
                        
                        // Store all payment data first
                        while ($payment = $pendingPayments->fetch_assoc()) {
                            $has_pending_payments = true;
                            $payment_rows[] = $payment;
                        }
                        
                        if ($has_pending_payments):
                            foreach ($payment_rows as $payment):
                        ?>
                        <tr>
                            <td>#<?= $payment['id'] ?></td>
                            <td>
                                <?= htmlspecialchars($payment['client_name']) ?>
                                <br>
                                <small class="text-muted"><?= htmlspecialchars($payment['client_email']) ?></small>
                            </td>
                            <td>
                                <strong>Location:</strong> <?= htmlspecialchars($payment['location']) ?>
                                <br>
                                <small class="text-muted">
                                    <i class="bx bx-calendar"></i> <?= formatDate($payment['pickup_date']) ?>
                                    <?php if ($payment['collector_name']): ?>
                                        <br><i class="bx bx-user"></i> <?= htmlspecialchars($payment['collector_name']) ?>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td><?= formatCurrency($payment['amount']) ?></td>
                            <td><?= formatDateTime($payment['created_at']) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-success approve-payment" 
                                        data-id="<?= $payment['id'] ?>"
                                        data-amount="<?= formatCurrency($payment['amount']) ?>"
                                        data-client="<?= htmlspecialchars($payment['client_name']) ?>">
                                    <i class="bx bx-check"></i> Approve
                                </button>
                                <button type="button" class="btn btn-sm btn-danger reject-payment" 
                                        data-id="<?= $payment['id'] ?>"
                                        data-amount="<?= formatCurrency($payment['amount']) ?>"
                                        data-client="<?= htmlspecialchars($payment['client_name']) ?>">
                                    <i class="bx bx-x"></i> Reject
                                </button>
                            </td>
                        </tr>
                        <?php 
                            endforeach;
                        else:
                        ?>
                        <tr>
                            <td colspan="6" class="border-0 p-0">
                                <?php
                                $type = 'pending_payments';
                                include APP_PATH . '/views/includes/empty_state.php';
                                ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Assign Collector Modal Removed - Collectors are now auto-assigned based on zones -->

<style>
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 14px;
}

.table td {
    vertical-align: middle;
}

.badge.bg-info {
    background-color: #0dcaf0 !important;
}

.text-warning strong {
    color: #f8a100 !important;
}

.table-responsive {
    border-radius: 8px;
}

.card-header h5 small {
    font-size: 0.75rem;
    font-weight: normal;
}
</style>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#activeRequestsTable').DataTable({
        order: [[0, 'desc']],
        responsive: true,
        pageLength: 10
    });

    // Initialize DataTable for pending payments
    $('#pendingPaymentsTable').DataTable({
        order: [[4, 'desc']], // Sort by date submitted
        responsive: true,
        pageLength: 10
    });

    // Collector assignment functionality removed - now handled automatically by zone assignment

    // Approve payment button handler
    $('.approve-payment').on('click', function() {
        var paymentId = $(this).data('id');
        var amount = $(this).data('amount');
        var client = $(this).data('client');
        
        Swal.fire({
            title: 'Approve Payment?',
            html: `
                <p><strong>Client:</strong> ${client}</p>
                <p><strong>Amount:</strong> ${amount}</p>
                <p>Are you sure you want to approve this payment?</p>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Approve',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                updatePaymentStatus(paymentId, 'completed');
            }
        });
    });

    // Reject payment button handler
    $('.reject-payment').on('click', function() {
        var paymentId = $(this).data('id');
        var amount = $(this).data('amount');
        var client = $(this).data('client');
        
        Swal.fire({
            title: 'Reject Payment?',
            html: `
                <p><strong>Client:</strong> ${client}</p>
                <p><strong>Amount:</strong> ${amount}</p>
                <p>Are you sure you want to reject this payment?</p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Reject',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                updatePaymentStatus(paymentId, 'failed');
            }
        });
    });

    // Function to update payment status
    function updatePaymentStatus(paymentId, status) {
        // Create form data
        var formData = new FormData();
        formData.append('payment_id', paymentId);
        formData.append('status', status);
        formData.append('csrf_token', '<?= generateCSRFToken() ?>');
        formData.append('update_payment', 'true');

        // Show loading
        Swal.fire({
            title: 'Processing...',
            text: 'Updating payment status',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Send AJAX request
        fetch('<?= url('admin/payments/update') ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                Swal.fire({
                    title: 'Success!',
                    text: 'Payment status updated successfully',
                    icon: 'success',
                    confirmButtonColor: '#28a745'
                }).then(() => {
                    location.reload();
                });
            } else {
                throw new Error('Network response was not ok');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error!',
                text: 'Failed to update payment status',
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        });
    }
});
</script>
