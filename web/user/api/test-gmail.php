<?php
/**
 * Test Gmail API Connection
 * Simple test to check if Gmail API is working
 */

require_once __DIR__ . '/../../config/Config.php';
require_once __DIR__ . '/../../api/includes/GmailClient.php';

header('Content-Type: application/json');

try {
    // Check Gmail configuration
    if (!defined('GMAIL_FROM_EMAIL') || !defined('GMAIL_FROM_NAME')) {
        echo json_encode([
            'success' => false, 
            'error' => 'Gmail configuration missing',
            'gmail_from_email' => defined('GMAIL_FROM_EMAIL') ? GMAIL_FROM_EMAIL : 'NOT_DEFINED',
            'gmail_from_name' => defined('GMAIL_FROM_NAME') ? GMAIL_FROM_NAME : 'NOT_DEFINED'
        ]);
        exit();
    }
    
    // Check if we can get access token
    try {
        $accessToken = GmailClient::getAccessToken();
        echo json_encode([
            'success' => true,
            'message' => 'Gmail API is working',
            'gmail_from_email' => GMAIL_FROM_EMAIL,
            'gmail_from_name' => GMAIL_FROM_NAME,
            'token_preview' => substr($accessToken, 0, 20) . '...'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Gmail authentication failed: ' . $e->getMessage(),
            'gmail_from_email' => GMAIL_FROM_EMAIL,
            'gmail_from_name' => GMAIL_FROM_NAME
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Test failed: ' . $e->getMessage()
    ]);
}
