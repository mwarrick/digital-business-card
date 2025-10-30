<?php
require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../api/includes/qr/Generator.php';
require_once __DIR__ . '/../../includes/themes.php';
require_once __DIR__ . '/../../api/includes/Sanitize.php';

UserAuth::requireAuth();
$db = Database::getInstance();
$userId = UserAuth::getUserId();

$id = $_GET['id'] ?? '';
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';
$debug && error_log('[QR EDIT DEBUG] userId=' . $userId . ' id=' . $id);
$row = $db->querySingle("SELECT * FROM custom_qr_codes WHERE id = ? AND user_id = ?", [$id, $userId]);
if (!$row) {
    http_response_code(404);
    if ($debug) {
        $raw = $db->querySingle("SELECT id,user_id,status,type,created_at FROM custom_qr_codes WHERE id = ?", [$id]);
        echo '<pre>QR code not found for this user. Current userId=' . htmlspecialchars((string)$userId) . "\nRaw lookup: " . htmlspecialchars(json_encode($raw)) . '</pre>';
    } else {
        echo 'QR code not found';
    }
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $theme = $_POST['theme_key'] ?? null;
    $cover = trim($_POST['cover_image_url'] ?? '');
    $landingTitle = trim($_POST['landing_title'] ?? '');
    $landingHtml = Sanitize::landingHtml($_POST['landing_html'] ?? '');
    $showLead = isset($_POST['show_lead_form']) ? 1 : 0;
    $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

    $payload = json_decode($row['payload_json'] ?? 'null', true) ?: [];
    switch ($row['type']) {
        case 'url': $payload['url'] = Sanitize::url($_POST['url'] ?? ($payload['url'] ?? '')); break;
        case 'social': $payload['platform'] = $_POST['platform'] ?? ($payload['platform'] ?? ''); $payload['username'] = Sanitize::username($_POST['username'] ?? ($payload['username'] ?? '')); break;
        case 'text': $payload['text'] = $_POST['text'] ?? ($payload['text'] ?? ''); break;
        case 'wifi': $payload['ssid'] = trim($_POST['ssid'] ?? ($payload['ssid'] ?? '')); $payload['auth'] = $_POST['auth'] ?? ($payload['auth'] ?? 'WPA'); $payload['password'] = trim($_POST['password'] ?? ($payload['password'] ?? '')); break;
        case 'appstore': $payload['ios_url'] = Sanitize::url($_POST['ios_url'] ?? ($payload['ios_url'] ?? '')); $payload['android_url'] = Sanitize::url($_POST['android_url'] ?? ($payload['android_url'] ?? '')); break;
    }

    if (!$errors) {
        $db->execute(
            "UPDATE custom_qr_codes SET title=?, theme_key=?, cover_image_url=?, landing_title=?, landing_html=?, show_lead_form=?, status=?, payload_json=? WHERE id=? AND user_id=?",
            [ $title, $theme, $cover, $landingTitle, $landingHtml, $showLead, $status, json_encode($payload, JSON_UNESCAPED_SLASHES), $id, $userId ]
        );
        header('Location: /user/qr/edit.php?id=' . urlencode($id) . '&saved=1');
        exit;
    }
}

