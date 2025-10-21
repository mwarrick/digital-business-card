<?php
/**
 * Email Signature Generator
 * Dedicated page for creating and customizing email signatures
 */

require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

UserAuth::requireAuth();

// Get card ID from URL parameter
$cardId = $_GET['card_id'] ?? null;
if (!$cardId) {
    header('Location: /user/dashboard.php');
    exit;
}

// Get card details
$db = Database::getInstance();
$card = $db->querySingle(
    "SELECT * FROM business_cards WHERE id = ? AND user_id = ? AND is_active = 1",
    [$cardId, UserAuth::getUserId()]
);

if (!$card) {
    header('Location: /user/dashboard.php');
    exit;
}

// Get additional contact info from separate tables
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

// Get primary email and phone
$primaryEmail = !empty($emails) ? $emails[0]['email'] : '';
$primaryPhone = !empty($phones) ? $phones[0]['phone_number'] : '';

// Convert websites to the format expected by JavaScript
$websiteLinks = [];
foreach ($websites as $website) {
    $websiteLinks[] = [
        'url' => $website['url'],
        'description' => $website['description'] ?? ''
    ];
}

// Build full name
$fullName = trim($card['first_name'] . ' ' . $card['last_name']);

// Get profile photo and company logo URLs
$profilePhotoUrl = null;
$companyLogoUrl = null;

if (!empty($card['profile_photo_path'])) {
    $profilePhotoUrl = "https://sharemycard.app/api/media/view?filename=" . urlencode($card['profile_photo_path']);
}

if (!empty($card['company_logo_path'])) {
    $companyLogoUrl = "https://sharemycard.app/api/media/view?filename=" . urlencode($card['company_logo_path']);
}

