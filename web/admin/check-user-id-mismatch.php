<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>Check User ID Mismatch</h2>";

try {
    // Check what user has the demo ID
    echo "<h3>User with ID 'demo-user-uuid-fixed':</h3>";
    $demoUser = $db->query("SELECT * FROM users WHERE id = 'demo-user-uuid-fixed'")->fetch();
    if ($demoUser) {
        echo "<pre>" . print_r($demoUser, true) . "</pre>";
    } else {
        echo "<p>❌ No user found with ID 'demo-user-uuid-fixed'</p>";
    }
    
    // Check what user has the demo email
    echo "<h3>User with email 'demo@sharemycard.app':</h3>";
    $demoEmailUser = $db->query("SELECT * FROM users WHERE email = 'demo@sharemycard.app'")->fetch();
    if ($demoEmailUser) {
        echo "<pre>" . print_r($demoEmailUser, true) . "</pre>";
    } else {
        echo "<p>❌ No user found with email 'demo@sharemycard.app'</p>";
    }
    
    // Check recent login records
    echo "<h3>Recent Login Records:</h3>";
    $logins = $db->query("
        SELECT ul.user_id, ul.email, u.id as user_table_id, u.email as user_table_email
        FROM user_logins ul
        LEFT JOIN users u ON ul.user_id = u.id
        WHERE ul.email = 'demo@sharemycard.app'
        ORDER BY ul.login_timestamp DESC
        LIMIT 3
    ");
    
    foreach ($logins as $login) {
        echo "<pre>" . print_r($login, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>



