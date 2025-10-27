<?php
/**
 * Test Demo Email Generation
 * Tests the demo data generation process with email addresses
 */

require_once __DIR__ . '/../api/includes/Database.php';
require_once __DIR__ . '/../api/includes/DemoUserHelper.php';

$db = Database::getInstance();

echo "<h1>Test Demo Email Generation</h1>";

try {
    // First, ensure email_primary is populated in demo_data
    echo "<h2>Step 1: Check Demo Data Email Primary</h2>";
    $demoData = $db->query("SELECT first_name, last_name, email_primary, website_url FROM demo_data WHERE website_type = 'primary' ORDER BY first_name");
    
    if (empty($demoData)) {
        echo "<p style='color: red;'>❌ No demo data found. Please run the populate script first.</p>";
        exit;
    }
    
    echo "<p><strong>Demo Data Records:</strong></p>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Name</th><th>Email Primary</th><th>Website URL</th></tr>";
    
    foreach ($demoData as $record) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($record['email_primary']) . "</td>";
        echo "<td>" . htmlspecialchars($record['website_url']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test demo card generation
    echo "<h2>Step 2: Test Demo Card Generation</h2>";
    echo "<p>Generating demo cards with email addresses...</p>";
    
    // Clear existing demo cards first
    $db->execute("DELETE FROM email_contacts WHERE business_card_id IN (SELECT id FROM business_cards WHERE user_id = ?)", [DemoUserHelper::DEMO_USER_ID]);
    $db->execute("DELETE FROM business_cards WHERE user_id = ?", [DemoUserHelper::DEMO_USER_ID]);
    
    // Generate demo cards
    DemoUserHelper::ensureDemoCards();
    
    // Check generated business cards
    echo "<p><strong>Generated Business Cards:</strong></p>";
    $businessCards = $db->query("SELECT id, first_name, last_name FROM business_cards WHERE user_id = ? ORDER BY first_name", [DemoUserHelper::DEMO_USER_ID]);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Card ID</th><th>Name</th></tr>";
    
    foreach ($businessCards as $card) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($card['id']) . "</td>";
        echo "<td>" . htmlspecialchars($card['first_name'] . ' ' . $card['last_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check generated email contacts
    echo "<p><strong>Generated Email Contacts:</strong></p>";
    $emailContacts = $db->query("
        SELECT ec.email, ec.type, ec.label, ec.is_primary, bc.first_name, bc.last_name 
        FROM email_contacts ec 
        JOIN business_cards bc ON ec.business_card_id = bc.id 
        WHERE bc.user_id = ? 
        ORDER BY bc.first_name, ec.is_primary DESC
    ", [DemoUserHelper::DEMO_USER_ID]);
    
    if (empty($emailContacts)) {
        echo "<p style='color: red;'>❌ No email contacts found!</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Name</th><th>Email</th><th>Type</th><th>Label</th><th>Primary</th></tr>";
        
        foreach ($emailContacts as $contact) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($contact['email']) . "</td>";
            echo "<td>" . htmlspecialchars($contact['type']) . "</td>";
            echo "<td>" . htmlspecialchars($contact['label']) . "</td>";
            echo "<td>" . ($contact['is_primary'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test vCard generation
    echo "<h2>Step 3: Test vCard Generation</h2>";
    if (!empty($businessCards)) {
        $cardId = $businessCards[0]['id'];
        echo "<p>Testing vCard generation for: " . htmlspecialchars($businessCards[0]['first_name'] . ' ' . $businessCards[0]['last_name']) . "</p>";
        
        // Get vCard data
        $vcardUrl = "https://sharemycard.app/vcard.php?id=" . urlencode($cardId);
        echo "<p><strong>vCard URL:</strong> <a href='$vcardUrl' target='_blank'>$vcardUrl</a></p>";
        
        // Test QR code generation
        $qrUrl = "https://sharemycard.app/user/cards/qr.php?id=" . urlencode($cardId);
        echo "<p><strong>QR Code URL:</strong> <a href='$qrUrl' target='_blank'>$qrUrl</a></p>";
    }
    
    echo "<p style='color: green;'>🎉 Demo email generation test completed!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Error details: " . $e->getTraceAsString() . "</p>";
}
?>
