<?php
/**
 * Email Tracking Pixel
 * GET /api/analytics/track-email?token={token}
 * 
 * Tracks when invitation emails are opened
 */

require_once __DIR__ . '/../includes/Database.php';

$db = Database::getInstance();

// Get token from query parameter
$token = $_GET['token'] ?? '';

if (!$token) {
    // Return 1x1 transparent pixel
    header('Content-Type: image/png');
    header('Content-Length: 43');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // 1x1 transparent PNG
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    exit();
}

try {
    // Find invitation by token
    $invitation = $db->querySingle(
        "SELECT id, opened_at FROM invitations WHERE invitation_token = ?",
        [$token]
    );
    
    if ($invitation && !$invitation['opened_at']) {
        // Update opened_at timestamp (only if not already set)
        $db->execute(
            "UPDATE invitations SET opened_at = NOW() WHERE invitation_token = ? AND opened_at IS NULL",
            [$token]
        );
    }
} catch (Exception $e) {
    // Silently fail - don't break email display
    error_log("Email tracking error: " . $e->getMessage());
}

// Return 1x1 transparent pixel
header('Content-Type: image/png');
header('Content-Length: 43');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 1x1 transparent PNG
echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
exit();
