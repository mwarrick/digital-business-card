<?php
/**
 * Text landing template for Custom QR Codes
 * Expects variables: $qr, $payload, $qrId
 */

$text = $payload['text'] ?? '';
$title = $qr['title'] ?: 'QR Code Message';
// Theme support
if (!function_exists('generateThemeCSS')) {
    require_once __DIR__ . '/../../../includes/themes.php';
}
$themeKey = $qr['theme_key'] ?? 'professional-blue';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        <?php echo generateThemeCSS($themeKey); ?>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: var(--font-family);
            background: var(--gradient);
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
            max-width: 600px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .text-icon {
            width: 80px;
            height: 80px;
            background: var(--accent-color);
            border-radius: 16px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
        }
        h1 {
            color: var(--text-color);
            margin-bottom: 24px;
            font-size: 24px;
            font-weight: 600;
        }
        .text-content {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
            text-align: left;
            white-space: pre-wrap;
            font-size: 16px;
            line-height: 1.6;
            color: #333;
            border-left: 4px solid #667eea;
        }
        .lead-button {
            display: inline-block;
            padding: 12px 24px;
            background: var(--accent-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .lead-button:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
    </style>
</head>
<body>
    <div class="container">
        <div class="text-icon">📝</div>
        <h1><?= htmlspecialchars($title) ?></h1>
        
        <div class="text-content"><?= htmlspecialchars($text) ?></div>
        
        <?php if ($qr['show_lead_form']): ?>
            <a href="/public/capture-lead.php?qr_id=<?= urlencode($qrId) ?>" class="lead-button">
                Get in Touch
            </a>
        <?php endif; ?>
        
        
        <div style="text-align:center; margin-top:12px; color:#666; font-size:13px;">
            Powered by <a href="https://sharemycard.app" style="color:#667eea; text-decoration:underline;">ShareMyCard.app</a>
        </div>
    </div>
</body>
</html>
