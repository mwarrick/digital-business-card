<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Read the migration file
    $migrationSql = file_get_contents(__DIR__ . '/../config/migrations/003_fix_user_logins_user_id_type.sql');
    
    // Execute the migration
    $db->execute($migrationSql);
    
    echo "✅ Migration completed successfully!<br>";
    echo "user_id column type changed from INT to VARCHAR(36)<br>";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "<br>";
}
?>






