<?php
requireRole('collector');

// Get current user
$collector = getCurrentUser();

// Get assigned requests
$assigned_requests = getCollectorRequests($collector['id']);

// Handle request status update
if (isPost() && isset($_POST['update_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $request_id = $_POST['request_id'] ?? 0;
        $status = $_POST['status'] ?? '';

        if (updateRequestStatus($request_id, $status)) {
            setFlashMessage('success', 'Request status updated successfully');
        } else {
            setFlashMessage('error', 'Failed to update request status');
        }
        redirect('collector');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collector Dashboard - Waste Management System</title>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .profile-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
            <a href="<?= url('collector') ?>" class="nav-link active">
                <i class='bx bx-dashboard'></i> Dashboard
            </a>
            <a href="<?= url('collector/profile') ?>" class="nav-link">
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
                            confirmButtonColor: "<?= $flash['type'] === 'error' ? '#d33' : '#3085d6' ?>",
                        });
                    <?php endif; ?>
                });
            </script>

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
                            <h4 class="mb-1"><?= htmlspecialchars($collector['name']) ?></h4>
                            <p class="text-muted mb-0">
                                <i class='bx bx-envelope'></i> <?= htmlspecialchars($collector['email']) ?>
                                <?php if (!empty($collector['phone'] ?? '')): ?>
                                    &nbsp;&nbsp;
                                    <i class='bx bx-phone'></i> <?= htmlspecialchars($collector['phone']) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assigned Requests Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Assigned Requests</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped" id="requestsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Location</th>
                                <th>Contact</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($request = $assigned_requests->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= $request['id'] ?></td>
                                    <td><?= htmlspecialchars($request['client_name']) ?></td>
                                    <td><?= htmlspecialchars($request['client_address']) ?></td>
                                    <td><?= htmlspecialchars($request['client_phone']) ?></td>
                                    <td><?= formatDate($request['pickup_date']) ?></td>
                                    <td><?= getStatusBadge($request['status']) ?></td>
                                    <td>
                                        <?php if ($request['status'] == 'assigned'): ?>
                                            <button type="button" class="btn btn-sm btn-success" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#updateStatusModal"
                                                    data-request-id="<?= $request['id'] ?>">
                                                Mark as Completed
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

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="request_id" id="requestIdInput">
                    <input type="hidden" name="status" value="completed">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Completion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to mark this request as completed?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-success">Confirm Completion</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    $(document).ready(function () {
        // Ensure DataTable doesn't throw reinitialization error
        if ($.fn.DataTable.isDataTable('#requestsTable')) {
            $('#requestsTable').DataTable().clear().destroy();
        }

        $('#requestsTable').DataTable({
            order: [[4, 'asc']],
            pageLength: 10,
            responsive: true,
            language: {
                search: "Search requests:",
                lengthMenu: "Show _MENU_ requests per page",
                info: "Showing _START_ to _END_ of _TOTAL_ requests",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });

        // Modal setup
        $('#updateStatusModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var requestId = button.data('request-id');
            $('#requestIdInput').val(requestId);
        });
    });
    </script>
</body>
</html>
