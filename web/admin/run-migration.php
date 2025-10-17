<?php
/**
 * Migration Runner - Run database migrations
 * SECURITY: This should be removed after running migrations
 */

require_once __DIR__ . '/includes/AdminAuth.php';
require_once __DIR__ . '/../api/includes/Database.php';

// Require admin authentication
AdminAuth::requireAuth();

$db = Database::getInstance();

// Migration 011: Add last login tracking
$migration = "011_add_last_login_tracking";

echo "<h1>Running Migration: $migration</h1>";

try {
    // Check if migration already ran
    $result = $db->querySingle("SHOW COLUMNS FROM users LIKE 'last_login'");
    if ($result) {
        echo "<p style='color: orange;'>⚠️ Migration already applied - last_login column exists</p>";
        exit;
    }
    
    echo "<p>Starting migration...</p>";
    
    // Add last_login field
    $db->execute("ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL AFTER updated_at");
    echo "<p>✅ Added last_login column</p>";
    
    // Add login_count field
    $db->execute("ALTER TABLE users ADD COLUMN login_count INT DEFAULT 0 AFTER last_login");
    echo "<p>✅ Added login_count column</p>";
    
    // Add indexes
    $db->execute("CREATE INDEX idx_last_login ON users(last_login)");
    echo "<p>✅ Added last_login index</p>";
    
    $db->execute("CREATE INDEX idx_login_count ON users(login_count)");
    echo "<p>✅ Added login_count index</p>";
    
    echo "<p style='color: green; font-weight: bold;'>🎉 Migration completed successfully!</p>";
    echo "<p>You can now delete this file for security.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Migration failed: " . $e->getMessage() . "</p>";
}
?>
