<?php
/**
 * Check what's actually in the database
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/includes/Database.php';

echo "Checking actual database data...\n";

try {
    $db = Database::getInstance();
    
    // Get demo user ID
    $demoUser = $db->querySingle("SELECT id FROM users WHERE email = 'demo@sharemycard.app'");
    if (!$demoUser) {
        echo "Demo user not found!\n";
        exit(1);
    }
    
    $demoUserId = $demoUser['id'];
    echo "Demo user ID: $demoUserId\n";
    
    // Get business cards
    $cards = $db->query("SELECT id, first_name, last_name, company_name FROM business_cards WHERE user_id = ?", [$demoUserId]);
    echo "Found " . count($cards) . " business cards:\n";
    
    foreach ($cards as $card) {
        echo "\n--- " . $card['first_name'] . " " . $card['last_name'] . " (" . $card['company_name'] . ") ---\n";
        echo "Card ID: " . $card['id'] . "\n";
        
        // Check addresses
        $addresses = $db->query("SELECT * FROM addresses WHERE business_card_id = ?", [$card['id']]);
        echo "Addresses found: " . count($addresses) . "\n";
        foreach ($addresses as $addr) {
            echo "  - " . $addr['street'] . ", " . $addr['city'] . ", " . $addr['state'] . " " . $addr['zip_code'] . ", " . $addr['country'] . "\n";
        }
        
        // Check website links
        $websites = $db->query("SELECT * FROM website_links WHERE business_card_id = ?", [$card['id']]);
        echo "Websites found: " . count($websites) . "\n";
        foreach ($websites as $site) {
            echo "  - " . $site['name'] . " - " . $site['url'] . " (primary: " . $site['is_primary'] . ")\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
