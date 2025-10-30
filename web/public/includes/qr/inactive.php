<?php
// Friendly page for inactive/disabled QR codes
$brandName = 'ShareMyCard.app';
$homeUrl = '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Inactive • <?php echo htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        body { margin:0; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background:#f5f5f7; color:#1f2937; }
        .hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 48px 20px; color:#fff; text-align:center; }
        .container { max-width: 720px; margin: -30px auto 40px; background:#fff; border-radius:16px; box-shadow:0 20px 50px rgba(0,0,0,.12); padding:28px; }
        h1 { margin:0 0 10px; font-size:26px; }
        p { margin:10px 0; line-height:1.6; }
        .btn { display:inline-block; background:#667eea; color:#fff; padding:10px 16px; border-radius:8px; text-decoration:none; margin-top:12px; }
        .brand { font-weight:700; }
    </style>
</head>
<body>
    <div class="hero">
        <h1>Sorry, this QR code is no longer active</h1>
        <p>Please contact the owner if you believe this is an error.</p>
    </div>
    <div class="container">
        <p>This QR code was created with <span class="brand"><?php echo htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8'); ?></span>.</p>
        <p>You can learn more about creating beautiful QR experiences, landing pages and lead capture by visiting our homepage.</p>
        <a class="btn" href="<?php echo htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8'); ?>">Go to <?php echo htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8'); ?></a>
    </div>
    <div style="text-align:center; margin-top:12px; color:#fff; font-size:13px; opacity:0.9;">
        Powered by <a href="https://sharemycard.app" style="color:#fff; text-decoration:underline;">ShareMyCard.app</a>
    </div>
</body>
</html>
