<?php
/**
 * Modern Empty State Component
 * 
 * @param string $type - The type of empty state (requests, payments, zones, users, etc.)
 * @param string $title - Custom title for the empty state
 * @param string $description - Custom description for the empty state
 * @param string $icon - Custom Boxicons icon class
 * @param string $action_text - Text for the action button
 * @param string $action_url - URL for the action button
 * @param string $action_modal - Modal target for the action button
 * @param string $size - Size of empty state (normal, large, small)
 */

$empty_states = [
    'pending_payments' => [
        'icon' => 'bx-credit-card',
        'title' => 'No Pending Payments',
        'description' => 'All payments have been processed! No pending approvals at the moment.',
        'action_text' => 'View All Payments',
        'action_url' => url('admin/payments'),
        'color' => 'success'
    ],
    'pending_requests' => [
        'icon' => 'bx-clipboard',
        'title' => 'No Pending Requests',
        'description' => 'All waste collection requests have been assigned to collectors. Great job!',
        'action_text' => 'View All Requests',
        'action_url' => url('admin/requests'),
        'color' => 'info'
    ],
    'zones' => [
        'icon' => 'bx-map',
        'title' => 'No Zones Created',
        'description' => 'Start by creating zones to organize your waste collection areas effectively.',
        'action_text' => 'Create First Zone',
        'action_modal' => '#addZoneModal',
        'color' => 'primary'
    ],
    'users' => [
        'icon' => 'bx-user-plus',
        'title' => 'No Users Found',
        'description' => 'Add users to start managing your waste management system.',
        'action_text' => 'Add New User',
        'action_modal' => '#addUserModal',
        'color' => 'primary'
    ],
    'collectors' => [
        'icon' => 'bx-user',
        'title' => 'No Collectors Available',
        'description' => 'Add collectors to handle waste collection requests in different zones.',
        'action_text' => 'Add Collector',
        'action_url' => url('admin/users/add'),
        'color' => 'warning'
    ],
    'clients' => [
        'icon' => 'bx-group',
        'title' => 'No Clients Registered',
        'description' => 'No clients have registered for waste collection services yet.',
        'action_text' => 'View Registration',
        'action_url' => url('register'),
        'color' => 'info'
    ],
    'reports' => [
        'icon' => 'bx-bar-chart-alt-2',
        'title' => 'No Data Available',
        'description' => 'Generate reports once you have requests and payment data.',
        'action_text' => 'Refresh Data',
        'action_url' => '#',
        'color' => 'secondary'
    ],
    'transactions' => [
        'icon' => 'bx-receipt',
        'title' => 'No Transactions',
        'description' => 'Payment transactions will appear here once clients start making payments.',
        'action_text' => 'View Dashboard',
        'action_url' => url('admin'),
        'color' => 'primary'
    ],
    'assignments' => [
        'icon' => 'bx-user-check',
        'title' => 'No Assignments',
        'description' => 'Collector assignments will be displayed here once requests are assigned.',
        'action_text' => 'View Requests',
        'action_url' => url('admin/requests'),
        'color' => 'info'
    ]
];

// Set defaults
$type = $type ?? 'default';
$size = $size ?? 'normal';
$config = $empty_states[$type] ?? [];

$icon = $icon ?? $config['icon'] ?? 'bx-info-circle';
$title = $title ?? $config['title'] ?? 'No Data Available';
$description = $description ?? $config['description'] ?? 'There is no data to display at the moment.';
$action_text = $action_text ?? $config['action_text'] ?? null;
$action_url = $action_url ?? $config['action_url'] ?? null;
$action_modal = $action_modal ?? $config['action_modal'] ?? null;
$color = $config['color'] ?? 'secondary';

// Size classes
$size_classes = [
    'small' => 'py-3',
    'normal' => 'py-5',
    'large' => 'py-6'
];
$size_class = $size_classes[$size] ?? $size_classes['normal'];

// Icon sizes
$icon_sizes = [
    'small' => 'fs-1',
    'normal' => 'display-1',
    'large' => 'display-1'
];
$icon_size = $icon_sizes[$size] ?? $icon_sizes['normal'];
?>

<div class="empty-state text-center <?= $size_class ?>">
    <div class="empty-state-icon mb-4">
        <i class="bx <?= $icon ?> <?= $icon_size ?> text-<?= $color ?> opacity-50"></i>
    </div>
    
    <div class="empty-state-content">
        <h4 class="text-muted mb-2"><?= htmlspecialchars($title) ?></h4>
        <p class="text-muted mb-4 mx-auto" style="max-width: 400px;">
            <?= htmlspecialchars($description) ?>
        </p>
        
        <?php if ($action_text): ?>
            <div class="empty-state-action">
                <?php if ($action_modal): ?>
                    <button type="button" class="btn btn-<?= $color ?>" data-bs-toggle="modal" data-bs-target="<?= $action_modal ?>">
                        <i class="bx bx-plus"></i> <?= htmlspecialchars($action_text) ?>
                    </button>
                <?php elseif ($action_url): ?>
                    <a href="<?= $action_url ?>" class="btn btn-<?= $color ?>">
                        <i class="bx bx-arrow-back"></i> <?= htmlspecialchars($action_text) ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($size === 'large'): ?>
        <div class="empty-state-decoration mt-4">
            <div class="d-flex justify-content-center gap-2 opacity-25">
                <div class="bg-<?= $color ?> rounded-circle" style="width: 8px; height: 8px;"></div>
                <div class="bg-<?= $color ?> rounded-circle" style="width: 6px; height: 6px;"></div>
                <div class="bg-<?= $color ?> rounded-circle" style="width: 4px; height: 4px;"></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.empty-state {
    transition: all 0.3s ease;
}

.empty-state-icon i {
    transition: all 0.3s ease;
}

.empty-state:hover .empty-state-icon i {
    transform: scale(1.1);
}

.empty-state-action .btn {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.empty-state-action .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}
</style> 