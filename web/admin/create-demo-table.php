<?php
/**
 * Create demo_data table manually
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/includes/Database.php';

try {
    $db = Database::getInstance();
    
    // Drop table if exists
    $db->execute("DROP TABLE IF EXISTS demo_data");
    echo "Dropped existing demo_data table\n";
    
    // Create table
    $db->execute("CREATE TABLE demo_data (
        id VARCHAR(36) PRIMARY KEY,
        card_id VARCHAR(36) NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        phone_number VARCHAR(20) NOT NULL,
        company_name VARCHAR(200),
        job_title VARCHAR(200),
        bio TEXT,
        theme VARCHAR(50),
        profile_photo_path VARCHAR(255),
        company_logo_path VARCHAR(255),
        cover_graphic_path VARCHAR(255),
        street VARCHAR(255),
        city VARCHAR(100),
        state VARCHAR(100),
        zip VARCHAR(20),
        country VARCHAR(100),
        website_name VARCHAR(100),
        website_url VARCHAR(500),
        website_type ENUM('primary', 'linkedin', 'other') DEFAULT 'other',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_card_id (card_id)
    )");
    echo "Created demo_data table\n";
    
    // Insert Alex Chen data
    $db->execute("INSERT INTO demo_data (id, card_id, first_name, last_name, phone_number, company_name, job_title, bio, theme, profile_photo_path, company_logo_path, cover_graphic_path, street, city, state, zip, country, website_name, website_url, website_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
        'demo-data-1-uuid', 'demo-card-1-uuid', 'Alex', 'Chen', '+1 (555) 123-4567', 'TechCorp Solutions', 'Senior Software Engineer', 'Passionate full-stack developer with expertise in React, Node.js, and cloud architecture. Building scalable solutions that make a difference.', 'professional-blue', 'demo-alex-profile.jpg', 'demo-techcorp-logo.jpg', 'demo-techcorp-cover.jpg', '1234 Market Street, Suite 500', 'San Francisco', 'CA', '94105', 'United States', 'Company Website', 'https://techcorp.com/', 'primary'
    ]);
    echo "Inserted Alex Chen primary data\n";
    
    $db->execute("INSERT INTO demo_data (id, card_id, first_name, last_name, phone_number, company_name, job_title, bio, theme, profile_photo_path, company_logo_path, cover_graphic_path, street, city, state, zip, country, website_name, website_url, website_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
        'demo-data-1-linkedin', 'demo-card-1-uuid', 'Alex', 'Chen', '+1 (555) 123-4567', 'TechCorp Solutions', 'Senior Software Engineer', 'Passionate full-stack developer with expertise in React, Node.js, and cloud architecture. Building scalable solutions that make a difference.', 'professional-blue', 'demo-alex-profile.jpg', 'demo-techcorp-logo.jpg', 'demo-techcorp-cover.jpg', '1234 Market Street, Suite 500', 'San Francisco', 'CA', '94105', 'United States', 'LinkedIn Profile', 'https://www.linkedin.com/in/alex-chen-69a94aa1/', 'linkedin'
    ]);
    echo "Inserted Alex Chen LinkedIn data\n";
    
    // Insert Sarah Martinez data
    $db->execute("INSERT INTO demo_data (id, card_id, first_name, last_name, phone_number, company_name, job_title, bio, theme, profile_photo_path, company_logo_path, cover_graphic_path, street, city, state, zip, country, website_name, website_url, website_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
        'demo-data-2-uuid', 'demo-card-2-uuid', 'Sarah', 'Martinez', '+1 (555) 987-6543', 'Design Studio Pro', 'Creative Director', 'Award-winning designer with expertise in brand identity, UI/UX design, and creative direction. Helping businesses tell their story through compelling visual design.', 'creative-sunset', 'demo-sarah-profile.jpg', 'demo-designstudio-logo.jpg', 'demo-designstudio-cover.jpg', '567 Broadway, Floor 12', 'New York', 'NY', '10012', 'United States', 'Portfolio Website', 'https://www.saramartinezdesign.com/', 'primary'
    ]);
    echo "Inserted Sarah Martinez primary data\n";
    
    $db->execute("INSERT INTO demo_data (id, card_id, first_name, last_name, phone_number, company_name, job_title, bio, theme, profile_photo_path, company_logo_path, cover_graphic_path, street, city, state, zip, country, website_name, website_url, website_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
        'demo-data-2-linkedin', 'demo-card-2-uuid', 'Sarah', 'Martinez', '+1 (555) 987-6543', 'Design Studio Pro', 'Creative Director', 'Award-winning designer with expertise in brand identity, UI/UX design, and creative direction. Helping businesses tell their story through compelling visual design.', 'creative-sunset', 'demo-sarah-profile.jpg', 'demo-designstudio-logo.jpg', 'demo-designstudio-cover.jpg', '567 Broadway, Floor 12', 'New York', 'NY', '10012', 'United States', 'LinkedIn Profile', 'https://www.linkedin.com/in/sara-martinezmcauliffe/', 'linkedin'
    ]);
    echo "Inserted Sarah Martinez LinkedIn data\n";
    
    // Insert Michael Thompson data
    $db->execute("INSERT INTO demo_data (id, card_id, first_name, last_name, phone_number, company_name, job_title, bio, theme, profile_photo_path, company_logo_path, cover_graphic_path, street, city, state, zip, country, website_name, website_url, website_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
        'demo-data-3-uuid', 'demo-card-3-uuid', 'Michael', 'Thompson', '+1 (555) 456-7890', 'Innovation Ventures', 'Chief Executive Officer', 'Visionary leader with 15+ years of experience in strategic planning, business development, and team leadership. Passionate about driving innovation and growth in emerging markets.', 'minimalist-gray', 'demo-michael-profile.jpg', 'demo-innovation-logo.jpg', 'demo-innovation-cover.jpg', '890 Innovation Drive, Suite 200', 'Boston', 'MA', '02108', 'United States', 'Company Website', 'https://innovationventures.com', 'primary'
    ]);
    echo "Inserted Michael Thompson primary data\n";
    
    $db->execute("INSERT INTO demo_data (id, card_id, first_name, last_name, phone_number, company_name, job_title, bio, theme, profile_photo_path, company_logo_path, cover_graphic_path, street, city, state, zip, country, website_name, website_url, website_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
        'demo-data-3-linkedin', 'demo-card-3-uuid', 'Michael', 'Thompson', '+1 (555) 456-7890', 'Innovation Ventures', 'Chief Executive Officer', 'Visionary leader with 15+ years of experience in strategic planning, business development, and team leadership. Passionate about driving innovation and growth in emerging markets.', 'minimalist-gray', 'demo-michael-profile.jpg', 'demo-innovation-logo.jpg', 'demo-innovation-cover.jpg', '890 Innovation Drive, Suite 200', 'Boston', 'MA', '02108', 'United States', 'LinkedIn Profile', 'https://linkedin.com/in/michaelthompson', 'linkedin'
    ]);
    echo "Inserted Michael Thompson LinkedIn data\n";
    
    $db->execute("INSERT INTO demo_data (id, card_id, first_name, last_name, phone_number, company_name, job_title, bio, theme, profile_photo_path, company_logo_path, cover_graphic_path, street, city, state, zip, country, website_name, website_url, website_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
        'demo-data-3-blog', 'demo-card-3-uuid', 'Michael', 'Thompson', '+1 (555) 456-7890', 'Innovation Ventures', 'Chief Executive Officer', 'Visionary leader with 15+ years of experience in strategic planning, business development, and team leadership. Passionate about driving innovation and growth in emerging markets.', 'minimalist-gray', 'demo-michael-profile.jpg', 'demo-innovation-logo.jpg', 'demo-innovation-cover.jpg', '890 Innovation Drive, Suite 200', 'Boston', 'MA', '02108', 'United States', 'Personal Blog', 'https://michaelthompson.blog', 'other'
    ]);
    echo "Inserted Michael Thompson blog data\n";
    
    echo "\n✅ Demo data table created and populated successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
