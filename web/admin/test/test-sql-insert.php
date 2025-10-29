<?php
/**
 * Test SQL Insert
 * Test the exact SQL insert statements to see what's failing
 */

require_once __DIR__ . '/../api/includes/Database.php';
require_once __DIR__ . '/../api/includes/DemoUserHelper.php';

$db = Database::getInstance();

echo "<h1>Test SQL Insert</h1>";

try {
    // Get a demo lead
    $demoLeads = $db->query("SELECT * FROM leads_demo LIMIT 1");
    if (empty($demoLeads)) {
        echo "<p style='color: red;'>❌ No demo leads found</p>";
        exit;
    }
    
    $demoLead = $demoLeads[0];
    echo "<p><strong>Demo Lead:</strong> " . $demoLead['first_name'] . " " . $demoLead['last_name'] . "</p>";
    
    // Get a business card
    $businessCards = $db->query("SELECT id FROM business_cards WHERE user_id = ? LIMIT 1", [DemoUserHelper::DEMO_USER_ID]);
    if (empty($businessCards)) {
        echo "<p style='color: red;'>❌ No business cards found</p>";
        exit;
    }
    
    $cardId = $businessCards[0]['id'];
    echo "<p><strong>Card ID:</strong> " . $cardId . "</p>";
    
    // Test the exact SQL with error reporting
    echo "<h2>Testing Lead Insert</h2>";
    
    $sql = "
        INSERT INTO leads (
            id_business_card, first_name, last_name, email_primary, work_phone, organization_name, job_title,
            comments_from_lead, notes, ip_address, user_agent, referrer, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $params = [
        $cardId, $demoLead['first_name'], $demoLead['last_name'], $demoLead['email'],
        $demoLead['phone'], $demoLead['company'], $demoLead['job_title'], $demoLead['message'],
        $demoLead['notes'], $demoLead['ip_address'], $demoLead['user_agent'], $demoLead['referrer'],
        $demoLead['captured_at']
    ];
    
    echo "<p><strong>SQL:</strong> " . htmlspecialchars($sql) . "</p>";
    echo "<p><strong>Params:</strong> " . print_r($params, true) . "</p>";
    
    try {
        $result = $db->execute($sql, $params);
        echo "<p style='color: green;'>✅ Lead insert successful</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Lead insert failed: " . $e->getMessage() . "</p>";
    }
    
    // Test contact insert
    echo "<h2>Testing Contact Insert</h2>";
    
    $demoContacts = $db->query("SELECT * FROM contacts_demo LIMIT 1");
    if (empty($demoContacts)) {
        echo "<p style='color: red;'>❌ No demo contacts found</p>";
        exit;
    }
    
    $demoContact = $demoContacts[0];
    echo "<p><strong>Demo Contact:</strong> " . $demoContact['first_name'] . " " . $demoContact['last_name'] . "</p>";
    
    $contactSql = "
        INSERT INTO contacts (
            id_user, first_name, last_name, email_primary, work_phone, organization_name, job_title,
            created_at, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $contactParams = [
        DemoUserHelper::DEMO_USER_ID, $demoContact['first_name'], $demoContact['last_name'],
        $demoContact['email'], $demoContact['phone'], $demoContact['company'],
        $demoContact['job_title'], $demoContact['created_at'], $demoContact['notes']
    ];
    
    echo "<p><strong>Contact SQL:</strong> " . htmlspecialchars($contactSql) . "</p>";
    echo "<p><strong>Contact Params:</strong> " . print_r($contactParams, true) . "</p>";
    
    try {
        $result = $db->execute($contactSql, $contactParams);
        echo "<p style='color: green;'>✅ Contact insert successful</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Contact insert failed: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}
?>
