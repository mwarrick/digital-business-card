<?php
/**
 * Run Custom QR Code Expiration Migration (044)
 */

require_once __DIR__ . '/../config/secure-config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "Running Custom QR Code Expiration Migration (044)...\n";
    
    // Check if columns already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM custom_qr_codes LIKE 'expires_at'");
    if ($stmt->rowCount() > 0) {
        echo "⚠️  Column 'expires_at' already exists. Skipping migration.\n";
        exit(0);
    }
    
    // Migration 044: Add expiration fields to custom_qr_codes
    $sql044 = file_get_contents(__DIR__ . '/../config/migrations/044_add_expiration_to_custom_qr_codes.sql');
    $pdo->exec($sql044);
    echo "✓ Added expires_at and expiration_notice columns to custom_qr_codes table\n";
    echo "✓ Added index on expires_at\n";
    
    // Verify the changes
    $stmt = $pdo->query("DESCRIBE custom_qr_codes");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('expires_at', $columns) && in_array('expiration_notice', $columns)) {
        echo "✓ Migration verified successfully!\n";
    } else {
        echo "⚠️  Warning: Migration may not have completed correctly.\n";
    }
    
    echo "\nAll migrations completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>

