<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>Database Tables Check</h2>";

try {
    echo "<p>Listing all tables in the database:</p>";
    $tables = $db->query("SHOW TABLES");
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        echo "<p>- {$tableName}</p>";
    }
    
    echo "<p>Looking specifically for user_logins:</p>";
    $userLogins = $db->query("SHOW TABLES LIKE 'user_logins'")->fetchAll();
    if (count($userLogins) > 0) {
        echo "<p>✅ user_logins table exists</p>";
        
        // Show structure
        $columns = $db->query("DESCRIBE user_logins");
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $column) {
            echo "<tr><td>{$column['Field']}</td><td>{$column['Type']}</td><td>{$column['Null']}</td><td>{$column['Key']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ user_logins table does not exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>

