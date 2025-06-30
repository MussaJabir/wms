<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Set layout and page variables
$layout = 'admin';
$pageTitle = "All Payments";
$currentPage = 'payments';

// Check authentication - allow admin access
if (!isset($_SESSION['user'])) {
    redirect('login');
} elseif ($_SESSION['user']['role'] !== 'admin') {
    redirect($_SESSION['user']['role']);
}

// Get all payments with full details
function getAllSystemPayments() {
    global $conn;
    
    $sql = "SELECT p.*, 
            r.location, 
            r.pickup_date,
            r.status as request_status,
            c.name as client_name,
            c.email as client_email,
            c.phone as client_phone,
            col.name as collector_name
            FROM payments p
            JOIN requests r ON p.request_id = r.id
            JOIN users c ON r.client_id = c.id
            LEFT JOIN users col ON r.collector_id = col.id
            ORDER BY p.created_at DESC";
            
    return $conn->query($sql);
}

$all_payments = getAllSystemPayments();

// Start content
ob_start();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bx bx-credit-card"></i> All System Payments
        </h1>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                <i class="bx bx-printer"></i> Print Report
            </button>
            <button type="button" class="btn btn-outline-success" onclick="exportToCSV()">
                <i class="bx bx-download"></i> Export CSV
            </button>
        </div>
    </div>

    <!-- Flash messages -->
    <?php include APP_PATH . '/views/includes/flash_messages.php'; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Filter by Status:</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Amount Range:</label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input type="number" class="form-control" id="amountFrom" placeholder="Min">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input type="number" class="form-control" id="amountTo" placeholder="Max">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date Range:</label>
                    <input type="date" class="form-control" id="dateFrom" placeholder="From">
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="text-primary">Total Payments</h5>
                    <h3 id="totalPayments">-</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="text-success">Total Revenue</h5>
                    <h3 id="totalRevenue">-</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="text-warning">Pending</h5>
                    <h3 id="pendingPayments">-</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="text-danger">Failed</h5>
                    <h3 id="failedPayments">-</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="bx bx-list-ul"></i> System Payments Overview
            </h5>
        </div>
        <div class="card-body">
            <?php 
            $has_payments = false;
            $payment_rows = [];
            
            // Store all payment data first
            while ($payment = $all_payments->fetch_assoc()) {
                $has_payments = true;
                $payment_rows[] = $payment;
            }
            
            if ($has_payments):
            ?>
            <div class="table-responsive">
                <table id="paymentsTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Client Details</th>
                            <th>Request Details</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date Submitted</th>
                            <th>Collector</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payment_rows as $payment): ?>
                            <tr>
                                <td>
                                    <strong>#<?= $payment['id'] ?></strong>
                                </td>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($payment['client_name']) ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="bx bx-envelope"></i> <?= htmlspecialchars($payment['client_email']) ?>
                                            <?php if ($payment['client_phone']): ?>
                                                <br><i class="bx bx-phone"></i> <?= htmlspecialchars($payment['client_phone']) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge bg-info">
                                            <i class="bx bx-map"></i> <?= htmlspecialchars($payment['location']) ?>
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            <i class="bx bx-calendar"></i> <?= formatDate($payment['pickup_date']) ?>
                                            <br>
                                            Request Status: <?= getStatusBadge($payment['request_status']) ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <strong class="text-success"><?= formatCurrency($payment['amount']) ?></strong>
                                </td>
                                <td>
                                    <?= getStatusBadge($payment['status']) ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= formatDate($payment['created_at']) ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($payment['collector_name']): ?>
                                        <span class="text-success">
                                            <i class="bx bx-user"></i> <?= htmlspecialchars($payment['collector_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            <i class="bx bx-user-x"></i> Not assigned
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($payment['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-success approve-payment" 
                                                    data-id="<?= $payment['id'] ?>"
                                                    data-amount="<?= formatCurrency($payment['amount']) ?>"
                                                    data-client="<?= htmlspecialchars($payment['client_name']) ?>"
                                                    title="Approve Payment">
                                                <i class="bx bx-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger reject-payment" 
                                                    data-id="<?= $payment['id'] ?>"
                                                    data-amount="<?= formatCurrency($payment['amount']) ?>"
                                                    data-client="<?= htmlspecialchars($payment['client_name']) ?>"
                                                    title="Reject Payment">
                                                <i class="bx bx-x"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="viewPaymentDetails(<?= $payment['id'] ?>)"
                                                title="View Details">
                                            <i class="bx bx-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <!-- Modern Empty State -->
                <?php
                $type = 'payments';
                include APP_PATH . '/views/includes/empty_state.php';
                ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Payment Details Modal -->
<div class="modal fade" id="paymentDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="paymentDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
.table td {
    vertical-align: middle;
}

.badge.bg-info {
    background-color: #0dcaf0 !important;
}

.table-responsive {
    border-radius: 8px;
}

.card-header h5 {
    color: #495057;
    font-weight: 600;
}

.card-header i {
    color: #007bff;
    margin-right: 8px;
}
</style>

<script>
$(document).ready(function() {
    // Initialize DataTable with advanced features
    const table = $('#paymentsTable').DataTable({
        order: [[5, 'desc']], // Sort by date submitted
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        columnDefs: [
            {
                targets: [7], // Actions column
                orderable: false,
                searchable: false
            }
        ],
        footerCallback: function (row, data, start, end, display) {
            // Calculate statistics
            const api = this.api();
            
            // Total payments
            $('#totalPayments').text(data.length);
            
            // Calculate totals for visible rows
            let totalRevenue = 0;
            let pendingCount = 0;
            let failedCount = 0;
            
            api.rows({search: 'applied'}).data().each(function(row) {
                const amount = parseFloat(row[3].replace(/[₱,]/g, ''));
                const status = row[4];
                
                if (status.includes('completed')) {
                    totalRevenue += amount;
                } else if (status.includes('pending')) {
                    pendingCount++;
                } else if (status.includes('failed')) {
                    failedCount++;
                }
            });
            
            $('#totalRevenue').text('₱' + totalRevenue.toLocaleString());
            $('#pendingPayments').text(pendingCount);
            $('#failedPayments').text(failedCount);
        }
    });

    // Status filter
    $('#statusFilter').on('change', function() {
        const value = this.value;
        table.column(4).search(value).draw();
    });

    // Amount range filter
    $('#amountFrom, #amountTo').on('change', function() {
        table.draw();
    });

    // Date filter
    $('#dateFrom').on('change', function() {
        table.draw();
    });

    // Custom amount filter
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        const amountFrom = parseFloat($('#amountFrom').val()) || 0;
        const amountTo = parseFloat($('#amountTo').val()) || 999999;
        const amount = parseFloat(data[3].replace(/[₱,]/g, ''));
        
        return amount >= amountFrom && amount <= amountTo;
    });

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
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Success!',
                    text: data.message || 'Payment status updated successfully',
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#28a745'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: data.message || 'Failed to update payment status',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error!',
                text: 'Network error. Please try again.',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc3545'
            });
        });
    }
});

// View payment details
function viewPaymentDetails(paymentId) {
    $('#paymentDetailsContent').html(`
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading payment details...</p>
        </div>
    `);
    
    $('#paymentDetailsModal').modal('show');
    
    // Simulate loading (replace with actual AJAX call)
    setTimeout(() => {
        $('#paymentDetailsContent').html(`
            <div class="alert alert-info">
                <h6>Payment #${paymentId} Details</h6>
                <p>This is a placeholder for detailed payment information. In a full implementation, this would show complete payment details, transaction history, and allow status updates.</p>
            </div>
        `);
    }, 1000);
}

// Export to CSV
function exportToCSV() {
    const table = $('#paymentsTable').DataTable();
    table.button('.buttons-csv').trigger();
}
</script>

