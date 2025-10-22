<?php
/**
 * Manual migration to fix demo_data table
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/includes/Database.php';

echo "Running manual migration...\n";

try {
    $db = Database::getInstance();
    
    // Step 1: Delete all existing demo data
    echo "Step 1: Deleting existing demo data...\n";
    $db->execute("DELETE FROM demo_data");
    echo "✓ Deleted existing demo data\n";
    
    // Step 2: Insert new primary data
    echo "Step 2: Inserting primary demo data...\n";
    
    // Alex Chen
    $db->execute(
        "INSERT INTO demo_data (id, card_id, first_name, last_name, phone_number, company_name, job_title, bio, theme, profile_photo_path, company_logo_path, cover_graphic_path, street, city, state, zip, country, website_name, website_url, website_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
        [
            'demo-data-alex-1',
            'demo-card-alex-chen',
            'Alex',
            'Chen',
            '+1 (555) 123-4567',
            'TechCorp Solutions',
            'Senior Software Engineer',
            'Passionate about building innovative software solutions that make a difference. I love working with cutting-edge technologies and leading development teams to create products that users love.',
            'professional-blue',
            'alex-chen-profile.jpg',
            'techcorp-logo.png',
            'techcorp-cover.jpg',
            '123 Tech Street',
            'San Francisco',
            'CA',
            '94105',
            'United States',
            'Website',
            'https://techcorp.com/',
            'primary'
        ]
    );
    echo "✓ Inserted Alex Chen\n";
    
    // Sarah Martinez
    $db->execute(
        "INSERT INTO demo_data (id, card_id, first_name, last_name, phone_number, company_name, job_title, bio, theme, profile_photo_path, company_logo_path, cover_graphic_path, street, city, state, zip, country, website_name, website_url, website_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
        [
            'demo-data-sarah-1',
            'demo-card-sarah-martinez',
            'Sarah',
            'Martinez',
            '+1 (555) 987-6543',
            'Design Studio Pro',
            'Creative Director',
            'Creative visionary with 8+ years of experience in digital design and brand strategy. I specialize in creating compelling visual narratives that connect brands with their audiences.',
            'creative-sunset',
            'sarah-martinez-profile.jpg',
            'designstudio-logo.png',
            'designstudio-cover.jpg',
            '456 Design Avenue',
            'Los Angeles',
            'CA',
            '90210',
            'United States',
            'Website',
            'https://designstudiopro.com/',
            'primary'
        ]
    );
    echo "✓ Inserted Sarah Martinez\n";
    
    // Michael Thompson
    $db->execute(
        "INSERT INTO demo_data (id, card_id, first_name, last_name, phone_number, company_name, job_title, bio, theme, profile_photo_path, company_logo_path, cover_graphic_path, street, city, state, zip, country, website_name, website_url, website_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
        [
            'demo-data-michael-1',
            'demo-card-michael-thompson',
            'Michael',
            'Thompson',
            '+1 (555) 456-7890',
            'Innovation Ventures',
            'CEO & Founder',
            'Serial entrepreneur and innovation strategist with a track record of building successful startups. I focus on identifying market opportunities and building teams that can execute on bold visions.',
            'minimalist-gray',
            'michael-thompson-profile.jpg',
            'innovation-logo.png',
            'innovation-cover.jpg',
            '789 Innovation Drive',
            'Austin',
            'TX',
            '73301',
            'United States',
            'Website',
            'https://innovationventures.com/',
            'primary'
        ]
    );
    echo "✓ Inserted Michael Thompson\n";
    
    // Verify the data
    echo "\nVerifying data...\n";
    $data = $db->query("SELECT first_name, last_name, company_name, website_url FROM demo_data WHERE website_type = 'primary'");
    foreach ($data as $row) {
        echo "- " . $row['first_name'] . " " . $row['last_name'] . " (" . $row['company_name'] . ") - " . $row['website_url'] . "\n";
    }
    
    echo "\n✅ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
