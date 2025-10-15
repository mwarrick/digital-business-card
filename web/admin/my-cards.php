<?php
/**
 * Admin's Personal Business Cards
 */

require_once __DIR__ . '/includes/AdminAuth.php';
require_once __DIR__ . '/../api/includes/Database.php';

AdminAuth::requireAuth();

$db = Database::getInstance();

// Get admin's business cards
$cards = $db->query(
    "SELECT * FROM business_cards WHERE user_id = (SELECT id FROM users WHERE email = ?) AND is_active = 1 ORDER BY created_at DESC",
    [AdminAuth::getEmail()]
);

$cardCount = count($cards);

// Get admin user ID for creating cards
$adminUser = $db->querySingle(
    "SELECT id FROM users WHERE email = ?",
    [AdminAuth::getEmail()]
);
$adminUserId = $adminUser['id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Business Cards - ShareMyCard Admin</title>
    <link rel="stylesheet" href="/admin/includes/admin-style.css">
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
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 14px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        
        .btn-primary-small {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary-small {
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
        
        .create-card-btn {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .create-card-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .page-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <div class="page-actions">
            <div>
                <h1>📇 My Business Cards</h1>
                <p class="subtitle">Manage your personal business cards</p>
            </div>
            <a href="/admin/cards/create.php" class="create-card-btn">+ Create New Card</a>
        </div>
        
        <?php if ($cardCount === 0): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📇</div>
                <h3>No Business Cards Yet</h3>
                <p>Create your first digital business card to get started!</p>
                <a href="/admin/cards/create.php" class="create-card-btn">+ Create Your First Card</a>
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
                            <a href="/admin/cards/view.php?id=<?php echo urlencode($card['id']); ?>" class="btn-small btn-primary-small">
                                View
                            </a>
                            <a href="/admin/cards/edit.php?id=<?php echo urlencode($card['id']); ?>" class="btn-small btn-secondary-small">
                                Edit
                            </a>
                            <a href="/admin/cards/qr.php?id=<?php echo urlencode($card['id']); ?>" class="btn-small btn-secondary-small">
                                QR Code
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

