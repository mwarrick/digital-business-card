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
    // Turn off output buffering for debug
    if (ob_get_level()) {
        ob_end_flush();
    }
}

require_once __DIR__ . '/../config/secure-config.php';
require_once __DIR__ . '/../api/includes/Sanitize.php';
require_once __DIR__ . '/../api/includes/RateLimiter.php';
require_once __DIR__ . '/../api/includes/qr/Generator.php';

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

// Debug helper - make sure it's set
$debug = $__qr_debug || (isset($_GET['debug']) && $_GET['debug'] == '1');
if ($debug) {
    error_log("[QR DEBUG] Request: path={$path} id={$qrId} ip=" . ($_SERVER['REMOTE_ADDR'] ?? ''));
    echo '<pre>=== DEBUG: QR ID EXTRACTED ===' . "\n";
    echo 'QR ID: ' . htmlspecialchars($qrId, ENT_QUOTES, 'UTF-8') . "\n";
    echo '$debug variable: ' . ($debug ? 'TRUE' : 'FALSE') . "\n";
    echo '</pre>';
    flush();
}

// Rate limiting disabled to prevent issues for legitimate iOS users
// $rateLimiter = new RateLimiter();
// $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
// if (!$rateLimiter->isAllowed($clientIp, 100, 60)) {
//     http_response_code(429);
//     echo 'Too many requests. Please try again later.';
//     exit;
// }

// Load QR record (allow inactive to show friendly message)
// Explicitly select all columns including expires_at to ensure it's included
$stAll = db()->prepare('SELECT id, user_id, type, payload_json, title, slug, theme_key, cover_image_url, landing_title, landing_html, show_lead_form, status, created_at, updated_at, expires_at, expiration_notice FROM custom_qr_codes WHERE id = ?');
$stAll->execute([$qrId]);
$qr = $stAll->fetch();

// CRITICAL DEBUG: Force output to verify file is being executed
if (isset($_GET['verify_file'])) {
    die("FILE IS EXECUTING! QR ID: {$qrId}\nexpires_at: " . var_export($qr['expires_at'] ?? 'NOT IN ARRAY', true) . "\nAll keys: " . implode(', ', array_keys($qr ?? [])) . "\n\nTesting expiration:\n" . 
        "expires_at value: " . var_export($qr['expires_at'] ?? null, true) . "\n" .
        "empty check: " . (empty($qr['expires_at']) ? 'YES' : 'NO') . "\n" .
        "null check: " . (($qr['expires_at'] ?? null) === null ? 'YES' : 'NO') . "\n" .
        "trim check: " . (trim($qr['expires_at'] ?? '') === '' ? 'YES' : 'NO'));
}

// FORCE OUTPUT: Dump the entire $qr array to see what we got
error_log('QR FETCH RESULT: ' . json_encode($qr));
if ($__qr_debug) {
    echo '<pre style="background:red;color:white;padding:20px;border:5px solid yellow;font-size:16px;font-weight:bold;position:fixed;top:0;left:0;z-index:99999;">';
    echo "=== DATABASE QUERY RESULT ===\n";
    echo "QR fetched: " . ($qr ? 'YES' : 'NO') . "\n";
    if ($qr) {
        echo "All keys in \$qr: " . implode(', ', array_keys($qr)) . "\n";
        echo "FULL \$qr ARRAY:\n";
        print_r($qr);
        echo "\nexpires_at value: " . var_export($qr['expires_at'] ?? 'KEY NOT FOUND', true) . "\n";
        echo "expires_at isset: " . (isset($qr['expires_at']) ? 'YES' : 'NO') . "\n";
        echo "expires_at empty: " . (empty($qr['expires_at']) ? 'YES' : 'NO') . "\n";
        echo "expires_at === null: " . (($qr['expires_at'] ?? null) === null ? 'YES' : 'NO') . "\n";
        echo "expires_at === '': " . (($qr['expires_at'] ?? '') === '' ? 'YES' : 'NO') . "\n";
    }
    echo '</pre>';
    flush();
}