$themes = getThemes();
$payload = json_decode($row['payload_json'] ?? 'null', true) ?: [];
$saved = isset($_GET['saved']) && $_GET['saved'] === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit QR Code</title>
    <link rel="stylesheet" href="/user/includes/user-style.css">
    <style>
        .container { max-width: 900px; margin: 30px auto; background: #fff; border-radius: 12px; box-shadow: 0 10px 24px rgba(0,0,0,.08); padding: 24px; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        label { font-weight: 600; display:block; margin: 10px 0 6px; }
        input[type=text], textarea, select { width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px; }
        textarea { min-height: 100px; }
        .btn { display: inline-block; padding: 10px 14px; border-radius: 8px; text-decoration: none; }
        .btn-primary { background: #667eea; color: #fff; }
        .muted { color:#666; font-size: 12px; }
        .status { margin-top: 8px; }
        .preview { margin-top: 16px; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navigation.php'; ?>
    <div class="container">
        <h1>Edit QR Code</h1>
        <?php
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'sharemycard.app';
            $publicUrl = $scheme . '://' . $host . '/qr/' . urlencode($id);
            $qrPng = \QRCard\QR\Generator::buildImageUrl($publicUrl, 180, 'png');
            $qrSvg = \QRCard\QR\Generator::buildImageUrl($publicUrl, 180, 'svg');
        ?>
        <div style="display:flex;gap:16px;align-items:center;margin:10px 0 18px;">
            <img src="<?php echo htmlspecialchars($qrPng, ENT_QUOTES, 'UTF-8'); ?>" alt="QR" style="width:120px;height:120px;object-fit:contain;border:1px solid #eee;border-radius:8px;background:#fff;">
            <div>
                <div class="muted" style="margin-bottom:6px;">Public URL</div>
                <input type="text" readonly value="<?php echo htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8'); ?>" style="width:420px;max-width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:8px;">
                <div style="margin-top:8px;font-size:12px;">
                    <a href="<?php echo htmlspecialchars($qrPng, ENT_QUOTES, 'UTF-8'); ?>" download>Download PNG</a>
                    ·
                    <a href="<?php echo htmlspecialchars($qrSvg, ENT_QUOTES, 'UTF-8'); ?>" download>Download SVG</a>
                    ·
                    <a href="/qr/<?php echo urlencode($id); ?>" target="_blank">Open public page</a>
                </div>
            </div>
        </div>
        <?php if ($saved): ?>
            <div style="background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:8px;padding:10px 12px;margin:10px 0;">
                ✔️ Changes saved. <a href="/qr/<?php echo urlencode($id); ?>" target="_blank">View public page</a>
            </div>
        <?php endif; ?>
        <form method="post" action="/user/qr/edit.php?id=<?php echo urlencode($id); ?>">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="row">
                <div>
                    <label>Title
                        <input type="text" name="title" value="<?php echo htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                </div>
                <div>
                    <label>Status
                        <select name="status" class="status">
                            <option value="active" <?php echo ($row['status']==='active')?'selected':''; ?>>Active</option>
                            <option value="inactive" <?php echo ($row['status']==='inactive')?'selected':''; ?>>Inactive</option>
                        </select>
                    </label>
                </div>
            </div>

            <?php if ($row['type']==='url'): ?>
            <label>Destination URL
                <input type="text" name="url" value="<?php echo htmlspecialchars($payload['url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </label>
            <?php elseif ($row['type']==='social'): ?>
            <div class="row">
                <div>
                    <label>Platform
                        <select name="platform">
                            <?php foreach (['linkedin','facebook','instagram','twitter','tiktok','youtube'] as $p): ?>
                            <option value="<?php echo $p; ?>" <?php echo (($payload['platform'] ?? '')===$p)?'selected':''; ?>><?php echo ucfirst($p); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <div>
                    <label>Username
                        <input type="text" name="username" value="<?php echo htmlspecialchars($payload['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                </div>
            </div>
            <?php elseif ($row['type']==='text'): ?>
            <label>Text
                <textarea name="text"><?php echo htmlspecialchars($payload['text'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>
            <?php elseif ($row['type']==='wifi'): ?>
            <div class="row">
                <div>
                    <label>SSID
                        <input type="text" name="ssid" value="<?php echo htmlspecialchars($payload['ssid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                </div>
                <div>
                    <label>Security
                        <select name="auth">
                            <?php foreach (['WPA','WEP','nopass'] as $a): ?>
                            <option value="<?php echo $a; ?>" <?php echo (($payload['auth'] ?? 'WPA')===$a)?'selected':''; ?>><?php echo $a; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <div>
                    <label>Password
                        <input type="text" name="password" value="<?php echo htmlspecialchars($payload['password'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                </div>
            </div>
            <?php elseif ($row['type']==='appstore'): ?>
            <div class="row">
                <div>
                    <label>iOS App Store URL
                        <input type="text" name="ios_url" value="<?php echo htmlspecialchars($payload['ios_url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                </div>
                <div>
                    <label>Google Play URL
                        <input type="text" name="android_url" value="<?php echo htmlspecialchars($payload['android_url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                </div>
            </div>
            <?php endif; ?>

            <hr style="margin:18px 0; border:none; border-top:1px solid #eee;">
            <div id="landing-section">
            <h3>Landing Customization</h3>
            <div class="row">
                <div>
                    <label>Theme
                        <select name="theme_key">
                            <option value="">(Default)</option>
                            <?php foreach ($themes as $key => $t): ?>
                                <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($row['theme_key'] ?? '')===$key)?'selected':''; ?>><?php echo htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <div>
                    <label>Cover image URL
                        <input type="text" name="cover_image_url" value="<?php echo htmlspecialchars($row['cover_image_url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                </div>
            </div>
            <div class="row">
                <div>
                    <label>Landing title
                        <input type="text" name="landing_title" value="<?php echo htmlspecialchars($row['landing_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                </div>
                <div>
                    <label>Show lead form
                        <input type="checkbox" name="show_lead_form" <?php echo ((int)$row['show_lead_form']===1)?'checked':''; ?>>
                    </label>
                </div>
            </div>
            <label>Landing HTML
                <textarea name="landing_html"><?php echo htmlspecialchars($row['landing_html'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>
            </div>

            <?php if ($saved): ?>
            <div class="preview">
                <a class="btn" href="/qr/<?php echo urlencode($id); ?>" target="_blank">Open Public Page</a>
            </div>
            <?php endif; ?>

            <div style="margin-top:16px;">
                <button class="btn btn-primary" type="submit">Save Changes</button>
                <a class="btn" href="/user/qr/">Back</a>
                <a class="btn" href="#" id="delete-link" style="background:#e53935;color:#fff;margin-left:8px;">Delete</a>
            </div>
        </form>
    </div>
    <div id="delete-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:12px;max-width:420px;width:92%;padding:18px;box-shadow:0 10px 24px rgba(0,0,0,.2)">
            <h3 style="margin:0 0 8px;">Delete QR Code?</h3>
            <p style="margin:0 0 12px;color:#444;">This will permanently delete this QR code and all its analytics. This action cannot be undone.</p>
            <form method="post" action="/user/qr/delete.php">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px;">
                    <button type="button" id="cancel-delete" class="btn">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background:#e53935;">Delete</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    (function(){
        const modal = document.getElementById('delete-modal');
        const del = document.getElementById('delete-link');
        const cancel = document.getElementById('cancel-delete');
        if (del) del.addEventListener('click', function(e){ e.preventDefault(); modal.style.display = 'flex'; });
        if (cancel) cancel.addEventListener('click', function(){ modal.style.display = 'none'; });
        modal && modal.addEventListener('click', function(e){ if (e.target === modal) modal.style.display = 'none'; });
    })();
    (function(){
        const landing = document.getElementById('landing-section');
        if (!landing) return;
        const type = '<?php echo htmlspecialchars($row['type'], ENT_QUOTES, 'UTF-8'); ?>';
        const noLandingTypes = ['url','social','appstore'];
        if (noLandingTypes.includes(type)) {
            landing.style.display = 'none';
        }
    })();
    </script>
</body>
</html>


