<?php
/**
 * Test demo system to see what's happening
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/includes/Database.php';
require_once __DIR__ . '/../api/includes/DemoUserHelper.php';

echo "Testing demo system...\n";

try {
    $db = Database::getInstance();
    
    // Check if demo_data table exists and has data
    $demoData = $db->query("SELECT COUNT(*) as count FROM demo_data");
    echo "Demo data table has " . $demoData[0]['count'] . " records\n";
    
    // Check if demo user exists
    $demoUser = $db->querySingle("SELECT id, email FROM users WHERE email = 'demo@sharemycard.app'");
    if ($demoUser) {
        echo "Demo user exists: " . $demoUser['email'] . "\n";
    } else {
        echo "Demo user does not exist!\n";
    }
    
    // Try to call ensureDemoCards
    echo "Calling ensureDemoCards...\n";
    DemoUserHelper::ensureDemoCards();
    echo "ensureDemoCards completed\n";
    
    // Check how many cards were created
    $cardCount = $db->querySingle("SELECT COUNT(*) as count FROM business_cards WHERE user_id = ?", [DemoUserHelper::DEMO_USER_ID])['count'];
    echo "Demo user now has " . $cardCount . " business cards\n";
    
    if ($cardCount > 0) {
        $cards = $db->query("SELECT id, first_name, last_name, company_name FROM business_cards WHERE user_id = ?", [DemoUserHelper::DEMO_USER_ID]);
        foreach ($cards as $card) {
            echo "- " . $card['first_name'] . " " . $card['last_name'] . " (" . $card['company_name'] . ")\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
