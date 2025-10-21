<?php
/**
 * Admin Tool: Link User to Invitation
 * Manually link an existing user account to an invitation record
 */

require_once __DIR__ . '/includes/AdminAuth.php';
require_once __DIR__ . '/../api/includes/Database.php';

// Require admin authentication
AdminAuth::requireAuth();

$db = Database::getInstance();
$success = '';
$error = '';

// Handle form submission
if ($_POST) {
    $userEmail = trim($_POST['user_email'] ?? '');
    $invitationEmail = trim($_POST['invitation_email'] ?? '');
    
    if (!$userEmail || !$invitationEmail) {
        $error = 'Both user email and invitation email are required.';
    } else {
        try {
            // Find the user
            $user = $db->querySingle(
                "SELECT id, email FROM users WHERE email = ? AND is_active = 1",
                [$userEmail]
            );
            
            if (!$user) {
                $error = 'User not found or not active.';
            } else {
                // Find the invitation
                $invitation = $db->querySingle(
                    "SELECT id, invitee_email, created_account FROM invitations 
                     WHERE invitee_email = ? AND created_account = 0",
                    [$invitationEmail]
                );
                
                if (!$invitation) {
                    $error = 'Invitation not found or already linked to an account.';
                } else {
                    // Link the invitation to the user
                    $db->execute(
                        "UPDATE invitations 
                         SET created_account = 1, created_account_at = NOW(), registered_user_id = ? 
                         WHERE id = ?",
                        [$user['id'], $invitation['id']]
                    );
                    
                    $success = "Successfully linked user {$user['email']} to invitation for {$invitationEmail}";
                }
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Get recent invitations that aren't linked
$unlinkedInvitations = $db->query(
    "SELECT i.*, CONCAT(bc.first_name, ' ', bc.last_name) as card_name, bc.company_name
     FROM invitations i
     JOIN business_cards bc ON i.business_card_id = bc.id
     WHERE i.created_account = 0
     ORDER BY i.sent_at DESC
     LIMIT 20"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Invitation - ShareMyCard Admin</title>
    <link rel="stylesheet" href="/admin/includes/admin-style.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="admin-container">
        <div class="page-header">
            <h1>🔗 Link User to Invitation</h1>
            <p>Manually link an existing user account to an invitation record.</p>
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
        
        <div class="form-section">
            <h2>Link User to Invitation</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="user_email">User Email (existing account):</label>
                    <input type="email" id="user_email" name="user_email" required 
                           placeholder="user@example.com">
                </div>
                
                <div class="form-group">
                    <label for="invitation_email">Invitation Email:</label>
                    <input type="email" id="invitation_email" name="invitation_email" required 
                           placeholder="invitee@example.com">
                </div>
                
                <button type="submit" class="btn btn-primary">Link Accounts</button>
            </form>
        </div>
        
        <div class="recent-section">
            <h2>Recent Unlinked Invitations</h2>
            <p>These invitations haven't been linked to user accounts yet:</p>
            
            <?php if (empty($unlinkedInvitations)): ?>
                <p>No unlinked invitations found.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Invitee</th>
                            <th>Email</th>
                            <th>Business Card</th>
                            <th>Sent</th>
                            <th>Response</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($unlinkedInvitations as $invitation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($invitation['invitee_first_name'] . ' ' . $invitation['invitee_last_name']); ?></td>
                                <td><?php echo htmlspecialchars($invitation['invitee_email']); ?></td>
                                <td><?php echo htmlspecialchars($invitation['card_name']); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($invitation['sent_at'])); ?></td>
                                <td>
                                    <?php if ($invitation['response_type'] === 'interested'): ?>
                                        <span style="color: #28a745;">Interested</span>
                                    <?php elseif ($invitation['response_type'] === 'not_interested'): ?>
                                        <span style="color: #dc3545;">Not Interested</span>
                                    <?php else: ?>
                                        <span style="color: #999;">No Response</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
        .form-section, .recent-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .admin-table th,
        .admin-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .admin-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
    </style>
</body>
</html>
