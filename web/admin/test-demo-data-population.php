<?php
/**
 * Test Demo Data Population
 * Tests that demo leads and contacts are properly populated
 */

require_once __DIR__ . '/../api/includes/Database.php';
require_once __DIR__ . '/../api/includes/DemoUserHelper.php';

$db = Database::getInstance();

echo "<h1>Testing Demo Data Population</h1>";

try {
    // Check current demo data in demo tables
    echo "<h2>📊 Demo Tables Data</h2>";
    
    $demoLeads = $db->query("SELECT COUNT(*) as count FROM leads_demo");
    $demoContacts = $db->query("SELECT COUNT(*) as count FROM contacts_demo");
    
    echo "<p><strong>Demo Leads:</strong> " . $demoLeads[0]['count'] . "</p>";
    echo "<p><strong>Demo Contacts:</strong> " . $demoContacts[0]['count'] . "</p>";
    
    // Check current real tables data
    echo "<h2>📋 Real Tables Data (Before Reset)</h2>";
    
    $realLeads = $db->query("SELECT COUNT(*) as count FROM leads WHERE id_business_card IN (SELECT id FROM business_cards WHERE user_id = ?)", [DemoUserHelper::DEMO_USER_ID]);
    $realContacts = $db->query("SELECT COUNT(*) as count FROM contacts WHERE id_user = ?", [DemoUserHelper::DEMO_USER_ID]);
    
    echo "<p><strong>Real Leads:</strong> " . $realLeads[0]['count'] . "</p>";
    echo "<p><strong>Real Contacts:</strong> " . $realContacts[0]['count'] . "</p>";
    
    // Trigger demo reset (simulate demo user login)
    echo "<h2>🔄 Triggering Demo Reset</h2>";
    echo "<p>Calling DemoUserHelper::resetDemoCards()...</p>";
    
    DemoUserHelper::resetDemoCards();
    
    echo "<p style='color: green;'>✅ Demo reset completed</p>";
    
    // Check real tables data after reset
    echo "<h2>📊 Real Tables Data (After Reset)</h2>";
    
    $realLeadsAfter = $db->query("SELECT COUNT(*) as count FROM leads WHERE id_business_card IN (SELECT id FROM business_cards WHERE user_id = ?)", [DemoUserHelper::DEMO_USER_ID]);
    $realContactsAfter = $db->query("SELECT COUNT(*) as count FROM contacts WHERE id_user = ?", [DemoUserHelper::DEMO_USER_ID]);
    
    echo "<p><strong>Real Leads:</strong> " . $realLeadsAfter[0]['count'] . "</p>";
    echo "<p><strong>Real Contacts:</strong> " . $realContactsAfter[0]['count'] . "</p>";
    
    // Show sample data
    echo "<h2>📋 Sample Leads Data</h2>";
    $sampleLeads = $db->query("
        SELECT l.first_name, l.last_name, l.email, l.company, bc.first_name as card_owner, bc.last_name as card_owner_last
        FROM leads l
        JOIN business_cards bc ON l.id_business_card = bc.id
        WHERE bc.user_id = ?
        LIMIT 5
    ", [DemoUserHelper::DEMO_USER_ID]);
    
    if (!empty($sampleLeads)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Lead Name</th><th>Email</th><th>Company</th><th>Card Owner</th></tr>";
        foreach ($sampleLeads as $lead) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($lead['first_name'] . ' ' . $lead['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($lead['email']) . "</td>";
            echo "<td>" . htmlspecialchars($lead['company']) . "</td>";
            echo "<td>" . htmlspecialchars($lead['card_owner'] . ' ' . $lead['card_owner_last']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No leads found</p>";
    }
    
    echo "<h2>👥 Sample Contacts Data</h2>";
    $sampleContacts = $db->query("
        SELECT first_name, last_name, email, company, source, created_at
        FROM contacts
        WHERE id_user = ?
        LIMIT 5
    ", [DemoUserHelper::DEMO_USER_ID]);
    
    if (!empty($sampleContacts)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Contact Name</th><th>Email</th><th>Company</th><th>Source</th><th>Created</th></tr>";
        foreach ($sampleContacts as $contact) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($contact['email']) . "</td>";
            echo "<td>" . htmlspecialchars($contact['company']) . "</td>";
            echo "<td>" . htmlspecialchars($contact['source']) . "</td>";
            echo "<td>" . htmlspecialchars($contact['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No contacts found</p>";
    }
    
    // Test results
    echo "<h2>🎯 Test Results</h2>";
    
    $leadsCount = $realLeadsAfter[0]['count'];
    $contactsCount = $realContactsAfter[0]['count'];
    
    if ($leadsCount > 0 && $contactsCount > 0) {
        echo "<p style='color: green;'>✅ SUCCESS: Demo data population working correctly!</p>";
        echo "<ul>";
        echo "<li>✅ " . $leadsCount . " leads populated</li>";
        echo "<li>✅ " . $contactsCount . " contacts populated</li>";
        echo "<li>✅ Data properly linked to business cards</li>";
        echo "<li>✅ Demo reset functionality working</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ FAILED: Demo data population not working</p>";
        echo "<ul>";
        echo "<li>❌ Leads count: " . $leadsCount . "</li>";
        echo "<li>❌ Contacts count: " . $contactsCount . "</li>";
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Test failed: " . $e->getMessage() . "</p>";
    echo "<p>Error details: " . $e->getTraceAsString() . "</p>";
}
?>
