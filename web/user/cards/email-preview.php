<?php
/**
 * Email Preview Page
 * Opens in new window to show invitation email preview
 */

require_once __DIR__ . '/../../config/Config.php';
require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../user/includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/EmailTemplates.php';

session_start();

// Check if user is logged in
if (!UserAuth::isLoggedIn()) {
    http_response_code(401);
    echo '<h1>Not authenticated</h1>';
    exit();
}

$userId = UserAuth::getUserId();
$db = Database::getInstance();

// Get invitation ID from URL
$invitationId = $_GET['id'] ?? '';

if (!$invitationId) {
    echo '<h1>Invitation ID required</h1>';
    exit();
}

try {
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
        echo '<h1>Invitation not found</h1>';
        exit();
    }

    // Prepare email data
    $inviterName = $invitation['first_name'] . ' ' . $invitation['last_name'];
    $cardUrl = 'https://sharemycard.app/card.php?id=' . urlencode($invitation['business_card_id']);
    $comment = $invitation['comment'] ?? '';
    $token = $invitation['invitation_token'];

    // Generate email template for preview
    $emailTemplate = EmailTemplates::invitationPreview($inviterName, $invitation['invitee_first_name'], $cardUrl, $comment, $token);
    
    // Get inviter email for "From" field
    $inviterEmail = $db->querySingle(
        "SELECT email FROM users WHERE id = ?",
        [$userId]
    )['email'];

} catch (Exception $e) {
    error_log("Email preview error: " . $e->getMessage());
    echo '<h1>Error loading preview</h1>';
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Preview - ShareMyCard</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f5f5;
        }
        .preview-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .email-headers {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #ddd;
        }
        .email-headers div {
            margin-bottom: 5px;
        }
        .email-content {
            padding: 0;
        }
        .close-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <button class="close-btn" onclick="window.close()">Close Preview</button>
    
    <div class="preview-container">
        <div class="email-headers">
            <div><strong>From:</strong> <?php echo htmlspecialchars($inviterEmail); ?></div>
            <div><strong>To:</strong> <?php echo htmlspecialchars($invitation['invitee_email']); ?></div>
            <div><strong>Subject:</strong> <?php echo htmlspecialchars($emailTemplate['subject']); ?></div>
        </div>
        
        <div class="email-content">
            <?php echo $emailTemplate['html']; ?>
        </div>
    </div>
</body>
</html>
