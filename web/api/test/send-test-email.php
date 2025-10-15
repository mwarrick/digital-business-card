<?php
/**
 * Test Email Sending
 * Send a test email to verify Gmail API is working
 */

require_once '../includes/GmailClient.php';
require_once '../includes/EmailTemplates.php';

header('Content-Type: application/json');

// Get recipient email from query parameter
$to = $_GET['to'] ?? null;

if (!$to) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing "to" parameter',
        'example' => '/api/test/send-test-email.php?to=your@email.com'
    ]);
    exit;
}

try {
    // Check if authenticated
    if (!GmailClient::isAuthenticated()) {
        throw new Exception('Gmail API not authorized. Please visit /api/auth/gmail-init.php');
    }
    
    // Test different email templates
    $testType = $_GET['type'] ?? 'verification';
    
    switch ($testType) {
        case 'verification':
            $code = sprintf('%06d', rand(0, 999999));
            $email = EmailTemplates::registrationVerification($code, $to);
            break;
            
        case 'login':
            $code = sprintf('%06d', rand(0, 999999));
            $email = EmailTemplates::loginVerification($code, $to);
            break;
            
        case 'reset':
            $code = sprintf('%06d', rand(0, 999999));
            $email = EmailTemplates::passwordReset($code, $to);
            break;
            
        case 'welcome':
            $email = EmailTemplates::welcome('Test User');
            break;
            
        default:
            $email = EmailTemplates::notification(
                'Test Email from ShareMyCard',
                'This is a test email to verify that the Gmail API integration is working correctly!'
            );
    }
    
    // Send email
    $result = GmailClient::sendEmail($to, $email['subject'], $email['html'], $email['text']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Email sent successfully!',
        'data' => [
            'to' => $to,
            'subject' => $email['subject'],
            'type' => $testType,
            'gmail_message_id' => $result['id'] ?? null
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send email',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

