<?php
/**
 * View Enhanced Business Card (displays all contact information)
 */

require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../includes/themes.php';

UserAuth::requireAuth();

$user = UserAuth::getUser();
$cardId = $_GET['id'] ?? '';

if (empty($cardId)) {
    header('Location: /user/dashboard.php');
    exit;
}

$db = Database::getInstance();

// Get card
$card = $db->querySingle(
    "SELECT * FROM business_cards WHERE id = ? AND user_id = ? AND is_active = 1",
    [$cardId, UserAuth::getUserId()]
);

if (!$card) {
    header('Location: /user/dashboard.php?error=not_found');
    exit;
}

// Get additional contact info
$emails = $db->query(
    "SELECT * FROM email_contacts WHERE business_card_id = ? ORDER BY is_primary DESC, created_at ASC",
    [$cardId]
);

$phones = $db->query(
    "SELECT * FROM phone_contacts WHERE business_card_id = ? ORDER BY created_at ASC",
    [$cardId]
);

$websites = $db->query(
    "SELECT * FROM website_links WHERE business_card_id = ? ORDER BY is_primary DESC, created_at ASC",
    [$cardId]
);

$address = $db->querySingle(
    "SELECT * FROM addresses WHERE business_card_id = ?",
    [$cardId]
);

// Get theme
$theme = $card['theme'] ?? 'professional-blue';
$themeCSS = generateThemeCSS($theme);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($card['first_name'] . ' ' . $card['last_name']); ?> - ShareMyCard</title>
    <link rel="stylesheet" href="/user/includes/user-style.css">
    <style>
        <?php echo $themeCSS; ?>
        .card-preview {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .cover-graphic {
            width: calc(100% + 80px);
            margin: -40px -40px 30px -40px;
            border-radius: 20px 20px 0 0;
            max-height: 300px;
            object-fit: cover;
        }
        
        .card-header {
            padding: 30px;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 30px;
            position: relative;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .profile-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            flex-shrink: 0;
        }
        
        .card-info {
            flex: 1;
        }
        
        .company-logo {
            max-width: 80px;
            max-height: 60px;
            object-fit: contain;
            flex-shrink: 0;
        }
        
        /* Image Sections */
        .image-section {
            margin-bottom: 30px;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 12px;
            text-align: center;
        }
        
        .image-section h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }
        
        .image-display {
            margin: 20px auto;
            max-width: 400px;
        }
        
        .image-display img {
            max-width: 100%;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .image-display.profile img {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .image-display.logo img {
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
            border-radius: 12px;
        }
        
        .image-display.cover img {
            width: 100%;
            max-height: 250px;
            object-fit: cover;
            border-radius: 12px;
        }
        
        .image-placeholder {
            padding: 60px 20px;
            background: white;
            border: 2px dashed #ddd;
            border-radius: 12px;
            color: #999;
        }
        
        .image-placeholder .icon {
            font-size: 64px;
            margin-bottom: 15px;
        }
        
        .image-placeholder .text {
            font-size: 16px;
            color: #666;
        }
        
        @media (max-width: 600px) {
            .card-header {
                flex-direction: column;
                text-align: center;
            }
        }
        
        .card-name {
            font-size: 32px;
            font-weight: bold;
            color: var(--text-color);
            margin-bottom: 10px;
        }
        
        .card-title {
            font-size: 18px;
            color: var(--accent-color);
            margin-bottom: 5px;
        }
        
        .card-company {
            font-size: 16px;
            color: var(--text-light);
        }
        
        .card-section {
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .card-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .card-section-title {
            font-size: 14px;
            text-transform: uppercase;
            color: var(--accent-color);
            margin-bottom: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-section-content {
            font-size: 16px;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .contact-item {
            display: flex;
            align-items: flex-start;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .contact-item:last-child {
            margin-bottom: 0;
        }
        
        .contact-item-icon {
            margin-right: 12px;
            font-size: 18px;
            min-width: 20px;
        }
        
        .contact-item-content {
            flex: 1;
        }
        
        .contact-item-value {
            font-size: 16px;
            color: #333;
            margin-bottom: 4px;
        }
        
        .contact-item-value a {
            color: #667eea;
            text-decoration: none;
        }
        
        .contact-item-value a:hover {
            text-decoration: underline;
        }
        
        .contact-item-label {
            font-size: 13px;
            color: #999;
        }
        
        .card-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #f5f5f5;
            color: #666;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .bio-content {
            white-space: pre-wrap;
            word-break: break-word;
        }
        
        @media (max-width: 768px) {
            .card-preview {
                padding: 25px;
            }
            
            .card-name {
                font-size: 24px;
            }
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
    </style>
    <!-- Define deleteCard function in HEAD to ensure it's available before body renders -->
    <script type="text/javascript">
        // CRITICAL: Define deleteCard in head before body loads
        window.deleteCard = function(cardId) {
            window.currentDeleteCardId = cardId;
            var modal = document.getElementById('deleteModal');
            var errorDiv = document.getElementById('deleteError');
            if (modal) {
                modal.style.display = 'flex';
            }
            if (errorDiv) {
                errorDiv.style.display = 'none';
            }
        };
        window.closeDeleteModal = function() {
            var modal = document.getElementById('deleteModal');
            if (modal) {
                modal.style.display = 'none';
            }
            window.currentDeleteCardId = null;
        };
        window.currentDeleteCardId = null;
        console.log('deleteCard function defined in HEAD');
        
        // Also attach event listener when DOM is ready as backup
        document.addEventListener('DOMContentLoaded', function() {
            var deleteBtn = document.getElementById('deleteCardBtn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var cardId = this.getAttribute('data-card-id');
                    if (cardId) {
                        window.deleteCard(cardId);
                    }
                });
                console.log('Delete button event listener attached');
            } else {
                console.error('Delete button not found');
            }
        });
    </script>
</head>
<body>
    <?php include __DIR__ . '/../includes/navigation.php'; ?>
    
    <div class="main-container">
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">
                ✅ Business card updated successfully!
            </div>
        <?php endif; ?>
        
        <!-- Action Buttons -->
        <div class="card-actions" style="margin-bottom: 30px; justify-content: flex-start;">
            <a href="/user/cards/edit.php?id=<?php echo urlencode($cardId); ?>" class="btn btn-primary">
                ✏️ Edit Card
            </a>
            <button onclick="duplicateCard('<?php echo $cardId; ?>')" class="btn btn-secondary" style="background: #17a2b8; color: white; border: none;">
                📋 Duplicate Card
            </button>
            <a href="/user/cards/analytics.php?card_id=<?php echo urlencode($cardId); ?>" class="btn btn-secondary" style="background: #667eea; color: white; border: none;">
                📊 View Analytics
            </a>
            <a href="/user/cards/qr.php?id=<?php echo urlencode($cardId); ?>" class="btn btn-secondary">
                📱 Generate QR Code
            </a>
            <a href="/user/cards/virtual-background.php?id=<?php echo urlencode($cardId); ?>" class="btn btn-secondary" style="background: #9b59b6; color: white; border: none;">
                🖼️ Create Virtual Background
            </a>
            <a href="/user/cards/name-tags.php?id=<?php echo urlencode($cardId); ?>" class="btn btn-secondary" style="background: #27ae60; color: white; border: none;">
                🏷️ Print Name Tags
            </a>
            <a href="/card.php?id=<?php echo urlencode($cardId); ?>" class="btn btn-secondary" style="background: #e67e22; color: white; border: none;" target="_blank">
                👁️ View Public Card
            </a>
            <a href="/user/cards/invite.php?card_id=<?php echo urlencode($cardId); ?>" class="btn btn-secondary" style="background: #3498db; color: white; border: none;">
                ✉️ Invite Someone
            </a>
            <button onclick="shareCard()" class="btn btn-secondary" style="background: #4CAF50; color: white; border: none;">
                🔗 Share Card
            </button>
            <button id="deleteCardBtn" data-card-id="<?php echo htmlspecialchars($cardId, ENT_QUOTES); ?>" class="btn btn-secondary" style="background: #e74c3c; color: white; border: none;">
                🗑️ Delete Card
            </button>
            <a href="/user/dashboard.php" class="btn btn-secondary">
                ← Back to Dashboard
            </a>
        </div>
        
        <div class="card-preview">
            <?php if (!empty($card['cover_graphic_path'])): ?>
                <img src="/api/media/view?filename=<?php echo urlencode($card['cover_graphic_path']); ?>" 
                     alt="Cover Graphic" 
                     class="cover-graphic"
                     onerror="this.style.display='none'">
            <?php else: ?>
                <!-- Default gradient cover -->
                <div style="width: calc(100% + 80px); margin: -40px -40px 30px -40px; height: 150px; background: linear-gradient(135deg, rgba(102,126,234,0.8), rgba(118,75,162,0.6)); border-radius: 20px 20px 0 0;"></div>
            <?php endif; ?>
            
            <div class="card-header">
                <!-- Profile Photo -->
                <?php if (!empty($card['profile_photo_path'])): ?>
                    <img src="/api/media/view?filename=<?php echo urlencode($card['profile_photo_path']); ?>" 
                         alt="<?php echo htmlspecialchars($card['first_name']); ?>'s photo" 
                         class="profile-photo"
                         onerror="this.outerHTML='<div class=\'profile-photo\' style=\'background:#f0f0f0;display:flex;align-items:center;justify-content:center;font-size:40px;\'>👤</div>'">
                <?php else: ?>
                    <div class="profile-photo" style="background:#f0f0f0;display:flex;align-items:center;justify-content:center;font-size:40px;">👤</div>
                <?php endif; ?>
                
                <!-- Name and Title -->
                <div class="card-info">
                    <div class="card-name">
                        <?php echo htmlspecialchars($card['first_name'] . ' ' . $card['last_name']); ?>
                    </div>
                    <?php if (!empty($card['job_title'])): ?>
                        <div class="card-title"><?php echo htmlspecialchars($card['job_title']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Company Logo -->
                <?php if (!empty($card['company_logo_path'])): ?>
                    <img src="/api/media/view?filename=<?php echo urlencode($card['company_logo_path']); ?>" 
                         alt="Company Logo" 
                         class="company-logo"
                         onerror="this.style.display='none'">
                <?php endif; ?>
            </div>
            
            <!-- Company Name -->
            <?php if (!empty($card['company_name'])): ?>
                <div style="text-align: center; margin-bottom: 20px; font-size: 18px; color: #666;">
                    <?php echo htmlspecialchars($card['company_name']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Image Sections -->
            
            <!-- Profile Photo Section -->
            <div class="image-section">
                <h3>Profile Photo</h3>
                <div class="image-display profile">
                    <?php if (!empty($card['profile_photo_path'])): ?>
                        <img src="/api/media/view?filename=<?php echo urlencode($card['profile_photo_path']); ?>" 
                             alt="Profile Photo"
                             onerror="this.parentElement.innerHTML='<div class=\'image-placeholder\'><div class=\'icon\'>👤</div><div class=\'text\'>Photo not available</div></div>'">
                    <?php else: ?>
                        <div class="image-placeholder">
                            <div class="icon">👤</div>
                            <div class="text">No Profile Photo</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Company Logo Section -->
            <div class="image-section">
                <h3>Company Logo</h3>
                <div class="image-display logo">
                    <?php if (!empty($card['company_logo_path'])): ?>
                        <img src="/api/media/view?filename=<?php echo urlencode($card['company_logo_path']); ?>" 
                             alt="Company Logo"
                             onerror="this.parentElement.innerHTML='<div class=\'image-placeholder\'><div class=\'icon\'>🏢</div><div class=\'text\'>Logo not available</div></div>'">
                    <?php else: ?>
                        <div class="image-placeholder">
                            <div class="icon">🏢</div>
                            <div class="text">No Company Logo</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Cover Graphic Section -->
            <div class="image-section">
                <h3>Cover Graphic</h3>
                <div class="image-display cover">
                    <?php if (!empty($card['cover_graphic_path'])): ?>
                        <img src="/api/media/view?filename=<?php echo urlencode($card['cover_graphic_path']); ?>" 
                             alt="Cover Graphic"
                             onerror="this.parentElement.innerHTML='<div class=\'image-placeholder\'><div class=\'icon\'>🎨</div><div class=\'text\'>Cover not available</div></div>'">
                    <?php else: ?>
                        <div class="image-placeholder">
                            <div class="icon">🎨</div>
                            <div class="text">No Cover Graphic</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Professional Information -->
            <?php if (!empty($card['job_title']) || !empty($card['company_name'])): ?>
            <div class="card-section">
                <div class="card-section-title">💼 Professional Information</div>
                
                <?php if (!empty($card['job_title'])): ?>
                <div class="contact-item">
                    <div class="contact-item-icon">👔</div>
                    <div class="contact-item-content">
                        <div class="contact-item-label">Job Title</div>
                        <div class="contact-item-value">
                            <?php echo htmlspecialchars($card['job_title']); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($card['company_name'])): ?>
                <div class="contact-item">
                    <div class="contact-item-icon">🏢</div>
                    <div class="contact-item-content">
                        <div class="contact-item-label">Company</div>
                        <div class="contact-item-value">
                            <?php echo htmlspecialchars($card['company_name']); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Primary Contact Section -->
            <div class="card-section">
                <div class="card-section-title">📞 Contact</div>
                
                <!-- Primary Phone -->
                <div class="contact-item">
                    <div class="contact-item-icon">📱</div>
                    <div class="contact-item-content">
                        <div class="contact-item-label">Primary Phone</div>
                        <div class="contact-item-value">
                            <a href="tel:<?php echo htmlspecialchars($card['phone_number']); ?>">
                                <?php echo htmlspecialchars($card['phone_number']); ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Primary Email -->
                <?php 
                $primaryEmail = null;
                foreach ($emails as $email) {
                    if ($email['is_primary']) {
                        $primaryEmail = $email;
                        break;
                    }
                }
                if (!$primaryEmail && !empty($emails)) {
                    $primaryEmail = $emails[0]; // First email if no primary set
                }
                ?>
                <?php if ($primaryEmail): ?>
                <div class="contact-item">
                    <div class="contact-item-icon">✉️</div>
                    <div class="contact-item-content">
                        <div class="contact-item-label">Primary Email</div>
                        <div class="contact-item-value">
                            <a href="mailto:<?php echo htmlspecialchars($primaryEmail['email']); ?>">
                                <?php echo htmlspecialchars($primaryEmail['email']); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Primary Website -->
                <?php 
                $primaryWebsite = null;
                foreach ($websites as $website) {
                    if ($website['is_primary']) {
                        $primaryWebsite = $website;
                        break;
                    }
                }
                if (!$primaryWebsite && !empty($websites)) {
                    $primaryWebsite = $websites[0]; // First website if no primary set
                }
                ?>
                <?php if ($primaryWebsite): ?>
                <div class="contact-item">
                    <div class="contact-item-icon">🌐</div>
                    <div class="contact-item-content">
                        <div class="contact-item-label">Primary Website</div>
                        <div class="contact-item-value">
                            <a href="<?php echo htmlspecialchars($primaryWebsite['url']); ?>" target="_blank" rel="noopener">
                                <?php echo htmlspecialchars($primaryWebsite['url']); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Address -->
                <?php if (!empty($address)): ?>
                <div class="contact-item">
                    <div class="contact-item-icon">📍</div>
                    <div class="contact-item-content">
                        <div class="contact-item-label">Address</div>
                        <div class="contact-item-value">
                            <a href="https://maps.google.com/?q=<?php echo urlencode(implode(', ', array_filter([$address['street'], $address['city'], $address['state'], $address['zip_code'], $address['country']]))); ?>" target="_blank" rel="noopener">
                                <?php 
                                $addressParts = array_filter([
                                    $address['street'],
                                    $address['city'],
                                    $address['state'],
                                    $address['zip_code'],
                                    $address['country']
                                ]);
                                echo htmlspecialchars(implode(', ', $addressParts));
                                ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Additional Information -->
            <?php 
            $additionalEmails = array_filter($emails, function($email) use ($primaryEmail) {
                return $email['id'] !== $primaryEmail['id'];
            });
            $additionalWebsites = array_filter($websites, function($website) use ($primaryWebsite) {
                return $website['id'] !== $primaryWebsite['id'];
            });
            ?>
            
            <?php if (!empty($additionalEmails) || !empty($phones) || !empty($additionalWebsites)): ?>
            <div class="card-section">
                <div class="card-section-title">ℹ️ Additional Information</div>
                
                <!-- Other Emails -->
                <?php if (!empty($additionalEmails)): ?>
                    <?php foreach ($additionalEmails as $email): ?>
                    <div class="contact-item">
                        <div class="contact-item-icon">✉️</div>
                        <div class="contact-item-content">
                            <div class="contact-item-label"><?php echo htmlspecialchars($email['label'] ?: ucfirst($email['type']) . ' Email'); ?></div>
                            <div class="contact-item-value">
                                <a href="mailto:<?php echo htmlspecialchars($email['email']); ?>">
                                    <?php echo htmlspecialchars($email['email']); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- Other Phones -->
                <?php if (!empty($phones)): ?>
                    <?php foreach ($phones as $phone): ?>
                    <div class="contact-item">
                        <div class="contact-item-icon">📞</div>
                        <div class="contact-item-content">
                            <div class="contact-item-label"><?php echo htmlspecialchars($phone['label'] ?: ucfirst($phone['type'])); ?></div>
                            <div class="contact-item-value">
                                <a href="tel:<?php echo htmlspecialchars($phone['phone_number']); ?>">
                                    <?php echo htmlspecialchars($phone['phone_number']); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- Other Websites -->
                <?php if (!empty($additionalWebsites)): ?>
                    <?php foreach ($additionalWebsites as $website): ?>
                    <div class="contact-item">
                        <div class="contact-item-icon">🌐</div>
                        <div class="contact-item-content">
                            <div class="contact-item-label"><?php echo htmlspecialchars($website['name'] ?: 'Website'); ?></div>
                            <div class="contact-item-value">
                                <a href="<?php echo htmlspecialchars($website['url']); ?>" target="_blank" rel="noopener">
                                    <?php echo htmlspecialchars($website['url']); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            
            <!-- Bio -->
            <?php if (!empty($card['bio'])): ?>
            <div class="card-section">
                <div class="card-section-title">ℹ️ About</div>
                <div class="card-section-content bio-content">
                    <?php 
                        // Auto-link URLs in bio
                        $bio = htmlspecialchars($card['bio']);
                        $bio = preg_replace(
                            '/(https?:\/\/[^\s]+)/',
                            '<a href="$1" target="_blank" rel="noopener">$1</a>',
                            $bio
                        );
                        echo $bio;
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Email Signature Section -->
        <div class="card-preview" style="margin-top: 30px;">
            <h2 style="text-align: center; margin-bottom: 25px; color: #333;">
                ✉️ Email Signature
            </h2>
            
            <p style="text-align: center; color: #666; margin-bottom: 30px;">
                Create a professional email signature with tracking capabilities
            </p>
            
            <div style="text-align: center;">
                <a href="/user/cards/email-signature.php?card_id=<?php echo urlencode($card['id']); ?>" 
                   style="display: inline-flex; align-items: center; gap: 8px; background: #667eea; color: white; padding: 15px 30px; border-radius: 10px; text-decoration: none; font-weight: 500; transition: all 0.2s;"
                   onmouseover="this.style.background='#5a6fd8'" 
                   onmouseout="this.style.background='#667eea'">
                    📧 Create Email Signature
                </a>
            </div>
        </div>
    </div>
    
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
    
    <!-- Share Modal -->
    <div id="shareModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; padding: 40px; border-radius: 20px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2 style="margin: 0; font-size: 24px;">Share Your Card</h2>
                <button onclick="closeShareModal()" style="background: none; border: none; font-size: 30px; cursor: pointer; color: #999;">&times;</button>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label for="shareLink" style="display: block; margin-bottom: 8px; font-weight: 500; color: #333;">Share Link:</label>
                <input type="text" id="shareLink" value="https://sharemycard.app/card.php?id=<?php echo urlencode($card['id']); ?>" 
                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px;" readonly>
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button onclick="copyShareLink()" style="background: #667eea; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500;">
                    📋 Copy Link
                </button>
                <button onclick="shareCard()" style="background: #4CAF50; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500;">
                    🔗 Share
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // confirmDelete function - deleteCard is already defined at top of page
        window.confirmDelete = function() {
            if (!window.currentDeleteCardId) return;
            
            var deleteBtn = document.getElementById('deleteConfirmBtn');
            var btnText = document.getElementById('deleteBtnText');
            var btnSpinner = document.getElementById('deleteBtnSpinner');
            var errorDiv = document.getElementById('deleteError');
            
            if (!deleteBtn || !btnText || !btnSpinner || !errorDiv) {
                console.error('Delete modal elements not found');
                return;
            }
            
            // Show loading state
            deleteBtn.disabled = true;
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline';
            errorDiv.style.display = 'none';
            
            // Use session-based authentication (no JWT needed)
            var formData = new FormData();
            formData.append('card_id', window.currentDeleteCardId);
            
            fetch('/user/api/delete-card.php', {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    // Success - close modal and redirect to dashboard
                    window.closeDeleteModal();
                    window.location.href = '/user/dashboard.php?success=card_deleted';
                } else {
                    // Show error in modal
                    errorDiv.textContent = 'Error: ' + (data.message || 'Failed to delete card');
                    errorDiv.style.display = 'block';
                }
            })
            .catch(function(error) {
                console.error('Delete error:', error);
                errorDiv.textContent = 'Error deleting card. Please try again.';
                errorDiv.style.display = 'block';
            })
            .finally(function() {
                // Reset button state
                deleteBtn.disabled = false;
                btnText.style.display = 'inline';
                btnSpinner.style.display = 'none';
            });
        };
        
        function shareCard() {
            document.getElementById('shareModal').style.display = 'flex';
        }
        
        function closeShareModal() {
            document.getElementById('shareModal').style.display = 'none';
        }
        
        function copyShareLink() {
            const input = document.getElementById('shareLink');
            input.select();
            input.setSelectionRange(0, 99999); // For mobile devices
            
            navigator.clipboard.writeText(input.value).then(() => {
                // Success feedback
                const btn = document.querySelector('button[onclick="copyShareLink()"]');
                const originalText = btn.innerHTML;
                btn.innerHTML = '✅ Copied!';
                btn.style.background = '#28a745';
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.style.background = '#667eea';
                }, 2000);
            }).catch(() => {
                // Fallback for older browsers
                document.execCommand('copy');
                alert('Link copied to clipboard!');
            });
        }
        
        // Close modal when clicking outside
        document.getElementById('shareModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeShareModal();
            }
        });
        
        function duplicateCard(cardId) {
            if (confirm('Are you sure you want to duplicate this card? This will create a complete copy with all contact information.')) {
                // Show loading state
                const button = event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '⏳ Duplicating...';
                button.disabled = true;
                
                // Call the duplicate API
                fetch('/user/api/duplicate-card.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        card_id: cardId
                    })
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    
                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        // If not JSON, get text to see what we got
                        return response.text().then(text => {
                            console.log('Non-JSON response:', text);
                            throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
                        });
                    }
                })
                .then(data => {
                    console.log('API response:', data);
                    if (data.success) {
                        // Redirect to edit the new card
                        window.location.href = '/user/cards/edit.php?id=' + data.new_card_id;
                    } else {
                        alert('Error duplicating card: ' + (data.error || 'Unknown error'));
                        // Reset button
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error duplicating card: ' + error.message);
                    // Reset button
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
            }
        }
        
        // Close delete modal when clicking outside
        window.addEventListener('click', function(event) {
            var modal = document.getElementById('deleteModal');
            if (modal && event.target === modal) {
                window.closeDeleteModal();
            }
        });
        
    </script>
</body>
</html>
