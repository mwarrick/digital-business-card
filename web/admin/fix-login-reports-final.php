<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>Final Fix for Login Reports</h2>";

try {
    // Step 1: Fix the user_id column type
    echo "<p>1. Fixing user_id column type...</p>";
    $db->execute("ALTER TABLE user_logins MODIFY COLUMN user_id VARCHAR(36) NOT NULL");
    echo "<p>✅ user_id column is now VARCHAR(36)</p>";
    
    // Step 2: Get the correct demo user ID
    echo "<p>2. Finding correct demo user...</p>";
    $demoUser = $db->query("SELECT id FROM users WHERE email = 'demo@sharemycard.app'")->fetch();
    if (!$demoUser) {
        echo "<p>❌ Demo user not found!</p>";
        exit;
    }
    $correctDemoId = $demoUser['id'];
    echo "<p>✅ Demo user ID: {$correctDemoId}</p>";
    
    // Step 3: Update all demo login records with correct user_id
    echo "<p>3. Updating demo login records...</p>";
    $updated = $db->execute("
        UPDATE user_logins 
        SET user_id = ? 
        WHERE email = 'demo@sharemycard.app'
    ", [$correctDemoId]);
    echo "<p>✅ Updated {$updated} demo login records</p>";
    
    // Step 4: Test the query
    echo "<p>4. Testing the query...</p>";
    $testResults = $db->query("
        SELECT 
            ul.email as login_email,
            u.email as user_email,
            u.is_active,
            u.is_admin
        FROM user_logins ul
        LEFT JOIN users u ON ul.user_id = u.id
        WHERE ul.email = 'demo@sharemycard.app'
        ORDER BY ul.login_timestamp DESC
        LIMIT 1
    ")->fetch();
    
    echo "<h3>Test Result:</h3>";
    echo "<pre>" . print_r($testResults, true) . "</pre>";
    
    if ($testResults && $testResults['is_active'] == 1) {
        echo "<p>✅ SUCCESS! Demo user should now show as Active in login reports</p>";
    } else {
        echo "<p>❌ Still not working. is_active = " . ($testResults['is_active'] ?? 'NULL') . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>