if (!$qr) {
    http_response_code(404);
    echo 'QR code not found';
    exit;
}

// If QR exists but is inactive, show a branded inactive page
if (($qr['status'] ?? 'active') !== 'active') {
    require __DIR__ . '/includes/qr/inactive.php';
    exit;
}

if ($__qr_debug) {
    error_log('[QR DEBUG] Loaded QR: ' . json_encode(['id'=>$qr['id'],'status'=>$qr['status'],'type'=>$qr['type'],'expires_at'=>$qr['expires_at'] ?? 'NULL']));
    error_log('[QR DEBUG] expires_at value: ' . var_export($qr['expires_at'] ?? null, true));
    error_log('[QR DEBUG] All QR keys: ' . implode(', ', array_keys($qr)));
    echo '<pre>=== QR CODE DEBUG ===' . "\n";
    echo 'QR ID: ' . htmlspecialchars($qr['id'] ?? 'NULL', ENT_QUOTES, 'UTF-8') . "\n";
    echo 'Status: ' . htmlspecialchars($qr['status'] ?? 'NULL', ENT_QUOTES, 'UTF-8') . "\n";
    echo 'Type: ' . htmlspecialchars($qr['type'] ?? 'NULL', ENT_QUOTES, 'UTF-8') . "\n";
    echo 'expires_at (raw): ' . var_export($qr['expires_at'] ?? null, true) . "\n";
    echo 'expires_at (isset): ' . (isset($qr['expires_at']) ? 'YES' : 'NO') . "\n";
    echo 'expires_at (empty): ' . (empty($qr['expires_at']) ? 'YES' : 'NO') . "\n";
    echo 'expiration_notice: ' . htmlspecialchars($qr['expiration_notice'] ?? 'NULL', ENT_QUOTES, 'UTF-8') . "\n";
    echo 'show_lead_form: ' . ($qr['show_lead_form'] ?? 'NULL') . "\n";
    echo 'All QR keys: ' . implode(', ', array_keys($qr)) . "\n";
    echo '</pre>';
    flush();
}

// EXPIRATION CHECK - Must happen BEFORE trackEvent and type/payload processing
// Check expiration using the $qr array we already have
$isExpired = false;
$expiresAtValue = $qr['expires_at'] ?? null;

// TEMPORARY DEBUG: Check if expires_at is actually in the array
if (isset($_GET['check_db'])) {
    die("DEBUG: expires_at in array: " . var_export($expiresAtValue, true) . "\nAll QR keys: " . implode(', ', array_keys($qr)));
}

// FORCE EXPIRATION CHECK - Run immediately, before any other checks
if (!empty($expiresAtValue) && $expiresAtValue !== null && trim($expiresAtValue) !== '') {
    try {
        $est = new DateTimeZone('America/New_York');
        $now = new DateTime('now', $est);
        $expiresAt = new DateTime($expiresAtValue, $est);
        if ($now >= $expiresAt) {
            $isExpired = true;
            error_log("QR FORCE CHECK: QR ID={$qrId} IS EXPIRED! Now={$now->format('Y-m-d H:i:s')}, Expires={$expiresAt->format('Y-m-d H:i:s')}");
        }
    } catch (Exception $e) {
        error_log("QR Force expiration check error: " . $e->getMessage());
    }
}

// DEBUG: Show expiration check details
if ($__qr_debug || $debug) {
    echo '<pre style="background:#ff6b6b;color:#fff;padding:20px;margin:20px;border:3px solid #c92a2a;font-size:14px;font-weight:bold;z-index:99999;">';
    echo "=== EXPIRATION CHECK DEBUG ===\n";
    echo "QR ID: " . htmlspecialchars($qrId, ENT_QUOTES, 'UTF-8') . "\n";
    echo "expires_at (raw from DB): " . var_export($expiresAtValue, true) . "\n";
    echo "expires_at (isset): " . (isset($qr['expires_at']) ? 'YES' : 'NO') . "\n";
    echo "expires_at (empty check): " . (empty($expiresAtValue) ? 'YES (EMPTY)' : 'NO (NOT EMPTY)') . "\n";
    echo "expires_at (trim check): " . (trim($expiresAtValue ?? '') === '' ? 'YES (EMPTY AFTER TRIM)' : 'NO (HAS VALUE)') . "\n";
    echo "expires_at type: " . gettype($expiresAtValue) . "\n";
}

