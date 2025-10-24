<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>Fix Demo User Account</h2>";

try {
    // First, run the user_id migration if not done
    echo "<p>Running user_id migration...</p>";
    try {
        $db->execute("ALTER TABLE user_logins MODIFY COLUMN user_id VARCHAR(36) NOT NULL");
        echo "<p>✅ user_id column type fixed</p>";
    } catch (Exception $e) {
        echo "<p>⚠️ Migration may already be done: " . $e->getMessage() . "</p>";
    }
    
    // Check if demo user exists
    $demoUser = $db->query("SELECT * FROM users WHERE email = 'demo@sharemycard.app'")->fetch();
    
    if (!$demoUser) {
        echo "<p>❌ Demo user not found. Creating demo user...</p>";
        
        // Create demo user with all necessary fields
        $db->execute("
            INSERT INTO users (id, email, password_hash, is_active, is_admin, first_name, last_name, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ", [
            'demo-user-uuid-fixed',
            'demo@sharemycard.app', 
            password_hash('123456789', PASSWORD_DEFAULT),
            1, // is_active
            0, // is_admin
            'Demo', // first_name
            'User'  // last_name
        ]);
        
        echo "<p>✅ Demo user created successfully!</p>";
    } else {
        echo "<p>Demo user exists. Updating all fields...</p>";
        
        // Update all fields to ensure they're correct
        $db->execute("
            UPDATE users SET 
                id = ?, 
                is_active = ?, 
                is_admin = ?, 
                first_name = ?, 
                last_name = ?,
                updated_at = NOW()
            WHERE email = ?
        ", [
            'demo-user-uuid-fixed',
            1, // is_active
            0, // is_admin
            'Demo', // first_name
            'User', // last_name
            'demo@sharemycard.app'
        ]);
        
        echo "<p>✅ Demo user updated!</p>";
    }
    
    // Show final status
    $finalDemoUser = $db->query("SELECT * FROM users WHERE email = 'demo@sharemycard.app'")->fetch();
    echo "<h3>Final Demo User Status:</h3>";
    echo "<pre>" . print_r($finalDemoUser, true) . "</pre>";
    
    // Clear old bad login records
    echo "<p>Clearing old bad login records...</p>";
    $db->execute("DELETE FROM user_logins WHERE user_id = '0' OR user_id = 0 OR user_id IS NULL");
    echo "<p>✅ Old bad login records cleared</p>";
    
    // Test the login tracking query
    echo "<h3>Testing Login Tracking Query:</h3>";
    $testQuery = $db->query("
        SELECT ul.*, u.email as user_email, u.is_active, u.is_admin 
        FROM user_logins ul
        LEFT JOIN users u ON ul.user_id = u.id
        WHERE ul.email = 'demo@sharemycard.app'
        ORDER BY ul.login_timestamp DESC
        LIMIT 5
    ");
    
    echo "<p>Recent demo logins:</p>";
    foreach ($testQuery as $login) {
        echo "<pre>" . print_r($login, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
