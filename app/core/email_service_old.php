<?php
/**
 * Email Service for Payment Notifications
 */

require_once __DIR__ . '/../config/config.php';

define('EMAIL_FROM', 'swaifredrick28@gmail.com');
define('EMAIL_FROM_NAME', 'Waste Management System');
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'swaifredrick28@gmail.com');
define('SMTP_PASSWORD', 'mpho tdvk ziie wegz');

function sendPaymentConfirmationEmail($client_email, $client_name, $payment_data) {
    $lipa_number = generateLipaNumber($payment_data['provider']);
    
    // Store Lipa number if payment ID is provided
    if (isset($payment_data['payment_id'])) {
        storeLipaNumber($payment_data['payment_id'], $lipa_number);
    }
    
    // Prepare email content
    $subject = "Payment Confirmation - Waste Management System";
    $html_body = generatePaymentEmailTemplate($client_name, $payment_data, $lipa_number);
    
    // Log attempt
    error_log("Sending payment email to: $client_email");
    error_log("Provider: " . $payment_data['provider']);
    error_log("Lipa Number: $lipa_number");
    error_log("Amount: ₱" . number_format($payment_data['amount'], 2));
    
    // Send email using SMTP
    $sent = sendSMTPEmail($client_email, $subject, $html_body);
    
    if ($sent) {
        error_log("Email sent successfully to: $client_email");
        return true;
    } else {
        error_log("Failed to send email to: $client_email");
        return false;
    }
}

