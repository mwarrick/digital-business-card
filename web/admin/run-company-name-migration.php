<?php
/**
 * Run migration 023_add_include_company_to_name_tag_preferences.sql
 * Adds include_company column to name_tag_preferences table
 */

require_once __DIR__ . '/../api/includes/Database.php';

try {
    $db = Database::getInstance();
    
    echo "<h1>Running Migration: 023_add_include_company_to_name_tag_preferences</h1>";
    
    // Check if column already exists
    $result = $db->querySingle("SHOW COLUMNS FROM name_tag_preferences LIKE 'include_company'");
    if ($result) {
        echo "<p style='color: orange;'>⚠️ Migration already applied - include_company column exists</p>";
        echo "<p>Column details: " . print_r($result, true) . "</p>";
        exit;
    }
    
    echo "<p>Starting migration...</p>";
    
    // Add include_company column
    $db->execute("ALTER TABLE name_tag_preferences ADD COLUMN include_company BOOLEAN DEFAULT FALSE AFTER include_title");
    echo "<p>✅ Added include_company column</p>";
    
    // Update existing records to have include_company = FALSE by default
    $db->execute("UPDATE name_tag_preferences SET include_company = FALSE WHERE include_company IS NULL");
    echo "<p>✅ Updated existing records</p>";
    
    echo "<p style='color: green; font-weight: bold;'>🎉 Migration completed successfully!</p>";
    echo "<p>The include_company column has been added to the name_tag_preferences table.</p>";
    echo "<p>You can now delete this file for security.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Migration failed: " . $e->getMessage() . "</p>";
    echo "<p>Error details: " . $e->getTraceAsString() . "</p>";
}
?>