// Check if expiration is set and not empty - use same logic as list view
// List view checks: if (empty($qr['expires_at']) || $qr['expires_at'] === null)
// So we check the inverse: if NOT empty AND NOT null
if (!empty($expiresAtValue) && $expiresAtValue !== null && trim($expiresAtValue) !== '') {
    if ($__qr_debug || $debug) {
        echo "✓ expires_at is not empty, proceeding with date check...\n";
    }
    try {
        $est = new DateTimeZone('America/New_York');
        $now = new DateTime('now', $est);
        // Use same approach as list view - new DateTime() is more flexible than createFromFormat
        $expiresAt = new DateTime($expiresAtValue, $est);
        
        if ($__qr_debug || $debug) {
            echo "Current EST time: " . $now->format('Y-m-d H:i:s T') . "\n";
            echo "expires_at (raw): " . htmlspecialchars($expiresAtValue, ENT_QUOTES, 'UTF-8') . "\n";
            echo "✓ Successfully parsed expires_at: " . $expiresAt->format('Y-m-d H:i:s T') . "\n";
            echo "Comparison: now >= expires_at? " . ($now >= $expiresAt ? 'YES (EXPIRED!)' : 'NO (NOT EXPIRED)') . "\n";
        }
        
        if ($now >= $expiresAt) {
            $isExpired = true;
            error_log("QR EXPIRATION: QR ID={$qrId} IS EXPIRED! Now={$now->format('Y-m-d H:i:s')}, Expires={$expiresAt->format('Y-m-d H:i:s')}");
            if ($__qr_debug || $debug) {
                echo "🚨 RESULT: QR CODE IS EXPIRED!\n";
            }
        } else {
            error_log("QR EXPIRATION: QR ID={$qrId} NOT EXPIRED. Now={$now->format('Y-m-d H:i:s')}, Expires={$expiresAt->format('Y-m-d H:i:s')}");
            if ($__qr_debug || $debug) {
                echo "✓ RESULT: QR CODE IS NOT EXPIRED (future date)\n";
            }
        }
    } catch (Exception $e) {
        // If date parsing fails, log error but don't treat as expired
        error_log("QR Expiration check error: " . $e->getMessage());
        if ($__qr_debug || $debug) {
            echo "❌ EXCEPTION: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "\n";
        }
    }
} else {
    if ($__qr_debug || $debug) {
        echo "⚠ expires_at is empty/null - skipping expiration check\n";
        echo "RESULT: QR CODE HAS NO EXPIRATION (treating as NOT expired)\n";
    }
}

if ($__qr_debug || $debug) {
    echo "\n=== FINAL EXPIRATION STATUS ===\n";
    echo "\$isExpired: " . ($isExpired ? 'TRUE (WILL SHOW EXPIRED PAGE)' : 'FALSE (WILL SHOW LANDING PAGE)') . "\n";
    echo "expiration_notice: " . htmlspecialchars($qr['expiration_notice'] ?? 'NULL (will use default)', ENT_QUOTES, 'UTF-8') . "\n";
    echo "show_lead_form: " . (($qr['show_lead_form'] ?? 1) == 1 ? 'TRUE' : 'FALSE') . "\n";
    echo "==============================\n";
    echo '</pre>';
    flush();
}

