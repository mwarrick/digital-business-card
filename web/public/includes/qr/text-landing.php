<?php
/**
 * Text landing template for Custom QR Codes
 * Expects variables: $qr, $payload, $qrId
 */

$text = $payload['text'] ?? '';
$title = $qr['title'] ?: 'QR Code Message';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
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
            max-width: 600px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .text-icon {
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
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .lead-button:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        .qr-info {
            margin-top: 24px;
            padding: 16px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="text-icon">📝</div>
        <h1><?= htmlspecialchars($title) ?></h1>
        
        <div class="text-content"><?= htmlspecialchars($text) ?></div>
        
        <?php if ($qr['show_lead_form']): ?>
            <a href="/capture-lead.php?qr_id=<?= urlencode($qrId) ?>" class="lead-button">
                Get in Touch
            </a>
        <?php endif; ?>
        
        <div class="qr-info">
            <p>This message was shared via QR code</p>
        </div>
    </div>
</body>
</html>
