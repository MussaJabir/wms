<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Set layout and page variables
$layout = 'admin';
$pageTitle = "Profile Settings";
$currentPage = 'profile';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    redirect('login');
}

// Get current user data
$user = $_SESSION['user'];

// Mock statistics
$mock_stats = [
    'total_collections' => 150,
    'average_rating' => 4.5,
    'total_requests' => 25,
    'total_spent' => 1250.00
];
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Profile Settings</h1>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                <i class="bx bx-printer"></i> Print Profile
            </button>
        </div>
    </div>

    <!-- Flash messages will be handled by SweetAlert -->
    <?php include APP_PATH . '/views/includes/flash_messages.php'; ?>

    <div class="row">
        <div class="col-md-4">
            <!-- Profile Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Profile Information</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="avatar-circle">
                            <span class="avatar-text"><?= strtoupper(substr($user['name'], 0, 2)) ?></span>
                        </div>
                    </div>
                    <h4 class="mb-1"><?= htmlspecialchars($user['name']) ?></h4>
                    <p class="text-muted mb-3"><?= ucfirst($user['role']) ?></p>
                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="bx bx-key"></i> Change Password
                        </button>
                        <button type="button" class="btn btn-danger" id="deactivateAccount">
                            <i class="bx bx-trash"></i> Deactivate
                        </button>
                    </div>
                </div>
            </div>

            <!-- Account Statistics -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Account Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted">Member Since</label>
                        <p class="mb-0"><?= date('F j, Y', strtotime($user['created_at'] ?? 'now')) ?></p>
                    </div>
                    <?php if ($user['role'] === 'collector'): ?>
                    <div class="mb-3">
                        <label class="text-muted">Total Collections</label>
                        <p class="mb-0"><?= number_format($mock_stats['total_collections']) ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted">Average Rating</label>
                        <p class="mb-0"><?= number_format($mock_stats['average_rating'], 1) ?> ⭐</p>
                    </div>
                    <?php elseif ($user['role'] === 'client'): ?>
                    <div class="mb-3">
                        <label class="text-muted">Total Requests</label>
                        <p class="mb-0"><?= number_format($mock_stats['total_requests']) ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted">Total Spent</label>
                        <p class="mb-0">$<?= number_format($mock_stats['total_spent'], 2) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- Profile Information Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Edit Profile Information</h5>
                </div>
                <div class="card-body">
                    <form id="profileForm" method="POST" action="<?= url('profile/update') ?>">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Activity</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?= date('M d, Y', strtotime('-2 days')) ?></td>
                                    <td>Profile Updated</td>
                                    <td><span class="badge bg-success">Completed</span></td>
                                </tr>
                                <tr>
                                    <td><?= date('M d, Y', strtotime('-5 days')) ?></td>
                                    <td>Password Changed</td>
                                    <td><span class="badge bg-success">Completed</span></td>
                                </tr>
                                <tr>
                                    <td><?= date('M d, Y', strtotime('-1 week')) ?></td>
                                    <td>Account Settings Updated</td>
                                    <td><span class="badge bg-success">Completed</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm" method="POST" action="<?= url('profile/change-password') ?>">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="savePassword">
                    <i class="bx bx-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.avatar-text {
    color: white;
    font-size: 2rem;
    font-weight: bold;
}
</style>

<script>
$(document).ready(function() {
    // Handle profile form submission with SweetAlert
    $('#profileForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Show loading state
        submitBtn.html('<i class="bx bx-loader-alt bx-spin"></i> Saving...').prop('disabled', true);
        
        $.post($(this).attr('action'), formData, function(response) {
            if (response.success) {
                Swal.fire({
                    title: 'Success!',
                    text: response.message || 'Profile updated successfully',
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#3085d6'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: response.message || 'Failed to update profile',
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

    // Handle password change with SweetAlert
    $('#savePassword').on('click', function() {
        const form = $('#changePasswordForm');
        const formData = form.serialize();
        const submitBtn = $(this);
        const originalText = submitBtn.html();
        
        // Validate passwords match
        const newPassword = form.find('input[name="new_password"]').val();
        const confirmPassword = form.find('input[name="confirm_password"]').val();
        
        if (newPassword !== confirmPassword) {
            Swal.fire({
                title: 'Error!',
                text: 'New passwords do not match',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#d33'
            });
            return;
        }
        
        if (newPassword.length < 6) {
            Swal.fire({
                title: 'Error!',
                text: 'Password must be at least 6 characters long',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#d33'
            });
            return;
        }
        
        // Show loading state
        submitBtn.html('<i class="bx bx-loader-alt bx-spin"></i> Saving...').prop('disabled', true);
        
        $.post(form.attr('action'), formData, function(response) {
            if (response.success) {
                Swal.fire({
                    title: 'Success!',
                    text: response.message || 'Password changed successfully',
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#3085d6'
                }).then(() => {
                    $('#changePasswordModal').modal('hide');
                    form[0].reset();
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: response.message || 'Failed to change password',
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

    // Handle account deactivation with SweetAlert
    $('#deactivateAccount').on('click', function() {
        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone. Your account will be deactivated.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, deactivate it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Here you would typically make an AJAX call to deactivate the account
                Swal.fire(
                    'Deactivated!',
                    'Your account has been deactivated.',
                    'success'
                );
            }
        });
    });
});
</script> 