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
                            <th>Service Price</th>
                            <th>Assigned Collectors</th>
                            <th>Coverage Status</th>
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
                        
                        $has_zones = false;
                        $zone_rows = [];
                        
                        // Store all zone data first
                        while ($zone = $zones->fetch_assoc()) {
                            $has_zones = true;
                            $zone_rows[] = $zone;
                        }

                        if ($has_zones):
                            foreach ($zone_rows as $zone):
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
                                    <span class="fw-bold text-success">
                                        <?= formatCurrency($zone['price'] ?? 0) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $collector_count = 0;
                                    $collector_names = [];
                                    while ($collector = $collectors->fetch_assoc()) {
                                        $collector_count++;
                                        $collector_names[] = $collector['name'];
                                        echo '<span class="badge bg-info me-1 mb-1" title="' . htmlspecialchars($collector['name']) . '">' . 
                                             '<i class="bx bx-user"></i> ' . htmlspecialchars($collector['name']) . '</span>';
                                    }
                                    if ($collector_count == 0) {
                                        echo '<span class="text-muted">No collectors assigned</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    // Determine coverage status based on collector count
                                    if ($collector_count == 0) {
                                        echo '<span class="badge bg-danger"><i class="bx bx-x"></i> No Coverage</span>';
                                    } elseif ($collector_count == 1) {
                                        echo '<span class="badge bg-warning"><i class="bx bx-user"></i> Single Collector</span>';
                                    } elseif ($collector_count <= 3) {
                                        echo '<span class="badge bg-success"><i class="bx bx-group"></i> Good Coverage (' . $collector_count . ')</span>';
                                    } else {
                                        echo '<span class="badge bg-primary"><i class="bx bx-crown"></i> Full Coverage (' . $collector_count . ')</span>';
                                    }
                                    ?>
                                </td>
                                <td><?= formatDate($zone['created_at']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-zone" 
                                            data-id="<?= $zone['id'] ?>" 
                                            data-name="<?= htmlspecialchars($zone['name']) ?>" 
                                            data-description="<?= htmlspecialchars($zone['description'] ?? '') ?>" 
                                            data-price="<?= number_format((float)($zone['price'] ?? 0), 2, '.', '') ?>" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editZoneModal">
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
                        <?php 
                            endforeach;
                        else:
                        ?>
                        <tr>
                            <td colspan="8" class="border-0 p-0">
                                <?php
                                $type = 'zones';
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
                    <div class="mb-3">
                        <label class="form-label">Service Price (PHP)</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" name="price" step="0.01" min="0" value="150.00" required>
                        </div>
                        <div class="form-text">Set the standard waste collection service price for this zone.</div>
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
                    <div class="mb-3">
                        <label class="form-label">Service Price (PHP)</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" name="price" id="editZonePrice" step="0.01" min="0" required>
                        </div>
                        <div class="form-text">Update the waste collection service price for this zone.</div>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="assignCollectorsForm" method="POST" action="<?= url('admin/zones/assign') ?>">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="zone_id" id="assignZoneId">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bx bx-group"></i> Assign Multiple Collectors to Zone
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle"></i>
                        <strong>Multi-Collector Assignment:</strong> 
                        Large zones can have multiple collectors to ensure efficient coverage. 
                        Select all collectors you want to assign to this zone.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bx bx-user-plus"></i> Select Collectors 
                            <span class="text-muted">(Hold Ctrl/Cmd for multiple selection)</span>
                        </label>
                        <select class="form-select select2" name="collector_ids[]" id="assignCollectors" multiple required>
                            <?php
                            $collectorQuery = "
                                SELECT u.id, u.name, u.phone,
                                COUNT(zc.zone_id) as assigned_zones
                                FROM users u 
                                LEFT JOIN zone_collectors zc ON u.id = zc.collector_id
                                WHERE u.role = 'collector' 
                                GROUP BY u.id, u.name, u.phone
                                ORDER BY u.name
                            ";
                            $collectors = $conn->query($collectorQuery);
                            while ($collector = $collectors->fetch_assoc()) {
                                $zone_count = $collector['assigned_zones'];
                                $status = $zone_count == 0 ? ' (Available)' : " ({$zone_count} zones)";
                                echo '<option value="' . $collector['id'] . '">' . 
                                     htmlspecialchars($collector['name']) . $status . '</option>';
                            }
                            ?>
                        </select>
                        <div class="form-text">
                            <i class="bx bx-lightbulb"></i> 
                            Tip: Assigning multiple collectors ensures better coverage and faster response times
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Current Assignment</h6>
                                    <div id="currentCollectors">Loading...</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Coverage Recommendation</h6>
                                    <div class="text-muted">
                                        <i class="bx bx-target-lock"></i><br>
                                        <small>
                                            • 1 collector: Basic coverage<br>
                                            • 2-3 collectors: Good coverage<br>
                                            • 4+ collectors: Full coverage
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-check"></i> Update Assignments
                    </button>
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
            const zoneName = $(this).data('name');
            const zoneDescription = $(this).data('description');
            const zonePrice = $(this).data('price');
            
            // Populate form with zone data from data attributes
            $('#editZoneId').val(zoneId);
            $('#editZoneName').val(zoneName || '');
            $('#editZoneDescription').val(zoneDescription || '');
            $('#editZonePrice').val(parseFloat(zonePrice || 0).toFixed(2));
            
            console.log('Zone data loaded from data attributes:', {
                id: zoneId,
                name: zoneName,
                description: zoneDescription,
                price: zonePrice
            });
        });

        // Handle Assign Collectors
        $('.assign-collectors').click(function() {
            const zoneId = $(this).data('id');
            $('#assignZoneId').val(zoneId);
            
            // Fetch current collectors for this zone
            $.get(`<?= url('admin/zones/collectors') ?>/${zoneId}`, function(data) {
                // Set selected values in dropdown
                $('#assignCollectors').val(data.map(c => c.id)).trigger('change');
                
                // Update current collectors display
                if (data.length > 0) {
                    let collectorsHtml = '';
                    data.forEach(function(collector) {
                        collectorsHtml += `<span class="badge bg-info me-1 mb-1">
                            <i class="bx bx-user"></i> ${collector.name}
                        </span>`;
                    });
                    collectorsHtml += `<br><small class="text-success">
                        <i class="bx bx-check"></i> ${data.length} collector(s) currently assigned
                    </small>`;
                    $('#currentCollectors').html(collectorsHtml);
                } else {
                    $('#currentCollectors').html(`
                        <span class="text-muted">
                            <i class="bx bx-user-x"></i> No collectors assigned
                        </span>`);
                }
            }).fail(function() {
                $('#currentCollectors').html(`
                    <span class="text-danger">
                        <i class="bx bx-error"></i> Failed to load
                    </span>`);
            });
        });

        // Enhanced form submission with better feedback
        $('#assignCollectorsForm').on('submit', function(e) {
            const selectedCollectors = $('#assignCollectors').val();
            if (!selectedCollectors || selectedCollectors.length === 0) {
                e.preventDefault();
                Swal.fire({
                    title: 'No Collectors Selected',
                    text: 'Please select at least one collector to assign to this zone.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return false;
            }

            // Show confirmation for multiple collectors
            if (selectedCollectors.length > 1) {
                e.preventDefault();
                Swal.fire({
                    title: 'Confirm Multiple Assignment',
                    text: `You are about to assign ${selectedCollectors.length} collectors to this zone. This will replace any existing assignments.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Assign All',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Submit form manually
                        this.submit();
                    }
                });
                return false;
            }
        });

        // Update select2 placeholder and options
        $('#assignCollectors').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Choose multiple collectors for this zone...',
            allowClear: true
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