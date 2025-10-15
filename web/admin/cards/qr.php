<?php
/**
 * QR Code Generator for Business Card
 */

require_once __DIR__ . '/../includes/AdminAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../includes/themes.php';

AdminAuth::requireAuth();

$cardId = $_GET['id'] ?? '';

if (empty($cardId)) {
    header('Location: /admin/my-cards.php');
    exit;
}

$db = Database::getInstance();

// Get admin user ID
$adminUser = $db->querySingle("SELECT id FROM users WHERE email = ?", [AdminAuth::getEmail()]);
$adminUserId = $adminUser['id'];

// Get card
$card = $db->querySingle(
    "SELECT * FROM business_cards WHERE id = ? AND user_id = ? AND is_active = 1",
    [$cardId, $adminUserId]
);

if (!$card) {
    header('Location: /admin/my-cards.php?error=not_found');
    exit;
}

// Get additional contact info
$emails = $db->query(
    "SELECT * FROM email_contacts WHERE business_card_id = ? ORDER BY created_at",
    [$cardId]
);

$phones = $db->query(
    "SELECT * FROM phone_contacts WHERE business_card_id = ? ORDER BY created_at",
    [$cardId]
);

$websites = $db->query(
    "SELECT * FROM website_links WHERE business_card_id = ? ORDER BY created_at",
    [$cardId]
);

$address = $db->querySingle(
    "SELECT * FROM addresses WHERE business_card_id = ?",
    [$cardId]
);

// Generate vCard data (Version 4.0 for better URI support)
$vcard = "BEGIN:VCARD\r\n";
$vcard .= "VERSION:4.0\r\n";
$vcard .= "FN:" . $card['first_name'] . " " . $card['last_name'] . "\r\n";
$vcard .= "N:" . $card['last_name'] . ";" . $card['first_name'] . ";;;\r\n";

// Primary phone
$vcard .= "TEL;TYPE=CELL:" . $card['phone_number'] . "\r\n";

// Additional emails
foreach ($emails as $email) {
    $type = strtoupper($email['type']);
    if ($type === 'PERSONAL') $type = 'HOME';
    $vcard .= "EMAIL;TYPE=" . $type . ":" . $email['email'] . "\r\n";
}

// Additional phones
foreach ($phones as $phone) {
    $type = strtoupper($phone['type']);
    if ($type === 'MOBILE') $type = 'CELL';
    $vcard .= "TEL;TYPE=" . $type . ":" . $phone['phone_number'] . "\r\n";
}

// Website links
foreach ($websites as $website) {
    $vcard .= "URL:" . $website['url'] . "\r\n";
}

// Address
if ($address) {
    // ADR format: ;;street;city;state;zip;country
    $vcard .= "ADR;TYPE=WORK:;;" . 
              ($address['street'] ?? '') . ";" . 
              ($address['city'] ?? '') . ";" . 
              ($address['state'] ?? '') . ";" . 
              ($address['zip_code'] ?? '') . ";" . 
              ($address['country'] ?? '') . "\r\n";
}

// Job title
if (!empty($card['job_title'])) {
    $vcard .= "TITLE:" . $card['job_title'] . "\r\n";
}

// Company name
if (!empty($card['company_name'])) {
    $vcard .= "ORG:" . $card['company_name'] . "\r\n";
}

// Bio as note
if (!empty($card['bio'])) {
    $vcard .= "NOTE:" . str_replace("\n", "\\n", $card['bio']) . "\r\n";
}

// Profile Photo URL (if available)
if (!empty($card['profile_photo_path'])) {
    // vCard 4.0 format - MEDIATYPE parameter is recommended
    $photoURL = "https://sharemycard.app/api/media/view?filename=" . $card['profile_photo_path'];
    $vcard .= "PHOTO;MEDIATYPE=image/jpeg:" . $photoURL . "\r\n";
}

$vcard .= "END:VCARD";

// URL encode for QR code
$qrData = urlencode($vcard);

