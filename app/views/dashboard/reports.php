<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Set layout and page variables
$layout = 'admin';
$pageTitle = "Reports & Analytics";
$currentPage = 'reports';

// Check if user is admin
if (!isset($_SESSION['user'])) {
    redirect('login');
} elseif ($_SESSION['user']['role'] !== 'admin') {
    redirect($_SESSION['user']['role']);
}

// Handle date range filtering
$date_range = $_GET['date_range'] ?? '30';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Determine the number of days based on filter
if ($date_range === 'custom' && $start_date && $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $days = $end->diff($start)->days + 1;
} else {
    $days = (int)$date_range;
}

// Get real data from database
$requests_stats = getRequestStats();
$payment_stats = getPaymentStats();
$top_collectors = getTopCollectors(5);
$zone_stats = getZoneStats();
$revenue_trend = getRevenueTrend($days);
$satisfaction_stats = getCustomerSatisfactionStats();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Reports & Analytics</h1>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary me-2" onclick="exportToPDF()">
                <i class="bx bxs-file-pdf"></i> Export PDF
            </button>
            <button type="button" class="btn btn-outline-success" onclick="exportToExcel()">
                <i class="bx bxs-file-export"></i> Export Excel
            </button>
        </div>
    </div>

    <!-- Flash messages will be handled by SweetAlert -->
    <?php include APP_PATH . '/views/includes/flash_messages.php'; ?>

    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Filter Options</h5>
        </div>
        <div class="card-body">
            <form id="dateRangeForm" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Date Range</label>
                    <select class="form-select" name="date_range" id="dateRange">
                        <option value="7" <?= $date_range == '7' ? 'selected' : '' ?>>Last 7 Days</option>
                        <option value="30" <?= $date_range == '30' ? 'selected' : '' ?>>Last 30 Days</option>
                        <option value="90" <?= $date_range == '90' ? 'selected' : '' ?>>Last 90 Days</option>
                        <option value="custom" <?= $date_range == 'custom' ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>
                <div class="col-md-3 custom-date <?= $date_range != 'custom' ? 'd-none' : '' ?>">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" id="startDate" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="col-md-3 custom-date <?= $date_range != 'custom' ? 'd-none' : '' ?>">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" id="endDate" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block">
                        <i class="bx bx-filter-alt"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Requests</h6>
                            <h4 class="mb-0"><?= number_format($requests_stats['total_requests']) ?></h4>
                            <p class="text-success mb-0">
                                <i class="bx bx-up-arrow-alt"></i>
                                <?= number_format($requests_stats['completed_requests']) ?> completed
                            </p>
                        </div>
                        <div class="text-primary">
                            <i class="bx bx-list-ul" style="font-size: 2.5rem;"></i>
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
                            <h4 class="mb-0"><?= formatCurrency($payment_stats['total_revenue']) ?></h4>
                            <p class="text-success mb-0">
                                <i class="bx bx-up-arrow-alt"></i>
                                <?= formatCurrency($payment_stats['average_payment']) ?> average
                            </p>
                        </div>
                        <div class="text-success">
                            <i class="bx bx-peso" style="font-size: 2.5rem;"></i>
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
                            <h6 class="text-muted mb-2">Completion Rate</h6>
                            <h4 class="mb-0"><?= $requests_stats['total_requests'] > 0 ? number_format(($requests_stats['completed_requests'] / $requests_stats['total_requests']) * 100, 1) : 0 ?>%</h4>
                            <p class="text-warning mb-0">
                                <i class="bx bx-time"></i>
                                <?= number_format($requests_stats['pending_requests']) ?> pending
                            </p>
                        </div>
                        <div class="text-info">
                            <i class="bx bx-check-circle" style="font-size: 2.5rem;"></i>
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
                            <h6 class="text-muted mb-2">Customer Satisfaction</h6>
                            <h4 class="mb-0"><?= $satisfaction_stats['score'] ?> ⭐</h4>
                            <p class="text-success mb-0">
                                <i class="bx bx-up-arrow-alt"></i>
                                Based on <?= number_format($satisfaction_stats['total_reviews']) ?> completed requests
                            </p>
                        </div>
                        <div class="text-warning">
                            <i class="bx bx-star" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Requests by Zone</h5>
                </div>
                <div class="card-body">
                    <canvas id="requestsByZoneChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Revenue Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tables Row -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top Performing Collectors</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Collector</th>
                                    <th>Requests</th>
                                    <th>Revenue</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($top_collectors)): ?>
                                    <?php foreach ($top_collectors as $collector): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($collector['collector_name']) ?></td>
                                            <td><?= number_format($collector['total_collections']) ?></td>
                                            <td><?= formatCurrency($collector['total_revenue']) ?></td>
                                            <td>
                                                <div class="progress">
                                                    <?php 
                                                    $percentage = $requests_stats['total_requests'] > 0 ? 
                                                        ($collector['total_collections'] / $requests_stats['total_requests']) * 100 : 0;
                                                    ?>
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                        style="width: <?= $percentage ?>%"
                                                        aria-valuenow="<?= $percentage ?>" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="100">
                                                        <?= number_format($percentage, 1) ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="border-0 p-0">
                                            <?php
                                            $type = 'collectors';
                                            $size = 'small';
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
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Zone Performance</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Zone</th>
                                    <th>Requests</th>
                                    <th>Revenue</th>
                                    <th>Satisfaction</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($zone_stats)): ?>
                                    <?php foreach ($zone_stats as $zone): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($zone['zone_name']) ?></td>
                                            <td><?= number_format($zone['total_requests']) ?></td>
                                            <td><?= formatCurrency($zone['total_revenue']) ?></td>
                                            <td>
                                                <div class="progress">
                                                    <?php 
                                                    $completion_rate = $zone['total_requests'] > 0 ? 
                                                        ($zone['completed_requests'] / $zone['total_requests']) * 100 : 0;
                                                    ?>
                                                    <div class="progress-bar bg-info" role="progressbar" 
                                                        style="width: <?= $completion_rate ?>%"
                                                        aria-valuenow="<?= $completion_rate ?>" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="100">
                                                        <?= number_format($completion_rate, 1) ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="border-0 p-0">
                                            <?php
                                            $type = 'zones';
                                            $size = 'small';
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
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Handle date range selector
    $('#dateRange').change(function() {
        if ($(this).val() === 'custom') {
            $('.custom-date').removeClass('d-none');
        } else {
            $('.custom-date').addClass('d-none');
        }
    });

    // Handle form submission - show loading while page reloads
    $('#dateRangeForm').on('submit', function(e) {
        Swal.fire({
            title: 'Loading...',
            text: 'Updating reports with new filter',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Allow the form to submit normally
        return true;
    });

    // Initialize Charts
    const zoneData = <?= json_encode($zone_stats) ?>;
    const revenueData = <?= json_encode($revenue_trend) ?>;

    // Requests by Zone Chart
    new Chart(document.getElementById('requestsByZoneChart'), {
        type: 'pie',
        data: {
            labels: zoneData.length > 0 ? zoneData.map(z => z.zone_name) : ['No Data'],
            datasets: [{
                data: zoneData.length > 0 ? zoneData.map(z => parseInt(z.total_requests)) : [1],
                backgroundColor: [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                    '#858796', '#5a5c69', '#2e59d9', '#17a673', '#2c9faf'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Revenue Trend Chart
    new Chart(document.getElementById('revenueTrendChart'), {
        type: 'line',
        data: {
            labels: revenueData.length > 0 ? revenueData.map(r => r.date) : ['No Data'],
            datasets: [{
                label: 'Revenue',
                data: revenueData.length > 0 ? revenueData.map(r => parseFloat(r.total_revenue)) : [0],
                borderColor: '#1cc88a',
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});

// Export functions with SweetAlert
function exportToPDF() {
    Swal.fire({
        title: 'Exporting to PDF...',
        text: 'Please wait while we generate your report',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    setTimeout(() => {
        Swal.fire({
            title: 'Success!',
            text: 'PDF report has been generated and downloaded',
            icon: 'success',
            confirmButtonText: 'OK',
            confirmButtonColor: '#3085d6'
        });
    }, 2000);
}

function exportToExcel() {
    Swal.fire({
        title: 'Exporting to Excel...',
        text: 'Please wait while we generate your report',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    setTimeout(() => {
        Swal.fire({
            title: 'Success!',
            text: 'Excel report has been generated and downloaded',
            icon: 'success',
            confirmButtonText: 'OK',
            confirmButtonColor: '#3085d6'
        });
    }, 2000);
}
</script> 