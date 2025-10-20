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
        
        /* Delete Modal Styles */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #333;
        }
        
        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        
        .close:hover {
            color: #000;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-body ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .modal-body li {
            margin: 5px 0;
            color: #666;
        }
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
            border: none;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            border: 1px solid #ef9a9a;
        }
        
        /* Impersonation Banner */
        .impersonation-banner {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            color: white;
            padding: 10px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .impersonation-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .impersonation-text {
            font-weight: 500;
            font-size: 14px;
        }
        
        .return-to-admin-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid rgba(255,255,255,0.3);
            transition: all 0.2s;
        }
        
        .return-to-admin-btn:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating']): ?>
    <div class="impersonation-banner">
        <div class="container">
            <div class="impersonation-content">
                <span class="impersonation-text">
                    🔍 You are viewing as <strong><?php echo htmlspecialchars($_SESSION['user_email']); ?></strong>
                </span>
                <a href="/admin/end-impersonation.php" class="return-to-admin-btn">
                    Return to Admin Dashboard
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <nav class="navbar">
        <div class="nav-brand">
            <a href="/user/dashboard.php">📱 ShareMyCard</a>
        </div>
        <div class="nav-links">
            <a href="/user/dashboard.php" class="nav-link">Dashboard</a>
            <a href="#" onclick="openAccountSecurity()" class="nav-link">🔒 Security</a>
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
                            <a href="/user/cards/virtual-background.php?id=<?php echo urlencode($card['id']); ?>" class="btn-small btn-secondary" style="background: #9b59b6; color: white;">
                                🖼️ Virtual Background
                            </a>
                            <a href="/user/cards/name-tags.php?id=<?php echo urlencode($card['id']); ?>" class="btn-small btn-secondary" style="background: #27ae60; color: white;">
                                🏷️ Name Tags
                            </a>
                            <a href="/card.php?id=<?php echo urlencode($card['id']); ?>" class="btn-small btn-secondary" style="background: #e67e22; color: white;" target="_blank">
                                👁️ View Public Card
                            </a>
                            <button onclick="shareCard('<?php echo urlencode($card['id']); ?>')" class="btn-small btn-secondary" style="background: #4CAF50; color: white;">
                                🔗 Share Card
                            </button>
                            <button onclick="deleteCard('<?php echo urlencode($card['id']); ?>')" class="btn-small btn-secondary" style="background: #e74c3c; color: white;">
                                🗑️ Delete Card
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    </div>
    
    <!-- Account Security Modal -->
    <?php 
    $userId = UserAuth::getUserId();
    include __DIR__ . '/includes/account-security-modal.php'; 
    ?>
    
    <!-- Delete Card Modal -->
    <div id="deleteModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🗑️ Delete Business Card</h3>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this business card?</p>
                <p><strong>This action cannot be undone.</strong></p>
                <p>This will also delete:</p>
                <ul>
                    <li>All analytics data for this card</li>
                    <li>All media files (photos, logos, cover graphics)</li>
                    <li>All contact information</li>
                </ul>
                <div id="deleteError" class="error-message" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
                <button onclick="confirmDelete()" class="btn btn-danger" id="deleteConfirmBtn">
                    <span id="deleteBtnText">Delete Card</span>
                    <span id="deleteBtnSpinner" style="display: none;">⏳ Deleting...</span>
                </button>
            </div>
        </div>
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
        
        let currentDeleteCardId = null;
        
        function deleteCard(cardId) {
            currentDeleteCardId = cardId;
            document.getElementById('deleteModal').style.display = 'flex';
            document.getElementById('deleteError').style.display = 'none';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            currentDeleteCardId = null;
        }
        
        function confirmDelete() {
            if (!currentDeleteCardId) return;
            
            const deleteBtn = document.getElementById('deleteConfirmBtn');
            const btnText = document.getElementById('deleteBtnText');
            const btnSpinner = document.getElementById('deleteBtnSpinner');
            const errorDiv = document.getElementById('deleteError');
            
            // Show loading state
            deleteBtn.disabled = true;
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline';
            errorDiv.style.display = 'none';
            
            // Use session-based authentication (no JWT needed)
            const formData = new FormData();
            formData.append('card_id', currentDeleteCardId);
            
            fetch('/user/api/delete-card.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Success - close modal and reload
                    closeDeleteModal();
                    location.reload();
                } else {
                    // Show error in modal
                    errorDiv.textContent = 'Error: ' + (data.message || 'Failed to delete card');
                    errorDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                errorDiv.textContent = 'Error deleting card. Please try again.';
                errorDiv.style.display = 'block';
            })
            .finally(() => {
                // Reset button state
                deleteBtn.disabled = false;
                btnText.style.display = 'inline';
                btnSpinner.style.display = 'none';
            });
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>
