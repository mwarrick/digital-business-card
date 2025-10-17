<?php
/**
 * Debug User Deletion Script
 * This script helps identify what's causing deletion failures
 */

require_once __DIR__ . '/includes/AdminAuth.php';
require_once __DIR__ . '/../api/includes/Database.php';

// Require authentication
AdminAuth::requireAuth();

$db = Database::getInstance();

// Get user ID from URL parameter
$userId = $_GET['user_id'] ?? null;

if (!$userId) {
    die("Please provide user_id parameter: ?user_id=USER_ID");
}

echo "<h1>Debug User Deletion for User ID: $userId</h1>";

try {
    // 1. Check if user exists
    $user = $db->querySingle("SELECT * FROM users WHERE id = ?", [$userId]);
    if (!$user) {
        die("User not found!");
    }
    
    echo "<h2>User Information:</h2>";
    echo "<pre>" . print_r($user, true) . "</pre>";
    
    // 2. Check business cards
    $cards = $db->query("SELECT * FROM business_cards WHERE user_id = ?", [$userId]);
    echo "<h2>Business Cards (" . count($cards) . "):</h2>";
    echo "<pre>" . print_r($cards, true) . "</pre>";
    
    // 3. Check verification codes
    $codes = $db->query("SELECT * FROM verification_codes WHERE email = ?", [$user['email']]);
    echo "<h2>Verification Codes (" . count($codes) . "):</h2>";
    echo "<pre>" . print_r($codes, true) . "</pre>";
    
    // 4. Check analytics data
    if (!empty($cards)) {
        $cardIds = array_column($cards, 'id');
        $placeholders = str_repeat('?,', count($cardIds) - 1) . '?';
        
        $analyticsDaily = $db->query("SELECT * FROM analytics_daily WHERE card_id IN ($placeholders)", $cardIds);
        echo "<h2>Analytics Daily (" . count($analyticsDaily) . "):</h2>";
        echo "<pre>" . print_r($analyticsDaily, true) . "</pre>";
        
        $analyticsEvents = $db->query("SELECT * FROM analytics_events WHERE card_id IN ($placeholders)", $cardIds);
        echo "<h2>Analytics Events (" . count($analyticsEvents) . "):</h2>";
        echo "<pre>" . print_r($analyticsEvents, true) . "</pre>";
        
        $analyticsSessions = $db->query("SELECT * FROM analytics_sessions WHERE card_id IN ($placeholders)", $cardIds);
        echo "<h2>Analytics Sessions (" . count($analyticsSessions) . "):</h2>";
        echo "<pre>" . print_r($analyticsSessions, true) . "</pre>";
    }
    
    // 5. Test individual DELETE queries
    echo "<h2>Testing DELETE Queries:</h2>";
    
    // Test verification codes deletion
    try {
        $result = $db->execute("DELETE FROM verification_codes WHERE email = ?", [$user['email']]);
        echo "✅ Verification codes deletion: SUCCESS<br>";
    } catch (Exception $e) {
        echo "❌ Verification codes deletion: FAILED - " . $e->getMessage() . "<br>";
    }
    
    // Test business cards deletion
    try {
        $result = $db->execute("DELETE FROM business_cards WHERE user_id = ?", [$userId]);
        echo "✅ Business cards deletion: SUCCESS<br>";
    } catch (Exception $e) {
        echo "❌ Business cards deletion: FAILED - " . $e->getMessage() . "<br>";
    }
    
    // Test user deletion
    try {
        $result = $db->execute("DELETE FROM users WHERE id = ?", [$userId]);
        echo "✅ User deletion: SUCCESS<br>";
    } catch (Exception $e) {
        echo "❌ User deletion: FAILED - " . $e->getMessage() . "<br>";
    }
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
