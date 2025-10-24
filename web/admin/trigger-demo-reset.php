<?php
/**
 * Trigger Demo Reset
 * Manually trigger demo reset to test data population
 */

require_once __DIR__ . '/../api/includes/Database.php';
require_once __DIR__ . '/../api/includes/DemoUserHelper.php';

echo "<h1>Triggering Demo Reset</h1>";

try {
    echo "<p>Starting demo reset...</p>";
    
    // Call the demo reset method
    DemoUserHelper::resetDemoCards();
    
    echo "<p style='color: green;'>✅ Demo reset completed</p>";
    
    // Check results
    $db = Database::getInstance();
    
    $realLeads = $db->query("SELECT COUNT(*) as count FROM leads WHERE id_business_card IN (SELECT id FROM business_cards WHERE user_id = ?)", [DemoUserHelper::DEMO_USER_ID]);
    $realContacts = $db->query("SELECT COUNT(*) as count FROM contacts WHERE id_user = ?", [DemoUserHelper::DEMO_USER_ID]);
    
    echo "<h2>Results</h2>";
    echo "<p><strong>Leads populated:</strong> " . $realLeads[0]['count'] . "</p>";
    echo "<p><strong>Contacts populated:</strong> " . $realContacts[0]['count'] . "</p>";
    
    if ($realLeads[0]['count'] > 0 && $realContacts[0]['count'] > 0) {
        echo "<p style='color: green;'>🎉 SUCCESS: Demo data population is working!</p>";
    } else {
        echo "<p style='color: red;'>❌ FAILED: Demo data not populated</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}
?>
