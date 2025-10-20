<?php
/**
 * Email Signature Tracking Pixel
 * GET /api/analytics/pixel.php?card_id={uuid}&t={timestamp}
 * 
 * Serves a 1x1 transparent GIF pixel and tracks email opens
 */

// Include dependencies
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Analytics.php';

try {
    // Validate required parameters
    if (!isset($_GET['card_id'])) {
        throw new Exception('card_id parameter is required');
    }
    
    $cardId = $_GET['card_id'];
    $timestamp = $_GET['t'] ?? time();
    
    // Validate card_id format (should be UUID)
    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $cardId)) {
        throw new Exception('Invalid card_id format');
    }
    
    // Initialize database and analytics
    $db = Database::getInstance()->getConnection();
    $analytics = new Analytics($db);
    
    // Track the email open event
    $result = $analytics->trackEvent($cardId, 'email_open', null, $_SERVER);
    
    // Debug: Log the tracking result
    error_log("Email tracking pixel - Card ID: $cardId, Result: " . json_encode($result));
    
    // Also log to a file for easier debugging
    file_put_contents('/home/sharipbf/public_html/pixel_debug.log', 
        date('Y-m-d H:i:s') . " - Card ID: $cardId, Result: " . json_encode($result) . "\n", 
        FILE_APPEND | LOCK_EX
    );
    
    // Set headers for pixel image
    header('Content-Type: image/gif');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Length: 43');
    
    // Return 1x1 transparent GIF pixel
    // This is the smallest possible GIF (43 bytes)
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    
} catch (Exception $e) {
    // Even if tracking fails, still return the pixel to avoid breaking email rendering
    header('Content-Type: image/gif');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Length: 43');
    
    // Log error for debugging (but don't expose to client)
    error_log("Email tracking pixel error: " . $e->getMessage());
    
    // Return the same 1x1 transparent GIF
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
}
