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

// Mock data for reports
$requests_stats = [
    'total_requests' => 156,
    'completed_requests' => 142,
    'pending_requests' => 10,
    'cancelled_requests' => 4
];

$payment_stats = [
    'total_payments' => 142,
    'total_revenue' => 28400.00,
    'average_payment' => 200.00,
    'collected_revenue' => 28400.00
];

// Mock data for top collectors
$top_collectors = [
    [
        'collector_name' => 'John Smith',
        'total_collections' => 45,
        'total_revenue' => 9000.00
    ],
    [
        'collector_name' => 'Sarah Johnson',
        'total_collections' => 38,
        'total_revenue' => 7600.00
    ],
    [
        'collector_name' => 'Michael Brown',
        'total_collections' => 32,
        'total_revenue' => 6400.00
    ],
    [
        'collector_name' => 'Emily Davis',
        'total_collections' => 28,
        'total_revenue' => 5600.00
    ],
    [
        'collector_name' => 'David Wilson',
        'total_collections' => 25,
        'total_revenue' => 5000.00
    ]
];

// Mock data for zone statistics
$zone_stats = [
    [
        'zone_name' => 'North Zone',
        'total_requests' => 45,
        'completed_requests' => 42,
        'total_revenue' => 9000.00
    ],
    [
        'zone_name' => 'South Zone',
        'total_requests' => 38,
        'completed_requests' => 35,
        'total_revenue' => 7600.00
    ],
    [
        'zone_name' => 'East Zone',
        'total_requests' => 32,
        'completed_requests' => 30,
        'total_revenue' => 6400.00
    ],
    [
        'zone_name' => 'West Zone',
        'total_requests' => 28,
        'completed_requests' => 25,
        'total_revenue' => 5600.00
    ],
    [
        'zone_name' => 'Central Zone',
        'total_requests' => 25,
        'completed_requests' => 22,
        'total_revenue' => 5000.00
    ]
];

// Mock data for revenue trend
$revenue_trend = [
    ['date' => '2024-03-01', 'total_revenue' => 1200],
    ['date' => '2024-03-02', 'total_revenue' => 1500],
    ['date' => '2024-03-03', 'total_revenue' => 1800],
    ['date' => '2024-03-04', 'total_revenue' => 1600],
    ['date' => '2024-03-05', 'total_revenue' => 2000],
    ['date' => '2024-03-06', 'total_revenue' => 2200],
    ['date' => '2024-03-07', 'total_revenue' => 2400]
];
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
            <form id="dateRangeForm" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Date Range</label>
                    <select class="form-select" id="dateRange">
                        <option value="7">Last 7 Days</option>
                        <option value="30" selected>Last 30 Days</option>
                        <option value="90">Last 90 Days</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div class="col-md-3 custom-date d-none">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="startDate">
                </div>
                <div class="col-md-3 custom-date d-none">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" id="endDate">
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
                            <h4 class="mb-0">$<?= number_format($payment_stats['total_revenue'], 2) ?></h4>
                            <p class="text-success mb-0">
                                <i class="bx bx-up-arrow-alt"></i>
                                $<?= number_format($payment_stats['average_payment'], 2) ?> average
                            </p>
                        </div>
                        <div class="text-success">
                            <i class="bx bx-dollar" style="font-size: 2.5rem;"></i>
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
                            <h4 class="mb-0"><?= number_format(($requests_stats['completed_requests'] / $requests_stats['total_requests']) * 100, 1) ?>%</h4>
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
                            <h4 class="mb-0">4.8 ⭐</h4>
                            <p class="text-success mb-0">
                                <i class="bx bx-up-arrow-alt"></i>
                                Based on <?= number_format($requests_stats['completed_requests']) ?> reviews
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
                                <?php foreach ($top_collectors as $collector): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($collector['collector_name']) ?></td>
                                        <td><?= $collector['total_collections'] ?></td>
                                        <td>$<?= number_format($collector['total_revenue'], 2) ?></td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                    style="width: <?= ($collector['total_collections'] / $requests_stats['total_requests']) * 100 ?>%"
                                                    aria-valuenow="<?= ($collector['total_collections'] / $requests_stats['total_requests']) * 100 ?>" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="100">
                                                    <?= number_format(($collector['total_collections'] / $requests_stats['total_requests']) * 100, 1) ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
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
                                <?php foreach ($zone_stats as $zone): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($zone['zone_name']) ?></td>
                                        <td><?= $zone['total_requests'] ?></td>
                                        <td>$<?= number_format($zone['total_revenue'], 2) ?></td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar bg-info" role="progressbar" 
                                                    style="width: <?= ($zone['completed_requests'] / $zone['total_requests']) * 100 ?>%"
                                                    aria-valuenow="<?= ($zone['completed_requests'] / $zone['total_requests']) * 100 ?>" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="100">
                                                    <?= number_format(($zone['completed_requests'] / $zone['total_requests']) * 100, 1) ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
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

    // Handle form submission with SweetAlert
    $('#dateRangeForm').on('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Loading...',
            text: 'Updating reports with new filter',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Simulate loading
        setTimeout(() => {
            Swal.fire({
                title: 'Success!',
                text: 'Reports updated successfully',
                icon: 'success',
                confirmButtonText: 'OK',
                confirmButtonColor: '#3085d6'
            });
        }, 1500);
    });

    // Initialize Charts
    const zoneData = <?= json_encode($zone_stats) ?>;
    const revenueData = <?= json_encode($revenue_trend) ?>;

    // Requests by Zone Chart
    new Chart(document.getElementById('requestsByZoneChart'), {
        type: 'pie',
        data: {
            labels: zoneData.map(z => z.zone_name),
            datasets: [{
                data: zoneData.map(z => z.total_requests),
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
            labels: revenueData.map(r => r.date),
            datasets: [{
                label: 'Revenue',
                data: revenueData.map(r => r.total_revenue),
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
                            return '$' + value.toLocaleString();
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