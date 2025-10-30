<?php
/**
 * Public Custom QR handler (v1)
 * - Loads QR by id
 * - Records a simple view event
 * - Switches behavior by type
 * - Renders minimal landing for default/text/wifi
 */

// Early debug bootstrap
$__qr_debug = isset($_GET['debug']) && $_GET['debug'] == '1';
if ($__qr_debug) {
    @ini_set('display_errors', '1');
    @ini_set('log_errors', '1');
    @error_reporting(E_ALL);
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    register_shutdown_function(function() {
        $e = error_get_last();
        if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            echo '<pre>FATAL: ' . htmlspecialchars(print_r($e, true)) . '</pre>';
        }
    });
    echo '<pre>DEBUG MODE ENABLED</pre>';
}

require_once __DIR__ . '/config/secure-config.php';
require_once __DIR__ . '/api/includes/Sanitize.php';
require_once __DIR__ . '/api/includes/RateLimiter.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4');
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    return $pdo;
}

function parseDeviceType(string $ua): string {
    $uaL = strtolower($ua);
    if (strpos($uaL, 'ipad') !== false || strpos($uaL, 'tablet') !== false) return 'Tablet';
    if (strpos($uaL, 'iphone') !== false || strpos($uaL, 'android') !== false || strpos($uaL, 'mobile') !== false) return 'Mobile';
    if (strpos($uaL, 'windows') !== false || strpos($uaL, 'macintosh') !== false || strpos($uaL, 'linux') !== false) return 'Desktop';
    return 'Unknown';
}

function parseBrowser(string $ua): string {
    $uaL = strtolower($ua);
    if (strpos($uaL, 'edg/') !== false || strpos($uaL, 'edge') !== false) return 'Edge';
    if (strpos($uaL, 'opr/') !== false || strpos($uaL, 'opera') !== false) return 'Opera';
    if (strpos($uaL, 'chrome') !== false && strpos($uaL, 'safari') !== false && strpos($uaL, 'edg') === false) return 'Chrome';
    if (strpos($uaL, 'firefox') !== false) return 'Firefox';
    if (strpos($uaL, 'safari') !== false && strpos($uaL, 'chrome') === false) return 'Safari';
    return 'Other';
}

function parseOS(string $ua): string {
    $uaL = strtolower($ua);
    if (strpos($uaL, 'iphone') !== false || strpos($uaL, 'ipad') !== false || strpos($uaL, 'ios') !== false) return 'iOS';
    if (strpos($uaL, 'android') !== false) return 'Android';
    if (strpos($uaL, 'windows') !== false) return 'Windows';
    if (strpos($uaL, 'macintosh') !== false || strpos($uaL, 'mac os') !== false) return 'macOS';
    if (strpos($uaL, 'linux') !== false) return 'Linux';
    return 'Other';
}

function geoLookup(string $ip): array {
    // File cache by IP (24h)
    $cacheDir = __DIR__ . '/storage/geoip';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
    }
    $cacheFile = $cacheDir . '/' . preg_replace('/[^0-9a-f:\\.]/i', '_', $ip) . '.json';
    if (is_file($cacheFile) && (time() - @filemtime($cacheFile) < 86400)) {
        $data = json_decode(@file_get_contents($cacheFile), true);
        return is_array($data) ? $data : [];
    }
    // Basic external lookup with timeout. Fails silently.
    $result = [];
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $resp = @file_get_contents('http://ip-api.com/json/' . urlencode($ip) . '?fields=status,country,city', false, $ctx);
    if ($resp) {
        $j = json_decode($resp, true);
        if (is_array($j) && ($j['status'] ?? '') === 'success') {
            $result = [
                'country' => $j['country'] ?? null,
                'city' => $j['city'] ?? null,
            ];
        }
    }
    @file_put_contents($cacheFile, json_encode($result));
    return $result;
}

function trackEvent(string $qrId, string $event, ?string $target = null): void {
    try {
        $stmt = db()->prepare('INSERT INTO custom_qr_events (qr_id, event, event_target, session_id, ip_address, user_agent, referrer, device_type, browser, os, city, country) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        $sessionId = $_COOKIE['qr_session'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $ref = $_SERVER['HTTP_REFERER'] ?? null;
        $device = $ua ? parseDeviceType($ua) : null;
        $browser = $ua ? parseBrowser($ua) : null;
        $os = $ua ? parseOS($ua) : null;
        $geo = $ip ? geoLookup($ip) : [];
        $city = $geo['city'] ?? null;
        $country = $geo['country'] ?? null;
        $stmt->execute([$qrId, $event, $target, $sessionId, $ip, $ua, $ref, $device, $browser, $os, $city, $country]);
    } catch (Throwable $e) {
        // fail-safe: do not break user flow on analytics errors
    }
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));
$qrId = $segments[1] ?? null; // /qr/{id}

