<?php
/**
 * Check Demo User ID
 * Shows the actual demo user ID in the database
 */

require_once __DIR__ . '/../api/includes/Database.php';

$db = Database::getInstance();

echo "<h1>Demo User ID Check</h1>";

try {
    // Get the demo user from the users table
    $demoUser = $db->query("SELECT id, email, first_name, last_name FROM users WHERE email = 'demo@sharemycard.app' LIMIT 1");
    
    if (empty($demoUser)) {
        echo "<p style='color: red;'>❌ Demo user not found in database</p>";
    } else {
        $user = $demoUser[0];
        echo "<p><strong>Demo User Found:</strong></p>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . htmlspecialchars($user['id']) . "</li>";
        echo "<li><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</li>";
        echo "<li><strong>Name:</strong> " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</li>";
        echo "</ul>";
        
        // Check if this matches the DemoUserHelper constant
        echo "<p><strong>DemoUserHelper::DEMO_USER_ID:</strong> demo-user-uuid-fixed</p>";
        
        if ($user['id'] === 'demo-user-uuid-fixed') {
            echo "<p style='color: green;'>✅ Demo user ID matches DemoUserHelper constant</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Demo user ID does NOT match DemoUserHelper constant</p>";
            echo "<p>This means the demo system is using a different user ID than expected.</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
