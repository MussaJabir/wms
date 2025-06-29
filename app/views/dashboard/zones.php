<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Set layout and page variables
$layout = 'admin';
$pageTitle = "Zone Management";
$currentPage = 'zones';

// Check if user is admin
if (!isset($_SESSION['user'])) {
    redirect('login');
} elseif ($_SESSION['user']['role'] !== 'admin') {
    redirect($_SESSION['user']['role']);
}

// Start content
ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Zone Management</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addZoneModal">
            <i class="bx bx-plus"></i> Add New Zone
        </button>
    </div>

    <!-- Flash messages will be handled by SweetAlert -->
    <?php include APP_PATH . '/views/includes/flash_messages.php'; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="zonesTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Zone Name</th>
                            <th>Description</th>
                            <th>Assigned Collectors</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        global $conn;
                        // Get all zones
                        $query = "SELECT * FROM zones ORDER BY id DESC";
                        $zones = $conn->query($query);

                        while ($zone = $zones->fetch_assoc()):
                            // Get collectors for this zone
                            $collectorQuery = "SELECT u.* FROM users u 
                                             INNER JOIN zone_collectors zc ON u.id = zc.collector_id 
                                             WHERE zc.zone_id = ?";
                            $stmt = $conn->prepare($collectorQuery);
                            $stmt->bind_param("i", $zone['id']);
                            $stmt->execute();
                            $collectors = $stmt->get_result();
                        ?>
                            <tr>
                                <td><?= $zone['id'] ?></td>
                                <td><?= htmlspecialchars($zone['name']) ?></td>
                                <td><?= htmlspecialchars($zone['description']) ?></td>
                                <td>
                                    <?php
                                    while ($collector = $collectors->fetch_assoc()) {
                                        echo '<span class="badge bg-info me-1">' . htmlspecialchars($collector['name']) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td><?= formatDate($zone['created_at']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-zone" data-id="<?= $zone['id'] ?>" data-bs-toggle="modal" data-bs-target="#editZoneModal">
                                        <i class="bx bx-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-info assign-collectors" data-id="<?= $zone['id'] ?>" data-bs-toggle="modal" data-bs-target="#assignCollectorsModal">
                                        <i class="bx bx-user-plus"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-zone" data-id="<?= $zone['id'] ?>">
                                        <i class="bx bx-trash"></i>
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

<!-- Add Zone Modal -->
<div class="modal fade" id="addZoneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addZoneForm" method="POST" action="<?= url('admin/zones/add') ?>">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Zone</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Zone Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Zone</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Zone Modal -->
<div class="modal fade" id="editZoneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editZoneForm" method="POST" action="<?= url('admin/zones/edit') ?>">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="zone_id" id="editZoneId">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Zone</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Zone Name</label>
                        <input type="text" class="form-control" name="name" id="editZoneName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="editZoneDescription" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Collectors Modal -->
<div class="modal fade" id="assignCollectorsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="assignCollectorsForm" method="POST" action="<?= url('admin/zones/assign') ?>">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="zone_id" id="assignZoneId">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Collectors to Zone</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Collectors</label>
                        <select class="form-select select2" name="collector_ids[]" id="assignCollectors" multiple required>
                            <?php
                            $collectorQuery = "SELECT id, name FROM users WHERE role = 'collector' ORDER BY name";
                            $collectors = $conn->query($collectorQuery);
                            while ($collector = $collectors->fetch_assoc()) {
                                echo '<option value="' . $collector['id'] . '">' . htmlspecialchars($collector['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Collectors</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#zonesTable').DataTable({
            order: [
                [0, 'desc']
            ],
            responsive: true
        });

        // Initialize Select2 for multiple select
        $('#assignCollectors').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });

        // Handle Edit Zone
        $('.edit-zone').click(function() {
            const zoneId = $(this).data('id');
            // Fetch zone data and populate form
            $.get(`<?= url('admin/zones/get') ?>/${zoneId}`, function(data) {
                $('#editZoneId').val(data.id);
                $('#editZoneName').val(data.name);
                $('#editZoneDescription').val(data.description);
            });
        });

        // Handle Assign Collectors
        $('.assign-collectors').click(function() {
            const zoneId = $(this).data('id');
            $('#assignZoneId').val(zoneId);
            // Fetch current collectors for this zone
            $.get(`<?= url('admin/zones/collectors') ?>/${zoneId}`, function(data) {
                $('#assignCollectors').val(data.map(c => c.id)).trigger('change');
            });
        });

        // Handle Delete Zone with SweetAlert
        $('.delete-zone').click(function() {
            const zoneId = $(this).data('id');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(`<?= url('admin/zones/delete') ?>`, {
                        zone_id: zoneId,
                        csrf_token: '<?= generateCSRFToken() ?>'
                    }, function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Deleted!',
                                'Zone has been deleted.',
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                'Failed to delete zone.',
                                'error'
                            );
                        }
                    });
                }
            });
        });
    });
</script>