<?php
/**
 * Check Demo Data Status
 * Simple check of current demo data status
 */

require_once __DIR__ . '/../api/includes/Database.php';
require_once __DIR__ . '/../api/includes/DemoUserHelper.php';

$db = Database::getInstance();

echo "<h1>Demo Data Status Check</h1>";

try {
    // Check demo tables
    echo "<h2>📊 Demo Tables</h2>";
    $demoLeads = $db->query("SELECT COUNT(*) as count FROM leads_demo");
    $demoContacts = $db->query("SELECT COUNT(*) as count FROM contacts_demo");
    echo "<p><strong>Demo Leads:</strong> " . $demoLeads[0]['count'] . "</p>";
    echo "<p><strong>Demo Contacts:</strong> " . $demoContacts[0]['count'] . "</p>";
    
    // Check real tables
    echo "<h2>📋 Real Tables</h2>";
    $realLeads = $db->query("SELECT COUNT(*) as count FROM leads WHERE id_business_card IN (SELECT id FROM business_cards WHERE user_id = ?)", [DemoUserHelper::DEMO_USER_ID]);
    $realContacts = $db->query("SELECT COUNT(*) as count FROM contacts WHERE id_user = ?", [DemoUserHelper::DEMO_USER_ID]);
    echo "<p><strong>Real Leads:</strong> " . $realLeads[0]['count'] . "</p>";
    echo "<p><strong>Real Contacts:</strong> " . $realContacts[0]['count'] . "</p>";
    
    // Check business cards
    echo "<h2>💳 Business Cards</h2>";
    $businessCards = $db->query("SELECT COUNT(*) as count FROM business_cards WHERE user_id = ?", [DemoUserHelper::DEMO_USER_ID]);
    echo "<p><strong>Business Cards:</strong> " . $businessCards[0]['count'] . "</p>";
    
    if ($businessCards[0]['count'] > 0) {
        $cards = $db->query("SELECT id, first_name, last_name, company_name FROM business_cards WHERE user_id = ?", [DemoUserHelper::DEMO_USER_ID]);
        echo "<p><strong>Card Details:</strong></p>";
        echo "<ul>";
        foreach ($cards as $card) {
            echo "<li>" . htmlspecialchars($card['first_name'] . ' ' . $card['last_name'] . ' (' . $card['company_name'] . ')') . " - ID: " . $card['id'] . "</li>";
        }
        echo "</ul>";
    }
    
    // Check if demo data population is working
    echo "<h2>🎯 Status</h2>";
    if ($realLeads[0]['count'] > 0 && $realContacts[0]['count'] > 0) {
        echo "<p style='color: green;'>✅ Demo data is populated in real tables</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Demo data not yet populated in real tables</p>";
        echo "<p>This is normal if demo user hasn't logged in yet.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
