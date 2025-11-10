<?php
/**
 * Test QR Expiration - Check what's in database
 */
require_once __DIR__ . '/../config/secure-config.php';

$qrId = '84b80cc0-671e-40e1-9be4-3183177cbc7d';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $stmt = $pdo->prepare('SELECT id, title, expires_at, expiration_notice, status FROM custom_qr_codes WHERE id = ?');
    $stmt->execute([$qrId]);
    $qr = $stmt->fetch();
    
    if (!$qr) {
        echo "QR code not found\n";
        exit;
    }
    
    echo "<pre>";
    echo "=== QR CODE DATA ===\n";
    echo "ID: " . $qr['id'] . "\n";
    echo "Title: " . $qr['title'] . "\n";
    echo "Status: " . $qr['status'] . "\n";
    echo "expires_at (raw): " . var_export($qr['expires_at'], true) . "\n";
    echo "expires_at type: " . gettype($qr['expires_at']) . "\n";
    echo "expires_at === null: " . ($qr['expires_at'] === null ? 'YES' : 'NO') . "\n";
    echo "expires_at === '': " . ($qr['expires_at'] === '' ? 'YES' : 'NO') . "\n";
    echo "empty(expires_at): " . (empty($qr['expires_at']) ? 'YES' : 'NO') . "\n";
    echo "isset(expires_at): " . (isset($qr['expires_at']) ? 'YES' : 'NO') . "\n";
    
    echo "\n=== EXPIRATION CHECK (using same logic as list view) ===\n";
    if (empty($qr['expires_at']) || $qr['expires_at'] === null) {
        echo "No expiration set\n";
    } else {
        $est = new DateTimeZone('America/New_York');
        $now = new DateTime('now', $est);
        $expiresAt = new DateTime($qr['expires_at'], $est);
        
        echo "Now (EST): " . $now->format('Y-m-d H:i:s T') . "\n";
        echo "Expires (EST): " . $expiresAt->format('Y-m-d H:i:s T') . "\n";
        echo "Is Expired: " . ($now >= $expiresAt ? 'YES' : 'NO') . "\n";
    }
    
    echo "\n=== TESTING PUBLIC HANDLER LOGIC ===\n";
    $expiresAtValue = $qr['expires_at'] ?? null;
    $isExpired = false;
    
    if (!empty($expiresAtValue) && $expiresAtValue !== null && trim($expiresAtValue) !== '') {
        echo "✓ expires_at is not empty, checking...\n";
        try {
            $est = new DateTimeZone('America/New_York');
            $now = new DateTime('now', $est);
            $expiresAt = new DateTime($expiresAtValue, $est);
            
            echo "Now: " . $now->format('Y-m-d H:i:s T') . "\n";
            echo "Expires: " . $expiresAt->format('Y-m-d H:i:s T') . "\n";
            
            if ($now >= $expiresAt) {
                $isExpired = true;
                echo "🚨 RESULT: EXPIRED!\n";
            } else {
                echo "✓ RESULT: NOT EXPIRED\n";
            }
        } catch (Exception $e) {
            echo "❌ ERROR: " . $e->getMessage() . "\n";
        }
    } else {
        echo "⚠ expires_at is empty/null - skipping check\n";
    }
    
    echo "\nFinal isExpired: " . ($isExpired ? 'TRUE' : 'FALSE') . "\n";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>


