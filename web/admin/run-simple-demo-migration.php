<?php
/**
 * Run simplified demo data migration
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/includes/Database.php';

try {
    $db = Database::getInstance();
    
    // Read and execute the simplified migration
    $migrationSql = file_get_contents(__DIR__ . '/../config/migrations/021_create_demo_data_table_simple.sql');
    
    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $migrationSql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $db->execute($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "\n✅ Simplified demo data migration executed successfully.\n";
    
} catch (Exception $e) {
    echo "❌ Error running migration: " . $e->getMessage() . "\n";
    exit(1);
}
?>
