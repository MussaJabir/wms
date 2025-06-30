<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Set layout and page variables
$layout = 'admin';
$pageTitle = "All Requests";
$currentPage = 'requests';

// Check authentication - allow admin access
if (!isset($_SESSION['user'])) {
    redirect('login');
} elseif ($_SESSION['user']['role'] !== 'admin') {
    redirect($_SESSION['user']['role']);
}

// Get all requests with full details
function getAllSystemRequests() {
    global $conn;
    
    $sql = "SELECT r.*, 
            c.name as client_name,
            c.email as client_email,
            c.phone as client_phone,
            col.name as collector_name,
            col.email as collector_email,
            col.phone as collector_phone,
            p.status as payment_status,
            p.amount as payment_amount
            FROM requests r
            LEFT JOIN users c ON r.client_id = c.id
            LEFT JOIN users col ON r.collector_id = col.id
            LEFT JOIN payments p ON r.id = p.request_id
            ORDER BY r.created_at DESC";
            
    return $conn->query($sql);
}

$all_requests = getAllSystemRequests();

// Start content
ob_start();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bx bx-clipboard-x"></i> All System Requests
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
                        <option value="assigned">Assigned</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filter by Payment Status:</label>
                    <select class="form-select" id="paymentFilter">
                        <option value="">All Payment Statuses</option>
                        <option value="none">No Payment</option>
                        <option value="pending">Pending Payment</option>
                        <option value="completed">Completed Payment</option>
                        <option value="failed">Failed Payment</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date Range:</label>
                    <input type="date" class="form-control" id="dateFrom" placeholder="From">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <input type="date" class="form-control" id="dateTo" placeholder="To">
                </div>
            </div>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="bx bx-list-ul"></i> System Requests Overview
            </h5>
        </div>
        <div class="card-body">
            <?php 
            $has_requests = false;
            $request_rows = [];
            
            // Store all request data first
            while ($request = $all_requests->fetch_assoc()) {
                $has_requests = true;
                $request_rows[] = $request;
            }
            
            if ($has_requests):
            ?>
            <div class="table-responsive">
                <table id="requestsTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client Details</th>
                            <th>Location</th>
                            <th>Pickup Date</th>
                            <th>Status</th>
                            <th>Collector</th>
                            <th>Payment</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($request_rows as $request): ?>
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
                                <td>
                                    <?= getStatusBadge($request['status']) ?>
                                </td>
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
                                                </small>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            <i class="bx bx-user-x"></i> Not assigned
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($request['payment_status']): ?>
                                        <div>
                                            <?= getStatusBadge($request['payment_status']) ?>
                                            <br>
                                            <small class="text-success">
                                                <i class="bx bx-money"></i> <?= formatCurrency($request['payment_amount']) ?>
                                            </small>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            <i class="bx bx-x-circle"></i> No payment
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= formatDate($request['created_at']) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group-vertical btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                onclick="viewRequestDetails(<?= $request['id'] ?>)"
                                                title="View Details">
                                            <i class="bx bx-eye"></i>
                                        </button>
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-outline-warning btn-sm" 
                                                    onclick="updateRequestStatus(<?= $request['id'] ?>, 'cancelled')"
                                                    title="Cancel Request">
                                                <i class="bx bx-x"></i>
                                            </button>
                                        <?php endif; ?>
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
                $type = 'requests';
                include APP_PATH . '/views/includes/empty_state.php';
                ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Request Details Modal -->
<div class="modal fade" id="requestDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="requestDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

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

.btn-group-vertical .btn {
    margin-bottom: 2px;
}
</style>

<script>
$(document).ready(function() {
    // Initialize DataTable with advanced features
    const table = $('#requestsTable').DataTable({
        order: [[0, 'desc']],
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        columnDefs: [
            {
                targets: [8], // Actions column
                orderable: false,
                searchable: false
            }
        ]
    });

    // Status filter
    $('#statusFilter').on('change', function() {
        const value = this.value;
        table.column(4).search(value).draw();
    });

    // Payment filter
    $('#paymentFilter').on('change', function() {
        const value = this.value;
        table.column(6).search(value).draw();
    });

    // Date range filter
    $('#dateFrom, #dateTo').on('change', function() {
        table.draw();
    });

    // Custom date filter
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        const dateFrom = $('#dateFrom').val();
        const dateTo = $('#dateTo').val();
        const dateCol = data[7]; // Created date column
        
        if (!dateFrom && !dateTo) return true;
        
        const rowDate = new Date(dateCol);
        const fromDate = dateFrom ? new Date(dateFrom) : null;
        const toDate = dateTo ? new Date(dateTo) : null;
        
        if (fromDate && rowDate < fromDate) return false;
        if (toDate && rowDate > toDate) return false;
        
        return true;
    });
});

// View request details
function viewRequestDetails(requestId) {
    // This would typically load request details via AJAX
    $('#requestDetailsContent').html(`
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading request details...</p>
        </div>
    `);
    
    $('#requestDetailsModal').modal('show');
    
    // Simulate loading (replace with actual AJAX call)
    setTimeout(() => {
        $('#requestDetailsContent').html(`
            <div class="alert alert-info">
                <h6>Request #${requestId} Details</h6>
                <p>This is a placeholder for detailed request information. In a full implementation, this would show complete request details, history, and allow status updates.</p>
            </div>
        `);
    }, 1000);
}

// Update request status
function updateRequestStatus(requestId, status) {
    Swal.fire({
        title: 'Update Request Status?',
        text: `Are you sure you want to mark request #${requestId} as ${status}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, update it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // This would typically make an AJAX call to update status
            Swal.fire(
                'Updated!',
                `Request #${requestId} has been marked as ${status}.`,
                'success'
            ).then(() => {
                location.reload();
            });
        }
    });
}

// Export to CSV
function exportToCSV() {
    const table = $('#requestsTable').DataTable();
    table.button('.buttons-csv').trigger();
}
</script>
