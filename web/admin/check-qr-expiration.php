<?php
/**
 * Check QR Code Expiration - Debug Script
 */

require_once __DIR__ . '/../config/secure-config.php';

$qrId = '84b80cc0-671e-40e1-9be4-3183177cbc7d';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $stmt = $pdo->prepare('SELECT * FROM custom_qr_codes WHERE id = ?');
    $stmt->execute([$qrId]);
    $qr = $stmt->fetch();
    
    if (!$qr) {
        echo "QR code not found\n";
        exit;
    }
    
    echo "=== QR CODE DATA ===\n";
    echo "ID: " . $qr['id'] . "\n";
    echo "Title: " . ($qr['title'] ?? 'NULL') . "\n";
    echo "Type: " . $qr['type'] . "\n";
    echo "Status: " . $qr['status'] . "\n";
    echo "expires_at: " . var_export($qr['expires_at'], true) . "\n";
    echo "expiration_notice: " . ($qr['expiration_notice'] ?? 'NULL') . "\n";
    echo "show_lead_form: " . ($qr['show_lead_form'] ?? 'NULL') . "\n";
    echo "\nAll columns: " . implode(', ', array_keys($qr)) . "\n";
    
    echo "\n=== EXPIRATION CHECK ===\n";
    require_once __DIR__ . '/../api/includes/qr/Generator.php';
    $isExpired = \QRCard\QR\Generator::isQrCodeExpired($qr);
    echo "isExpired: " . ($isExpired ? 'TRUE' : 'FALSE') . "\n";
    
    $est = new DateTimeZone('America/New_York');
    $now = new DateTime('now', $est);
    echo "Current EST time: " . $now->format('Y-m-d H:i:s T') . "\n";
    
    if (!empty($qr['expires_at'])) {
        $expiresAtString = trim($qr['expires_at']);
        echo "expires_at string: " . $expiresAtString . "\n";
        $expiresAt = DateTime::createFromFormat('Y-m-d H:i:s', $expiresAtString, $est);
        if ($expiresAt) {
            echo "Parsed expires_at: " . $expiresAt->format('Y-m-d H:i:s T') . "\n";
            echo "Comparison: now >= expires_at? " . ($now >= $expiresAt ? 'YES (EXPIRED)' : 'NO (NOT EXPIRED)') . "\n";
        } else {
            echo "Failed to parse with Y-m-d H:i:s\n";
        }
    } else {
        echo "expires_at is NULL or empty\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>


