<?php
/**
 * Test Resend Endpoint
 * Simple test to see if the API is working
 */

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$invitationId = $input['invitation_id'] ?? '';

echo json_encode([
    'success' => true, 
    'message' => 'Test endpoint working',
    'received_id' => $invitationId,
    'timestamp' => date('Y-m-d H:i:s')
]);
exit();
