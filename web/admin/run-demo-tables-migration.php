<?php
/**
 * Demo Tables Migration Runner
 * Creates leads_demo and contacts_demo tables
 */

require_once __DIR__ . '/../api/includes/Database.php';

$db = Database::getInstance();

echo "<h1>Running Demo Tables Migration</h1>";

try {
    // Check if leads_demo table already exists
    $result = $db->query("SHOW TABLES LIKE 'leads_demo'");
    if (count($result) > 0) {
        echo "<p style='color: orange;'>⚠️ leads_demo table already exists</p>";
    } else {
        echo "<p>Creating leads_demo table...</p>";
        
        // Create leads_demo table
        $db->execute("
            CREATE TABLE leads_demo (
                id INT PRIMARY KEY AUTO_INCREMENT,
                id_business_card INT NOT NULL,
                
                -- Lead Information
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(255),
                phone VARCHAR(20),
                company VARCHAR(200),
                job_title VARCHAR(200),
                
                -- Additional Information
                message TEXT,
                notes TEXT,
                
                -- Lead Source & Status
                source VARCHAR(50) DEFAULT 'web_form',
                status VARCHAR(50) DEFAULT 'new',
                
                -- Conversion Tracking
                converted_to_contact BOOLEAN DEFAULT FALSE,
                id_contact INT NULL,
                converted_at TIMESTAMP NULL,
                
                -- Metadata
                captured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                ip_address VARCHAR(45),
                user_agent TEXT,
                referrer VARCHAR(500),
                
                -- Demo-specific fields
                demo_business_card_id INT NOT NULL,
                demo_lead_id INT NOT NULL,
                
                -- Indexes
                INDEX idx_demo_business_card (demo_business_card_id),
                INDEX idx_demo_lead_id (demo_lead_id),
                INDEX idx_status (status),
                INDEX idx_converted (converted_to_contact),
                INDEX idx_captured_at (captured_at),
                INDEX idx_email (email)
            )
        ");
        echo "<p>✅ Created leads_demo table</p>";
    }
    
    // Check if contacts_demo table already exists
    $result = $db->query("SHOW TABLES LIKE 'contacts_demo'");
    if (count($result) > 0) {
        echo "<p style='color: orange;'>⚠️ contacts_demo table already exists</p>";
    } else {
        echo "<p>Creating contacts_demo table...</p>";
        
        // Create contacts_demo table
        $db->execute("
            CREATE TABLE contacts_demo (
                id INT PRIMARY KEY AUTO_INCREMENT,
                id_user CHAR(36) NOT NULL,
                id_lead INT NULL,
                id_business_card INT NULL,
                
                -- Contact Information
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(255),
                phone VARCHAR(20),
                company VARCHAR(200),
                job_title VARCHAR(200),
                
                -- Additional Contact Details
                phone_mobile VARCHAR(20),
                phone_work VARCHAR(20),
                email_work VARCHAR(255),
                email_personal VARCHAR(255),
                
                -- Address Information
                address_street VARCHAR(255),
                address_city VARCHAR(100),
                address_state VARCHAR(100),
                address_zip VARCHAR(20),
                address_country VARCHAR(100),
                
                -- Social & Web
                website VARCHAR(500),
                linkedin_url VARCHAR(500),
                twitter_url VARCHAR(500),
                
                -- Notes & Tags
                notes TEXT,
                tags VARCHAR(500),
                
                -- Contact Source
                source VARCHAR(50) DEFAULT 'manual',
                
                -- Relationship Status
                relationship_status VARCHAR(50) DEFAULT 'active',
                favorite BOOLEAN DEFAULT FALSE,
                
                -- Metadata
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                last_contacted_at TIMESTAMP NULL,
                
                -- Demo-specific fields
                demo_user_id CHAR(36) NOT NULL,
                demo_contact_id INT NOT NULL,
                demo_lead_id INT NULL,
                
                -- Indexes
                INDEX idx_demo_user (demo_user_id),
                INDEX idx_demo_contact_id (demo_contact_id),
                INDEX idx_demo_lead_id (demo_lead_id),
                INDEX idx_name (first_name, last_name),
                INDEX idx_email (email),
                INDEX idx_favorite (favorite),
                INDEX idx_created_at (created_at)
            )
        ");
        echo "<p>✅ Created contacts_demo table</p>";
    }
    
    echo "<p style='color: green;'>🎉 Demo tables migration completed successfully!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Migration failed: " . $e->getMessage() . "</p>";
    echo "<p>Error details: " . $e->getTraceAsString() . "</p>";
}
?>