// Get theme
$theme = $card['theme'] ?? 'professional-blue';
$themeCSS = generateThemeCSS($theme);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - <?php echo htmlspecialchars($card['first_name'] . ' ' . $card['last_name']); ?></title>
    <link rel="stylesheet" href="/admin/includes/admin-style.css">
    <style>
        <?php echo $themeCSS; ?>
        .qr-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .qr-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .qr-subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
        }
        
        .qr-code-wrapper {
            display: inline-block;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .qr-code-wrapper img {
            display: block;
            max-width: 100%;
            height: auto;
        }
        
        .qr-instructions {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .qr-instructions h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .qr-instructions ol {
            margin-left: 20px;
            line-height: 1.8;
            color: #666;
        }
        
        .qr-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
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
            background: var(--gradient);
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
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="main-container">
        <header class="page-header">
            <h1>QR Code Generator</h1>
            <p>Share your business card instantly</p>
        </header>
        
        <div class="qr-container">
            <div class="qr-title">
                <?php echo htmlspecialchars($card['first_name'] . ' ' . $card['last_name']); ?>
            </div>
            <div class="qr-subtitle">
                <?php if (!empty($card['job_title'])): ?>
                    <?php echo htmlspecialchars($card['job_title']); ?>
                    <?php if (!empty($card['company_name'])): ?>
                        at <?php echo htmlspecialchars($card['company_name']); ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <div class="qr-code-wrapper">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?php echo $qrData; ?>&format=png" 
                     alt="QR Code"
                     width="300"
                     height="300">
            </div>
            
            <?php if (!empty($card['profile_photo_path'])): ?>
                <div style="margin: 20px 0; padding: 20px; background: #f0f8ff; border-radius: 10px; border: 2px solid #4CAF50;">
                    <h3 style="color: #2E7D32; margin-bottom: 15px;">✅ Profile Photo Included in QR Code</h3>
                    <p style="color: #666; margin-bottom: 10px;"><strong>Path:</strong> <?php echo htmlspecialchars($card['profile_photo_path']); ?></p>
                    <p style="color: #666; margin-bottom: 15px;"><strong>URL:</strong> https://sharemycard.app/api/media/view?filename=<?php echo urlencode($card['profile_photo_path']); ?></p>
                    <img src="https://sharemycard.app/api/media/view?filename=<?php echo urlencode($card['profile_photo_path']); ?>" 
                         alt="Profile Photo" 
                         style="max-width: 150px; border-radius: 50%; border: 3px solid #4CAF50; box-shadow: 0 4px 8px rgba(0,0,0,0.2);"
                         onerror="this.parentElement.innerHTML += '<p style=\'color: red;\'>❌ Error loading image</p>'">
                </div>
            <?php else: ?>
                <div style="margin: 20px 0; padding: 20px; background: #fff3cd; border-radius: 10px; border: 2px solid #ffc107;">
                    <h3 style="color: #856404;">⚠️ No Profile Photo</h3>
                    <p style="color: #856404;">This card doesn't have a profile photo set. Upload one to include it in the QR code!</p>
                </div>
            <?php endif; ?>
            
            <div class="qr-instructions">
                <h3>📱 How to Share Your Business Card:</h3>
                <ol>
                    <li>Show this QR code on your screen</li>
                    <li>Ask someone to scan it with their phone camera</li>
                    <li>They'll be prompted to add your contact info</li>
                    <li>That's it! Your details are saved to their contacts</li>
                </ol>
            </div>
            
            <!-- Debug: Show vCard data -->
            <details style="margin: 20px 0; padding: 20px; background: #f5f5f5; border-radius: 10px;">
                <summary style="cursor: pointer; font-weight: bold; color: #333;">🔍 Debug: View vCard Data</summary>
                <pre style="margin-top: 15px; padding: 15px; background: #282c34; color: #abb2bf; border-radius: 5px; overflow-x: auto; white-space: pre-wrap; word-break: break-all;"><?php echo htmlspecialchars($vcard); ?></pre>
            </details>
            
            <div class="qr-actions">
                <a href="https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&data=<?php echo $qrData; ?>&format=png" 
                   download="qr-code-<?php echo urlencode($card['first_name'] . '-' . $card['last_name']); ?>.png"
                   class="btn btn-primary">
                    💾 Download QR Code
                </a>
                <a href="/admin/cards/vcard.php?id=<?php echo urlencode($card['id']); ?>" 
                   class="btn btn-primary"
                   style="background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);">
                    📇 Download vCard (with Photo)
                </a>
                <a href="/admin/cards/view.php?id=<?php echo urlencode($card['id']); ?>" class="btn btn-secondary">
                    View Card
                </a>
                <a href="/admin/my-cards.php" class="btn btn-secondary">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>

