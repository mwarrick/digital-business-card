<?php
/**
 * Dashboard Test - Basic functionality check
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Dashboard Test</h1>";

try {
    echo "<p>1. Loading UserAuth...</p>";
    require_once __DIR__ . '/includes/UserAuth.php';
    echo "<p>✅ UserAuth loaded</p>";
    
    echo "<p>2. Loading Database...</p>";
    require_once __DIR__ . '/../api/includes/Database.php';
    echo "<p>✅ Database loaded</p>";
    
    echo "<p>3. Checking authentication...</p>";
    UserAuth::requireAuth();
    echo "<p>✅ User authenticated</p>";
    
    echo "<p>4. Getting user info...</p>";
    $user = UserAuth::getUser();
    echo "<p>✅ User: " . ($user['email'] ?? 'Unknown') . "</p>";
    
    echo "<p>5. Getting database connection...</p>";
    $db = Database::getInstance();
    echo "<p>✅ Database connection established</p>";
    
    echo "<p>6. Testing business cards query...</p>";
    $cards = $db->query(
        "SELECT * FROM business_cards WHERE user_id = ? AND is_active = 1 ORDER BY created_at DESC",
        [UserAuth::getUserId()]
    );
    echo "<p>✅ Business cards: " . count($cards) . " found</p>";
    
    echo "<p>7. Testing leads query...</p>";
    $leadsCount = $db->querySingle("
        SELECT COUNT(*) 
        FROM leads l
        JOIN business_cards bc ON l.id_business_card = bc.id
        WHERE bc.user_id = ?
    ", [UserAuth::getUserId()]);
    echo "<p>✅ Leads count: " . $leadsCount . "</p>";
    
    echo "<p>8. Testing contacts query...</p>";
    $contactsCount = $db->querySingle("
        SELECT COUNT(*) 
        FROM contacts 
        WHERE id_user = ?
    ", [UserAuth::getUserId()]);
    echo "<p>✅ Contacts count: " . $contactsCount . "</p>";
    
    echo "<h2>✅ All tests passed! Dashboard should work.</h2>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error: " . $e->getMessage() . "</h2>";
    echo "<p>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
}
?>
