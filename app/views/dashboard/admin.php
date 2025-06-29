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
$pendingRequests = getAllPendingRequests();

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

    <!-- Pending Requests Table -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Pending Collection Requests</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="pendingRequestsTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Location</th>
                            <th>Pickup Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($request = $pendingRequests->fetch_assoc()): ?>
                        <tr>
                            <td><?= $request['id'] ?></td>
                            <td><?= htmlspecialchars($request['client_name']) ?></td>
                            <td><?= htmlspecialchars($request['location']) ?></td>
                            <td><?= formatDate($request['pickup_date']) ?></td>
                            <td><?= getStatusBadge($request['status']) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary assign-collector" 
                                        data-id="<?= $request['id'] ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#assignCollectorModal">
                                    <i class="bx bx-user-plus"></i> Assign
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Assign Collector Modal -->
<div class="modal fade" id="assignCollectorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="assignCollectorForm" method="POST" action="<?= url('admin/requests/assign') ?>">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="request_id" id="requestId">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Collector</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Collector</label>
                        <select class="form-select select2" name="collector_id" required>
                            <option value="">Choose a collector...</option>
                            <?php
                            $collectors = getAllCollectors();
                            while ($collector = $collectors->fetch_assoc()) {
                                echo '<option value="' . $collector['id'] . '">' . htmlspecialchars($collector['name']) . ' (' . htmlspecialchars($collector['email']) . ')</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle"></i>
                        <strong>Note:</strong> Assigning a collector will change the request status to "Assigned" and notify the collector.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-user-plus"></i> Assign Collector
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#pendingRequestsTable').DataTable({
        order: [[0, 'desc']],
        responsive: true,
        pageLength: 10
    });

    // Initialize Select2 for collector selection
    $('#assignCollectorModal .select2').select2({
        theme: 'bootstrap-5',
        width: '100%',
        dropdownParent: $('#assignCollectorModal')
    });

    // Handle Assign Collector
    $('.assign-collector').click(function() {
        const requestId = $(this).data('id');
        $('#requestId').val(requestId);
        
        // Reset form
        $('#assignCollectorForm')[0].reset();
        $('#assignCollectorModal .select2').val('').trigger('change');
    });

    // Handle form submission with SweetAlert
    $('#assignCollectorForm').submit(function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Show loading state
        submitBtn.html('<i class="bx bx-loader-alt bx-spin"></i> Assigning...').prop('disabled', true);
        
        $.post($(this).attr('action'), formData, function(response) {
            if (response.success) {
                Swal.fire({
                    title: 'Success!',
                    text: response.message || 'Collector assigned successfully',
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#3085d6'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: response.message || 'Failed to assign collector',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#d33'
                });
            }
        }).fail(function() {
            Swal.fire({
                title: 'Error!',
                text: 'Network error. Please try again.',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#d33'
            });
        }).always(function() {
            // Reset button state
            submitBtn.html(originalText).prop('disabled', false);
        });
    });
});
</script>
