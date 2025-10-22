<?php
/**
 * Check what tables exist in the database
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/includes/Database.php';

try {
    $db = Database::getInstance();
    
    // Check what tables exist
    $tables = $db->query("SHOW TABLES");
    echo "Existing tables:\n";
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        echo "- $tableName\n";
    }
    
    // Check if addresses table exists and its structure
    try {
        $addresses = $db->query("DESCRIBE addresses");
        echo "\nAddresses table structure:\n";
        foreach ($addresses as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    } catch (Exception $e) {
        echo "\nAddresses table does not exist: " . $e->getMessage() . "\n";
    }
    
    // Check if website_links table exists and its structure
    try {
        $websiteLinks = $db->query("DESCRIBE website_links");
        echo "\nWebsite_links table structure:\n";
        foreach ($websiteLinks as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    } catch (Exception $e) {
        echo "\nWebsite_links table does not exist: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
