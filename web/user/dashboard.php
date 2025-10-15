<?php
/**
 * User Dashboard
 */

require_once __DIR__ . '/includes/UserAuth.php';
require_once __DIR__ . '/../api/includes/Database.php';

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
    <title>My Business Cards - ShareMyCard</title>
    <link rel="stylesheet" href="/user/includes/user-style.css">
    <style>
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .card-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .card-name {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .card-title {
            font-size: 14px;
            color: #666;
            margin-bottom: 3px;
        }
        
        .card-company {
            font-size: 14px;
            color: #888;
        }
        
        .card-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 14px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #f5f5f5;
            color: #666;
        }
        
        .btn-small:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #666;
            margin-bottom: 30px;
        }
        
        .btn-large {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-large:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(76, 175, 80, 0.3);
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .nav-brand a {
            color: white;
            text-decoration: none;
        }
        
        .nav-brand a:hover {
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <a href="/user/dashboard.php">📱 ShareMyCard</a>
        </div>
        <div class="nav-links">
            <a href="/user/dashboard.php" class="nav-link">Dashboard</a>
            <a href="/user/logout.php" class="nav-link">Logout</a>
        </div>
    </nav>
    
    <div class="main-container">
        <header class="page-header">
            <div>
                <h1>My Business Cards</h1>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            <a href="/user/cards/create.php" class="btn-large">+ Create New Card</a>
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
                            <a href="/user/cards/view.php?id=<?php echo urlencode($card['id']); ?>" class="btn-small btn-primary">
                                👁️ View
                            </a>
                            <a href="/user/cards/edit.php?id=<?php echo urlencode($card['id']); ?>" class="btn-small btn-secondary">
                                ✏️ Edit Card
                            </a>
                            <a href="/user/cards/analytics.php?card_id=<?php echo urlencode($card['id']); ?>" class="btn-small btn-secondary" style="background: #667eea; color: white;">
                                📊 View Analytics
                            </a>
                            <a href="/user/cards/qr.php?id=<?php echo urlencode($card['id']); ?>" class="btn-small btn-secondary">
                                📱 Generate QR Code
                            </a>
                            <a href="/card.php?id=<?php echo urlencode($card['id']); ?>" class="btn-small btn-secondary" style="background: #e67e22; color: white;" target="_blank">
                                👁️ View Public Card
                            </a>
                            <button onclick="shareCard('<?php echo urlencode($card['id']); ?>')" class="btn-small btn-secondary" style="background: #4CAF50; color: white;">
                                🔗 Share Card
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function shareCard(cardId) {
            const url = `https://sharemycard.app/card.php?id=${cardId}`;
            
            if (navigator.share) {
                // Use native sharing if available
                navigator.share({
                    title: 'My Business Card',
                    text: 'Check out my digital business card!',
                    url: url
                }).catch(err => {
                    console.log('Error sharing:', err);
                    fallbackShare(url);
                });
            } else {
                // Fallback to clipboard
                fallbackShare(url);
            }
        }
        
        function fallbackShare(url) {
            // Copy to clipboard
            navigator.clipboard.writeText(url).then(() => {
                alert('Card link copied to clipboard!');
            }).catch(() => {
                // Final fallback - show URL in prompt
                prompt('Copy this link to share your card:', url);
            });
        }
    </script>
</body>
</html>