// If expired, show expired template and STOP immediately
// CRITICAL: This check MUST happen before any other processing
if ($isExpired === true) {
    // ALWAYS log this for debugging
    error_log("QR EXPIRED CHECK: QR ID={$qrId}, isExpired={$isExpired}, expires_at=" . var_export($expiresAtValue, true));
    
    // TEMPORARY: Force output to verify this code is running
    if (isset($_GET['test_expired'])) {
        die("EXPIRED CHECK IS RUNNING! isExpired=" . var_export($isExpired, true) . ", expires_at=" . var_export($expiresAtValue, true));
    }
    
    // Clear any output buffering
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Send headers first
    if (!headers_sent()) {
        http_response_code(410); // Gone
        header('Content-Type: text/html; charset=UTF-8');
    }
    
    $expirationNotice = !empty($qr['expiration_notice']) 
        ? $qr['expiration_notice'] 
        : 'Sorry, this QR code has expired.';
    $showLeadForm = ($qr['show_lead_form'] ?? 1) == 1;
    
    // Always record view event (even if expired) - before showing expired page
    trackEvent($qrId, 'view');
    
    // Show expired template and exit
    $expiredTemplatePath = __DIR__ . '/includes/qr/expired.php';
    error_log("QR EXPIRED: Template path={$expiredTemplatePath}, exists=" . (file_exists($expiredTemplatePath) ? 'YES' : 'NO'));
    
    if (file_exists($expiredTemplatePath)) {
        require $expiredTemplatePath;
        exit(0);
    } else {
        // Fallback if template doesn't exist
        echo '<!DOCTYPE html><html><head><title>QR Code Expired</title></head><body>';
        echo '<h1>QR Code Expired</h1>';
        echo '<p>' . htmlspecialchars($expirationNotice, ENT_QUOTES, 'UTF-8') . '</p>';
        if ($showLeadForm) {
            echo '<p><a href="/public/capture-lead.php?qr_id=' . urlencode($qrId) . '">Contact Us</a></p>';
        }
        echo '</body></html>';
        exit(0);
    }
}

// ALWAYS log if we're continuing past expiration check
error_log("QR NOT EXPIRED: QR ID={$qrId}, isExpired={$isExpired}, expires_at=" . var_export($expiresAtValue, true));

// Always record view event (for non-expired QR codes)
trackEvent($qrId, 'view');

$type = $qr['type'];
$payload = json_decode($qr['payload_json'] ?? 'null', true) ?: [];

if ($debug) {
    echo '<pre>Type=' . htmlspecialchars($type) . "\nPayload=" . htmlspecialchars(json_encode($payload, JSON_PRETTY_PRINT)) . '</pre>';
    flush();
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
    require __DIR__ . '/includes/qr/appstore-interstitial.php';
    exit;
}

if ($type === 'text') {
    require __DIR__ . '/includes/qr/text-landing.php';
    exit;
}

if ($type === 'wifi') {
    require __DIR__ . '/includes/qr/wifi-landing.php';
    exit;
}

// Render via shared include
if ($__qr_debug || $debug) {
    echo '<pre style="background:#4ecdc4;color:#000;padding:20px;margin:20px;border:3px solid #26a69a;font-size:14px;font-weight:bold;z-index:99999;">';
    echo "=== ABOUT TO INCLUDE LANDING.PHP ===\n";
    echo "⚠ WARNING: This means expiration check returned FALSE\n";
    echo "\$isExpired: " . (isset($isExpired) ? ($isExpired ? 'TRUE' : 'FALSE') : 'NOT SET') . "\n";
    echo "expires_at (raw): " . var_export($qr['expires_at'] ?? 'NOT SET', true) . "\n";
    echo "expires_at (isset): " . (isset($qr['expires_at']) ? 'YES' : 'NO') . "\n";
    echo "expires_at (empty): " . (empty($qr['expires_at']) ? 'YES' : 'NO') . "\n";
    echo "QR Type: " . htmlspecialchars($qr['type'] ?? 'NOT SET', ENT_QUOTES, 'UTF-8') . "\n";
    echo "QR Status: " . htmlspecialchars($qr['status'] ?? 'NOT SET', ENT_QUOTES, 'UTF-8') . "\n";
    echo "If this QR should be expired, check the expiration logic above!\n";
    echo "=====================================\n";
    echo '</pre>';
    flush();
}
require __DIR__ . '/includes/qr/landing.php';


