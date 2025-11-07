<?php
require_once __DIR__ . '/../config/secure-config.php';

$qrId = '84b80cc0-671e-40e1-9be4-3183177cbc7d';

$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$stmt = $pdo->prepare('SELECT id, expires_at, expiration_notice, show_lead_form FROM custom_qr_codes WHERE id = ?');
$stmt->execute([$qrId]);
$qr = $stmt->fetch();

echo '<pre>';
echo "=== QR CODE EXPIRATION TEST ===\n\n";
echo "QR ID: {$qrId}\n\n";
echo "Database row:\n";
print_r($qr);
echo "\n\n";

if (empty($qr)) {
    echo "ERROR: QR code not found!\n";
    exit;
}

echo "expires_at value: " . var_export($qr['expires_at'], true) . "\n";
echo "expires_at type: " . gettype($qr['expires_at']) . "\n";
echo "expires_at === null: " . ($qr['expires_at'] === null ? 'YES' : 'NO') . "\n";
echo "expires_at === '': " . ($qr['expires_at'] === '' ? 'YES' : 'NO') . "\n";
echo "empty(expires_at): " . (empty($qr['expires_at']) ? 'YES' : 'NO') . "\n";
echo "isset(expires_at): " . (isset($qr['expires_at']) ? 'YES' : 'NO') . "\n";
echo "\n";

if (!empty($qr['expires_at'])) {
    $est = new DateTimeZone('America/New_York');
    $now = new DateTime('now', $est);
    $expiresAt = DateTime::createFromFormat('Y-m-d H:i:s', trim($qr['expires_at']), $est);
    if (!$expiresAt) {
        $expiresAt = DateTime::createFromFormat('Y-m-d H:i', trim($qr['expires_at']), $est);
    }
    
    echo "Now (EST): " . $now->format('Y-m-d H:i:s T') . "\n";
    if ($expiresAt) {
        echo "Expires (EST): " . $expiresAt->format('Y-m-d H:i:s T') . "\n";
        echo "Is Expired: " . ($now >= $expiresAt ? 'YES' : 'NO') . "\n";
    } else {
        echo "ERROR: Could not parse expires_at value\n";
    }
} else {
    echo "No expiration date set (expires_at is empty/null)\n";
}
echo '</pre>';

