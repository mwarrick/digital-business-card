<?php
/**
 * Debug the demo data migration
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/includes/Database.php';

echo "Debugging demo data migration...\n";

try {
    $db = Database::getInstance();
    
    // Check current demo_data structure
    echo "Current demo_data table structure:\n";
    try {
        $structure = $db->query("DESCRIBE demo_data");
        foreach ($structure as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    } catch (Exception $e) {
        echo "Error describing demo_data: " . $e->getMessage() . "\n";
    }
    
    // Check current data
    echo "\nCurrent demo_data records:\n";
    try {
        $data = $db->query("SELECT * FROM demo_data LIMIT 3");
        foreach ($data as $row) {
            echo "- " . $row['first_name'] . " " . $row['last_name'] . "\n";
        }
    } catch (Exception $e) {
        echo "Error querying demo_data: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
