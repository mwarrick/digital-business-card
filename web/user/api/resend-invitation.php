<?php
/**
 * Resend Invitation API
 * POST /user/api/resend-invitation.php
 * 
 * Resends an existing invitation email
 */

require_once __DIR__ . '/../../config/Config.php';
require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../api/includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/GmailClient.php';
require_once __DIR__ . '/../../api/includes/EmailTemplates.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    $auth = new UserAuth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit();
    }

    $userId = $auth->getUserId();
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
    
    error_log("Resend invitation debug - invitation found: " . ($invitation ? 'yes' : 'no'));
    if ($invitation) {
        error_log("Resend invitation debug - inviter name: " . $invitation['first_name'] . ' ' . $invitation['last_name']);
    }

    if (!$invitation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Invitation not found']);
        exit();
    }

    // Generate new token for resend
    $newToken = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );

    // Update invitation with new token and reset tracking
    $db->execute(
        "UPDATE invitations 
         SET invitation_token = ?, 
             sent_at = NOW(), 
             opened_at = NULL, 
             responded_at = NULL, 
             response_type = 'no_response',
             updated_at = NOW()
         WHERE id = ?",
        [$newToken, $invitationId]
    );

    // Prepare email data
    $inviterName = $invitation['first_name'] . ' ' . $invitation['last_name'];
    $cardUrl = 'https://sharemycard.app/card.php?id=' . urlencode($invitation['business_card_id']);
    $comment = $invitation['comment'] ?? '';

    // Send email
    error_log("Resend invitation debug - attempting to send email to: " . $invitation['invitee_email']);
    $gmail = new GmailClient();
    $emailSent = $gmail->sendEmail(
        $invitation['invitee_email'],
        $invitation['invitee_first_name'] . ' ' . $invitation['invitee_last_name'],
        EmailTemplates::invitation($inviterName, $invitation['invitee_first_name'], $cardUrl, $comment, $newToken)
    );
    
    error_log("Resend invitation debug - email sent result: " . ($emailSent ? 'success' : 'failed'));

    if (!$emailSent) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to send email']);
        exit();
    }

    echo json_encode(['success' => true, 'message' => 'Invitation resent successfully']);

} catch (Exception $e) {
    error_log("Resend invitation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