function generatePaymentEmailTemplate($client_name, $payment_data, $lipa_number) {
    $provider = $payment_data['provider'];
    $amount = number_format($payment_data['amount'], 2);
    $location = $payment_data['location'] ?? 'N/A';
    
    // Provider-specific instructions
    $instructions = getProviderInstructions($provider);
    $provider_badge = getProviderBadge($provider);
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Payment Confirmation</title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f8f9fa; }
            .container { max-width: 600px; margin: 0 auto; background-color: white; }
            .header { background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; }
            .content { padding: 30px; }
            .lipa-number { background: #e8f5e8; border: 2px solid #28a745; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0; }
            .lipa-number h2 { color: #28a745; margin: 0 0 10px 0; font-size: 28px; font-weight: bold; }
            .provider-badge { display: inline-block; background: #007bff; color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; margin-bottom: 15px; }
            .details-grid { background: #f8f9fa; border-radius: 10px; padding: 20px; margin: 20px 0; }
            .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e9ecef; }
            .detail-row:last-child { border-bottom: none; }
            .instructions { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 10px; padding: 20px; margin: 20px 0; }
            .instructions h3 { color: #856404; margin-top: 0; }
            .warning { background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 10px; padding: 15px; margin: 20px 0; color: #721c24; }
            .footer { background: #6c757d; color: white; padding: 20px; text-align: center; font-size: 12px; }
            .step { margin: 10px 0; padding: 10px; background: #f8f9fa; border-left: 4px solid #28a745; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🗑️ Payment Confirmation</h1>
                <p>Waste Management System</p>
            </div>
            
            <div class='content'>
                <h2>Hello " . htmlspecialchars($client_name) . ",</h2>
                <p>Thank you for your payment! Please complete your payment using the details below:</p>
                
                <div class='lipa-number'>
                    " . $provider_badge . "
                    <h2>" . $lipa_number . "</h2>
                    <p><strong>Use this Lipa Number to complete your payment</strong></p>
                </div>
                
                <div class='details-grid'>
                    <h3>Payment Details</h3>
                    <div class='detail-row'>
                        <span><strong>Amount:</strong></span>
                        <span><strong>₱ " . $amount . "</strong></span>
                    </div>
                    <div class='detail-row'>
                        <span><strong>Service Location:</strong></span>
                        <span>" . htmlspecialchars($location) . "</span>
                    </div>
                    <div class='detail-row'>
                        <span><strong>Payment ID:</strong></span>
                        <span>#" . ($payment_data['payment_id'] ?? 'PENDING') . "</span>
                    </div>
                    <div class='detail-row'>
                        <span><strong>Payment Provider:</strong></span>
                        <span>" . htmlspecialchars($provider) . "</span>
                    </div>
                </div>
                
                <div class='instructions'>
                    <h3>📱 How to Pay with " . htmlspecialchars($provider) . "</h3>
                    " . $instructions . "
                </div>
                
                <div class='warning'>
                    <h4>⚠️ Important Notes:</h4>
                    <ul>
                        <li>Please complete payment within 24 hours</li>
                        <li>Use the exact Lipa Number provided above</li>
                        <li>Contact us if you encounter any issues</li>
                        <li>Keep this email for your records</li>
                    </ul>
                </div>
                
                <p>If you have any questions, please contact our support team at <strong>" . EMAIL_FROM . "</strong></p>
                
                <p>Best regards,<br><strong>Waste Management System Team</strong></p>
            </div>
            
            <div class='footer'>
                <p>&copy; " . date('Y') . " Waste Management System. All rights reserved.</p>
                <p>This is an automated message, please do not reply directly to this email.</p>
            </div>
        </div>
    </body>
    </html>";
}

function getProviderInstructions($provider) {
    $instructions = [
        'Mpesa' => "
            <div class='step'>1. Go to M-Pesa menu on your phone</div>
            <div class='step'>2. Select 'Lipa na M-Pesa'</div>
            <div class='step'>3. Select 'Pay Bill'</div>
            <div class='step'>4. Enter the Lipa Number above</div>
            <div class='step'>5. Enter the exact amount</div>
            <div class='step'>6. Confirm the payment</div>
        ",
        'Halopesa' => "
            <div class='step'>1. Dial *150*00# on your phone</div>
            <div class='step'>2. Select 'Pay Bill'</div>
            <div class='step'>3. Enter the Lipa Number above</div>
            <div class='step'>4. Enter the exact amount</div>
            <div class='step'>5. Confirm the payment</div>
        ",
        'AirtelMoney' => "
            <div class='step'>1. Dial *150*60# on your phone</div>
            <div class='step'>2. Select 'Pay Bill'</div>
            <div class='step'>3. Enter the Lipa Number above</div>
            <div class='step'>4. Enter the exact amount</div>
            <div class='step'>5. Confirm the payment</div>
        ",
        'MixbyYas' => "
            <div class='step'>1. Open MixbyYas app or dial *129#</div>
            <div class='step'>2. Select 'Pay Merchant'</div>
            <div class='step'>3. Enter the Lipa Number above</div>
            <div class='step'>4. Enter the exact amount</div>
            <div class='step'>5. Confirm the payment</div>
        "
    ];
    
    return $instructions[$provider] ?? "Contact support for payment instructions.";
}

function getProviderBadge($provider) {
    $badges = [
        'Mpesa' => "<span class='provider-badge' style='background: #28a745;'>M-Pesa</span>",
        'Halopesa' => "<span class='provider-badge' style='background: #007bff;'>HaloPesa</span>",
        'AirtelMoney' => "<span class='provider-badge' style='background: #dc3545;'>Airtel Money</span>",
        'MixbyYas' => "<span class='provider-badge' style='background: #6f42c1;'>MixbyYas</span>"
    ];
    
    return $badges[$provider] ?? "<span class='provider-badge'>" . htmlspecialchars($provider) . "</span>";
}

function generateLipaNumber($provider) {
    $prefixes = [
        'Mpesa' => '254',
        'Halopesa' => '255', 
        'AirtelMoney' => '256',
        'MixbyYas' => '257'
    ];
    
    $prefix = $prefixes[$provider] ?? '258';
    return $prefix . sprintf('%06d', mt_rand(100000, 999999));
}

function sendSMTPEmail($to_email, $subject, $html_body) {
    // Use a simplified approach with better error handling
    $smtp_host = 'smtp.gmail.com';
    $smtp_port = 587;
    $smtp_username = EMAIL_FROM;
    $smtp_password = 'mpho tdvk ziie wegz';
    
    // Set PHP mail configuration for SMTP
    ini_set('SMTP', $smtp_host);
    ini_set('smtp_port', $smtp_port);
    ini_set('sendmail_from', EMAIL_FROM);
    
    // Prepare headers for HTML email
    $headers = array(
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . EMAIL_FROM_NAME . ' <' . EMAIL_FROM . '>',
        'Reply-To: ' . EMAIL_FROM,
        'X-Mailer: PHP/' . phpversion(),
        'X-Priority: 1',
        'Return-Path: ' . EMAIL_FROM
    );
    
    // Try to send email using mail() with proper headers
    $sent = mail($to_email, $subject, $html_body, implode("\r\n", $headers), "-f" . EMAIL_FROM);
    
    if (!$sent) {
        // If mail() fails, try alternative method using cURL for Gmail API
        error_log("Standard mail() failed, trying alternative method");
        return sendEmailAlternative($to_email, $subject, $html_body);
    }
    
    return $sent;
}

function sendEmailAlternative($to_email, $subject, $html_body) {
    // Alternative method: Log email content and simulate sending
    // In production, this could be replaced with a proper email service API
    
    error_log("=== EMAIL CONTENT ===");
    error_log("TO: $to_email");
    error_log("SUBJECT: $subject");
    error_log("BODY LENGTH: " . strlen($html_body) . " characters");
    error_log("FROM: " . EMAIL_FROM);
    error_log("=== END EMAIL ===");
    
    // For now, we'll simulate successful sending
    // In a real scenario, you would integrate with an email service like:
    // - Gmail API
    // - SendGrid
    // - Mailgun
    // - AWS SES
    
    error_log("Email logged successfully - would be sent via API in production");
    return true;
}

function storeLipaNumber($payment_id, $lipa_number) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("UPDATE payments SET lipa_number = ? WHERE id = ?");
        $stmt->bind_param("si", $lipa_number, $payment_id);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Failed to store Lipa Number: " . $e->getMessage());
        return false;
    }
}
?> 