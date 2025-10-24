<?php
/**
 * Debug Demo Mapping
 * Check the business card mapping issue
 */

require_once __DIR__ . '/../api/includes/Database.php';
require_once __DIR__ . '/../api/includes/DemoUserHelper.php';

$db = Database::getInstance();

echo "<h1>Debug Demo Mapping</h1>";

try {
    // Check business cards
    echo "<h2>💳 Business Cards</h2>";
    $businessCards = $db->query("SELECT id, first_name, last_name, company_name FROM business_cards WHERE user_id = ?", [DemoUserHelper::DEMO_USER_ID]);
    
    echo "<p><strong>Found " . count($businessCards) . " business cards:</strong></p>";
    echo "<ul>";
    foreach ($businessCards as $card) {
        echo "<li><strong>ID:</strong> " . htmlspecialchars($card['id']) . "</li>";
        echo "<li><strong>Name:</strong> " . htmlspecialchars($card['first_name'] . ' ' . $card['last_name']) . "</li>";
        echo "<li><strong>Company:</strong> " . htmlspecialchars($card['company_name']) . "</li>";
        echo "<br>";
    }
    echo "</ul>";
    
    // Check demo leads mapping
    echo "<h2>📋 Demo Leads Mapping</h2>";
    $demoLeads = $db->query("SELECT demo_business_card_id, first_name, last_name FROM leads_demo LIMIT 3");
    
    echo "<p><strong>Demo leads business card IDs:</strong></p>";
    echo "<ul>";
    foreach ($demoLeads as $lead) {
        echo "<li><strong>Demo Card ID:</strong> " . htmlspecialchars($lead['demo_business_card_id']) . "</li>";
        echo "<li><strong>Lead Name:</strong> " . htmlspecialchars($lead['first_name'] . ' ' . $lead['last_name']) . "</li>";
        echo "<br>";
    }
    echo "</ul>";
    
    // Test the mapping logic
    echo "<h2>🔗 Mapping Test</h2>";
    $cardMapping = [];
    foreach ($businessCards as $card) {
        $cardMapping[$card['first_name'] . ' ' . $card['last_name']] = $card['id'];
    }
    
    echo "<p><strong>Card mapping:</strong></p>";
    echo "<pre>" . print_r($cardMapping, true) . "</pre>";
    
    // Test specific mappings
    $testMappings = [
        'demo-card-1-uuid' => 'Alex Chen',
        'demo-card-2-uuid' => 'Sarah Martinez', 
        'demo-card-3-uuid' => 'Michael Thompson'
    ];
    
    echo "<p><strong>Test mappings:</strong></p>";
    foreach ($testMappings as $demoId => $name) {
        $actualId = $cardMapping[$name] ?? 'NOT FOUND';
        echo "<p><strong>$demoId</strong> → <strong>$name</strong> → <strong>$actualId</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
