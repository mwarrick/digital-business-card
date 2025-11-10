<?php
/**
 * Fix verification_codes table structure
 * Adds missing columns: type, used_at
 */

require_once __DIR__ . '/../../api/includes/Database.php';

$db = Database::getInstance();

echo "Checking verification_codes table structure...\n";

try {
    // Check if 'type' column exists
    $columns = $db->query("SHOW COLUMNS FROM verification_codes LIKE 'type'");
    if (empty($columns)) {
        echo "Adding 'type' column...\n";
        $db->execute(
            "ALTER TABLE verification_codes ADD COLUMN type VARCHAR(20) NULL DEFAULT NULL AFTER code"
        );
        echo "✓ Added 'type' column\n";
    } else {
        echo "✓ 'type' column already exists\n";
    }
    
    // Check if 'used_at' column exists
    $columns = $db->query("SHOW COLUMNS FROM verification_codes LIKE 'used_at'");
    if (empty($columns)) {
        echo "Adding 'used_at' column...\n";
        $db->execute(
            "ALTER TABLE verification_codes ADD COLUMN used_at TIMESTAMP NULL DEFAULT NULL AFTER expires_at"
        );
        echo "✓ Added 'used_at' column\n";
    } else {
        echo "✓ 'used_at' column already exists\n";
    }
    
    // Show final structure
    echo "\nFinal table structure:\n";
    $structure = $db->query("DESCRIBE verification_codes");
    foreach ($structure as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
    echo "\n✓ Verification codes table structure is correct!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

