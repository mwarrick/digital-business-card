<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>Dropping User Logins Table - Debug</h2>";

try {
    // First, check if table exists
    echo "<p>1. Checking if user_logins table exists...</p>";
    $tables = $db->query("SHOW TABLES LIKE 'user_logins'")->fetchAll();
    if (count($tables) > 0) {
        echo "<p>✅ user_logins table exists</p>";
        
        // Show table structure before dropping
        echo "<p>2. Current table structure:</p>";
        $columns = $db->query("DESCRIBE user_logins");
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $column) {
            echo "<tr><td>{$column['Field']}</td><td>{$column['Type']}</td><td>{$column['Null']}</td><td>{$column['Key']}</td></tr>";
        }
        echo "</table>";
        
        // Drop the table
        echo "<p>3. Dropping user_logins table...</p>";
        $db->execute("DROP TABLE user_logins");
        echo "<p>✅ user_logins table dropped successfully</p>";
        
        // Verify it's gone
        echo "<p>4. Verifying table is gone...</p>";
        $tables = $db->query("SHOW TABLES LIKE 'user_logins'")->fetchAll();
        if (count($tables) == 0) {
            echo "<p>✅ Confirmed: user_logins table no longer exists</p>";
        } else {
            echo "<p>❌ Table still exists!</p>";
        }
        
    } else {
        echo "<p>⚠️ user_logins table does not exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}
?>



