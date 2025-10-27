<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>Fix Login Records</h2>";

try {
    // First, ensure the user_id column is VARCHAR
    echo "<p>Ensuring user_id column is VARCHAR...</p>";
    try {
        $db->execute("ALTER TABLE user_logins MODIFY COLUMN user_id VARCHAR(36) NOT NULL");
        echo "<p>✅ user_id column is now VARCHAR(36)</p>";
    } catch (Exception $e) {
        echo "<p>⚠️ Column may already be VARCHAR: " . $e->getMessage() . "</p>";
    }
    
    // Check current login records
    echo "<h3>Current Login Records:</h3>";
    $currentLogins = $db->query("SELECT user_id, email, COUNT(*) as count FROM user_logins GROUP BY user_id, email ORDER BY count DESC");
    foreach ($currentLogins as $login) {
        echo "<p>User ID: '{$login['user_id']}', Email: '{$login['email']}', Count: {$login['count']}</p>";
    }
    
    // Fix demo user login records
    echo "<p>Fixing demo user login records...</p>";
    $updated = $db->execute("
        UPDATE user_logins 
        SET user_id = 'demo-user-uuid-fixed' 
        WHERE email = 'demo@sharemycard.app' AND (user_id = '0' OR user_id = 0 OR user_id IS NULL)
    ");
    echo "<p>✅ Updated {$updated} demo login records</p>";
    
    // Test the JOIN query
    echo "<h3>Testing JOIN Query:</h3>";
    $testQuery = $db->query("
        SELECT ul.user_id, ul.email, u.email as user_email, u.is_active, u.is_admin
        FROM user_logins ul
        LEFT JOIN users u ON ul.user_id = u.id
        WHERE ul.email = 'demo@sharemycard.app'
        ORDER BY ul.login_timestamp DESC
        LIMIT 3
    ");
    
    foreach ($testQuery as $row) {
        echo "<pre>" . print_r($row, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>


