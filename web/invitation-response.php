<?php
/**
 * Invitation Response Handler
 * Public page to handle invitation responses (no login required)
 */

require_once __DIR__ . '/api/includes/Database.php';

$db = Database::getInstance();

$token = $_GET['token'] ?? '';
$response = $_GET['response'] ?? '';

$error = '';
$success = '';
$invitation = null;

// Validate token and get invitation
if ($token) {
    $invitation = $db->querySingle(
        "SELECT i.*, bc.name as card_name, bc.company, bc.title, 
                u.first_name as inviter_first_name, u.last_name as inviter_last_name
         FROM invitations i
         JOIN business_cards bc ON i.business_card_id = bc.id
         JOIN users u ON i.inviter_user_id = u.id
         WHERE i.invitation_token = ?",
        [$token]
    );
    
    if (!$invitation) {
        $error = 'Invalid invitation link.';
    } else {
        // Track opened_at if not already set
        if (!$invitation['opened_at']) {
            $db->execute(
                "UPDATE invitations SET opened_at = NOW() WHERE invitation_token = ?",
                [$token]
            );
        }
    }
} else {
    $error = 'Invalid invitation link.';
}

// Handle response
if ($invitation && $response && in_array($response, ['interested', 'not_interested'])) {
    // Update invitation with response
    $db->execute(
        "UPDATE invitations 
         SET response_type = ?, responded_at = NOW() 
         WHERE invitation_token = ?",
        [$response, $token]
    );
    
    if ($response === 'interested') {
        // Redirect to homepage with invitation tracking
        $redirectUrl = 'https://sharemycard.app/?ref=invitation&token=' . urlencode($token);
        header('Location: ' . $redirectUrl);
        exit;
    } else {
        $success = 'Thank you for your response. We appreciate your feedback.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Response - ShareMyCard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        
        .logo {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #363;
            border: 1px solid #cfc;
        }
        
        .invitation-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
        
        .invitation-info h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .invitation-info p {
            color: #666;
            margin-bottom: 5px;
        }
        
        .response-buttons {
            margin: 30px 0;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 0 10px 10px 0;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-interested {
            background: #28a745;
            color: white;
        }
        
        .btn-interested:hover {
            background: #218838;
        }
        
        .btn-not-interested {
            background: #6c757d;
            color: white;
        }
        
        .btn-not-interested:hover {
            background: #5a6268;
        }
        
        .btn-home {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">📱</div>
        <h1>ShareMyCard</h1>
        <p class="subtitle">Digital Business Cards</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <a href="https://sharemycard.app/" class="btn btn-home">Go to ShareMyCard</a>
        <?php elseif ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <a href="https://sharemycard.app/" class="btn btn-home">Visit ShareMyCard</a>
        <?php elseif ($invitation): ?>
            <div class="invitation-info">
                <h3>You're Invited!</h3>
                <p><strong><?php echo htmlspecialchars($invitation['inviter_first_name'] . ' ' . $invitation['inviter_last_name']); ?></strong> has invited you to check out their business card.</p>
                <p><strong>Card:</strong> <?php echo htmlspecialchars($invitation['card_name']); ?>
                <?php if ($invitation['company']): ?>
                    - <?php echo htmlspecialchars($invitation['company']); ?>
                <?php endif; ?>
                <?php if ($invitation['title']): ?>
                    (<?php echo htmlspecialchars($invitation['title']); ?>)
                <?php endif; ?>
                </p>
                <?php if ($invitation['comment']): ?>
                    <p><strong>Personal Message:</strong> "<?php echo htmlspecialchars($invitation['comment']); ?>"</p>
                <?php endif; ?>
            </div>
            
            <div class="response-buttons">
                <h3>Interested in creating your own digital business card?</h3>
                <p style="color: #666; margin-bottom: 20px;">ShareMyCard makes it easy to create and share professional digital business cards.</p>
                
                <a href="?token=<?php echo urlencode($token); ?>&response=interested" class="btn btn-interested">
                    Yes, I'm Interested
                </a>
                <a href="?token=<?php echo urlencode($token); ?>&response=not_interested" class="btn btn-not-interested">
                    No, Not Interested
                </a>
            </div>
            
            <div style="margin-top: 30px;">
                <a href="https://sharemycard.app/card.php?id=<?php echo urlencode($invitation['business_card_id']); ?>" 
                   class="btn btn-home" target="_blank">
                    View Business Card
                </a>
            </div>
        <?php endif; ?>
        
        <div class="footer">
            <p>This invitation was sent by <?php echo htmlspecialchars($invitation['inviter_first_name'] . ' ' . $invitation['inviter_last_name'] ?? 'ShareMyCard'); ?>.</p>
            <p>If you didn't expect this invitation, you can safely ignore this page.</p>
        </div>
    </div>
</body>
</html>
