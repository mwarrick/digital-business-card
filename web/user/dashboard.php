<?php
/**
 * User Dashboard
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/includes/UserAuth.php';
    require_once __DIR__ . '/../api/includes/Database.php';

    UserAuth::requireAuth();
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    die("Dashboard configuration error: " . $e->getMessage());
}

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
    <title>Dashboard - ShareMyCard</title>
    <link rel="stylesheet" href="/user/includes/user-style.css">
    <style>
        .mobile-notice {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            display: none;
        }
        
        .mobile-notice.show {
            display: block;
        }
        
        .mobile-notice .icon {
            font-size: 24px;
            margin-right: 10px;
        }
        
        .mobile-notice .content {
            display: flex;
            align-items: center;
        }
        
        .mobile-notice .close-btn {
            margin-left: auto;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
        }
    </style>
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
        
        .card-info {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #666;
        }
        
        .card-id {
            font-family: monospace;
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 4px;
        }
        
        .card-date {
            color: #888;
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

        /* Feature cards - match admin nav card feel */
        .features .feature-card {
            background: #ffffff;
            border: 2px solid transparent;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        .features .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.15);
            border-color: #3498db;
        }
        .features .feature-card .feature-icon {
            transition: transform 0.3s ease;
        }
        .features .feature-card:hover .feature-icon {
            transform: scale(1.1);
        }
        /* Features grid: match admin layout */
        .features { display: grid; gap: 20px; margin: 40px 0; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
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
    
    <?php include __DIR__ . '/includes/navigation.php'; ?>
    
    <div class="main-container">
        <header class="page-header">
            <div>
                <h1>Dashboard</h1>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
        </header>
        
        <!-- Mobile Browser Notice -->
        <div id="mobile-notice" class="mobile-notice">
            <div class="content">
                <span class="icon">📱</span>
                <div>
                    <strong>Mobile Browser Detected</strong><br>
                    <span id="browser-info">We see you're using a mobile browser.</span> Some functions of this website work best on computers.
                </div>
                <button class="close-btn" onclick="document.getElementById('mobile-notice').style.display='none'">&times;</button>
            </div>
        </div>
        
        <!-- Features Grid -->
        <div class="features">
            <a href="/user/cards/" style="text-decoration: none; color: inherit;">
                <div class="feature-card" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 15px; padding: 25px; text-align: center;">
                    <div class="feature-icon" style="font-size: 40px; margin-bottom: 12px;">📇</div>
                    <h3 style="margin: 0 0 8px 0; color: #333; font-size: 18px;">Business Cards</h3>
                    <p style="color: #666; font-size: 14px;">View and manage all of your digital business cards.</p>
                </div>
            </a>
            <a href="/user/leads/index.php" style="text-decoration: none; color: inherit;">
                <div class="feature-card" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 15px; padding: 25px; text-align: center;">
                    <div class="feature-icon" style="font-size: 40px; margin-bottom: 12px;">📋</div>
                    <h3 style="margin: 0 0 8px 0; color: #333; font-size: 18px;">Leads</h3>
                    <p style="color: #666; font-size: 14px;">View, manage, and convert leads captured from cards and custom QR pages.</p>
                </div>
            </a>
            <a href="/user/contacts/index.php" style="text-decoration: none; color: inherit;">
                <div class="feature-card" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 15px; padding: 25px; text-align: center;">
                    <div class="feature-icon" style="font-size: 40px; margin-bottom: 12px;">👥</div>
                    <h3 style="margin: 0 0 8px 0; color: #333; font-size: 18px;">Contacts</h3>
                    <p style="color: #666; font-size: 14px;">Your saved contacts and converted leads, all in one place.</p>
                </div>
            </a>
            <a href="/user/cards/analytics.php" style="text-decoration: none; color: inherit;">
                <div class="feature-card" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 15px; padding: 25px; text-align: center;">
                    <div class="feature-icon" style="font-size: 40px; margin-bottom: 12px;">📊</div>
                    <h3 style="margin: 0 0 8px 0; color: #333; font-size: 18px;">Analytics</h3>
                    <p style="color: #666; font-size: 14px;">View analytics for your business cards. Select a card to drill down.</p>
                </div>
            </a>
            <a href="/user/signatures/" style="text-decoration: none; color: inherit;">
                <div class="feature-card" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 15px; padding: 25px; text-align: center;">
                    <div class="feature-icon" style="font-size: 40px; margin-bottom: 12px;">✉️</div>
                    <h3 style="margin: 0 0 8px 0; color: #333; font-size: 18px;">Email Signatures</h3>
                    <p style="color: #666; font-size: 14px;">Generate a polished email signature from one of your business cards.</p>
                </div>
            </a>
            <a href="/user/cards/invitation-analytics.php" style="text-decoration: none; color: inherit;">
                <div class="feature-card" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 15px; padding: 25px; text-align: center;">
                    <div class="feature-icon" style="font-size: 40px; margin-bottom: 12px;">📧</div>
                    <h3 style="margin: 0 0 8px 0; color: #333; font-size: 18px;">Invitations</h3>
                    <p style="color: #666; font-size: 14px;">Send invitations to view your card and monitor responses.</p>
                </div>
            </a>
            <a href="/user/backgrounds/" style="text-decoration: none; color: inherit;">
                <div class="feature-card" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 15px; padding: 25px; text-align: center;">
                    <div class="feature-icon" style="font-size: 40px; margin-bottom: 12px;">🖼️</div>
                    <h3 style="margin: 0 0 8px 0; color: #333; font-size: 18px;">Virtual Backgrounds</h3>
                    <p style="color: #666; font-size: 14px;">Create branded backgrounds with your QR code for video calls.</p>
                </div>
            </a>
            <a href="/user/name-tags/" style="text-decoration: none; color: inherit;">
                <div class="feature-card" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 15px; padding: 25px; text-align: center;">
                    <div class="feature-icon" style="font-size: 40px; margin-bottom: 12px;">🏷️</div>
                    <h3 style="margin: 0 0 8px 0; color: #333; font-size: 18px;">Name Tags</h3>
                    <p style="color: #666; font-size: 14px;">Generate printable name tags with QR codes for events and networking.</p>
                </div>
            </a>
            <a href="/user/qr/" style="text-decoration: none; color: inherit;">
                <div class="feature-card" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 15px; padding: 25px; text-align: center;">
                    <div class="feature-icon" style="font-size: 40px; margin-bottom: 12px;">🔳</div>
                    <h3 style="margin: 0 0 8px 0; color: #333; font-size: 18px;">Custom QR Codes</h3>
                    <p style="color: #666; font-size: 14px;">Create URL, social, text, Wi‑Fi, or app store QR codes with analytics.</p>
                </div>
            </a>
            <a href="https://github.com/mwarrick/digital-business-card/issues" target="_blank" style="text-decoration: none; color: inherit;">
                <div class="feature-card" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 15px; padding: 25px; text-align: center;">
                    <div class="feature-icon" style="font-size: 40px; margin-bottom: 12px;">🐛</div>
                    <h3 style="margin: 0 0 8px 0; color: #333; font-size: 18px;">Report Issues</h3>
                    <p style="color: #666; font-size: 14px;">Found a bug or have feedback? Open an issue on GitHub.</p>
                </div>
            </a>
            <a href="#" onclick="openAccountSecurity(); return false;" style="text-decoration: none; color: inherit;">
                <div class="feature-card" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 15px; padding: 25px; text-align: center;">
                    <div class="feature-icon" style="font-size: 40px; margin-bottom: 12px;">🔒</div>
                    <h3 style="margin: 0 0 8px 0; color: #333; font-size: 18px;">Security</h3>
                    <p style="color: #666; font-size: 14px;">Manage login security and verification settings for your account.</p>
                </div>
            </a>
            <a href="/user/logout.php" style="text-decoration: none; color: inherit;">
                <div class="feature-card" style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 15px; padding: 25px; text-align: center;">
                    <div class="feature-icon" style="font-size: 40px; margin-bottom: 12px;">🚪</div>
                    <h3 style="margin: 0 0 8px 0; color: #333; font-size: 18px;">Logout</h3>
                    <p style="color: #666; font-size: 14px;">Sign out of your account securely.</p>
                </div>
            </a>
        </div>
        
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
        
        // Browser Detection
        function detectBrowser() {
            const userAgent = navigator.userAgent;
            const screenWidth = window.innerWidth;
            
            // Only show notice for mobile devices or small screens
            if (screenWidth <= 768) {
                let browserName = 'a mobile browser';
                
                // Detect specific mobile browsers
                if (userAgent.includes('iPhone')) {
                    browserName = 'Safari on iPhone';
                } else if (userAgent.includes('iPad')) {
                    browserName = 'Safari on iPad';
                } else if (userAgent.includes('Android')) {
                    if (userAgent.includes('Chrome')) {
                        browserName = 'Chrome on Android';
                    } else {
                        browserName = 'Android browser';
                    }
                } else if (userAgent.includes('SamsungBrowser')) {
                    browserName = 'Samsung Internet';
                } else if (userAgent.includes('Opera')) {
                    browserName = 'Opera Mobile';
                } else if (userAgent.includes('Firefox')) {
                    browserName = 'Firefox Mobile';
                } else if (userAgent.includes('Edge')) {
                    browserName = 'Edge Mobile';
                }
                
                // Update browser info and show notice
                document.getElementById('browser-info').textContent = `We see you're using ${browserName}.`;
                document.getElementById('mobile-notice').classList.add('show');
            }
        }
        
        // Run detection when page loads
        document.addEventListener('DOMContentLoaded', detectBrowser);
    </script>
</body>
</html>
