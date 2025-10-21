<?php
/**
 * Send Invitation API
 * Handles invitation submission and email sending
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/EmailTemplates.php';
require_once __DIR__ . '/../../api/includes/GmailClient.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Require authentication
    UserAuth::requireAuth();
    
    $db = Database::getInstance();
    $userId = UserAuth::getUserId();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    $inviteeFirstName = trim($input['invitee_first_name'] ?? '');
    $inviteeLastName = trim($input['invitee_last_name'] ?? '');
    $inviteeEmail = trim($input['invitee_email'] ?? '');
    $businessCardId = $input['business_card_id'] ?? '';
    $comment = trim($input['comment'] ?? '');
    
    if (empty($inviteeFirstName) || empty($inviteeLastName) || empty($inviteeEmail) || empty($businessCardId)) {
        throw new Exception('All fields except comment are required');
    }
    
    if (!filter_var($inviteeEmail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }
    
    if (strlen($comment) > 500) {
        throw new Exception('Comment must be 500 characters or less');
    }
    
    // Sanitize comment (strip HTML tags)
    $comment = strip_tags($comment);
    
    // Verify business card belongs to user
    $card = $db->querySingle(
        "SELECT id, first_name, last_name, company_name, job_title FROM business_cards 
         WHERE id = ? AND user_id = ? AND is_active = 1",
        [$businessCardId, $userId]
    );
    
    if (!$card) {
        throw new Exception('Invalid business card selected');
    }
    
    // Check for existing invitation to same email in last 24 hours (rate limiting)
    $existingInvitation = $db->querySingle(
        "SELECT id FROM invitations 
         WHERE inviter_user_id = ? AND invitee_email = ? 
         AND sent_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
        [$userId, $inviteeEmail]
    );
    
    if ($existingInvitation) {
        throw new Exception('You have already sent an invitation to this email address in the last 24 hours');
    }
    
    // Generate secure random token
    $invitationToken = bin2hex(random_bytes(32));
    
    // Get inviter email (users table doesn't have name fields)
    $inviter = $db->querySingle(
        "SELECT email FROM users WHERE id = ?",
        [$userId]
    );
    $inviterName = $inviter['email']; // Use email as name since users table doesn't have name fields
    
    // Create business card URL
    $cardUrl = 'https://sharemycard.app/card.php?id=' . urlencode($businessCardId);
    
    // Insert invitation record
    $invitationId = $db->generateUuid();
    $db->execute(
        "INSERT INTO invitations (
            id, inviter_user_id, business_card_id, invitee_first_name, 
            invitee_last_name, invitee_email, comment, invitation_token, sent_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
        [
            $invitationId, $userId, $businessCardId, $inviteeFirstName,
            $inviteeLastName, $inviteeEmail, $comment, $invitationToken
        ]
    );
    
    // Send email
    $emailTemplate = EmailTemplates::invitation(
        $inviterName,
        $inviteeFirstName,
        $cardUrl,
        $comment,
        $invitationToken
    );
    
    error_log("Attempting to send email to: " . $inviteeEmail);
    
    $gmailClient = new GmailClient();
    $emailResult = $gmailClient->sendEmail(
        $inviteeEmail,
        $emailTemplate['subject'],
        $emailTemplate['html'],
        $emailTemplate['text']
    );
    
    error_log("Email send result: " . ($emailResult ? 'success' : 'failed'));
    
    echo json_encode([
        'success' => true,
        'message' => 'Invitation sent successfully',
        'invitation_id' => $invitationId
    ]);
    
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Send invitation error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
