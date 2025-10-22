<?php
/**
 * Fix demo image filenames to match actual files in storage
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/includes/Database.php';

echo "Fixing demo image filenames...\n";

try {
    $db = Database::getInstance();
    
    // Update demo_data table with correct filenames
    echo "Updating demo_data table...\n";
    
    // Alex Chen
    $db->execute(
        "UPDATE demo_data SET profile_photo_path = ?, company_logo_path = ?, cover_graphic_path = ? WHERE card_id = ?",
        ['demo-alex-profile.jpg', 'demo-techcorp-logo.jpg', 'demo-techcorp-cover.jpg', 'demo-card-alex-chen']
    );
    echo "✓ Updated Alex Chen images\n";
    
    // Sarah Martinez
    $db->execute(
        "UPDATE demo_data SET profile_photo_path = ?, company_logo_path = ?, cover_graphic_path = ? WHERE card_id = ?",
        ['demo-sarah-profile.jpg', 'demo-designstudio-logo.jpg', 'demo-designstudio-cover.jpg', 'demo-card-sarah-martinez']
    );
    echo "✓ Updated Sarah Martinez images\n";
    
    // Michael Thompson
    $db->execute(
        "UPDATE demo_data SET profile_photo_path = ?, company_logo_path = ?, cover_graphic_path = ? WHERE card_id = ?",
        ['demo-michael-profile.jpg', 'demo-innovation-logo.jpg', 'demo-innovation-cover.jpg', 'demo-card-michael-thompson']
    );
    echo "✓ Updated Michael Thompson images\n";
    
    // Update business_cards table with correct filenames
    echo "\nUpdating business_cards table...\n";
    
    // Alex Chen
    $db->execute(
        "UPDATE business_cards SET profile_photo_path = ?, company_logo_path = ?, cover_graphic_path = ?, profile_photo = ?, company_logo = ?, cover_graphic = ? WHERE id = ?",
        ['demo-alex-profile.jpg', 'demo-techcorp-logo.jpg', 'demo-techcorp-cover.jpg', 'demo-alex-profile.jpg', 'demo-techcorp-logo.jpg', 'demo-techcorp-cover.jpg', 'demo-card-alex-chen']
    );
    echo "✓ Updated Alex Chen business card images\n";
    
    // Sarah Martinez
    $db->execute(
        "UPDATE business_cards SET profile_photo_path = ?, company_logo_path = ?, cover_graphic_path = ?, profile_photo = ?, company_logo = ?, cover_graphic = ? WHERE id = ?",
        ['demo-sarah-profile.jpg', 'demo-designstudio-logo.jpg', 'demo-designstudio-cover.jpg', 'demo-sarah-profile.jpg', 'demo-designstudio-logo.jpg', 'demo-designstudio-cover.jpg', 'demo-card-sarah-martinez']
    );
    echo "✓ Updated Sarah Martinez business card images\n";
    
    // Michael Thompson
    $db->execute(
        "UPDATE business_cards SET profile_photo_path = ?, company_logo_path = ?, cover_graphic_path = ?, profile_photo = ?, company_logo = ?, cover_graphic = ? WHERE id = ?",
        ['demo-michael-profile.jpg', 'demo-innovation-logo.jpg', 'demo-innovation-cover.jpg', 'demo-michael-profile.jpg', 'demo-innovation-logo.jpg', 'demo-innovation-cover.jpg', 'demo-card-michael-thompson']
    );
    echo "✓ Updated Michael Thompson business card images\n";
    
    // Verify the changes
    echo "\nVerifying changes...\n";
    $cards = $db->query("SELECT first_name, last_name, profile_photo_path, company_logo_path, cover_graphic_path FROM business_cards WHERE user_id = 'demo-user-uuid-fixed'");
    
    foreach ($cards as $row) {
        echo "\n--- " . $row['first_name'] . " " . $row['last_name'] . " ---\n";
        echo "Profile Photo: " . $row['profile_photo_path'] . "\n";
        echo "Company Logo: " . $row['company_logo_path'] . "\n";
        echo "Cover Graphic: " . $row['cover_graphic_path'] . "\n";
    }
    
    echo "\n✅ Demo image filenames fixed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
