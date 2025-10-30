<?php
require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../includes/themes.php';
require_once __DIR__ . '/../../api/includes/Sanitize.php';

UserAuth::requireAuth();
$db = Database::getInstance();
$userId = UserAuth::getUserId();

function uuidv4(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? 'default';
    $title = trim($_POST['title'] ?? '');
    $theme = $_POST['theme_key'] ?? null;
    $cover = trim($_POST['cover_image_url'] ?? '');
    $landingTitle = trim($_POST['landing_title'] ?? '');
    $landingHtml = Sanitize::landingHtml($_POST['landing_html'] ?? '');
    $showLead = isset($_POST['show_lead_form']) ? 1 : 0;

    $payload = [];
    if ($type === 'url') {
        $payload['url'] = Sanitize::url($_POST['url'] ?? '');
    } elseif ($type === 'social') {
        $payload['platform'] = trim($_POST['platform'] ?? '');
        $payload['username'] = Sanitize::username($_POST['username'] ?? '');
    } elseif ($type === 'text') {
        $payload['text'] = $_POST['text'] ?? '';
    } elseif ($type === 'wifi') {
        $payload['ssid'] = trim($_POST['ssid'] ?? '');
        $payload['auth'] = trim($_POST['auth'] ?? 'WPA');
        $payload['password'] = trim($_POST['password'] ?? '');
    } elseif ($type === 'appstore') {
        $payload['ios_url'] = Sanitize::url($_POST['ios_url'] ?? '');
        $payload['android_url'] = Sanitize::url($_POST['android_url'] ?? '');
    }

    if ($type === 'url' && !$payload['url']) { $errors[] = 'Valid URL required'; }
    if ($type === 'appstore') {
        foreach (['ios_url','android_url'] as $k) {
            if (!empty($_POST[$k]) && !$payload[$k]) { $errors[] = strtoupper($k) . ' must be http(s) URL'; }
        }
    }

    if (!$errors) {
        $id = uuidv4();
        try {
            error_log('[QR CREATE] user=' . $userId . ' id=' . $id . ' type=' . $type . ' title=' . $title);
            error_log('[QR CREATE] payload=' . json_encode($payload));
            $result = $db->execute(
                "INSERT INTO custom_qr_codes (id,user_id,type,payload_json,title,theme_key,cover_image_url,landing_title,landing_html,show_lead_form,status) VALUES (?,?,?,?,?,?,?,?,?,?, 'active')",
                [
                    $id,
                    $userId,
                    $type,
                    json_encode($payload, JSON_UNESCAPED_SLASHES),
                    $title,
                    $theme,
                    $cover,
                    $landingTitle,
                    $landingHtml,
                    $showLead
                ]
            );
            
            if ($result > 0) {
                // QR code was successfully created
                header('Location: /user/qr/?created=' . urlencode($id));
                exit;
            } else {
                $errors[] = 'Failed to create QR code. Please try again.';
            }
        } catch (Exception $e) {
            error_log("QR creation error: " . $e->getMessage());
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

$themes = getThemes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Custom QR Code</title>
    <link rel="stylesheet" href="/user/includes/user-style.css">
    <style>
        .container { max-width: 900px; margin: 30px auto; background: #fff; border-radius: 12px; box-shadow: 0 10px 24px rgba(0,0,0,.08); padding: 24px; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .actions { margin-top: 16px; }
        label { font-weight: 600; display:block; margin: 10px 0 6px; }
        input[type=text], textarea, select { width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px; }
        textarea { min-height: 100px; }
        .btn { display: inline-block; padding: 10px 14px; border-radius: 8px; text-decoration: none; }
        .btn-primary { background: #667eea; color: #fff; }
        .muted { color:#666; font-size: 12px; }
        .error { background:#ffe8e8; color:#a40000; padding:10px; border-radius:8px; margin-bottom:10px; }
        .hidden { display:none; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navigation.php'; ?>
    <div class="container">
        <h1>Create Custom QR Code</h1>

        <?php foreach ($errors as $e): ?>
            <div class="error"><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endforeach; ?>

        <form method="post">
            <label>Title
                <input type="text" name="title" placeholder="My QR" value="<?php echo htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </label>

            <label>Type
                <select name="type" id="type">
                    <option value="default" <?php echo (($_POST['type'] ?? '')==='default')?'selected':''; ?>>Default landing</option>
                    <option value="url" <?php echo (($_POST['type'] ?? '')==='url')?'selected':''; ?>>Custom URL</option>
                    <option value="social" <?php echo (($_POST['type'] ?? '')==='social')?'selected':''; ?>>Social profile</option>
                    <option value="text" <?php echo (($_POST['type'] ?? '')==='text')?'selected':''; ?>>Custom text</option>
                    <option value="wifi" <?php echo (($_POST['type'] ?? '')==='wifi')?'selected':''; ?>>Wi‑Fi</option>
                    <option value="appstore" <?php echo (($_POST['type'] ?? '')==='appstore')?'selected':''; ?>>App Store links</option>
                </select>
            </label>

            <div id="fields-url" class="type-fields hidden">
                <label>Destination URL
                    <input type="text" name="url" placeholder="https://example.com" value="<?php echo htmlspecialchars($_POST['url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </label>
            </div>

            <div id="fields-social" class="type-fields hidden row">
                <div>
                    <label>Platform
                        <select name="platform">
                            <option value="linkedin">LinkedIn</option>
                            <option value="facebook">Facebook</option>
                            <option value="instagram">Instagram</option>
                            <option value="twitter">Twitter/X</option>
                            <option value="tiktok">TikTok</option>
                            <option value="youtube">YouTube</option>
                        </select>
                    </label>
                </div>
                <div>
                    <label>Username (without @)
                        <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                </div>
            </div>

            <div id="fields-text" class="type-fields hidden">
                <label>Text to display
                    <textarea name="text"><?php echo htmlspecialchars($_POST['text'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </label>
            </div>

            <div id="fields-wifi" class="type-fields hidden row">
                <div>
                    <label>SSID
                        <input type="text" name="ssid" value="<?php echo htmlspecialchars($_POST['ssid'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                </div>
                <div>
                    <label>Security
                        <select name="auth">
                            <option value="WPA">WPA/WPA2</option>
                            <option value="WEP">WEP</option>
                            <option value="nopass">Open (no password)</option>
                        </select>
                    </label>
                </div>
                <div>
                    <label>Password
                        <input type="text" name="password" value="<?php echo htmlspecialchars($_POST['password'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                </div>
            </div>

            <div id="fields-appstore" class="type-fields hidden row">
                <div>
                    <label>iOS App Store URL
                        <input type="text" name="ios_url" placeholder="https://apps.apple.com/..." value="<?php echo htmlspecialchars($_POST['ios_url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                </div>
                <div>
                    <label>Google Play URL
                        <input type="text" name="android_url" placeholder="https://play.google.com/store/apps/details?id=..." value="<?php echo htmlspecialchars($_POST['android_url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                </div>
            </div>

            <hr style="margin:18px 0; border:none; border-top:1px solid #eee;">
            <div id="landing-section">
            <h3>Landing Customization</h3>
            <div class="row">
                <div>
                    <label>Theme
                        <select name="theme_key">
                            <option value="">(Default)</option>
                            <?php foreach ($themes as $key => $t): ?>
                                <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <div>
                    <label>Cover image URL
                        <input type="text" name="cover_image_url" placeholder="https://..." value="<?php echo htmlspecialchars($_POST['cover_image_url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="muted">You can upload in Media later. For now, paste an image URL.</div>
                    </label>
                </div>
            </div>
            <div class="row">
                <div>
                    <label>Landing title
                        <input type="text" name="landing_title" value="<?php echo htmlspecialchars($_POST['landing_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </label>
                </div>
                <div>
                    <label>Show lead form
                        <input type="checkbox" name="show_lead_form" <?php echo (isset($_POST['show_lead_form']) ? 'checked' : 'checked'); ?>>
                    </label>
                </div>
            </div>
            <label>Landing HTML (optional)
                <textarea name="landing_html" placeholder="<p>Welcome to my link hub</p>"><?php echo htmlspecialchars($_POST['landing_html'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </label>
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit">Create</button>
                <a class="btn" href="/user/qr/">Cancel</a>
            </div>
        </form>
    </div>
    <script>
    const typeSelect = document.getElementById('type');
    const sections = document.querySelectorAll('.type-fields');
    function updateTypeFields(){
        sections.forEach(s=>s.classList.add('hidden'));
        const id = 'fields-' + typeSelect.value;
        const el = document.getElementById(id);
        if (el) el.classList.remove('hidden');
        // Hide landing customization for redirect-only types
        const landing = document.getElementById('landing-section');
        const noLandingTypes = ['url','social','appstore'];
        if (landing) {
            landing.style.display = noLandingTypes.includes(typeSelect.value) ? 'none' : '';
        }
    }
    typeSelect.addEventListener('change', updateTypeFields);
    updateTypeFields();
    </script>
</body>
</html>


