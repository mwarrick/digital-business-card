<?php
/**
 * Test Migration - Check if login tracking fields exist
 * This script tests if the migration was successful
 */

require_once __DIR__ . '/../api/includes/Database.php';

$db = Database::getInstance();

echo "<h1>Migration Test Results</h1>";

try {
    // Check if last_login column exists
    $result = $db->querySingle("SHOW COLUMNS FROM users LIKE 'last_login'");
    if ($result) {
        echo "<p style='color: green;'>✅ last_login column exists</p>";
        echo "<p>Column details: " . print_r($result, true) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ last_login column does not exist</p>";
    }
    
    // Check if login_count column exists
    $result = $db->querySingle("SHOW COLUMNS FROM users LIKE 'login_count'");
    if ($result) {
        echo "<p style='color: green;'>✅ login_count column exists</p>";
        echo "<p>Column details: " . print_r($result, true) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ login_count column does not exist</p>";
    }
    
    // Check if indexes exist
    $indexes = $db->query("SHOW INDEX FROM users WHERE Key_name IN ('idx_last_login', 'idx_login_count')");
    if (count($indexes) > 0) {
        echo "<p style='color: green;'>✅ Indexes exist</p>";
        echo "<p>Indexes: " . print_r($indexes, true) . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Indexes may not exist</p>";
    }
    
    // Test a sample query
    $sample = $db->querySingle("SELECT id, email, last_login, login_count FROM users LIMIT 1");
    if ($sample) {
        echo "<p style='color: green;'>✅ Sample query successful</p>";
        echo "<p>Sample data: " . print_r($sample, true) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Sample query failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
