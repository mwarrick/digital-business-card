<?php
/**
 * Public Business Card View
 * Shareable card page - no authentication required
 */

require_once __DIR__ . '/api/includes/Database.php';
require_once __DIR__ . '/includes/themes.php';

$cardId = $_GET['id'] ?? '';

if (empty($cardId)) {
    header('Location: /');
    exit;
}

$db = Database::getInstance();

// Get card (public view - no user_id check)
$card = $db->querySingle(
    "SELECT * FROM business_cards WHERE id = ? AND is_active = 1",
    [$cardId]
);

if (!$card) {
    header('Location: /?error=card_not_found');
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

$fullName = htmlspecialchars($card['first_name'] . ' ' . $card['last_name']);
$pageTitle = $fullName . ' - ShareMyCard';

// Get theme (default to professional-blue if not set)
$theme = $card['theme'] ?? 'professional-blue';
$themeCSS = generateThemeCSS($theme);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:title" content="<?php echo $fullName; ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($card['job_title'] ?? '') . ' at ' . htmlspecialchars($card['company_name'] ?? ''); ?>">
    <meta property="og:type" content="profile">
    <meta property="og:url" content="https://sharemycard.app/card.php?id=<?php echo urlencode($cardId); ?>">
    <?php if (!empty($card['profile_photo_path'])): ?>
    <meta property="og:image" content="https://sharemycard.app/api/media/view?filename=<?php echo urlencode($card['profile_photo_path']); ?>">
    <?php endif; ?>
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?php echo $fullName; ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($card['job_title'] ?? '') . ' at ' . htmlspecialchars($card['company_name'] ?? ''); ?>">
    
    <style>
        <?php echo $themeCSS; ?>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-family);
            background: var(--gradient);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .card-container {
            background: white;
            max-width: 700px;
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        /* Cover Photo Section */
        .cover-section {
            position: relative;
            height: 334px; /* 16:9 aspect ratio (700px width) - reduced by 60px */
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .cover-section.has-image {
            background: none;
        }
        
        .cover-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        /* Subtle hover effect for cover image */
        .cover-section:hover img {
            transform: scale(1.02);
        }
        
        /* Dynamic cover image sizing based on container width */
        .cover-section {
            /* Responsive height based on container width */
            height: calc(100vw * 0.48); /* 16:9 aspect ratio for mobile */
            max-height: 334px; /* Maximum height for larger screens */
            min-height: 200px; /* Minimum height for very small screens */
        }
        
        @media (min-width: 600px) {
            .cover-section {
                height: 334px; /* Fixed height for larger screens */
            }
        }
        
        /* Profile Section */
        .profile-section {
            padding: 40px;
            text-align: center;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            justify-content: center;
        }
        
        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            object-fit: cover;
            background: #f0f0f0;
            flex-shrink: 0;
        }
        
        .profile-photo.placeholder {
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
        }
        
        .profile-info {
            flex: 1;
            text-align: left;
        }
        
        .company-logo {
            width: 120px;
            height: 120px;
            flex-shrink: 0;
        }
        
        .company-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 8px;
        }
        
        .name {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 10px;
        }
        
        .title {
            font-size: 18px;
            color: var(--accent-color);
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .company {
            font-size: 16px;
            color: var(--text-light);
            margin-bottom: 0;
        }
        
        .bio {
            font-size: 15px;
            color: var(--text-light);
            line-height: 1.6;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
        }
        
        /* QR Section */
        .qr-section {
            padding: 0 40px 20px 40px;
        }
        .qr-title {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            margin-bottom: 12px;
            font-weight: 600;
        }
        .qr-code-wrapper {
            display: flex;
            justify-content: center;
        }
        .qr-code-wrapper img {
            display: block;
            width: 220px;
            height: 220px;
            border-radius: 12px;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 14px;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .qr-code-wrapper img:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .qr-code-wrapper a {
            text-decoration: none;
            display: block;
        }
        
        /* Company Logo */
        .company-logo {
            margin: 20px auto;
            max-width: 150px;
        }
        
        .company-logo img {
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
            border-radius: 12px;
            border: 2px solid #f0f0f0;
        }
        
        /* Contact Sections */
        .contact-section {
            padding: 20px 40px;
            border-top: 2px solid #f0f0f0;
        }
        
        .contact-section h3 {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .contact-item:last-child {
            border-bottom: none;
        }
        
        .contact-icon {
            font-size: 20px;
            width: 30px;
            text-align: center;
        }
        
        .contact-info {
            flex: 1;
        }
        
        .contact-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .contact-value {
            font-size: 15px;
            color: var(--text-color);
            font-weight: 500;
        }
        
        .contact-value a {
            color: var(--accent-color);
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .contact-value a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        /* Action Buttons */
        .action-buttons {
            padding: 30px 40px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background: var(--gradient);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: white;
            color: var(--accent-color);
            border: 2px solid var(--accent-color);
        }
        
        .btn-secondary:hover {
            background: var(--accent-color);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        
        /* Footer */
        .card-footer {
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            font-size: 13px;
            color: var(--text-light);
        }
        
        .card-footer a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        /* Responsive */
        @media (max-width: 600px) {
            .card-container {
                margin: 10px;
                max-width: calc(100vw - 20px);
            }
            
            .cover-section {
                height: calc(100vw * 0.4); /* Slightly smaller on mobile */
                min-height: 180px;
            }
            
            .profile-section {
                padding: 20px;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .profile-info {
                text-align: center;
            }
            
            .company-logo {
                width: 100px;
                height: 100px;
            }
            
            .contact-section {
                padding: 20px;
            }
            
            .action-buttons {
                padding: 20px;
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .name {
                font-size: 24px;
            }
        }
        
        /* Extra small screens */
        @media (max-width: 400px) {
            .cover-section {
                height: calc(100vw * 0.35);
                min-height: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="card-container">
        <!-- Cover Photo -->
        <div class="cover-section <?php echo !empty($card['cover_graphic_path']) ? 'has-image' : ''; ?>">
            <?php if (!empty($card['cover_graphic_path'])): ?>
                <img src="/api/media/view?filename=<?php echo urlencode($card['cover_graphic_path']); ?>" alt="Cover">
            <?php endif; ?>
        </div>
        
        <!-- Profile Section -->
        <div class="profile-section">
            <div class="profile-header">
                <?php if (!empty($card['profile_photo_path'])): ?>
                    <img src="/api/media/view?filename=<?php echo urlencode($card['profile_photo_path']); ?>" 
                         alt="<?php echo $fullName; ?>" 
                         class="profile-photo">
                <?php else: ?>
                    <div class="profile-photo placeholder">
                        <?php echo strtoupper(substr($card['first_name'], 0, 1) . substr($card['last_name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                
                <div class="profile-info">
                    <h1 class="name"><?php echo $fullName; ?></h1>
                    <?php if (!empty($card['job_title'])): ?>
                        <div class="title"><?php echo htmlspecialchars($card['job_title']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($card['company_name'])): ?>
                        <div class="company"><?php echo htmlspecialchars($card['company_name']); ?></div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($card['company_logo_path'])): ?>
                    <div class="company-logo">
                        <img src="/api/media/view?filename=<?php echo urlencode($card['company_logo_path']); ?>" 
                             alt="<?php echo htmlspecialchars($card['company_name'] ?? 'Company Logo'); ?>">
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($card['bio'])): ?>
                <div class="bio"><?php echo nl2br(htmlspecialchars($card['bio'])); ?></div>
            <?php endif; ?>
        </div>
        
        <?php 
        // Prepare QR code for vCard download URL so scanners can save contact
        $vcardUrl = 'https://sharemycard.app/vcard.php?id=' . urlencode($cardId) . '&src=qr';
        $qrEncoded = urlencode($vcardUrl);
        ?>
        <!-- QR Code Section -->
        <div class="qr-section">
            <div class="qr-title">Scan or Click<br>to Save Contact</div>
            <div class="qr-code-wrapper">
                <a href="/vcard.php?id=<?php echo urlencode($cardId); ?>" title="Click to save contact">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?php echo $qrEncoded; ?>&format=png" alt="QR Code to save contact">
                </a>
            </div>
        </div>
        
        <!-- Primary Contact Section -->
        <div class="contact-section">
            <h3>Contact</h3>
            
            <!-- Primary Phone -->
            <?php if (!empty($card['phone_number'])): ?>
            <div class="contact-item">
                <div class="contact-icon">📱</div>
                <div class="contact-info">
                    <div class="contact-label">Primary Phone</div>
                    <div class="contact-value">
                        <a href="tel:<?php echo htmlspecialchars($card['phone_number']); ?>">
                            <?php echo htmlspecialchars($card['phone_number']); ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
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
                <div class="contact-icon">✉️</div>
                <div class="contact-info">
                    <div class="contact-label">Primary Email</div>
                    <div class="contact-value">
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
                <div class="contact-icon">🌐</div>
                <div class="contact-info">
                    <div class="contact-label">Primary Website</div>
                    <div class="contact-value">
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
                <div class="contact-icon">📍</div>
                <div class="contact-info">
                    <div class="contact-label">Address</div>
                    <div class="contact-value">
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
        <div class="contact-section">
            <h3>Additional Information</h3>
            
            <!-- Other Emails -->
            <?php if (!empty($additionalEmails)): ?>
                <?php foreach ($additionalEmails as $email): ?>
                <div class="contact-item">
                    <div class="contact-icon">✉️</div>
                    <div class="contact-info">
                        <div class="contact-label"><?php echo htmlspecialchars($email['label'] ?: ucfirst($email['type']) . ' Email'); ?></div>
                        <div class="contact-value">
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
                    <div class="contact-icon">📞</div>
                    <div class="contact-info">
                        <div class="contact-label"><?php echo htmlspecialchars($phone['label'] ?: ucfirst($phone['type'])); ?></div>
                        <div class="contact-value">
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
                    <div class="contact-icon">🌐</div>
                    <div class="contact-info">
                        <div class="contact-label"><?php echo htmlspecialchars($website['name'] ?: 'Website'); ?></div>
                        <div class="contact-value">
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
        
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="/vcard.php?id=<?php echo urlencode($cardId); ?>" class="btn btn-primary">
                📇 Save to Contacts
            </a>
            <a href="/public/capture-lead.php?card=<?php echo urlencode($cardId); ?>" class="btn btn-success">
                💬 Connect with Me
            </a>
            <button onclick="shareCard()" class="btn btn-secondary">
                🔗 Share This Card
            </button>
        </div>
        
        <!-- Footer -->
        <div class="card-footer">
            <p>Create your own digital business card at <a href="https://sharemycard.app">ShareMyCard.app</a></p>
        </div>
    </div>
    
    <script>
        // Analytics tracking with consent check
        const cardId = '<?php echo $cardId; ?>';
        
        function canTrack() {
            const consent = localStorage.getItem('analytics_consent');
            // Track if accepted or no choice made (implied consent)
            return consent !== 'declined';
        }
        
        // Track page view
        if (canTrack()) {
            fetch('/api/analytics/track.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    card_id: cardId,
                    event_type: 'view'
                })
            }).catch(err => console.log('Analytics tracking failed:', err));
        }
        
        // Track link clicks
        document.addEventListener('DOMContentLoaded', () => {
            // Track all external links (emails, phones, websites)
            document.querySelectorAll('a[href^="mailto:"], a[href^="tel:"], a[href^="http"]').forEach(link => {
                link.addEventListener('click', () => {
                    if (canTrack()) {
                        fetch('/api/analytics/track.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                card_id: cardId,
                                event_type: 'click',
                                event_target: link.href
                            })
                        }).catch(err => console.log('Click tracking failed:', err));
                    }
                });
            });
        });
        
        function shareCard() {
            const url = window.location.href;
            
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes($fullName); ?>',
                    text: 'Check out my business card',
                    url: url
                }).catch(() => {
                    copyToClipboard(url);
                });
            } else {
                copyToClipboard(url);
            }
        }
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Link copied to clipboard!');
            }).catch(() => {
                // Fallback
                const input = document.createElement('input');
                input.value = text;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                alert('Link copied to clipboard!');
            });
        }
    </script>
    
    <?php require_once __DIR__ . '/includes/cookie-banner.php'; ?>
</body>
</html>

