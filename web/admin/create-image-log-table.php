<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Creating image_creation_log table...<br>";

try {
    require_once __DIR__ . '/../api/includes/Database.php';
    $db = Database::getInstance();
    
    $sql = "CREATE TABLE IF NOT EXISTS image_creation_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(36) NOT NULL,
        user_email VARCHAR(255) NOT NULL,
        page_url TEXT NOT NULL,
        url_parameters TEXT,
        image_name VARCHAR(255) NOT NULL,
        image_path VARCHAR(500) NOT NULL,
        image_type ENUM('profile_photo', 'company_logo', 'cover_graphic', 'virtual_background', 'name_tag', 'other') NOT NULL,
        creation_method ENUM('upload', 'generated', 'placeholder', 'demo_copy') NOT NULL,
        file_size INT,
        image_dimensions VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45),
        user_agent TEXT,
        
        INDEX idx_user_id (user_id),
        INDEX idx_user_email (user_email),
        INDEX idx_created_at (created_at),
        INDEX idx_image_type (image_type),
        INDEX idx_creation_method (creation_method),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $result = $db->execute($sql);
    echo "✅ image_creation_log table created successfully!<br>";
    
    // Verify table structure
    echo "<h3>Table Structure:</h3>";
    $columns = $db->query("DESCRIBE image_creation_log");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $column) {
        echo "<tr><td>{$column['Field']}</td><td>{$column['Type']}</td><td>{$column['Null']}</td><td>{$column['Key']}</td></tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Error details: " . print_r($e, true) . "<br>";
}
?>
