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
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">📱 ShareMyCard</div>
        <div class="nav-links">
            <a href="/user/dashboard.php" class="nav-link">Dashboard</a>
            <a href="/user/cards/create.php" class="nav-link">Create Card</a>
            <?php if ($user['is_admin']): ?>
                <a href="/admin/dashboard.php" class="nav-link" style="background: rgba(255,255,255,0.2);">⚙️ Admin Panel</a>
            <?php endif; ?>
            <a href="/user/logout.php" class="nav-link">Logout</a>
        </div>
    </nav>
    
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
            <a href="/user/cards/analytics.php?card_id=<?php echo urlencode($cardId); ?>" class="btn btn-secondary" style="background: #667eea; color: white; border: none;">
                📊 View Analytics
            </a>
            <a href="/user/cards/qr.php?id=<?php echo urlencode($cardId); ?>" class="btn btn-secondary">
                📱 Generate QR Code
            </a>
            <a href="/card.php?id=<?php echo urlencode($cardId); ?>" class="btn btn-secondary" style="background: #e67e22; color: white; border: none;" target="_blank">
                👁️ View Public Card
            </a>
            <button onclick="shareCard()" class="btn btn-secondary" style="background: #4CAF50; color: white; border: none;">
                🔗 Share Card
            </button>
            <button onclick="deleteCard('<?php echo urlencode($cardId); ?>')" class="btn btn-secondary" style="background: #e74c3c; color: white; border: none;">
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
                Customize and copy this HTML signature for your email client (Gmail, Outlook, etc.)
            </p>
            
            <!-- Customization Options -->
            <div style="background: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 25px;">
                <h3 style="margin-top: 0; margin-bottom: 20px; color: #333; font-size: 18px;">
                    ⚙️ Customize Your Signature
                </h3>
                
                <!-- Image Selection -->
                <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #ddd;">
                    <h4 style="margin: 0 0 15px 0; color: #666; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">
                        Signature Image
                    </h4>
                    <div style="display: flex; gap: 20px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; user-select: none;">
                            <input type="radio" name="signatureImage" value="profile" checked onchange="updateSignature()" 
                                   style="width: 18px; height: 18px; cursor: pointer;">
                            <span style="color: #333; font-weight: 500;">👤 Profile Photo</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; user-select: none;">
                            <input type="radio" name="signatureImage" value="logo" onchange="updateSignature()" 
                                   style="width: 18px; height: 18px; cursor: pointer;">
                            <span style="color: #333; font-weight: 500;">🏢 Company Logo</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; user-select: none;">
                            <input type="radio" name="signatureImage" value="none" onchange="updateSignature()" 
                                   style="width: 18px; height: 18px; cursor: pointer;">
                            <span style="color: #333; font-weight: 500;">🚫 No Image</span>
                        </label>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- Left Column: Element Selection & Order -->
                    <div>
                        <h4 style="margin: 0 0 15px 0; color: #666; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">
                            Elements & Order
                        </h4>
                        
                        <div id="elementOrderList" style="background: white; border-radius: 8px; overflow: hidden; border: 1px solid #ddd;">
                            <!-- Name & Title (always first, locked) -->
                            <div style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0; background: #fafafa; display: flex; align-items: center; gap: 10px;">
                                <span style="color: #999; font-size: 18px;">🔒</span>
                                <span style="flex: 1; color: #666; font-weight: 500;">Name & Title</span>
                                <span style="color: #999; font-size: 12px;">Always first</span>
                            </div>
                            
                            <!-- Phone -->
                            <div data-element="phone" style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" id="includePhone" checked onchange="updateSignature()" 
                                       style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="flex: 1; color: #333; font-weight: 500;">📱 Phone</span>
                                <button onclick="moveElement('phone', -1)" style="padding: 4px 10px; background: #e0e0e0; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">↑</button>
                                <button onclick="moveElement('phone', 1)" style="padding: 4px 10px; background: #e0e0e0; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">↓</button>
                            </div>
                            
                            <!-- Email -->
                            <div data-element="email" style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" id="includeEmail" checked onchange="updateSignature()" 
                                       style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="flex: 1; color: #333; font-weight: 500;">✉️ Email</span>
                                <button onclick="moveElement('email', -1)" style="padding: 4px 10px; background: #e0e0e0; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">↑</button>
                                <button onclick="moveElement('email', 1)" style="padding: 4px 10px; background: #e0e0e0; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">↓</button>
                            </div>
                            
                            <!-- Websites (if any) -->
                            <?php if (!empty($websites)): ?>
                            <div data-element="websites" style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" id="includeWebsitesGroup" onchange="updateSignature()" 
                                       style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="flex: 1; color: #333; font-weight: 500;">🌐 Websites</span>
                                <button onclick="moveElement('websites', -1)" style="padding: 4px 10px; background: #e0e0e0; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">↑</button>
                                <button onclick="moveElement('websites', 1)" style="padding: 4px 10px; background: #e0e0e0; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">↓</button>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Address (if exists) -->
                            <?php if (!empty($address['street']) || !empty($address['city'])): ?>
                            <div data-element="address" style="padding: 12px 15px; display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" id="includeAddress" onchange="updateSignature()" 
                                       style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="flex: 1; color: #333; font-weight: 500;">📍 Address</span>
                                <button onclick="moveElement('address', -1)" style="padding: 4px 10px; background: #e0e0e0; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">↑</button>
                                <button onclick="moveElement('address', 1)" style="padding: 4px 10px; background: #e0e0e0; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">↓</button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Right Column: Individual Website Selection -->
                    <div>
                        <h4 style="margin: 0 0 15px 0; color: #666; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">
                            Select Websites
                        </h4>
                        
                        <?php if (!empty($websites)): ?>
                        <div style="background: white; border-radius: 8px; padding: 15px; border: 1px solid #ddd;">
                            <?php foreach ($websites as $index => $website): ?>
                            <label style="display: flex; align-items: center; gap: 10px; padding: 10px; margin-bottom: 8px; background: #f8f9fa; border-radius: 6px; cursor: pointer; user-select: none;">
                                <input type="checkbox" class="website-checkbox" data-index="<?php echo $index; ?>" 
                                       onchange="updateSignature()" 
                                       style="width: 18px; height: 18px; cursor: pointer;">
                                <div style="flex: 1; min-width: 0;">
                                    <div style="color: #333; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php echo htmlspecialchars($website['url']); ?>
                                    </div>
                                    <?php if (!empty($website['description'])): ?>
                                    <div style="color: #999; font-size: 12px; margin-top: 2px;">
                                        <?php echo htmlspecialchars($website['description']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div style="background: white; border-radius: 8px; padding: 20px; border: 1px solid #ddd; text-align: center; color: #999;">
                            No websites added to your card
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Preview -->
            <div style="background: #f8f9fa; padding: 30px; border-radius: 12px; margin-bottom: 25px; border: 2px dashed #ddd;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; color: #666; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">
                        Preview
                    </h3>
                    <button onclick="copyVisualSignature()" id="copyVisualBtn" 
                            style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.2s;">
                        📋 Copy for Gmail
                    </button>
                </div>
                <div id="signaturePreview" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <!-- Signature will be generated here by JavaScript -->
                </div>
                <p style="font-size: 13px; color: #666; margin-top: 15px; text-align: center;">
                    💡 <strong>For Gmail:</strong> Click "Copy for Gmail" above, then paste directly into Gmail signature editor
                </p>
            </div>
            
            <!-- HTML Code to Copy -->
            <div style="margin-bottom: 25px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0; color: #333;">HTML Code</h3>
                    <button onclick="copySignature()" id="copySignatureBtn" 
                            style="padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.2s;">
                        📋 Copy to Clipboard
                    </button>
                </div>
                <textarea id="signatureCode" readonly 
                          style="width: 100%; min-height: 200px; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.4; resize: vertical;"></textarea>
            </div>
            
            <!-- Hidden data for JavaScript -->
            <script>
                const cardData = {
                    fullName: <?php echo json_encode(trim($card['first_name'] . ' ' . $card['last_name'])); ?>,
                    jobTitle: <?php echo json_encode($card['job_title'] ?? ''); ?>,
                    company: <?php echo json_encode($card['company_name'] ?? ''); ?>,
                    primaryPhone: <?php echo json_encode(!empty($phones) && isset($phones[0]) ? $phones[0]['phone_number'] : ''); ?>,
                    primaryEmail: <?php echo json_encode(!empty($emails) && isset($emails[0]) ? $emails[0]['email'] : ''); ?>,
                    profilePhotoUrl: <?php echo json_encode(!empty($card['profile_photo_path']) ? 'https://sharemycard.app/api/media/view?filename=' . urlencode($card['profile_photo_path']) : ''); ?>,
                    companyLogoUrl: <?php echo json_encode(!empty($card['company_logo_path']) ? 'https://sharemycard.app/api/media/view?filename=' . urlencode($card['company_logo_path']) : ''); ?>,
                    websites: <?php echo json_encode(array_map(function($w) { return ['url' => $w['url'], 'description' => $w['description'] ?? '']; }, $websites)); ?>,
                    address: <?php 
                        $addr = [];
                        if (!empty($address['street'])) $addr[] = $address['street'];
                        if (!empty($address['city'])) $addr[] = $address['city'];
                        if (!empty($address['state'])) $addr[] = $address['state'];
                        if (!empty($address['zip_code'])) $addr[] = $address['zip_code'];
                        echo json_encode(!empty($addr) ? implode(', ', $addr) : '');
                    ?>
                };
            </script>
            
            <!-- Installation Instructions -->
            <div style="background: #e8f4fd; padding: 25px; border-radius: 12px; border-left: 4px solid #667eea;">
                <h3 style="margin-top: 0; margin-bottom: 15px; color: #333;">
                    📧 Installation Instructions
                </h3>
                
                <details style="margin-bottom: 15px;" open>
                    <summary style="cursor: pointer; font-weight: 600; color: #667eea; margin-bottom: 10px; user-select: none;">
                        Gmail (Web) - RECOMMENDED
                    </summary>
                    <ol style="margin: 10px 0 0 20px; color: #666; line-height: 1.8;">
                        <li>Click <strong>"📋 Copy for Gmail"</strong> button above the signature preview</li>
                        <li>Open Gmail → click the <strong>gear icon</strong> → <strong>See all settings</strong></li>
                        <li>Scroll down to the <strong>Signature</strong> section</li>
                        <li>Click <strong>Create new</strong> (or select existing signature to replace)</li>
                        <li><strong>Paste</strong> the signature directly into the editor (Ctrl+V or Cmd+V)</li>
                        <li>Scroll to bottom and click <strong>Save Changes</strong></li>
                    </ol>
                    <p style="margin: 10px 0 0 20px; color: #999; font-size: 13px;">
                        ⚠️ Gmail requires copying the <strong>visual signature</strong>, not raw HTML code
                    </p>
                </details>
                
                <details style="margin-bottom: 15px;">
                    <summary style="cursor: pointer; font-weight: 600; color: #667eea; margin-bottom: 10px; user-select: none;">
                        Outlook (Web)
                    </summary>
                    <ol style="margin: 10px 0 0 20px; color: #666; line-height: 1.8;">
                        <li>Click <strong>Settings</strong> (gear icon) → <strong>View all Outlook settings</strong></li>
                        <li>Go to <strong>Mail</strong> → <strong>Compose and reply</strong></li>
                        <li>Under <strong>Email signature</strong>, paste the HTML code</li>
                        <li>Click <strong>Save</strong></li>
                    </ol>
                </details>
                
                <details style="margin-bottom: 15px;">
                    <summary style="cursor: pointer; font-weight: 600; color: #667eea; margin-bottom: 10px; user-select: none;">
                        Apple Mail (Mac)
                    </summary>
                    <ol style="margin: 10px 0 0 20px; color: #666; line-height: 1.8;">
                        <li>Open <strong>Mail</strong> → <strong>Settings</strong> → <strong>Signatures</strong></li>
                        <li>Click the <strong>+</strong> button to create a new signature</li>
                        <li>Copy the HTML code and paste it into the signature editor</li>
                        <li>Close settings to save</li>
                    </ol>
                </details>
                
                <details>
                    <summary style="cursor: pointer; font-weight: 600; color: #667eea; margin-bottom: 10px; user-select: none;">
                        Outlook (Desktop - Windows/Mac)
                    </summary>
                    <ol style="margin: 10px 0 0 20px; color: #666; line-height: 1.8;">
                        <li>Go to <strong>File</strong> → <strong>Options</strong> → <strong>Mail</strong> → <strong>Signatures</strong></li>
                        <li>Click <strong>New</strong> to create a signature</li>
                        <li>Paste the HTML code into the editor</li>
                        <li>Set as default for new messages and/or replies</li>
                        <li>Click <strong>OK</strong> to save</li>
                    </ol>
                </details>
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
            
            <div style="margin-bottom: 25px;">
                <label style="display: block; font-weight: 600; margin-bottom: 10px; color: #333;">Public Link</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="shareLink" readonly value="https://sharemycard.app/card.php?id=<?php echo urlencode($cardId); ?>" 
                           style="flex: 1; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px;">
                    <button onclick="copyShareLink()" style="padding: 12px 20px; background: #4CAF50; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; white-space: nowrap;">
                        📋 Copy
                    </button>
                </div>
                <p style="font-size: 13px; color: #666; margin-top: 8px;">Anyone with this link can view your card</p>
            </div>
            
            <div style="margin-bottom: 25px;">
                <label style="display: block; font-weight: 600; margin-bottom: 10px; color: #333;">QR Code</label>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; text-align: center;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode('https://sharemycard.app/card.php?id=' . $cardId); ?>" 
                         alt="QR Code" style="max-width: 200px; height: auto;">
                    <p style="font-size: 13px; color: #666; margin-top: 10px;">Scan to view card</p>
                </div>
            </div>
            
            <div style="margin-bottom: 25px;">
                <label style="display: block; font-weight: 600; margin-bottom: 10px; color: #333;">Download vCard</label>
                <a href="/vcard.php?id=<?php echo urlencode($cardId); ?>" 
                   style="display: block; padding: 12px; background: #4CAF50; color: white; text-align: center; border-radius: 8px; text-decoration: none; font-weight: 600;">
                    📇 Save to Contacts
                </a>
            </div>
            
            <div style="margin-bottom: 25px;">
                <label style="display: block; font-weight: 600; margin-bottom: 10px; color: #333;">Share via Email</label>
                <a href="mailto:?subject=<?php echo urlencode($card['first_name'] . ' ' . $card['last_name'] . ' - Business Card'); ?>&body=<?php echo urlencode('Check out my business card: https://sharemycard.app/card.php?id=' . $cardId); ?>" 
                   style="display: block; padding: 12px; background: #667eea; color: white; text-align: center; border-radius: 8px; text-decoration: none; font-weight: 600;">
                    ✉️ Send via Email
                </a>
            </div>
            
            <div style="border-top: 2px solid #f0f0f0; padding-top: 20px;">
                <p style="font-size: 13px; color: #666; line-height: 1.6;">
                    <strong>💡 Pro Tip:</strong> Add this link to your email signature, LinkedIn profile, or social media bios!
                </p>
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
    
    <script>
        function shareCard() {
            document.getElementById('shareModal').style.display = 'flex';
        }
        
        function closeShareModal() {
            document.getElementById('shareModal').style.display = 'none';
        }
        
        function copyShareLink() {
            const input = document.getElementById('shareLink');
            input.select();
            
            navigator.clipboard.writeText(input.value).then(() => {
                alert('✅ Link copied to clipboard!');
            }).catch(() => {
                // Fallback
                document.execCommand('copy');
                alert('✅ Link copied to clipboard!');
            });
        }
        
        // Element order array (initialized on page load)
        let elementOrder = ['phone', 'email', 'websites', 'address'];
        
        function moveElement(element, direction) {
            const currentIndex = elementOrder.indexOf(element);
            if (currentIndex === -1) return;
            
            const newIndex = currentIndex + direction;
            
            // Check bounds
            if (newIndex < 0 || newIndex >= elementOrder.length) return;
            
            // Swap elements
            [elementOrder[currentIndex], elementOrder[newIndex]] = [elementOrder[newIndex], elementOrder[currentIndex]];
            
            // Update visual order in the UI
            updateElementOrderUI();
            
            // Regenerate signature
            updateSignature();
        }
        
        function updateElementOrderUI() {
            const container = document.getElementById('elementOrderList');
            const lockedElement = container.querySelector('[data-element=""]'); // Name & Title (no data-element)
            
            // Get all moveable elements
            const elements = elementOrder.map(name => {
                return container.querySelector(`[data-element="${name}"]`);
            }).filter(el => el !== null);
            
            // Reorder DOM elements
            elements.forEach(el => {
                container.appendChild(el);
            });
        }
        
        function getSelectedWebsites() {
            const checkboxes = document.querySelectorAll('.website-checkbox:checked');
            const selected = [];
            
            checkboxes.forEach(cb => {
                const index = parseInt(cb.getAttribute('data-index'));
                if (cardData.websites[index]) {
                    selected.push(cardData.websites[index]);
                }
            });
            
            return selected;
        }
        
        function generateSignatureHTML() {
            const includePhone = document.getElementById('includePhone')?.checked ?? false;
            const includeEmail = document.getElementById('includeEmail')?.checked ?? false;
            const includeWebsitesGroup = document.getElementById('includeWebsitesGroup')?.checked ?? false;
            const includeAddress = document.getElementById('includeAddress')?.checked ?? false;
            
            // Get selected image type
            const imageType = document.querySelector('input[name="signatureImage"]:checked')?.value ?? 'profile';
            
            let html = '<table cellpadding="0" cellspacing="0" border="0" style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.5; color: #333333;">';
            html += '<tr>';
            
            // Image column (profile photo, company logo, or none)
            let imageUrl = '';
            let imageAlt = '';
            let imageStyle = 'border-radius: 50%; display: block;'; // Default: round for profile
            
            if (imageType === 'profile' && cardData.profilePhotoUrl) {
                imageUrl = cardData.profilePhotoUrl;
                imageAlt = escapeHtml(cardData.fullName);
                imageStyle = 'border-radius: 50%; display: block;'; // Round for profile
            } else if (imageType === 'logo' && cardData.companyLogoUrl) {
                imageUrl = cardData.companyLogoUrl;
                imageAlt = escapeHtml(cardData.company || 'Company Logo');
                imageStyle = 'border-radius: 8px; display: block;'; // Rounded corners for logo
            }
            
            if (imageUrl) {
                html += '<td style="padding-right: 15px; vertical-align: top;">';
                html += '<img src="' + escapeHtml(imageUrl) + '" width="80" height="80" style="' + imageStyle + '" alt="' + imageAlt + '">';
                html += '</td>';
            }
            
            // Info column
            html += '<td style="vertical-align: top;">';
            
            // Name (always first)
            html += '<div style="font-weight: bold; font-size: 16px; color: #333333; margin-bottom: 3px;">' + escapeHtml(cardData.fullName) + '</div>';
            
            // Job Title (if available)
            if (cardData.jobTitle) {
                html += '<div style="color: #666666; font-size: 14px; margin-bottom: 2px;">' + escapeHtml(cardData.jobTitle) + '</div>';
            }
            
            // Company (if available)
            if (cardData.company) {
                html += '<div style="color: #888888; font-size: 14px; margin-bottom: 8px;">' + escapeHtml(cardData.company) + '</div>';
            } else if (cardData.jobTitle) {
                // Add bottom margin if we have job title but no company
                html += '<div style="margin-bottom: 8px;"></div>';
            }
            
            // Build elements in custom order
            const elementBuilders = {
                phone: () => {
                    if (includePhone && cardData.primaryPhone) {
                        return '<div style="font-size: 13px; color: #666666; margin-bottom: 8px;"><span style="color: #667eea;">📱</span> ' + escapeHtml(cardData.primaryPhone) + '</div>';
                    }
                    return '';
                },
                email: () => {
                    if (includeEmail && cardData.primaryEmail) {
                        return '<div style="font-size: 13px; color: #666666; margin-bottom: 8px;"><span style="color: #667eea;">✉️</span> <a href="mailto:' + escapeHtml(cardData.primaryEmail) + '" style="color: #333333; text-decoration: none;">' + escapeHtml(cardData.primaryEmail) + '</a></div>';
                    }
                    return '';
                },
                websites: () => {
                    if (includeWebsitesGroup) {
                        const selectedWebsites = getSelectedWebsites();
                        if (selectedWebsites.length > 0) {
                            let websiteLinks = selectedWebsites.map(w => {
                                return '<a href="' + escapeHtml(w.url) + '" style="color: #667eea; text-decoration: none;">' + escapeHtml(w.url) + '</a>';
                            });
                            return '<div style="font-size: 13px; color: #666666; margin-bottom: 8px;"><span style="color: #667eea;">🌐</span> ' + websiteLinks.join(' &nbsp;•&nbsp; ') + '</div>';
                        }
                    }
                    return '';
                },
                address: () => {
                    if (includeAddress && cardData.address) {
                        return '<div style="font-size: 13px; color: #666666; margin-bottom: 8px;"><span style="color: #667eea;">📍</span> ' + escapeHtml(cardData.address) + '</div>';
                    }
                    return '';
                }
            };
            
            // Add elements in custom order
            elementOrder.forEach(elementName => {
                if (elementBuilders[elementName]) {
                    html += elementBuilders[elementName]();
                }
            });
            
            html += '</td>';
            html += '</tr>';
            html += '</table>';
            
            return html;
        }
        
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
        
        function updateSignature() {
            const signatureHTML = generateSignatureHTML();
            document.getElementById('signaturePreview').innerHTML = signatureHTML;
            document.getElementById('signatureCode').value = signatureHTML;
        }
        
        function copyVisualSignature() {
            const preview = document.getElementById('signaturePreview');
            const btn = document.getElementById('copyVisualBtn');
            
            // Create a range and selection
            const range = document.createRange();
            range.selectNodeContents(preview);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
            
            try {
                // Copy the visual content (rendered HTML)
                document.execCommand('copy');
                
                // Success feedback
                btn.textContent = '✅ Copied!';
                btn.style.background = '#2ecc71';
                
                setTimeout(() => {
                    btn.textContent = '📋 Copy for Gmail';
                    btn.style.background = '#667eea';
                }, 2000);
                
                // Clear selection
                selection.removeAllRanges();
            } catch (err) {
                alert('Please manually select and copy the signature above');
            }
        }
        
        function copySignature() {
            const textarea = document.getElementById('signatureCode');
            const btn = document.getElementById('copySignatureBtn');
            
            textarea.select();
            textarea.setSelectionRange(0, 99999); // For mobile
            
            navigator.clipboard.writeText(textarea.value).then(() => {
                // Success feedback
                btn.textContent = '✅ Copied!';
                btn.style.background = '#2ecc71';
                
                setTimeout(() => {
                    btn.textContent = '📋 Copy to Clipboard';
                    btn.style.background = '#4CAF50';
                }, 2000);
            }).catch(() => {
                // Fallback for older browsers
                document.execCommand('copy');
                btn.textContent = '✅ Copied!';
                btn.style.background = '#2ecc71';
                
                setTimeout(() => {
                    btn.textContent = '📋 Copy to Clipboard';
                    btn.style.background = '#4CAF50';
                }, 2000);
            });
        }
        
        // Initialize signature on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Filter element order based on what's actually available
            const availableElements = [];
            if (document.getElementById('includePhone')) availableElements.push('phone');
            if (document.getElementById('includeEmail')) availableElements.push('email');
            if (document.getElementById('includeWebsitesGroup')) availableElements.push('websites');
            if (document.getElementById('includeAddress')) availableElements.push('address');
            
            elementOrder = availableElements;
            
            updateSignature();
        });
        
        // Close modal when clicking outside
        document.getElementById('shareModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeShareModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeShareModal();
            }
        });
        
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
                    // Success - redirect to dashboard
                    window.location.href = '/user/dashboard.php';
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

