<?php
/**
 * Run addresses table migration
 * This script creates the addresses table if it doesn't exist
 */

require_once __DIR__ . '/../config/database.php';

try {
    // Read the migration file
    $migrationFile = __DIR__ . '/../config/migrations/020_add_addresses_table.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    if (empty($sql)) {
        throw new Exception("Migration file is empty");
    }
    
    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $db = Database::getInstance();
    
    echo "Running addresses table migration...\n";
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            $db->execute($statement);
        }
    }
    
    echo "✅ Migration completed successfully!\n";
    echo "Addresses table created/verified.\n";
    
    // Test the table by checking if it exists
    $result = $db->querySingle("SHOW TABLES LIKE 'addresses'");
    if ($result) {
        echo "✅ Addresses table confirmed to exist.\n";
        
        // Show table structure
        $structure = $db->query("DESCRIBE addresses");
        echo "\nTable structure:\n";
        foreach ($structure as $column) {
            echo "- {$column['Field']}: {$column['Type']}\n";
        }
    } else {
        echo "❌ Addresses table not found after migration.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
