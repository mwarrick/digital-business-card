<?php
/**
 * Check what demo images are being referenced
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/includes/Database.php';

echo "Checking demo image references...\n";

try {
    $db = Database::getInstance();
    
    // Get demo data with image paths
    $demoData = $db->query("SELECT first_name, last_name, profile_photo_path, company_logo_path, cover_graphic_path FROM demo_data WHERE website_type = 'primary'");
    
    foreach ($demoData as $row) {
        echo "\n--- " . $row['first_name'] . " " . $row['last_name'] . " ---\n";
        echo "Profile Photo: " . $row['profile_photo_path'] . "\n";
        echo "Company Logo: " . $row['company_logo_path'] . "\n";
        echo "Cover Graphic: " . $row['cover_graphic_path'] . "\n";
    }
    
    // Check what's actually in the business_cards table
    echo "\n--- Business Cards Table ---\n";
    $cards = $db->query("SELECT first_name, last_name, profile_photo_path, company_logo_path, cover_graphic_path FROM business_cards WHERE user_id = 'demo-user-uuid-fixed'");
    
    foreach ($cards as $row) {
        echo "\n--- " . $row['first_name'] . " " . $row['last_name'] . " ---\n";
        echo "Profile Photo: " . $row['profile_photo_path'] . "\n";
        echo "Company Logo: " . $row['company_logo_path'] . "\n";
        echo "Cover Graphic: " . $row['cover_graphic_path'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
