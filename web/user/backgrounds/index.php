<?php
/**
 * Virtual Backgrounds – Landing Page
 * Explains the feature and asks the user to select a business card to continue.
 */

require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

UserAuth::requireAuth();

$user = UserAuth::getUser();
$db = Database::getInstance();

// Get user's active business cards
$cards = $db->query(
    "SELECT id, first_name, last_name, job_title, company_name, created_at FROM business_cards WHERE user_id = ? AND is_active = 1 ORDER BY created_at DESC",
    [UserAuth::getUserId()]
);

$hasCards = !empty($cards);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Backgrounds – ShareMyCard</title>
    <link rel="stylesheet" href="/user/includes/user-style.css">
    <style>
        .intro { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); margin-bottom: 24px; }
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; }
        .card-item { background: white; border: 1px solid #e9ecef; border-radius: 10px; padding: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .card-name { font-size: 18px; font-weight: 700; color: #333; }
        .muted { color: #777; font-size: 14px; }
        .actions { margin-top: 14px; display: flex; gap: 10px; flex-wrap: wrap; }
        .btn { padding: 10px 16px; border-radius: 8px; text-decoration: none; display: inline-block; transition: transform .15s, box-shadow .15s; font-weight: 600; }
        .btn-primary { background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%); color: #fff; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 6px 12px rgba(0,0,0,0.1); }
        .empty { text-align: center; background: #fff; border-radius: 12px; padding: 40px 20px; border: 1px solid #e9ecef; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navigation.php'; ?>

    <div class="main-container">
        <header class="page-header">
            <div>
                <h1>Virtual Backgrounds</h1>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
        </header>

        <div class="intro">
            <h2 style="margin:0 0 10px 0; color:#333;">Create branded virtual backgrounds</h2>
            <p class="muted" style="margin:0 0 10px 0;">
                Generate high-quality backgrounds for Zoom, Teams, and Meet featuring your card details and a scannable QR code.
            </p>
            <ul class="muted" style="margin:0 0 0 18px; line-height:1.8;">
                <li>Multiple layouts and accent colors</li>
                <li>Embedded QR code linked to your public card</li>
                <li>Export PNG sized for common video platforms</li>
            </ul>
        </div>

        <?php if (!$hasCards): ?>
            <div class="empty">
                <div style="font-size:40px; margin-bottom:10px;">🖼️</div>
                <h3 style="margin:0 0 8px 0;">No business cards yet</h3>
                <p class="muted" style="margin:0 0 16px 0;">Create a card first, then come back to build a background.</p>
                <a href="/user/cards/create.php" class="btn btn-primary" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);">Create Your First Card</a>
            </div>
        <?php else: ?>
            <h3 style="margin: 10px 0 16px 0; color:#333;">Select a card to continue</h3>
            <div class="cards-grid">
                <?php foreach ($cards as $card): ?>
                <div class="card-item">
                    <div class="card-name"><?php echo htmlspecialchars($card['first_name'] . ' ' . $card['last_name']); ?></div>
                    <?php if (!empty($card['job_title'])): ?>
                        <div class="muted"><?php echo htmlspecialchars($card['job_title']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($card['company_name'])): ?>
                        <div class="muted"><?php echo htmlspecialchars($card['company_name']); ?></div>
                    <?php endif; ?>
                    <div class="actions">
                        <a class="btn btn-primary" href="/user/cards/virtual-background.php?id=<?php echo urlencode($card['id']); ?>">Use This Card</a>
                        <a class="btn" style="background:#f5f5f5;color:#555;" href="/user/cards/view.php?id=<?php echo urlencode($card['id']); ?>">Preview Card</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>


