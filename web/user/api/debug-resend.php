<?php
/**
 * Debug Resend Endpoint
 * Simple test to see if the resend API is being called
 */

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$invitationId = $input['invitation_id'] ?? '';

error_log("DEBUG RESEND - Called with invitation_id: " . $invitationId);

echo json_encode([
    'success' => true,
    'message' => 'Debug resend endpoint called successfully',
    'received_id' => $invitationId,
    'timestamp' => date('Y-m-d H:i:s'),
    'input_data' => $input
]);
exit();
