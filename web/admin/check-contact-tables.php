<?php
/**
 * Check contact tables structure
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/includes/Database.php';

try {
    $db = Database::getInstance();
    
    // Check email_contacts table structure
    try {
        $emailContacts = $db->query("DESCRIBE email_contacts");
        echo "Email_contacts table structure:\n";
        foreach ($emailContacts as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    } catch (Exception $e) {
        echo "Email_contacts table does not exist: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Check phone_contacts table structure
    try {
        $phoneContacts = $db->query("DESCRIBE phone_contacts");
        echo "Phone_contacts table structure:\n";
        foreach ($phoneContacts as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    } catch (Exception $e) {
        echo "Phone_contacts table does not exist: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
