<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>Fixing User Logins Schema</h2>";

try {
    // Step 1: Change user_id column to VARCHAR
    echo "<p>1. Changing user_id column to VARCHAR(36)...</p>";
    $db->execute("ALTER TABLE user_logins MODIFY COLUMN user_id VARCHAR(36) NOT NULL");
    echo "<p>✅ Changed user_id to VARCHAR(36)</p>";
    
    // Step 2: Populate user_id with correct user IDs
    echo "<p>2. Populating user_id with correct user IDs...</p>";
    $updated = $db->execute("
        UPDATE user_logins ul
        JOIN users u ON ul.email = u.email
        SET ul.user_id = u.id
    ");
    echo "<p>✅ Updated {$updated} records with correct user IDs</p>";
    
    // Step 3: Add index for performance
    echo "<p>3. Adding index...</p>";
    $db->execute("CREATE INDEX idx_user_logins_user_id ON user_logins(user_id)");
    echo "<p>✅ Added index</p>";
    
    // Step 4: Verify column type
    echo "<p>4. Verifying column type...</p>";
    $columnInfo = $db->query("DESCRIBE user_logins user_id")->fetch();
    echo "<p>user_id column type: " . $columnInfo['Type'] . "</p>";
    
    // Step 5: Test the JOIN
    echo "<p>5. Testing JOIN...</p>";
    $test = $db->query("
        SELECT ul.user_id, ul.email, u.email as user_email, u.is_active
        FROM user_logins ul
        LEFT JOIN users u ON ul.user_id = u.id
        LIMIT 3
    ");
    
    echo "<h3>Test Results:</h3>";
    foreach ($test as $row) {
        echo "<pre>" . print_r($row, true) . "</pre>";
    }
    
    echo "<p>✅ Schema fix completed successfully!</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
