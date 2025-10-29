<?php
/**
 * Test Simple Population
 * Test just the population part without the full demo reset
 */

require_once __DIR__ . '/../api/includes/Database.php';
require_once __DIR__ . '/../api/includes/DemoUserHelper.php';

$db = Database::getInstance();

echo "<h1>Test Simple Population</h1>";

try {
    // Get demo leads
    $demoLeads = $db->query("SELECT * FROM leads_demo LIMIT 3");
    echo "<p><strong>Found " . count($demoLeads) . " demo leads</strong></p>";
    
    // Get business cards
    $businessCards = $db->query("SELECT id, first_name, last_name FROM business_cards WHERE user_id = ?", [DemoUserHelper::DEMO_USER_ID]);
    echo "<p><strong>Found " . count($businessCards) . " business cards</strong></p>";
    
    if (empty($businessCards)) {
        echo "<p style='color: red;'>❌ No business cards found</p>";
        exit;
    }
    
    // Use the first business card for all leads
    $cardId = $businessCards[0]['id'];
    echo "<p><strong>Using card ID:</strong> " . $cardId . "</p>";
    
    // Insert a few test leads
    $inserted = 0;
    foreach ($demoLeads as $demoLead) {
        try {
            $db->execute("
                INSERT INTO leads (
                    id_business_card, first_name, last_name, email, phone, company, job_title,
                    message, source, status, captured_at, ip_address, user_agent, referrer
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                $cardId, $demoLead['first_name'], $demoLead['last_name'], $demoLead['email'],
                $demoLead['phone'], $demoLead['company'], $demoLead['job_title'], $demoLead['message'],
                $demoLead['source'], $demoLead['status'], $demoLead['captured_at'],
                $demoLead['ip_address'], $demoLead['user_agent'], $demoLead['referrer']
            ]);
            $inserted++;
            echo "<p>✅ Inserted lead: " . $demoLead['first_name'] . " " . $demoLead['last_name'] . "</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Failed to insert lead: " . $e->getMessage() . "</p>";
        }
    }
    
    // Insert a few test contacts
    $demoContacts = $db->query("SELECT * FROM contacts_demo LIMIT 3");
    echo "<p><strong>Found " . count($demoContacts) . " demo contacts</strong></p>";
    
    $contactInserted = 0;
    foreach ($demoContacts as $demoContact) {
        try {
            $db->execute("
                INSERT INTO contacts (
                    id_user, first_name, last_name, email, phone, company, job_title,
                    source, created_at, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                DemoUserHelper::DEMO_USER_ID, $demoContact['first_name'], $demoContact['last_name'],
                $demoContact['email'], $demoContact['phone'], $demoContact['company'],
                $demoContact['job_title'], $demoContact['source'], $demoContact['created_at'],
                $demoContact['notes']
            ]);
            $contactInserted++;
            echo "<p>✅ Inserted contact: " . $demoContact['first_name'] . " " . $demoContact['last_name'] . "</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Failed to insert contact: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2>🎯 Results</h2>";
    echo "<p><strong>Leads inserted:</strong> $inserted</p>";
    echo "<p><strong>Contacts inserted:</strong> $contactInserted</p>";
    
    if ($inserted > 0 && $contactInserted > 0) {
        echo "<p style='color: green;'>🎉 SUCCESS: Demo data population is working!</p>";
    } else {
        echo "<p style='color: red;'>❌ FAILED: Demo data not populated</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}
?>
