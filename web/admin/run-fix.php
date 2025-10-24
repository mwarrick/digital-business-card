<?php
require_once __DIR__ . '/../config/database.php';

// Get the correct demo user ID
$demoUser = $db->query("SELECT id FROM users WHERE email = 'demo@sharemycard.app'")->fetch();
if ($demoUser) {
    $correctDemoId = $demoUser['id'];
    
    // Update all demo login records to use the correct user_id
    $updated = $db->execute("
        UPDATE user_logins 
        SET user_id = ? 
        WHERE email = 'demo@sharemycard.app'
    ", [$correctDemoId]);
    
    echo "Updated {$updated} demo login records with user_id: {$correctDemoId}";
} else {
    echo "Demo user not found!";
}
?>
