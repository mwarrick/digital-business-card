<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>Demo User Status Check</h2>";

try {
    // Check demo user in users table
    $demoUser = $db->query("SELECT * FROM users WHERE email = 'demo@sharemycard.app'")->fetch();
    
    if ($demoUser) {
        echo "<h3>Demo User in users table:</h3>";
        echo "<pre>" . print_r($demoUser, true) . "</pre>";
    } else {
        echo "<p>❌ Demo user not found in users table</p>";
    }
    
    // Check recent logins
    echo "<h3>Recent logins from user_logins table:</h3>";
    $logins = $db->query("SELECT * FROM user_logins WHERE email = 'demo@sharemycard.app' ORDER BY login_timestamp DESC LIMIT 5");
    foreach ($logins as $login) {
        echo "<pre>" . print_r($login, true) . "</pre>";
    }
    
    // Check if demo user exists with the UUID
    echo "<h3>Demo user by UUID:</h3>";
    $demoByUuid = $db->query("SELECT * FROM users WHERE id = 'demo-user-uuid-fixed'")->fetch();
    if ($demoByUuid) {
        echo "<pre>" . print_r($demoByUuid, true) . "</pre>";
    } else {
        echo "<p>❌ Demo user not found by UUID</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>








