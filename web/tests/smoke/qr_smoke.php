<?php
/**
 * QR smoke tests.
 * Usage: BASE_URL=https://sharemycard.app php web/tests/smoke/qr_smoke.php
 */

$baseUrl = rtrim(getenv('BASE_URL') ?: '', '/');
if ($baseUrl === '') {
    fwrite(STDERR, "Set BASE_URL env var, e.g. BASE_URL=https://sharemycard.app\n");
    exit(2);
}

function httpRequest(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'QR Smoke Test',
    ]);
    $resp = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $location = null;
    if (preg_match('/^Location:\s*(.*?)\r?$/im', (string)$resp, $m)) {
        $location = trim($m[1]);
    }
    curl_close($ch);
    return [$status, $location];
}

function pass(string $name, string $detail = ''): void {
    echo "✔ {$name}" . ($detail ? " — {$detail}" : '') . "\n";
}

function fail(string $name, string $detail): void {
    echo "✘ {$name} — {$detail}\n";
}

$exitCode = 0;

// Cases to probe (ids must exist in DB). Provide via env for flexibility.
$ids = [
    'url' => getenv('QR_ID_URL') ?: '',
    'social' => getenv('QR_ID_SOCIAL') ?: '',
    'appstore' => getenv('QR_ID_APPSTORE') ?: '',
    'default' => getenv('QR_ID_DEFAULT') ?: '',
];

// 404 case — use random UUID-like string
$invalidId = '00000000-0000-0000-0000-000000000000';

// 404
[$status] = httpRequest("{$baseUrl}/qr/{$invalidId}");
if ($status === 404) pass('Invalid QR returns 404'); else { fail('Invalid QR returns 404', "got {$status}"); $exitCode = 1; }

// URL redirect
if ($ids['url'] !== '') {
    [$status, $loc] = httpRequest("{$baseUrl}/qr/{$ids['url']}");
    if ($status === 302 && $loc && preg_match('/^https?:\/\//i', $loc)) pass('URL QR redirects to http(s)', $loc);
    else { fail('URL QR redirects to http(s)', "status={$status} location={$loc}"); $exitCode = 1; }
} else {
    echo "(skip) URL QR — set QR_ID_URL to test\n";
}

// Social redirect
if ($ids['social'] !== '') {
    [$status, $loc] = httpRequest("{$baseUrl}/qr/{$ids['social']}");
    if ($status === 302 && $loc && preg_match('/^https?:\/\//i', $loc)) pass('Social QR redirects to platform', $loc);
    else { fail('Social QR redirects to platform', "status={$status} location={$loc}"); $exitCode = 1; }
} else {
    echo "(skip) Social QR — set QR_ID_SOCIAL to test\n";
}

// App store — either redirects or shows interstitial (200)
if ($ids['appstore'] !== '') {
    [$status, $loc] = httpRequest("{$baseUrl}/qr/{$ids['appstore']}");
    if ($status === 302 || $status === 200) pass('AppStore QR returns 302 or 200', "status={$status}");
    else { fail('AppStore QR returns 302 or 200', "status={$status}"); $exitCode = 1; }
} else {
    echo "(skip) AppStore QR — set QR_ID_APPSTORE to test\n";
}

// Default landing returns 200
if ($ids['default'] !== '') {
    [$status] = httpRequest("{$baseUrl}/qr/{$ids['default']}");
    if ($status === 200) pass('Default landing returns 200'); else { fail('Default landing returns 200', "got {$status}"); $exitCode = 1; }
} else {
    echo "(skip) Default QR — set QR_ID_DEFAULT to test\n";
}

exit($exitCode);


