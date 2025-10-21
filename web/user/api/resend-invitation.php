<?php
/**
 * Resend Invitation API
 * POST /user/api/resend-invitation.php
 * 
 * Resends an existing invitation email
 */

require_once __DIR__ . '/../../config/Config.php';
require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../user/includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/GmailClient.php';
require_once __DIR__ . '/../../api/includes/EmailTemplates.php';

header('Content-Type: application/json');

error_log("RESEND DEBUG - Starting resend invitation process");

try {
    // Check if user is logged in
    $auth = new UserAuth();
    if (!$auth->isLoggedIn()) {
        error_log("RESEND DEBUG - User not authenticated");
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit();
    }

    $userId = $auth->getUserId();
    error_log("RESEND DEBUG - User authenticated, ID: " . $userId);
    
    $db = Database::getInstance();
    error_log("RESEND DEBUG - Database connection established");

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $invitationId = $input['invitation_id'] ?? '';
    
    error_log("RESEND DEBUG - Received invitation ID: " . $invitationId);
    error_log("RESEND DEBUG - Input data: " . json_encode($input));

    if (!$invitationId) {
        error_log("RESEND DEBUG - No invitation ID provided");
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invitation ID required']);
        exit();
    }

    // Get invitation details
    error_log("RESEND DEBUG - Looking up invitation with ID: " . $invitationId . " for user: " . $userId);
    
    $invitation = $db->querySingle(
        "SELECT i.*, 
                CONCAT(bc.first_name, ' ', bc.last_name) as inviter_name,
                bc.first_name, bc.last_name
         FROM invitations i
         JOIN business_cards bc ON i.business_card_id = bc.id
         WHERE i.id = ? AND i.inviter_user_id = ?",
        [$invitationId, $userId]
    );
    
    error_log("RESEND DEBUG - Invitation lookup result: " . ($invitation ? 'found' : 'not found'));
    if ($invitation) {
        error_log("RESEND DEBUG - Invitation details: " . json_encode($invitation));
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
    error_log("Resend invitation debug - inviter name: " . $inviterName);
    error_log("Resend invitation debug - card URL: " . $cardUrl);
    error_log("Resend invitation debug - comment: " . $comment);
    error_log("Resend invitation debug - new token: " . $newToken);
    
    try {
        $emailTemplate = EmailTemplates::invitation($inviterName, $invitation['invitee_first_name'], $cardUrl, $comment, $newToken);
        error_log("Resend invitation debug - email template generated, length: " . strlen($emailTemplate));
        
        // Check if Gmail is configured
        if (!defined('GMAIL_FROM_EMAIL') || !defined('GMAIL_FROM_NAME')) {
            throw new Exception('Gmail configuration missing - GMAIL_FROM_EMAIL or GMAIL_FROM_NAME not defined');
        }
        
        error_log("Resend invitation debug - Gmail config: " . GMAIL_FROM_EMAIL . " / " . GMAIL_FROM_NAME);
        
        // Check if Gmail tokens exist
        try {
            $accessToken = GmailClient::getAccessToken();
            error_log("Resend invitation debug - Gmail access token obtained: " . substr($accessToken, 0, 20) . "...");
        } catch (Exception $tokenError) {
            error_log("Resend invitation debug - Gmail token error: " . $tokenError->getMessage());
            throw new Exception('Gmail authentication failed: ' . $tokenError->getMessage());
        }
        
        $emailResult = GmailClient::sendEmail(
            $invitation['invitee_email'],
            $emailTemplate['subject'],
            $emailTemplate['html'],
            $emailTemplate['text']
        );
        
        $emailSent = !empty($emailResult);
        error_log("Resend invitation debug - email sent result: " . ($emailSent ? 'success' : 'failed'));
        if ($emailSent) {
            error_log("Resend invitation debug - email ID: " . ($emailResult['id'] ?? 'unknown'));
        } else {
            error_log("Resend invitation debug - email result was empty: " . json_encode($emailResult));
        }
    } catch (Exception $emailError) {
        error_log("Resend invitation debug - email sending exception: " . $emailError->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gmail API Error: ' . $emailError->getMessage()]);
        exit();
    }

    if (!$emailSent) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to send email - no result returned']);
        exit();
    }

    echo json_encode(['success' => true, 'message' => 'Invitation resent successfully']);

} catch (Exception $e) {
    error_log("Resend invitation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
