<?php
/**
 * Run migration to fix demo_data table structure
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/includes/Database.php';

echo "Fixing demo_data table structure...\n";

try {
    $db = Database::getInstance();
    
    // Read and execute the migration
    $migrationSql = file_get_contents(__DIR__ . '/../config/migrations/022_fix_demo_data_structure.sql');
    
    // Split by semicolon and execute each statement
    $statements = explode(';', $migrationSql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $db->execute($statement);
            echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
        } catch (Exception $e) {
            echo "✗ Error executing: " . substr($statement, 0, 50) . "...\n";
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
    
    // Verify the new structure
    $demoData = $db->query("SELECT card_id, first_name, last_name, company_name, primary_website_url FROM demo_data");
    echo "\nDemo data after migration:\n";
    foreach ($demoData as $row) {
        echo "- " . $row['first_name'] . " " . $row['last_name'] . " (" . $row['company_name'] . ") - " . $row['primary_website_url'] . "\n";
    }
    
    echo "\n✅ Demo data structure fixed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
