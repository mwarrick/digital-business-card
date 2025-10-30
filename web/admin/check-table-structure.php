<?php
require_once __DIR__ . '/../api/includes/Database.php';

try {
    $db = Database::getInstance();
    
    echo "Checking custom_qr_events table structure:\n";
    $result = $db->query("DESCRIBE custom_qr_events");
    
    foreach($result as $row) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
