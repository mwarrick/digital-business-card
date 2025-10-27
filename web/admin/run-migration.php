<?php
/**
 * Run Database Migration
 * Apply the contact source tracking migration
 */

require_once __DIR__ . '/../api/includes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Starting migration: Add contact source tracking...\n";
    
    // Check if columns already exist
    $result = $db->query("SHOW COLUMNS FROM contacts LIKE 'source'");
    if ($result->rowCount() > 0) {
        echo "Column 'source' already exists. Skipping...\n";
    } else {
        // Add source field
        $db->execute("ALTER TABLE contacts ADD COLUMN source VARCHAR(50) DEFAULT 'manual' COMMENT 'Contact creation method: manual, qr_scan, converted'");
        echo "Added 'source' column.\n";
    }
    
    $result = $db->query("SHOW COLUMNS FROM contacts LIKE 'source_metadata'");
    if ($result->rowCount() > 0) {
        echo "Column 'source_metadata' already exists. Skipping...\n";
    } else {
        // Add source_metadata field
        $db->execute("ALTER TABLE contacts ADD COLUMN source_metadata TEXT COMMENT 'JSON metadata about contact creation (device info, timestamps, etc.)'");
        echo "Added 'source_metadata' column.\n";
    }
    
    // Check if index exists
    $result = $db->query("SHOW INDEX FROM contacts WHERE Key_name = 'idx_contacts_source'");
    if ($result->rowCount() > 0) {
        echo "Index 'idx_contacts_source' already exists. Skipping...\n";
    } else {
        // Create index
        $db->execute("CREATE INDEX idx_contacts_source ON contacts(source)");
        echo "Created index on 'source' column.\n";
    }
    
    // Update existing contacts
    $db->execute("UPDATE contacts SET source = 'manual' WHERE source IS NULL");
    echo "Updated existing contacts to 'manual' source.\n";
    
    $db->execute("UPDATE contacts SET source = 'converted' WHERE id_lead IS NOT NULL AND id_lead > 0");
    echo "Updated converted contacts to 'converted' source.\n";
    
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>