if (!$qrId) {
    http_response_code(400);
    echo 'Invalid QR code URL';
    exit;
}

// Debug helper
$debug = $__qr_debug;
if ($debug) {
    error_log("[QR DEBUG] Request: path={$path} id={$qrId} ip=" . ($_SERVER['REMOTE_ADDR'] ?? ''));
}

// Rate limiting: 100 views per minute per IP
$rateLimiter = new RateLimiter();
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!$rateLimiter->isAllowed($clientIp, 100, 60)) {
    http_response_code(429);
    echo 'Too many requests. Please try again later.';
    exit;
}

// Load QR record (allow inactive to show friendly message)
$stAll = db()->prepare('SELECT * FROM custom_qr_codes WHERE id = ?');
$stAll->execute([$qrId]);
$qr = $stAll->fetch();

if (!$qr) {
    http_response_code(404);
    echo 'QR code not found';
    exit;
}

// If QR exists but is inactive, show a branded inactive page
if (($qr['status'] ?? 'active') !== 'active') {
    require __DIR__ . '/public/includes/qr/inactive.php';
    exit;
}

if ($debug) {
    error_log('[QR DEBUG] Loaded QR: ' . json_encode(['id'=>$qr['id'],'status'=>$qr['status'],'type'=>$qr['type']]));
}

trackEvent($qrId, 'view');

$type = $qr['type'];
$payload = json_decode($qr['payload_json'] ?? 'null', true) ?: [];

if ($debug) {
    echo '<pre>Type=' . htmlspecialchars($type) . "\nPayload=" . htmlspecialchars(json_encode($payload, JSON_PRETTY_PRINT)) . '</pre>';
}

// Redirect helpers
function isValidHttpUrl(string $url): bool {
    if (!preg_match('/^https?:\/\//i', $url)) return false;
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

if ($type === 'url') {
    $url = Sanitize::url($payload['url'] ?? '');
    if ($url) {
        if ($debug) { echo '<pre>Redirect → ' . htmlspecialchars($url) . '</pre>'; }
        trackEvent($qrId, 'redirect', $url);
        header('Location: ' . $url, true, 302);
        exit;
    }
}

if ($type === 'social') {
    $platform = strtolower($payload['platform'] ?? '');
    $username = Sanitize::username($payload['username'] ?? '');
    $map = [
        'twitter' => 'https://x.com/%s',
        'x' => 'https://x.com/%s',
        'instagram' => 'https://instagram.com/%s',
        'facebook' => 'https://facebook.com/%s',
        'linkedin' => 'https://www.linkedin.com/in/%s',
        'tiktok' => 'https://tiktok.com/@%s',
        'youtube' => 'https://youtube.com/@%s',
    ];
    if (isset($map[$platform]) && $username !== '') {
        $url = sprintf($map[$platform], rawurlencode($username));
        if ($debug) { echo '<pre>Redirect → ' . htmlspecialchars($url) . '</pre>'; }
        trackEvent($qrId, 'redirect', $url);
        header('Location: ' . $url, true, 302);
        exit;
    }
}

if ($type === 'appstore') {
    $ios = Sanitize::url($payload['ios_url'] ?? '');
    $android = Sanitize::url($payload['android_url'] ?? '');
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    $target = null;
    if (strpos($ua, 'iphone') !== false || strpos($ua, 'ipad') !== false) {
        $target = $ios;
    } elseif (strpos($ua, 'android') !== false) {
        $target = $android;
    }
    if ($target) {
        trackEvent($qrId, 'redirect', $target);
        header('Location: ' . $target, true, 302);
        exit;
    }
    // Show interstitial if no valid redirect
    require __DIR__ . '/public/includes/qr/appstore-interstitial.php';
    exit;
}

if ($type === 'text') {
    require __DIR__ . '/public/includes/qr/text-landing.php';
    exit;
}

if ($type === 'wifi') {
    require __DIR__ . '/public/includes/qr/wifi-landing.php';
    exit;
}

// Render via shared include
require __DIR__ . '/public/includes/qr/landing.php';


