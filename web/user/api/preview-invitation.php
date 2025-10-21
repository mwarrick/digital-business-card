<?php
/**
 * Preview Invitation API
 * POST /user/api/preview-invitation.php
 * 
 * Shows a preview of the email that was sent for an invitation
 */

require_once __DIR__ . '/../../config/Config.php';
require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../user/includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/EmailTemplates.php';

header('Content-Type: application/json');

// Start session for authentication
session_start();

try {
    // Check if user is logged in
    if (!UserAuth::isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit();
    }

    $userId = UserAuth::getUserId();
    $db = Database::getInstance();

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $invitationId = $input['invitation_id'] ?? '';
    
    if (!$invitationId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invitation ID required']);
        exit();
    }

    // Get invitation details
    $invitation = $db->querySingle(
        "SELECT i.*, 
                CONCAT(bc.first_name, ' ', bc.last_name) as inviter_name,
                bc.first_name, bc.last_name
         FROM invitations i
         JOIN business_cards bc ON i.business_card_id = bc.id
         WHERE i.id = ? AND i.inviter_user_id = ?",
        [$invitationId, $userId]
    );

    if (!$invitation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Invitation not found']);
        exit();
    }

    // Prepare email data (same as original invitation)
    $inviterName = $invitation['first_name'] . ' ' . $invitation['last_name'];
    $cardUrl = 'https://sharemycard.app/card.php?id=' . urlencode($invitation['business_card_id']);
    $comment = $invitation['comment'] ?? '';
    $token = $invitation['invitation_token'];

    // Generate email template (same as original)
    $emailTemplate = EmailTemplates::invitation($inviterName, $invitation['invitee_first_name'], $cardUrl, $comment, $token);
    
    // Return the HTML content for preview
    echo json_encode([
        'success' => true, 
        'html' => $emailTemplate['html'],
        'subject' => $emailTemplate['subject']
    ]);

} catch (Exception $e) {
    error_log("Preview invitation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    exit();
}