$pageTitle = 'Email Signature - ' . $fullName;
$user = UserAuth::getUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - ShareMyCard</title>
    <link rel="stylesheet" href="/user/includes/user-style.css">
    <style>
        .signature-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .signature-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .signature-header h1 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .signature-header p {
            color: #666;
            font-size: 16px;
        }
        
        .signature-generator {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .signature-controls {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .signature-preview {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .control-section {
            margin-bottom: 25px;
        }
        
        .control-section h4 {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
        }
        
        .image-options {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .image-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
        }
        
        .image-option input[type="radio"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .element-list {
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .element-item {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .element-item:last-child {
            border-bottom: none;
        }
        
        .element-item.locked {
            background: #e9ecef;
            color: #6c757d;
        }
        
        .element-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .element-item .element-name {
            flex: 1;
            font-weight: 500;
        }
        
        .element-item .element-locked {
            font-size: 12px;
            color: #999;
        }
        
        .element-controls {
            display: flex;
            gap: 5px;
        }
        
        .element-controls button {
            padding: 4px 10px;
            background: #e0e0e0;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .element-controls button:hover {
            background: #d0d0d0;
        }
        
        .tracking-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #e0e0e0;
        }
        
        .tracking-section label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
            margin-bottom: 8px;
        }
        
        .tracking-section input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .tracking-description {
            color: #666;
            font-size: 12px;
            margin-left: 26px;
        }
        
        .website-selection {
            background: white;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #ddd;
        }
        
        .website-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .website-item:last-child {
            border-bottom: none;
        }
        
        .website-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        
        .website-url {
            flex: 1;
            font-size: 14px;
            color: #333;
        }
        
        .website-description {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }
        
        .preview-section h4 {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
        }
        
        .signature-preview-content {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: #fafafa;
            min-height: 200px;
        }
        
        .signature-code {
            margin-top: 20px;
        }
        
        .signature-code textarea {
            width: 100%;
            height: 150px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            resize: vertical;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .back-link:hover {
            color: #5a6fd8;
        }
        
        @media (max-width: 768px) {
            .signature-generator {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .image-options {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <a href="/user/dashboard.php" class="brand-link">
                <span class="brand-icon">📇</span>
                <span class="brand-text">ShareMyCard</span>
            </a>
        </div>
        
        <button class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div class="nav-links mobile">
            <a href="/user/dashboard.php" class="nav-link">Dashboard</a>
            <a href="/user/cards/create.php" class="nav-link">Create Card</a>
            <a href="/user/cards/invitation-analytics.php" class="nav-link">📧 Invitations</a>
            <?php if ($user['is_admin']): ?>
                <a href="/admin/dashboard.php" class="nav-link" style="background: rgba(255,255,255,0.2);">⚙️ Admin Panel</a>
            <?php endif; ?>
            <a href="https://github.com/mwarrick/digital-business-card/issues" target="_blank" class="nav-link">🐛 Report Issues</a>
            <a href="/user/logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <div class="signature-container">
        <a href="/user/cards/view.php?id=<?php echo urlencode($cardId); ?>" class="back-link">
            ← Back to Card
        </a>
        
        <div class="signature-header">
            <h1>📧 Email Signature</h1>
            <p>Customize and copy this HTML signature for your email client (Gmail, Outlook, etc.)</p>
        </div>
        
        <div class="signature-generator">
            <!-- Left Column: Controls -->
            <div class="signature-controls">
                <!-- Signature Image -->
                <div class="control-section">
                    <h4>Signature Image</h4>
                    <div class="image-options">
                        <label class="image-option">
                            <input type="radio" name="signatureImage" value="profile" checked onchange="updateSignature()">
                            <span>👤 Profile Photo</span>
                        </label>
                        <label class="image-option">
                            <input type="radio" name="signatureImage" value="logo" onchange="updateSignature()">
                            <span>🏢 Company Logo</span>
                        </label>
                        <label class="image-option">
                            <input type="radio" name="signatureImage" value="none" onchange="updateSignature()">
                            <span>🚫 No Image</span>
                        </label>
                    </div>
                </div>
                
                <!-- Elements & Order -->
                <div class="control-section">
                    <h4>Elements & Order</h4>
                    <div class="element-list" id="elementList">
                        <!-- Name (always first, locked) -->
                        <div class="element-item locked" data-element="name">
                            <input type="checkbox" checked disabled>
                            <span class="element-name">👤 Name</span>
                            <span class="element-locked">Always first</span>
                        </div>
                        
                        <!-- Job Title -->
                        <?php if (!empty($card['job_title'])): ?>
                        <div class="element-item" data-element="title">
                            <input type="checkbox" id="includeTitle" checked onchange="updateSignature()">
                            <span class="element-name">💼 Job Title</span>
                            <div class="element-controls">
                                <button onclick="moveElement('title', -1)">↑</button>
                                <button onclick="moveElement('title', 1)">↓</button>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Company -->
                        <?php if (!empty($card['company_name'])): ?>
                        <div class="element-item" data-element="company">
                            <input type="checkbox" id="includeCompany" checked onchange="updateSignature()">
                            <span class="element-name">🏢 Company</span>
                            <div class="element-controls">
                                <button onclick="moveElement('company', -1)">↑</button>
                                <button onclick="moveElement('company', 1)">↓</button>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Primary Phone -->
                        <?php if ($primaryPhone): ?>
                        <div class="element-item" data-element="phone">
                            <input type="checkbox" id="includePhone" checked onchange="updateSignature()">
                            <span class="element-name">📱 Primary Phone</span>
                            <div class="element-controls">
                                <button onclick="moveElement('phone', -1)">↑</button>
                                <button onclick="moveElement('phone', 1)">↓</button>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Primary Email -->
                        <div class="element-item" data-element="email">
                            <input type="checkbox" id="includeEmail" <?php echo $primaryEmail ? 'checked' : ''; ?> onchange="updateSignature()">
                            <span class="element-name">✉️ Primary Email</span>
                            <div class="element-controls">
                                <button onclick="moveElement('email', -1)">↑</button>
                                <button onclick="moveElement('email', 1)">↓</button>
                            </div>
                        </div>
                        
                        <!-- Primary Website -->
                        <div class="element-item" data-element="primaryWebsite">
                            <input type="checkbox" id="includePrimaryWebsite" <?php echo (!empty($websiteLinks) && !empty($websiteLinks[0]['url'])) ? 'checked' : ''; ?> onchange="updateSignature()">
                            <span class="element-name">🌐 Primary Website</span>
                            <div class="element-controls">
                                <button onclick="moveElement('primaryWebsite', -1)">↑</button>
                                <button onclick="moveElement('primaryWebsite', 1)">↓</button>
                            </div>
                        </div>
                        
                        
                        <!-- Address -->
                        <div class="element-item" data-element="address">
                            <input type="checkbox" id="includeAddress" onchange="updateSignature()">
                            <span class="element-name">📍 Address</span>
                            <div class="element-controls">
                                <button onclick="moveElement('address', -1)">↑</button>
                                <button onclick="moveElement('address', 1)">↓</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Email Tracking -->
                <div class="control-section">
                    <div class="tracking-section">
                        <label>
                            <input type="checkbox" id="includeTracking" onchange="updateSignature()">
                            <span>📧 Track email opens</span>
                        </label>
                        <div class="tracking-description">
                            Adds invisible tracking pixel to measure email open rates
                        </div>
                    </div>
                </div>
                
            </div>
            
            <!-- Right Column: Preview -->
            <div class="signature-preview">
                <h4>Preview</h4>
                <div class="signature-preview-content" id="signaturePreview">
                    <!-- Preview will be generated here -->
                </div>
                
                <div class="signature-code">
                    <h4>HTML Code</h4>
                    <textarea id="signatureCode" readonly placeholder="HTML signature will appear here..."></textarea>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="copyVisualSignature()">
                        📋 Copy for Gmail
                    </button>
                    <button class="btn btn-secondary" onclick="copySignature()">
                        📋 Copy HTML
                    </button>
                    <button class="btn btn-success" onclick="downloadSignature()">
                        💾 Download HTML
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Card data for JavaScript
        const cardData = {
            id: '<?php echo addslashes($cardId); ?>',
            fullName: '<?php echo addslashes($fullName); ?>',
            jobTitle: '<?php echo addslashes($card['job_title'] ?? ''); ?>',
            company: '<?php echo addslashes($card['company_name'] ?? ''); ?>',
            primaryPhone: '<?php echo addslashes($primaryPhone); ?>',
            primaryEmail: '<?php echo addslashes($primaryEmail); ?>',
            address: '<?php 
                $addressParts = [];
                if (!empty($address['street'])) $addressParts[] = $address['street'];
                if (!empty($address['city'])) $addressParts[] = $address['city'];
                if (!empty($address['state'])) $addressParts[] = $address['state'];
                if (!empty($address['zip_code'])) $addressParts[] = $address['zip_code'];
                if (!empty($address['country'])) $addressParts[] = $address['country'];
                echo addslashes(implode(', ', $addressParts));
            ?>',
            profilePhotoUrl: '<?php echo $profilePhotoUrl ? addslashes($profilePhotoUrl) : ''; ?>',
            companyLogoUrl: '<?php echo $companyLogoUrl ? addslashes($companyLogoUrl) : ''; ?>',
            websites: <?php echo json_encode($websiteLinks); ?>
        };
        
        // Debug: Log the card data
        console.log('Card Data Debug:', cardData);
        console.log('Primary Email:', cardData.primaryEmail);
        console.log('Primary Phone:', cardData.primaryPhone);
        console.log('Websites:', cardData.websites);
        console.log('Address:', cardData.address);
        
        // Element order (will be updated based on available elements)
        let elementOrder = ['name'];
        
        // Initialize element order based on available elements
        function initializeElementOrder() {
            const availableElements = [];
            if (document.getElementById('includeTitle')) availableElements.push('title');
            if (document.getElementById('includeCompany')) availableElements.push('company');
            if (document.getElementById('includePhone')) availableElements.push('phone');
            if (document.getElementById('includeEmail')) availableElements.push('email');
            if (document.getElementById('includePrimaryWebsite')) availableElements.push('primaryWebsite');
            if (document.getElementById('includeAddress')) availableElements.push('address');
            
            elementOrder = ['name', ...availableElements];
        }
        
        // Move element up or down in order
        function moveElement(elementName, direction) {
            const currentIndex = elementOrder.indexOf(elementName);
            if (currentIndex === -1) return;
            
            const newIndex = currentIndex + direction;
            if (newIndex < 1 || newIndex >= elementOrder.length) return; // Keep 'name' first
            
            // Swap elements
            [elementOrder[currentIndex], elementOrder[newIndex]] = [elementOrder[newIndex], elementOrder[currentIndex]];
            
            // Update DOM order
            updateElementOrder();
            updateSignature();
        }
        
        // Update DOM element order
        function updateElementOrder() {
            const container = document.getElementById('elementList');
            const elements = elementOrder.map(name => {
                return container.querySelector(`[data-element="${name}"]`);
            }).filter(el => el !== null);
            
            // Reorder DOM elements
            elements.forEach(el => {
                container.appendChild(el);
            });
        }
        
        // Get selected additional websites (excluding primary)
        function getSelectedWebsites() {
            const checkboxes = document.querySelectorAll('.website-checkbox:checked');
            const selected = [];
            
            checkboxes.forEach(cb => {
                const index = parseInt(cb.getAttribute('data-index'));
                if (cardData.websites[index] && index > 0) { // Exclude primary website (index 0)
                    selected.push(cardData.websites[index]);
                }
            });
            
            return selected;
        }
        
        // Generate signature HTML
        function generateSignatureHTML() {
            const includeTitle = document.getElementById('includeTitle')?.checked ?? false;
            const includeCompany = document.getElementById('includeCompany')?.checked ?? false;
            const includePhone = document.getElementById('includePhone')?.checked ?? false;
            const includeEmail = document.getElementById('includeEmail')?.checked ?? false;
            const includePrimaryWebsite = document.getElementById('includePrimaryWebsite')?.checked ?? false;
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
            
            // Build elements in custom order
            const elementBuilders = {
                title: () => {
                    if (includeTitle && cardData.jobTitle) {
                        return '<div style="color: #666666; font-size: 14px; margin-bottom: 2px;">' + escapeHtml(cardData.jobTitle) + '</div>';
                    }
                    return '';
                },
                company: () => {
                    if (includeCompany && cardData.company) {
                        return '<div style="color: #888888; font-size: 14px; margin-bottom: 8px;">' + escapeHtml(cardData.company) + '</div>';
                    }
                    return '';
                },
                phone: () => {
                    if (includePhone && cardData.primaryPhone) {
                        return '<div style="font-size: 13px; color: #666666; margin-bottom: 8px;"><span style="color: #667eea;">📱</span> ' + escapeHtml(cardData.primaryPhone) + '</div>';
                    }
                    return '';
                },
                email: () => {
                    if (includeEmail) {
                        if (cardData.primaryEmail) {
                            return '<div style="font-size: 13px; color: #666666; margin-bottom: 8px;"><span style="color: #667eea;">✉️</span> <a href="mailto:' + escapeHtml(cardData.primaryEmail) + '" style="color: #333333; text-decoration: none;">' + escapeHtml(cardData.primaryEmail) + '</a></div>';
                        } else {
                            return '<div style="font-size: 13px; color: #999; margin-bottom: 8px;"><span style="color: #667eea;">✉️</span> [No email available]</div>';
                        }
                    }
                    return '';
                },
                primaryWebsite: () => {
                    if (includePrimaryWebsite) {
                        if (cardData.websites && cardData.websites.length > 0 && cardData.websites[0].url) {
                            const primaryWebsite = cardData.websites[0];
                            return '<div style="font-size: 13px; color: #666666; margin-bottom: 8px;"><span style="color: #667eea;">🌐</span> <a href="' + escapeHtml(primaryWebsite.url) + '" style="color: #667eea; text-decoration: none;">' + escapeHtml(primaryWebsite.url) + '</a></div>';
                        } else {
                            return '<div style="font-size: 13px; color: #999; margin-bottom: 8px;"><span style="color: #667eea;">🌐</span> [No website available]</div>';
                        }
                    }
                    return '';
                },
                address: () => {
                    if (includeAddress) {
                        if (cardData.address && cardData.address.trim()) {
                            return '<div style="font-size: 13px; color: #666666; margin-bottom: 8px;"><span style="color: #667eea;">📍</span> ' + escapeHtml(cardData.address) + '</div>';
                        } else {
                            return '<div style="font-size: 13px; color: #999; margin-bottom: 8px;"><span style="color: #667eea;">📍</span> [No address available]</div>';
                        }
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
            
            // Add tracking pixel if enabled
            const includeTracking = document.getElementById('includeTracking')?.checked ?? false;
            if (includeTracking && cardData.id) {
                const timestamp = Math.floor(Date.now() / 1000);
                html += '<img src="https://sharemycard.app/api/analytics/pixel.php?card_id=' + escapeHtml(cardData.id) + '&t=' + timestamp + '" width="1" height="1" style="display:block;" alt="">';
            }
            
            return html;
        }
        
        // Escape HTML
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
        
        // Update signature
        function updateSignature() {
            const signatureHTML = generateSignatureHTML();
            document.getElementById('signaturePreview').innerHTML = signatureHTML;
            document.getElementById('signatureCode').value = signatureHTML;
        }
        
        // Copy visual signature
        function copyVisualSignature() {
            const preview = document.getElementById('signaturePreview');
            const btn = document.querySelector('button[onclick="copyVisualSignature()"]');
            
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
                btn.innerHTML = '✅ Copied!';
                btn.style.background = '#28a745';
                
                setTimeout(() => {
                    btn.innerHTML = '📋 Copy for Gmail';
                    btn.style.background = '#667eea';
                }, 2000);
                
                // Clear selection
                selection.removeAllRanges();
            } catch (err) {
                alert('Please manually select and copy the signature above');
            }
        }
        
        // Copy signature HTML
        function copySignature() {
            const textarea = document.getElementById('signatureCode');
            const btn = document.querySelector('button[onclick="copySignature()"]');
            
            textarea.select();
            textarea.setSelectionRange(0, 99999); // For mobile
            
            navigator.clipboard.writeText(textarea.value).then(() => {
                // Success feedback
                btn.innerHTML = '✅ Copied!';
                btn.style.background = '#28a745';
                
                setTimeout(() => {
                    btn.innerHTML = '📋 Copy HTML';
                    btn.style.background = '#6c757d';
                }, 2000);
            }).catch(() => {
                // Fallback for older browsers
                document.execCommand('copy');
                btn.innerHTML = '✅ Copied!';
                btn.style.background = '#28a745';
                
                setTimeout(() => {
                    btn.innerHTML = '📋 Copy HTML';
                    btn.style.background = '#6c757d';
                }, 2000);
            });
        }
        
        // Download signature HTML
        function downloadSignature() {
            const html = document.getElementById('signatureCode').value;
            const blob = new Blob([html], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'email-signature.html';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
        
        // Initialize signature on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeElementOrder();
            updateSignature();
        });
        
        // Navigation toggle functionality
        document.addEventListener("DOMContentLoaded", function() {
            const navToggle = document.querySelector(".hamburger");
            const navMenu = document.querySelector(".nav-links.mobile");
            
            if (navToggle && navMenu) {
                navToggle.addEventListener("click", function() {
                    navMenu.classList.toggle("active");
                    navToggle.classList.toggle("active");
                });
            }
        });
    </script>
</body>
</html>
