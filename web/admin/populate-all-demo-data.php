<?php
/**
 * Populate All Demo Data
 * Manually populate all demo leads and contacts
 */

require_once __DIR__ . '/../api/includes/Database.php';
require_once __DIR__ . '/../api/includes/DemoUserHelper.php';

$db = Database::getInstance();

echo "<h1>Populate All Demo Data</h1>";

try {
    // Clear existing data first
    echo "<p>Clearing existing demo data...</p>";
    $db->execute("DELETE FROM leads WHERE id_business_card IN (SELECT id FROM business_cards WHERE user_id = ?)", [DemoUserHelper::DEMO_USER_ID]);
    $db->execute("DELETE FROM contacts WHERE id_user = ?", [DemoUserHelper::DEMO_USER_ID]);
    echo "<p>✅ Cleared existing data</p>";
    
    // Get all demo leads
    $demoLeads = $db->query("SELECT * FROM leads_demo");
    echo "<p><strong>Found " . count($demoLeads) . " demo leads</strong></p>";
    
    // Get business cards
    $businessCards = $db->query("SELECT id, first_name, last_name FROM business_cards WHERE user_id = ?", [DemoUserHelper::DEMO_USER_ID]);
    $cardMapping = [];
    foreach ($businessCards as $card) {
        $cardMapping[$card['first_name'] . ' ' . $card['last_name']] = $card['id'];
    }
    echo "<p><strong>Business card mapping:</strong> " . print_r($cardMapping, true) . "</p>";
    
    // Insert all leads
    $leadsInserted = 0;
    foreach ($demoLeads as $demoLead) {
        // Use the first available card for all leads
        $cardId = $businessCards[0]['id'];
        
        try {
            $db->execute("
                INSERT INTO leads (
                    id_business_card, first_name, last_name, email_primary, work_phone, organization_name, job_title,
                    comments_from_lead, notes, ip_address, user_agent, referrer, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                $cardId, $demoLead['first_name'], $demoLead['last_name'], $demoLead['email'],
                $demoLead['phone'], $demoLead['company'], $demoLead['job_title'], $demoLead['message'],
                $demoLead['notes'], $demoLead['ip_address'], $demoLead['user_agent'], $demoLead['referrer'],
                $demoLead['captured_at']
            ]);
            $leadsInserted++;
            echo "<p>✅ Inserted lead: " . $demoLead['first_name'] . " " . $demoLead['last_name'] . "</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Failed to insert lead " . $demoLead['first_name'] . ": " . $e->getMessage() . "</p>";
        }
    }
    
    // Get all demo contacts
    $demoContacts = $db->query("SELECT * FROM contacts_demo");
    echo "<p><strong>Found " . count($demoContacts) . " demo contacts</strong></p>";
    
    // Insert all contacts
    $contactsInserted = 0;
    foreach ($demoContacts as $demoContact) {
        try {
            $db->execute("
                INSERT INTO contacts (
                    id_user, first_name, last_name, email_primary, work_phone, organization_name, job_title,
                    created_at, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                DemoUserHelper::DEMO_USER_ID, $demoContact['first_name'], $demoContact['last_name'],
                $demoContact['email'], $demoContact['phone'], $demoContact['company'],
                $demoContact['job_title'], $demoContact['created_at'], $demoContact['notes']
            ]);
            $contactsInserted++;
            echo "<p>✅ Inserted contact: " . $demoContact['first_name'] . " " . $demoContact['last_name'] . "</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Failed to insert contact " . $demoContact['first_name'] . ": " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2>🎯 Results</h2>";
    echo "<p><strong>Leads inserted:</strong> $leadsInserted</p>";
    echo "<p><strong>Contacts inserted:</strong> $contactsInserted</p>";
    
    if ($leadsInserted > 0 && $contactsInserted > 0) {
        echo "<p style='color: green;'>🎉 SUCCESS: Demo data population is working!</p>";
    } else {
        echo "<p style='color: red;'>❌ FAILED: Demo data not populated</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}
?>
