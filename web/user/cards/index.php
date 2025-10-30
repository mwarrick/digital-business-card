<?php
/**
 * User Business Cards Page
 * Standalone view of user's business cards with the same actions as dashboard
 */

require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

UserAuth::requireAuth();

$user = UserAuth::getUser();
$db = Database::getInstance();

// Get user's business cards
$cards = $db->query(
    "SELECT * FROM business_cards WHERE user_id = ? AND is_active = 1 ORDER BY created_at DESC",
    [UserAuth::getUserId()]
);

$cardCount = count($cards);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Cards – ShareMyCard</title>
    <link rel="stylesheet" href="/user/includes/user-style.css">
    <style>
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .card-item { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.2s, box-shadow 0.2s; }
        .card-item:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .card-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; }
        .card-name { font-size: 20px; font-weight: bold; color: #333; margin-bottom: 5px; }
        .card-title { font-size: 14px; color: #666; margin-bottom: 3px; }
        .card-company { font-size: 14px; color: #888; }
        .card-actions { display: flex; gap: 8px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; flex-wrap: wrap; }
        .btn-small { padding: 8px 16px; font-size: 14px; border-radius: 6px; text-decoration: none; display: inline-block; transition: all 0.2s; }
        .btn-secondary { background: #f5f5f5; color: #666; }
        .btn-primary { background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%); color: white; }
        .btn-small:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .card-info { margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #666; }
        .card-id { font-family: monospace; background: #f0f0f0; padding: 2px 6px; border-radius: 4px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 10px; margin-top: 20px; }
        .empty-state-icon { font-size: 64px; margin-bottom: 20px; }
        .btn-large { display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.2s; }
        .btn-large:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3); }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navigation.php'; ?>

    <div class="main-container">
        <header class="page-header">
            <div>
                <h1>Business Cards</h1>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            <a href="/user/cards/create.php" class="btn-large">➕ New Card</a>
        </header>

        <?php if ($cardCount === 0): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📇</div>
                <h3>No Business Cards Yet</h3>
                <p>Create your first digital business card to get started!</p>
                <a href="/user/cards/create.php" class="btn-large">+ Create Your First Card</a>
            </div>
        <?php else: ?>
            <div class="cards-grid">
                <?php foreach ($cards as $card): ?>
                    <div class="card-item">
                        <div class="card-header">
                            <div>
                                <div class="card-name">
                                    <?php echo htmlspecialchars($card['first_name'] . ' ' . $card['last_name']); ?>
                                </div>
                                <?php if (!empty($card['job_title'])): ?>
                                    <div class="card-title"><?php echo htmlspecialchars($card['job_title']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($card['company_name'])): ?>
                                    <div class="card-company"><?php echo htmlspecialchars($card['company_name']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card-actions">
                            <a href="/user/cards/view.php?id=<?php echo urlencode($card['id']); ?>" class="btn-small btn-primary">👁️ View</a>
                            <a href="/user/cards/edit.php?id=<?php echo urlencode($card['id']); ?>" class="btn-small btn-secondary">✏️ Edit Card</a>
                            <a href="/user/cards/analytics.php?card_id=<?php echo urlencode($card['id']); ?>" class="btn-small btn-secondary" style="background: #667eea; color: white;">📊 View Analytics</a>
                            <a href="/user/cards/email-signature.php?card_id=<?php echo urlencode($card['id']); ?>" class="btn-small btn-secondary" style="background: #9C27B0; color: white;">📧 Email Signature</a>
                            <a href="/user/cards/virtual-background.php?id=<?php echo urlencode($card['id']); ?>" class="btn-small btn-secondary" style="background: #9b59b6; color: white;">🖼️ Virtual Background</a>
                            <a href="/user/cards/name-tags.php?id=<?php echo urlencode($card['id']); ?>" class="btn-small btn-secondary" style="background: #27ae60; color: white;">🏷️ Name Tags</a>
                            <a href="/user/cards/invite.php?card_id=<?php echo urlencode($card['id']); ?>" class="btn-small btn-secondary" style="background: #3498db; color: white;">✉️ Invite Someone</a>
                            <a href="/card.php?id=<?php echo urlencode($card['id']); ?>" class="btn-small btn-secondary" style="background: #e67e22; color: white;" target="_blank">👁️ View Public Card</a>
                        </div>

                        <div class="card-info">
                            <div class="card-id">Card ID: #<?php echo substr($card['id'], 0, 8); ?></div>
                            <div class="card-date">Created: <?php echo date('M d, Y', strtotime($card['created_at'])); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>


