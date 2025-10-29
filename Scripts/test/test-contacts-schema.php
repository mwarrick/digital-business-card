<?php
// Test contacts table schema and data
require_once __DIR__ . '/web/api/includes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "=== CONTACTS TABLE SCHEMA ===\n";
    $schema = $db->query("DESCRIBE contacts");
    foreach ($schema as $field) {
        echo "Field: {$field['Field']}, Type: {$field['Type']}, Null: {$field['Null']}\n";
    }
    
    echo "\n=== SAMPLE CONTACT DATA ===\n";
    $contacts = $db->query("SELECT * FROM contacts LIMIT 1");
    if (!empty($contacts)) {
        $contact = $contacts[0];
        echo "Sample contact fields:\n";
        foreach ($contact as $key => $value) {
            echo "  $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
        }
    } else {
        echo "No contacts found in database\n";
    }
    
    echo "\n=== CONTACTS COUNT ===\n";
    $count = $db->query("SELECT COUNT(*) as count FROM contacts");
    echo "Total contacts: " . $count[0]['count'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
