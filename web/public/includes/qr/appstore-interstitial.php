<?php
/**
 * App Store interstitial for Custom QR Codes
 * Shows when app store links are missing or invalid
 * Expects variables: $qr, $payload, $qrId
 */

$ios = $payload['ios_url'] ?? '';
$android = $payload['android_url'] ?? '';
$ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
$isIOS = strpos($ua, 'iphone') !== false || strpos($ua, 'ipad') !== false;
$isAndroid = strpos($ua, 'android') !== false;
$hasIOS = !empty($ios);
$hasAndroid = !empty($android);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($qr['title'] ?: 'App Download') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .app-icon {
            width: 80px;
            height: 80px;
            background: #667eea;
            border-radius: 16px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
        }
        h1 {
            color: #333;
            margin-bottom: 16px;
            font-size: 24px;
            font-weight: 600;
        }
        .subtitle {
            color: #666;
            margin-bottom: 32px;
            font-size: 16px;
            line-height: 1.5;
        }
        .download-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .download-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 16px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.2s;
            border: 2px solid transparent;
        }
        .download-btn.ios {
            background: #000;
            color: white;
        }
        .download-btn.ios:hover {
            background: #333;
            transform: translateY(-2px);
        }
        .download-btn.android {
            background: #01875f;
            color: white;
        }
        .download-btn.android:hover {
            background: #016b4a;
            transform: translateY(-2px);
        }
        .download-btn:disabled {
            background: #e0e0e0;
            color: #999;
            cursor: not-allowed;
            transform: none;
        }
        .platform-icon {
            margin-right: 8px;
            font-size: 18px;
        }
        .fallback-message {
            color: #666;
            font-size: 14px;
            margin-top: 20px;
            padding: 16px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .qr-code {
            margin-top: 24px;
            padding: 16px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .qr-code img {
            max-width: 200px;
            height: auto;
        }
        @media (min-width: 480px) {
            .download-buttons {
                flex-direction: row;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="app-icon">📱</div>
        <h1><?= htmlspecialchars($qr['title'] ?: 'Download Our App') ?></h1>
        <p class="subtitle">Get the best experience on your device</p>
        
        <div class="download-buttons">
            <?php if ($hasIOS): ?>
                <a href="<?= htmlspecialchars($ios) ?>" class="download-btn ios">
                    <span class="platform-icon">🍎</span>
                    Download for iOS
                </a>
            <?php else: ?>
                <button class="download-btn ios" disabled>
                    <span class="platform-icon">🍎</span>
                    iOS App Coming Soon
                </button>
            <?php endif; ?>
            
            <?php if ($hasAndroid): ?>
                <a href="<?= htmlspecialchars($android) ?>" class="download-btn android">
                    <span class="platform-icon">🤖</span>
                    Download for Android
                </a>
            <?php else: ?>
                <button class="download-btn android" disabled>
                    <span class="platform-icon">🤖</span>
                    Android App Coming Soon
                </button>
            <?php endif; ?>
        </div>
        
        <?php if (!$hasIOS && !$hasAndroid): ?>
            <div class="fallback-message">
                <p>App download links are not yet available. Please check back later or contact us for more information.</p>
            </div>
        <?php endif; ?>
        
        <?php if ($qr['landing_html']): ?>
            <div style="margin-top: 32px; text-align: left;">
                <?php
                if (!class_exists('Sanitize')) {
                    require_once __DIR__ . '/../../../api/includes/Sanitize.php';
                }
                echo Sanitize::landingHtml($qr['landing_html']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if ($qr['show_lead_form']): ?>
            <div style="margin-top: 32px;">
                <a href="/capture-lead.php?qr_id=<?= urlencode($qrId) ?>" 
                   style="display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">
                    Get Updates
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
