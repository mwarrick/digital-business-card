<?php
/**
 * Run email_tracking table migration
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Read the migration file
    $migrationSQL = file_get_contents(__DIR__ . '/../config/migrations/028_create_email_tracking_table.sql');
    
    if (!$migrationSQL) {
        throw new Exception('Could not read migration file');
    }
    
    // Execute the migration
    $db->exec($migrationSQL);
    
    echo "✅ Email tracking table created successfully!\n";
    
    // Verify table exists
    $stmt = $db->query("SHOW TABLES LIKE 'email_tracking'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table verification: email_tracking table exists\n";
        
        // Show table structure
        $stmt = $db->query("DESCRIBE email_tracking");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\n📋 Table structure:\n";
        foreach ($columns as $column) {
            echo "- {$column['Field']}: {$column['Type']}\n";
        }
    } else {
        echo "❌ Table verification failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
