<?php
/**
 * Run migration 023_add_image_paths_to_demo_data.sql
 * Adds image path columns to demo_data table and populates with correct filenames
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/includes/Database.php';

try {
    $db = Database::getInstance();
    
    // Read and execute the migration
    $migrationSql = file_get_contents(__DIR__ . '/../config/migrations/023_add_image_paths_to_demo_data.sql');
    
    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $migrationSql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $db->execute($statement);
            echo "Executed: " . substr($statement, 0, 80) . "...\n";
        }
    }
    
    echo "\n✅ Migration 023_add_image_paths_to_demo_data.sql executed successfully.\n";
    echo "Demo data table now has image path columns with correct filenames.\n";
    
    // Verify the data was updated correctly
    $demoData = $db->query("SELECT card_id, first_name, last_name, profile_photo_path, company_logo_path, cover_graphic_path FROM demo_data WHERE website_type = 'primary'");
    
    echo "\n📊 Updated demo data:\n";
    foreach ($demoData as $row) {
        echo "- {$row['first_name']} {$row['last_name']}: Profile={$row['profile_photo_path']}, Logo={$row['company_logo_path']}, Cover={$row['cover_graphic_path']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error running migration: " . $e->getMessage() . "\n";
    exit(1);
}
?>
