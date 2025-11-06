<?php
/**
 * Expired QR Code Template
 * Displays expiration notice and optional lead form button
 * Expects variables: $expirationNotice (string), $showLeadForm (bool), $qrId (string)
 */

$brandName = 'ShareMyCard.app';
$homeUrl = '/';
$leadFormUrl = '/public/capture-lead.php?qr_id=' . urlencode($qrId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Expired • <?php echo htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        body { margin:0; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background:#f5f5f7; color:#1f2937; }
        .hero { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 48px 20px; color:#fff; text-align:center; }
        .container { max-width: 720px; margin: -30px auto 40px; background:#fff; border-radius:16px; box-shadow:0 20px 50px rgba(0,0,0,.12); padding:28px; }
        h1 { margin:0 0 10px; font-size:26px; }
        p { margin:10px 0; line-height:1.6; }
        .expiration-notice { background:#fef3c7; border-left:4px solid #f59e0b; padding:16px; border-radius:8px; margin:20px 0; color:#92400e; font-size:16px; }
        .btn { display:inline-block; background:#667eea; color:#fff; padding:12px 20px; border-radius:8px; text-decoration:none; margin-top:12px; font-weight:600; transition:all 0.2s; }
        .btn:hover { background:#5568d3; transform:translateY(-2px); box-shadow:0 4px 12px rgba(102,126,234,0.4); }
        .btn-secondary { background:#f59e0b; margin-left:12px; }
        .btn-secondary:hover { background:#d97706; }
        .brand { font-weight:700; }
        .button-group { margin-top:24px; }
    </style>
</head>
<body>
    <div class="hero">
        <h1>QR Code Expired</h1>
        <p>This QR code is no longer active</p>
    </div>
    <div class="container">
        <div class="expiration-notice">
            <?php echo nl2br(htmlspecialchars($expirationNotice, ENT_QUOTES, 'UTF-8')); ?>
        </div>
        
        <?php if ($showLeadForm): ?>
            <div class="button-group">
                <p>Would you like to get in touch? You can still contact us:</p>
                <a class="btn" href="<?php echo htmlspecialchars($leadFormUrl, ENT_QUOTES, 'UTF-8'); ?>">Contact Us / Leave Your Info</a>
            </div>
        <?php endif; ?>
        
        <p style="margin-top:24px;">This QR code was created with <span class="brand"><?php echo htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8'); ?></span>.</p>
        <p>You can learn more about creating beautiful QR experiences, landing pages and lead capture by visiting our homepage.</p>
        <a class="btn btn-secondary" href="<?php echo htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8'); ?>">Go to <?php echo htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8'); ?></a>
    </div>
    <div style="text-align:center; margin-top:12px; color:#fff; font-size:13px; opacity:0.9;">
        Powered by <a href="https://sharemycard.app" style="color:#fff; text-decoration:underline;">ShareMyCard.app</a>
    </div>
</body>
</html>

