<?php
require_once __DIR__ . '/../api/includes/Database.php';

try {
    $db = Database::getInstance();
    
    // Read the migration file
    $migrationFile = __DIR__ . '/../config/migrations/019_add_invitations.sql';
    $sql = file_get_contents($migrationFile);
    
    if (!$sql) {
        throw new Exception("Could not read migration file");
    }
    
    // Execute the migration
    $db->execute($sql);
    
    echo "✅ Migration 019_add_invitations.sql executed successfully!\n";
    echo "✅ Invitations table created with all required columns and indexes.\n";
    
    // Verify the table was created
    $result = $db->querySingle("SHOW TABLES LIKE 'invitations'");
    if ($result) {
        echo "✅ Table 'invitations' confirmed to exist.\n";
    } else {
        echo "❌ Table 'invitations' not found after migration.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
