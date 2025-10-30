<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Checking image_creation_log table...<br>";

try {
    require_once __DIR__ . '/../api/includes/Database.php';
    $db = Database::getInstance();
    
    // Check if table exists
    $result = $db->query("SHOW TABLES LIKE 'image_creation_log'");
    if (count($result) > 0) {
        echo "✅ Table image_creation_log already exists<br>";
        
        // Show table structure
        echo "<h3>Table Structure:</h3>";
        $columns = $db->query("DESCRIBE image_creation_log");
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        foreach ($columns as $column) {
            echo "<tr><td>{$column['Field']}</td><td>{$column['Type']}</td><td>{$column['Null']}</td><td>{$column['Key']}</td></tr>";
        }
        echo "</table>";
        
        // Check if there's any data
        $count = $db->querySingle("SELECT COUNT(*) as count FROM image_creation_log");
        echo "<br>Records in table: " . $count['count'] . "<br>";
        
    } else {
        echo "❌ Table image_creation_log does not exist<br>";
        
        // Try to create it with a simpler structure
        echo "Creating table with simpler structure...<br>";
        $sql = "CREATE TABLE image_creation_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(36),
            user_email VARCHAR(255),
            page_url TEXT,
            url_parameters TEXT,
            image_name VARCHAR(255),
            image_path VARCHAR(500),
            image_type VARCHAR(50),
            creation_method VARCHAR(50),
            file_size INT,
            image_dimensions VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            user_agent TEXT
        )";
        
        $db->execute($sql);
        echo "✅ Table created successfully!<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?>




