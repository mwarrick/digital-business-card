<?php
require_once __DIR__ . '/../config/secure-config.php';
require_once __DIR__ . '/../api/includes/Database.php';

try {
    $db = Database::getInstance();
    
    echo "Running migration 033: Add analytics columns to custom_qr_events...\n";
    
    $sql = "ALTER TABLE custom_qr_events 
            ADD COLUMN device_type VARCHAR(50) NULL AFTER user_agent,
            ADD COLUMN browser VARCHAR(100) NULL AFTER device_type,
            ADD COLUMN location_type VARCHAR(100) NULL AFTER browser";
    
    $db->getConnection()->exec($sql);
    
    echo "Migration 033 completed successfully!\n";
    echo "Added columns: device_type, browser, location_type\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
