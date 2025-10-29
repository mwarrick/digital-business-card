<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>Debug Login Data</h2>";

try {
    // Check what's in user_logins table
    echo "<h3>Raw user_logins data:</h3>";
    $logins = $db->query("SELECT user_id, email, login_timestamp FROM user_logins ORDER BY login_timestamp DESC LIMIT 5");
    foreach ($logins as $login) {
        echo "<p>user_id: '{$login['user_id']}', email: '{$login['email']}', time: {$login['login_timestamp']}</p>";
    }
    
    // Check what users exist
    echo "<h3>Users in database:</h3>";
    $users = $db->query("SELECT id, email FROM users ORDER BY email");
    foreach ($users as $user) {
        echo "<p>id: '{$user['id']}', email: '{$user['email']}'</p>";
    }
    
    // Test the JOIN query
    echo "<h3>JOIN query result:</h3>";
    $joinResult = $db->query("
        SELECT 
            ul.user_id,
            ul.email as login_email,
            u.id as user_table_id,
            u.email as user_table_email
        FROM user_logins ul
        LEFT JOIN users u ON ul.user_id = u.id
        WHERE ul.email = 'demo@sharemycard.app'
        ORDER BY ul.login_timestamp DESC
        LIMIT 3
    ");
    
    foreach ($joinResult as $row) {
        echo "<pre>" . print_r($row, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>



