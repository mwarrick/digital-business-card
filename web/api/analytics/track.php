<?php
/**
 * Analytics Tracking API Endpoint
 * POST /api/analytics/track.php
 * 
 * Accepts event tracking data and logs to database
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Include dependencies
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Analytics.php';

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    if (!isset($data['card_id']) || !isset($data['event_type'])) {
        throw new Exception('card_id and event_type are required');
    }
    
    $cardId = $data['card_id'];
    $eventType = $data['event_type'];
    $eventTarget = $data['event_target'] ?? null;
    
    // Validate event type
    $validTypes = ['view', 'click', 'download', 'email_open'];
    if (!in_array($eventType, $validTypes)) {
        throw new Exception('Invalid event_type. Must be: view, click, download, or email_open');
    }
    
    // Initialize database and analytics
    $db = Database::getInstance()->getConnection();
    $analytics = new Analytics($db);
    
    // Track the event
    $result = $analytics->trackEvent($cardId, $eventType, $eventTarget, $_SERVER);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Event tracked successfully',
        'data' => $result
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

