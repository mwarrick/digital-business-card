<?php
/**
 * Run Custom QR Code migrations
 */

require_once __DIR__ . '/../config/secure-config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "Running Custom QR Code migrations...\n";
    
    // Migration 030: Create custom_qr_codes table
    $sql030 = file_get_contents(__DIR__ . '/../config/migrations/030_create_custom_qr_codes.sql');
    $pdo->exec($sql030);
    echo "✓ Created custom_qr_codes table\n";
    
    // Migration 031: Make leads card nullable and add qr_id
    $sql031 = file_get_contents(__DIR__ . '/../config/migrations/031_make_leads_card_nullable.sql');
    $pdo->exec($sql031);
    echo "✓ Updated leads table for QR codes\n";
    
    echo "All migrations completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
