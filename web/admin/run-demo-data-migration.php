<?php
/**
 * Run migration 021_create_demo_data_table.sql
 * Creates demo_data table and populates it with current demo user information
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/includes/Database.php';

try {
    $db = Database::getInstance();
    
    // Read and execute the migration
    $migrationSql = file_get_contents(__DIR__ . '/../config/migrations/021_create_demo_data_table.sql');
    
    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $migrationSql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $db->execute($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "\n✅ Migration 021_create_demo_data_table.sql executed successfully.\n";
    echo "Demo data table created and populated with current demo user information.\n";
    
} catch (Exception $e) {
    echo "❌ Error running migration: " . $e->getMessage() . "\n";
    exit(1);
}
?>
