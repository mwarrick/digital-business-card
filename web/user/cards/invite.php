<?php
/**
 * Invite Someone Page
 * Form to invite others to join ShareMyCard
 */

require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../includes/UserAuth.php';

// Require authentication
UserAuth::requireAuth();

$db = Database::getInstance();
$userId = UserAuth::getUserId();

// Get user information including role
$user = $db->querySingle(
    "SELECT email, role FROM users WHERE id = ?",
    [$userId]
);

// Get user's business cards
$cards = $db->query(
    "SELECT id, first_name, last_name, company_name, job_title FROM business_cards 
     WHERE user_id = ? AND is_active = 1 
     ORDER BY created_at DESC",
    [$userId]
);

// Get selected card if provided
$selectedCardId = $_GET['card_id'] ?? '';

// Get card data for preview (use first card if none selected)
$cardData = null;
if ($selectedCardId) {
    $cardData = $db->querySingle(
        "SELECT first_name, last_name FROM business_cards WHERE id = ? AND user_id = ?",
        [$selectedCardId, $userId]
    );
}
if (!$cardData && !empty($cards)) {
    $cardData = $cards[0]; // Use first card as default
}
$selectedCard = null;
if ($selectedCardId) {
    foreach ($cards as $card) {
        if ($card['id'] === $selectedCardId) {
            $selectedCard = $card;
            break;
        }
    }
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inviteeFirstName = trim($_POST['invitee_first_name'] ?? '');
    $inviteeLastName = trim($_POST['invitee_last_name'] ?? '');
    $inviteeEmail = trim($_POST['invitee_email'] ?? '');
    $businessCardId = $_POST['business_card_id'] ?? '';
    $comment = trim($_POST['comment'] ?? '');
    
    // Validation
    if (empty($inviteeFirstName) || empty($inviteeLastName) || empty($inviteeEmail) || empty($businessCardId)) {
        $error = 'All fields except comment are required.';
    } elseif (!filter_var($inviteeEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($comment) > 500) {
        $error = 'Comment must be 500 characters or less.';
    } else {
        // Verify business card belongs to user
        $cardExists = false;
        foreach ($cards as $card) {
            if ($card['id'] === $businessCardId) {
                $cardExists = true;
                break;
            }
        }
        
        if (!$cardExists) {
            $error = 'Invalid business card selected.';
        } else {
            // Send invitation directly (avoid session conflicts)
            try {
                // Get business card information for inviter name
                $cardData = $db->querySingle(
                    "SELECT first_name, last_name FROM business_cards WHERE id = ? AND user_id = ?",
                    [$businessCardId, $userId]
                );
                $inviterName = $cardData['first_name'] . ' ' . $cardData['last_name'];
                
                // Check for existing invitation to same email in last 24 hours (rate limiting)
                $existingInvitation = $db->querySingle(
                    "SELECT id FROM invitations 
                     WHERE inviter_user_id = ? AND invitee_email = ? 
                     AND sent_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
                    [$userId, $inviteeEmail]
                );
                
                if ($existingInvitation) {
                    $error = 'You have already sent an invitation to this email address in the last 24 hours';
                } else {
                    // Generate secure random token
                    $invitationToken = bin2hex(random_bytes(32));
                    
                    // Create business card URL
                    $cardUrl = 'https://sharemycard.app/card.php?id=' . urlencode($businessCardId);
                    
                    // Insert invitation record
                    $invitationId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000,
                        mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    );
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
                    
                    // Check if user is demo account
                    if ($user['role'] === 'demo') {
                        // Demo account - simulate sending but don't actually send email
                        $success = 'Invitation recorded! (Demo account - no email sent)';
                        // Clear form
                        $inviteeFirstName = $inviteeLastName = $inviteeEmail = $comment = '';
                        $selectedCardId = '';
                    } else {
                        // Regular account - send actual email
                        require_once __DIR__ . '/../../api/includes/EmailTemplates.php';
                        require_once __DIR__ . '/../../api/includes/GmailClient.php';
                        
                        // Debug: Log the inviter name being used
                        error_log("Invitation sending - inviterName: " . $inviterName);
                        
                        $emailTemplate = EmailTemplates::invitation(
                            $inviterName,
                            $inviteeFirstName,
                            $cardUrl,
                            $comment,
                            $invitationToken
                        );
                        
                        $gmailClient = new GmailClient();
                        $emailResult = $gmailClient->sendEmail(
                            $inviteeEmail,
                            $emailTemplate['subject'],
                            $emailTemplate['html'],
                            $emailTemplate['text']
                        );
                        
                        if ($emailResult) {
                            $success = 'Invitation sent successfully!';
                            // Clear form
                            $inviteeFirstName = $inviteeLastName = $inviteeEmail = $comment = '';
                            $selectedCardId = '';
                        } else {
                            $error = 'Failed to send email. Please try again.';
                        }
                    }
                }
            } catch (Exception $e) {
                $error = 'Failed to send invitation: ' . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invite Someone - ShareMyCard</title>
    <link rel="stylesheet" href="/user/includes/user-style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-brand">
            <a href="/user/dashboard.php">📱 ShareMyCard</a>
        </div>
        
        <button class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <div class="nav-links mobile">
            <a href="/user/dashboard.php" class="nav-link">🏠 Dashboard</a>
            <a href="/user/cards/create.php" class="nav-link">➕ New Card</a>
            <a href="/user/cards/analytics.php" class="nav-link">📊 Analytics</a>
            <a href="/user/cards/invitation-analytics.php" class="nav-link">📧 Invitations</a>
            <a href="/user/cards/email-signature.php" class="nav-link">✉️ Email Signature</a>
            <a href="/user/cards/name-tags.php" class="nav-link">🏷️ Name Tags</a>
            <a href="/user/cards/virtual-background.php" class="nav-link">🖼️ Virtual Background</a>
            <a href="https://github.com/mwarrick/digital-business-card/issues" class="nav-link" target="_blank">🐛 Report Issues</a>
            <a href="#" onclick="openAccountSecurity()" class="nav-link">🔒 Security</a>
            <a href="/user/logout.php" class="nav-link">🚪 Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>✉️ Invite Someone</h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" id="inviteForm">
                <div class="form-group">
                    <label for="invitee_first_name">Invitee First Name *</label>
                    <input type="text" id="invitee_first_name" name="invitee_first_name" 
                           value="<?php echo htmlspecialchars($inviteeFirstName ?? ''); ?>" 
                           required maxlength="255">
                </div>

                <div class="form-group">
                    <label for="invitee_last_name">Invitee Last Name *</label>
                    <input type="text" id="invitee_last_name" name="invitee_last_name" 
                           value="<?php echo htmlspecialchars($inviteeLastName ?? ''); ?>" 
                           required maxlength="255">
                </div>

                <div class="form-group">
                    <label for="invitee_email">Invitee Email *</label>
                    <input type="email" id="invitee_email" name="invitee_email" 
                           value="<?php echo htmlspecialchars($inviteeEmail ?? ''); ?>" 
                           required maxlength="255">
                </div>

                <div class="form-group">
                    <label for="business_card_id">Select Business Card *</label>
                    <select id="business_card_id" name="business_card_id" required>
                        <option value="">Choose a business card...</option>
                        <?php foreach ($cards as $card): ?>
                            <option value="<?php echo htmlspecialchars($card['id']); ?>" 
                                    <?php echo ($selectedCardId === $card['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($card['first_name'] . ' ' . $card['last_name']); ?>
                                <?php if ($card['company_name']): ?>
                                    - <?php echo htmlspecialchars($card['company_name']); ?>
                                <?php endif; ?>
                                <?php if ($card['job_title']): ?>
                                    (<?php echo htmlspecialchars($card['job_title']); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="comment">Personal Message (Optional)</label>
                    <textarea id="comment" name="comment" rows="6" maxlength="500" 
                              placeholder="Add a personal message to include with the invitation..."
                              style="width: 400%; max-width: 100%;"><?php echo htmlspecialchars($comment ?? ''); ?></textarea>
                </div>
                <div style="margin-top: -15px; margin-bottom: 20px;">
                    <small class="form-help">Maximum 500 characters. Plain text only.</small>
                </div>
                
                <div class="form-group">
                    <button type="button" onclick="previewMessage()" class="btn btn-secondary" style="margin-bottom: 15px;">
                        👁️ Preview Your Message
                    </button>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">📧 Send Invitation</button>
                    <a href="/user/dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

    </div>

    <script>
        // Form validation
        document.getElementById('inviteForm').addEventListener('submit', function(e) {
            const comment = document.getElementById('comment').value;
            if (comment.length > 500) {
                e.preventDefault();
                alert('Comment must be 500 characters or less.');
                return false;
            }
        });

        // Character counter for comment
        const commentField = document.getElementById('comment');
        const commentHelp = document.querySelector('.form-help');
        
        commentField.addEventListener('input', function() {
            const remaining = 500 - this.value.length;
            commentHelp.textContent = `${remaining} characters remaining. Plain text only.`;
            
            if (remaining < 0) {
                commentHelp.style.color = '#e74c3c';
            } else {
                commentHelp.style.color = '#666';
            }
        });

        // Navigation handled by user-script.js
        
        function previewMessage() {
            const firstName = document.getElementById('invitee_first_name').value;
            const lastName = document.getElementById('invitee_last_name').value;
            const email = document.getElementById('invitee_email').value;
            const cardSelect = document.getElementById('business_card_id');
            const selectedCard = cardSelect.options[cardSelect.selectedIndex];
            const comment = document.getElementById('comment').value;
            
            // Get inviter info from the page
            const inviterEmail = '<?php echo htmlspecialchars($user["email"]); ?>';
            const inviterName = '<?php echo htmlspecialchars($cardData["first_name"] . " " . $cardData["last_name"]); ?>';
            
            // Validate required fields
            if (!firstName || !lastName || !email || !selectedCard.value) {
                alert('Please fill in all required fields before previewing.');
                return;
            }
            
            // Build preview content to match the actual email template
            let previewHTML = `
                <div style="font-family: Arial, sans-serif; line-height: 1.6; max-width: 600px; margin: 0 auto;">
                    <!-- Email Header Info -->
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; color: #666;">
                        <p style="margin: 0;"><strong>To:</strong> ${firstName} ${lastName} (${email})</p>
                        <p style="margin: 5px 0 0 0;"><strong>From:</strong> ${inviterName} (${inviterEmail})</p>
                    </div>
                    
                    <!-- ShareMyCard Banner -->
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 12px; text-align: center; margin-bottom: 30px;">
                        <div style="color: white; font-size: 24px; font-weight: bold;">
                            📱 ShareMyCard
                        </div>
                    </div>
                    
                    <!-- Main Content -->
                    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <h1 style="color: #333; font-size: 28px; margin: 0 0 20px 0;">You're Invited to ShareMyCard</h1>
                        
                        <p style="font-size: 16px; margin: 0 0 20px 0;">Hi ${firstName},</p>
                        
                        <p style="font-size: 16px; margin: 0 0 30px 0;"><strong>${inviterName}</strong> has invited you to check out their business card on ShareMyCard!</p>
                        
                        <!-- View Business Card Section -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea;">
                            <h3 style="margin: 0 0 10px 0; color: #333; font-size: 18px;">View Business Card</h3>
                            <p style="margin: 0 0 15px 0; color: #666; font-size: 14px;">Click the link below to see their digital business card:</p>
                            <div style="text-align: center; margin: 20px 0;">
                                <a href="/card.php?id=${selectedCard.value}" style="display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;">
                                    View Business Card
                                </a>
                            </div>
                        </div>
                        
                        ${comment ? `
                        <!-- Personal Message Section -->
                        <div style="background: #fff3cd; padding: 15px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #ffc107;">
                            <h4 style="margin: 0 0 10px 0; color: #856404; font-size: 16px;">Personal Message:</h4>
                            <p style="margin: 0; color: #856404; font-style: italic; font-size: 14px;">"${comment}"</p>
                        </div>
                        ` : ''}
                        
                        <!-- Call to Action Section -->
                        <div style="background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
                            <h3 style="margin: 0 0 15px 0; color: #333; font-size: 18px;">Interested in creating your own digital business card?</h3>
                            <p style="margin: 0 0 20px 0; color: #666; font-size: 14px;">ShareMyCard makes it easy to create and share professional digital business cards.</p>
                            
                            <div style="margin: 20px 0;">
                                <a href="#" style="display: inline-block; padding: 12px 25px; background: #28a745; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 0 10px 10px 0; font-size: 14px;">
                                    Yes, I'm Interested
                                </a>
                                <a href="#" style="display: inline-block; padding: 12px 25px; background: #6c757d; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 0 10px 10px 0; font-size: 14px;">
                                    No, Not Interested
                                </a>
                            </div>
                        </div>
                        
                        <!-- Footer -->
                        <p style="color: #666; font-size: 12px; margin-top: 30px; text-align: center;">
                            This invitation was sent by ${inviterName}. If you didn't expect this invitation, you can safely ignore this email.
                        </p>
                    </div>
                </div>
            `;
            
            document.getElementById('previewContent').innerHTML = previewHTML;
            document.getElementById('messagePreviewModal').style.display = 'flex';
        }
        
        function closePreview() {
            document.getElementById('messagePreviewModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('messagePreviewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePreview();
            }
        });
    </script>
    
    <!-- Message Preview Modal -->
    <div id="messagePreviewModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; padding: 30px; border-radius: 10px; max-width: 600px; width: 90%; max-height: 80%; overflow-y: auto;">
            <h3 style="margin-top: 0; color: #333;">📧 Message Preview</h3>
            <p style="color: #666; margin-bottom: 20px;">Here's how your invitation will look:</p>
            
            <div id="previewContent" style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea; margin-bottom: 20px;">
                <!-- Preview content will be inserted here -->
            </div>
            
            <div style="text-align: right;">
                <button onclick="closePreview()" class="btn btn-primary">Close Preview</button>
            </div>
        </div>
    </div>
    
    <script src="/user/includes/user-script.js"></script>
</body>
</html>
