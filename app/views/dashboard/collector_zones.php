<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Set layout and page variables
$layout = 'admin';
$pageTitle = "Collector Zone Management";
$currentPage = 'collector_zones';

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
        <div>
            <h1 class="h3 mb-0">Collector Zone Management</h1>
            <p class="text-muted mb-0">Manage multiple collectors per zone for optimal coverage</p>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bulkAssignModal">
                <i class="bx bx-group"></i> Bulk Assignment
            </button>
            <a href="<?= url('admin/zones') ?>" class="btn btn-outline-secondary">
                <i class="bx bx-map"></i> Zone Management
            </a>
        </div>
    </div>

    <!-- Flash messages will be handled by SweetAlert -->
    <?php include APP_PATH . '/views/includes/flash_messages.php'; ?>

    <!-- Coverage Overview Cards -->
    <div class="row mb-4">
        <?php
        // Get zone coverage statistics
        global $conn;
        $coverage_stats = $conn->query("
            SELECT 
                z.id, z.name, z.description,
                COUNT(zc.collector_id) as collector_count,
                GROUP_CONCAT(u.name SEPARATOR ', ') as collector_names
            FROM zones z
            LEFT JOIN zone_collectors zc ON z.id = zc.zone_id
            LEFT JOIN users u ON zc.collector_id = u.id
            GROUP BY z.id, z.name, z.description
            ORDER BY collector_count DESC, z.name
        ");

        $total_zones = 0;
        $well_covered = 0;
        $under_covered = 0;
        $no_coverage = 0;

        while ($zone = $coverage_stats->fetch_assoc()):
            $total_zones++;
            $collector_count = (int)$zone['collector_count'];
            
            if ($collector_count == 0) {
                $no_coverage++;
                $status_class = 'danger';
                $status_text = 'No Coverage';
                $status_icon = 'bx-x';
            } elseif ($collector_count == 1) {
                $under_covered++;
                $status_class = 'warning';
                $status_text = 'Single Collector';
                $status_icon = 'bx-user';
            } else {
                $well_covered++;
                $status_class = 'success';
                $status_text = 'Multiple Collectors';
                $status_icon = 'bx-group';
            }
        ?>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-<?= $status_class ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-<?= $status_class ?> mb-1"><?= htmlspecialchars($zone['name']) ?></h6>
                            <p class="mb-1 small text-muted"><?= htmlspecialchars($zone['description']) ?></p>
                            <span class="badge bg-<?= $status_class ?>">
                                <i class="bx <?= $status_icon ?>"></i> <?= $status_text ?>
                            </span>
                        </div>
                        <div class="text-<?= $status_class ?>">
                            <h4 class="mb-0"><?= $collector_count ?></h4>
                            <small>collectors</small>
                        </div>
                    </div>
                    <?php if ($collector_count > 0): ?>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="bx bx-users"></i> <?= htmlspecialchars($zone['collector_names']) ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <!-- Coverage Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h4><?= $total_zones ?></h4>
                    <p class="mb-0">Total Zones</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h4><?= $well_covered ?></h4>
                    <p class="mb-0">Well Covered</p>
                    <small>(2+ collectors)</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h4><?= $under_covered ?></h4>
                    <p class="mb-0">Under Covered</p>
                    <small>(1 collector)</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h4><?= $no_coverage ?></h4>
                    <p class="mb-0">No Coverage</p>
                    <small>(0 collectors)</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Assignment Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Zone-Collector Assignment Matrix</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="assignmentTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Zone</th>
                            <th>Collectors Assigned</th>
                            <th>Coverage Level</th>
                            <th>Active Requests</th>
                            <th>Total Revenue</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Reset the query for table display
                        $coverage_stats = $conn->query("
                            SELECT 
                                z.id, z.name, z.description, z.price,
                                COUNT(zc.collector_id) as collector_count,
                                GROUP_CONCAT(u.name SEPARATOR ', ') as collector_names,
                                COUNT(r.id) as active_requests,
                                COALESCE(SUM(p.amount), 0) as total_revenue
                            FROM zones z
                            LEFT JOIN zone_collectors zc ON z.id = zc.zone_id
                            LEFT JOIN users u ON zc.collector_id = u.id
                            LEFT JOIN requests r ON z.name = r.location AND r.status = 'pending'
                            LEFT JOIN payments p ON r.id = p.request_id AND p.status = 'completed'
                            GROUP BY z.id, z.name, z.description, z.price
                            ORDER BY collector_count DESC, z.name
                        ");

                        while ($zone = $coverage_stats->fetch_assoc()):
                            $collector_count = (int)$zone['collector_count'];
                        ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($zone['name']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($zone['description']) ?></small><br>
                                    <span class="text-success fw-bold"><?= formatCurrency($zone['price']) ?></span>
                                </td>
                                <td>
                                    <?php if ($collector_count > 0): ?>
                                        <?php
                                        $names = explode(', ', $zone['collector_names']);
                                        foreach ($names as $name) {
                                            echo '<span class="badge bg-info me-1 mb-1"><i class="bx bx-user"></i> ' . htmlspecialchars($name) . '</span>';
                                        }
                                        ?>
                                        <br><small class="text-success"><?= $collector_count ?> collector(s)</small>
                                    <?php else: ?>
                                        <span class="text-muted">No collectors assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    if ($collector_count == 0) {
                                        echo '<span class="badge bg-danger"><i class="bx bx-x"></i> No Coverage</span>';
                                    } elseif ($collector_count == 1) {
                                        echo '<span class="badge bg-warning"><i class="bx bx-user"></i> Basic</span>';
                                    } elseif ($collector_count <= 3) {
                                        echo '<span class="badge bg-success"><i class="bx bx-group"></i> Good</span>';
                                    } else {
                                        echo '<span class="badge bg-primary"><i class="bx bx-crown"></i> Excellent</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $zone['active_requests'] > 0 ? 'warning' : 'secondary' ?>">
                                        <?= $zone['active_requests'] ?> requests
                                    </span>
                                </td>
                                <td><?= formatCurrency($zone['total_revenue']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary manage-collectors" 
                                            data-zone-id="<?= $zone['id'] ?>" 
                                            data-zone-name="<?= htmlspecialchars($zone['name']) ?>">
                                        <i class="bx bx-cog"></i> Manage
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

<!-- Quick Manage Modal -->
<div class="modal fade" id="quickManageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bx bx-cog"></i> Quick Manage: <span id="modalZoneName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Currently Assigned</h6>
                        <div id="currentAssigned" class="mb-3"></div>
                    </div>
                    <div class="col-md-6">
                        <h6>Available Collectors</h6>
                        <div id="availableCollectors"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" id="fullManageLink" class="btn btn-primary">
                    <i class="bx bx-edit"></i> Full Management
                </a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#assignmentTable').DataTable({
        order: [[2, 'asc']], // Order by coverage level
        pageLength: 10,
        responsive: true
    });

    // Handle quick manage
    $('.manage-collectors').click(function() {
        const zoneId = $(this).data('zone-id');
        const zoneName = $(this).data('zone-name');
        
        $('#modalZoneName').text(zoneName);
        $('#fullManageLink').attr('href', `<?= url('admin/zones') ?>?zone=${zoneId}`);
        
        // Load current assignments and available collectors
        loadQuickManageData(zoneId);
        
        $('#quickManageModal').modal('show');
    });

    function loadQuickManageData(zoneId) {
        // Load current assigned collectors
        $.get(`<?= url('admin/zones/collectors') ?>/${zoneId}`, function(data) {
            if (data.length > 0) {
                let html = '';
                data.forEach(function(collector) {
                    html += `<div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-info">
                            <i class="bx bx-user"></i> ${collector.name}
                        </span>
                        <button class="btn btn-sm btn-outline-danger remove-collector" 
                                data-zone-id="${zoneId}" 
                                data-collector-id="${collector.id}">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>`;
                });
                $('#currentAssigned').html(html);
            } else {
                $('#currentAssigned').html('<span class="text-muted">No collectors assigned</span>');
            }
        });

        // Load available collectors
        $.get(`<?= url('admin/users') ?>?role=collector&available=true`, function(data) {
            // This would need a new endpoint, for now show placeholder
            $('#availableCollectors').html(`
                <div class="text-muted">
                    <i class="bx bx-info-circle"></i> 
                    Use "Full Management" for detailed assignment options
                </div>
            `);
        });
    }
});
</script>

<?php
$content = ob_get_clean();
include APP_PATH . '/views/layouts/' . $layout . '.php';
?> 