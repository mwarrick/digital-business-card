<?php
/**
 * Simple Resend Invitation API
 * Minimal working version
 */

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$invitationId = $input['invitation_id'] ?? '';

if (!$invitationId) {
    echo json_encode(['success' => false, 'error' => 'Invitation ID required']);
    exit();
}

// For now, just return success - we'll add the actual logic after this works
echo json_encode([
    'success' => true, 
    'message' => 'Resend endpoint working - invitation ID: ' . $invitationId
]);
exit();
