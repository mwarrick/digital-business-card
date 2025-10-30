<?php
/**
 * Shared landing renderer for Custom QR Codes
 * Expects variables: $qr (row), $payload (array), $qrId (string)
 */

$landingHtml = $qr['landing_html'] ?? '';
if (!class_exists('Sanitize')) {
    require_once __DIR__ . '/../../../api/includes/Sanitize.php';
}
$landingHtml = Sanitize::landingHtml($landingHtml);

$cover = $qr['cover_image_url'] ?: null;
$landingTitle = $qr['landing_title'] ?: ($qr['title'] ?: '');
$showLead = (int)$qr['show_lead_form'] === 1;
$type = $qr['type'];
// Theme support
if (!function_exists('generateThemeCSS')) {
    require_once __DIR__ . '/../../../includes/themes.php';
}
$themeKey = $qr['theme_key'] ?: 'professional-blue';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($landingTitle ?: 'QR Code', ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        <?php echo generateThemeCSS($themeKey); ?>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: var(--font-family); background: var(--gradient); min-height: 100vh; padding: 40px 20px; }
        .container { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 40px; }
        .cover { width: 100%; max-height: 320px; object-fit: cover; border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,0.15); margin-bottom: 16px; }
        h1 { font-size: 28px; background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 10px; }
        .content { color: var(--text-color); line-height: 1.6; }
        .content p { margin: 8px 0; }
        .section { margin-top: 18px; }
        .cta { display: inline-block; margin-top: 16px; padding: 12px 18px; background: var(--accent-color); color: #fff; border-radius: 8px; text-decoration: none; }
        .meta { color: var(--text_light); font-size: 14px; margin-top: 16px; }
        .wifi { background: #f8f9ff; border: 1px solid #e6e8ff; padding: 12px; border-radius: 8px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    </style>
    <meta name="robots" content="noindex">
</head>
<body>
    <div class="container">
        <?php if ($cover): ?>
            <img class="cover" src="<?php echo htmlspecialchars($cover, ENT_QUOTES, 'UTF-8'); ?>" alt="Cover">
        <?php endif; ?>
        <?php if ($landingTitle): ?><h1><?php echo htmlspecialchars($landingTitle, ENT_QUOTES, 'UTF-8'); ?></h1><?php endif; ?>
        <div class="content section"><?php echo $landingHtml; ?></div>

        <?php if ($type === 'text'): ?>
            <div class="section">
                <div class="wifi" style="background:#f7f7fb;border-color:#eee;">
                    <?php echo nl2br(htmlspecialchars($payload['text'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($type === 'wifi'): ?>
            <div class="section wifi">
                <div class="grid">
                    <div><strong>SSID:</strong> <?php echo htmlspecialchars($payload['ssid'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div><strong>Security:</strong> <?php echo htmlspecialchars($payload['auth'] ?? 'WPA', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div><strong>Password:</strong> <?php echo htmlspecialchars($payload['password'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($type === 'appstore' && empty($payload['redirected'])): ?>
            <div class="section">
                <a class="cta" href="<?php echo htmlspecialchars($payload['ios_url'] ?? '#', ENT_QUOTES, 'UTF-8'); ?>">Open in App Store</a>
                <a class="cta" style="background:#34a853;margin-left:8px;" href="<?php echo htmlspecialchars($payload['android_url'] ?? '#', ENT_QUOTES, 'UTF-8'); ?>">Open in Google Play</a>
            </div>
        <?php endif; ?>

        <?php if ((int)$qr['show_lead_form'] === 1): ?>
            <div class="section">
                <a class="cta" href="/public/capture-lead.php?qr_id=<?php echo urlencode($qrId); ?>">Contact / Leave your info</a>
            </div>
        <?php endif; ?>

        <div class="meta">QR ID: <?php echo htmlspecialchars($qrId, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
</body>
</html>


