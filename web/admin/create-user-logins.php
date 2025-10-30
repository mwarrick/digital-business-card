<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Creating user_logins table...<br>";

try {
    require_once __DIR__ . '/../api/includes/Database.php';
    $db = Database::getInstance();
    
    $sql = "CREATE TABLE user_logins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(36) NOT NULL,
        email VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT NOT NULL,
        browser_name VARCHAR(100),
        browser_version VARCHAR(50),
        operating_system VARCHAR(100),
        device_type VARCHAR(50),
        screen_resolution VARCHAR(20),
        country VARCHAR(100),
        city VARCHAR(100),
        login_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        session_id VARCHAR(255),
        is_successful BOOLEAN DEFAULT TRUE,
        failure_reason VARCHAR(255),
        
        INDEX idx_user_id (user_id),
        INDEX idx_email (email),
        INDEX idx_ip_address (ip_address),
        INDEX idx_login_timestamp (login_timestamp),
        INDEX idx_browser_name (browser_name),
        INDEX idx_device_type (device_type),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $result = $db->execute($sql);
    echo "Table created successfully!<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>




