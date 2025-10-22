<?php
/**
 * Test demo system details - check addresses and websites
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/includes/Database.php';
require_once __DIR__ . '/../api/includes/DemoUserHelper.php';

echo "Testing demo system details...\n";

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
        
        // Check addresses
        $addresses = $db->query("SELECT street, city, state, zip_code, country FROM addresses WHERE business_card_id = ?", [$card['id']]);
        if ($addresses) {
            foreach ($addresses as $addr) {
                echo "Address: " . $addr['street'] . ", " . $addr['city'] . ", " . $addr['state'] . " " . $addr['zip_code'] . ", " . $addr['country'] . "\n";
            }
        } else {
            echo "No addresses found\n";
        }
        
        // Check website links
        $websites = $db->query("SELECT name, url FROM website_links WHERE business_card_id = ?", [$card['id']]);
        if ($websites) {
            foreach ($websites as $site) {
                echo "Website: " . $site['name'] . " - " . $site['url'] . "\n";
            }
        } else {
            echo "No websites found\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
