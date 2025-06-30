<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

// Require admin role
requireRole('admin');

// Handle payment status update
if (isPost() && isset($_POST['update_payment'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $payment_id = $_POST['payment_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        
        if (empty($payment_id) || empty($status)) {
            setFlashMessage('error', 'Invalid payment data');
        } else {
            if (updatePaymentStatus($payment_id, $status)) {
                setFlashMessage('success', 'Payment status updated successfully');
            } else {
                setFlashMessage('error', 'Failed to update payment status');
            }
        }
    } else {
        setFlashMessage('error', 'Invalid request token');
    }
} else {
    setFlashMessage('error', 'Invalid request method');
}

// Redirect back to admin dashboard
redirect('admin');
?> 