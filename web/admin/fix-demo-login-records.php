<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>Fix Demo Login Records</h2>";

try {
    // Get the correct demo user ID
    $demoUser = $db->query("SELECT id FROM users WHERE email = 'demo@sharemycard.app'")->fetch();
    if (!$demoUser) {
        echo "<p>❌ Demo user not found!</p>";
        exit;
    }
    
    $correctDemoId = $demoUser['id'];
    echo "<p>✅ Demo user ID: {$correctDemoId}</p>";
    
    // Update all demo login records to use the correct user_id
    $updated = $db->execute("
        UPDATE user_logins 
        SET user_id = ? 
        WHERE email = 'demo@sharemycard.app'
    ", [$correctDemoId]);
    
    echo "<p>✅ Updated {$updated} demo login records</p>";
    
    // Verify the fix
    $verify = $db->query("
        SELECT 
            ul.user_id,
            ul.email as login_email,
            u.email as user_email,
            u.is_active
        FROM user_logins ul
        LEFT JOIN users u ON ul.user_id = u.id
        WHERE ul.email = 'demo@sharemycard.app'
        ORDER BY ul.login_timestamp DESC
        LIMIT 1
    ")->fetch();
    
    echo "<h3>Verification:</h3>";
    echo "<pre>" . print_r($verify, true) . "</pre>";
    
    if ($verify && $verify['user_email'] === 'demo@sharemycard.app') {
        echo "<p>✅ SUCCESS! Demo logins now link to correct user</p>";
    } else {
        echo "<p>❌ Still not working</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>


