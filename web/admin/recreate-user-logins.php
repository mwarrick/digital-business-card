<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>Recreating User Logins Table</h2>";

try {
    // Step 1: Drop the existing table
    echo "<p>1. Dropping existing user_logins table...</p>";
    $db->execute("DROP TABLE IF EXISTS user_logins");
    echo "<p>✅ Dropped user_logins table</p>";
    
    // Step 2: Create the table with correct schema
    echo "<p>2. Creating new user_logins table with correct schema...</p>";
    $db->execute("
        CREATE TABLE user_logins (
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
        )
    ");
    echo "<p>✅ Created user_logins table with correct schema</p>";
    
    // Step 3: Test the table structure
    echo "<p>3. Verifying table structure...</p>";
    $columns = $db->query("DESCRIBE user_logins");
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Step 4: Test inserting a record
    echo "<p>4. Testing record insertion...</p>";
    $testUserId = $db->query("SELECT id FROM users WHERE email = 'demo@sharemycard.app'")->fetch();
    if ($testUserId) {
        $db->execute("
            INSERT INTO user_logins (
                user_id, email, ip_address, user_agent, browser_name, 
                operating_system, device_type, is_successful
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $testUserId['id'],
            'demo@sharemycard.app',
            '127.0.0.1',
            'Test User Agent',
            'Chrome',
            'Windows',
            'Desktop',
            1
        ]);
        echo "<p>✅ Test record inserted successfully</p>";
    } else {
        echo "<p>⚠️ Demo user not found, skipping test insert</p>";
    }
    
    // Step 5: Test the JOIN
    echo "<p>5. Testing JOIN with users table...</p>";
    $joinTest = $db->query("
        SELECT 
            ul.user_id, 
            ul.email as login_email, 
            u.email as user_email, 
            u.is_active, 
            u.is_admin
        FROM user_logins ul
        LEFT JOIN users u ON ul.user_id = u.id
        LIMIT 3
    ");
    
    echo "<h3>JOIN Test Results:</h3>";
    foreach ($joinTest as $row) {
        echo "<pre>" . print_r($row, true) . "</pre>";
    }
    
    echo "<p>✅ User logins table recreated successfully!</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>






