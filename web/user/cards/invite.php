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

// Get user information
$user = $db->querySingle(
    "SELECT email FROM users WHERE id = ?",
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
            // Send invitation via API
            $postData = [
                'invitee_first_name' => $inviteeFirstName,
                'invitee_last_name' => $inviteeLastName,
                'invitee_email' => $inviteeEmail,
                'business_card_id' => $businessCardId,
                'comment' => $comment
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://sharemycard.app/user/api/send-invitation.php');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $result = json_decode($response, true);
                if ($result['success']) {
                    $success = 'Invitation sent successfully!';
                    // Clear form
                    $inviteeFirstName = $inviteeLastName = $inviteeEmail = $comment = '';
                    $selectedCardId = '';
                } else {
                    $error = $result['message'] ?? 'Failed to send invitation.';
                }
            } else {
                $error = 'Failed to send invitation. Please try again.';
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
            const inviterName = inviterEmail; // Use email as name since users table doesn't have name fields
            
            // Validate required fields
            if (!firstName || !lastName || !email || !selectedCard.value) {
                alert('Please fill in all required fields before previewing.');
                return;
            }
            
            // Build preview content
            let previewHTML = `
                <div style="font-family: Arial, sans-serif; line-height: 1.6;">
                    <p><strong>To:</strong> ${firstName} ${lastName} (${email})</p>
                    <p><strong>From:</strong> ${inviterName} (${inviterEmail})</p>
                    <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">
                    
                    <p>Hi ${firstName},</p>
                    
                    <p>I'd love to connect with you! I've been using ShareMyCard to create digital business cards that make networking easier. It's a great way to share contact info instantly via QR code.</p>
                    
                    <br>
                    
                    ${comment ? `<p><em>Personal message:</em><br><br>"${comment}"</p>` : ''}
                    
                    <br>
                    
                    <p>Here's my business card: <a href="/card.php?id=${selectedCard.value}" target="_blank" style="color: #667eea; text-decoration: none;"><strong>${selectedCard.text}</strong></a></p>
                    
                    <br>
                    
                    <p>Would you be interested in checking it out?</p>
                    
                    <br>
                    
                    <div style="margin: 20px 0; text-align: center;">
                        <div style="display: inline-block; margin: 0 10px;">
                            <a href="#" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">✓ Yes, I'm Interested</a>
                        </div>
                        <div style="display: inline-block; margin: 0 10px;">
                            <a href="#" style="background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">✗ Not Interested</a>
                        </div>
                    </div>
                    
                    <br>
                    
                    <p style="color: #666; font-size: 12px; margin-top: 30px;">
                        This invitation was sent via ShareMyCard. You can respond using the buttons above.
                    </p>
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
