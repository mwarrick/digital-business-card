<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting with Database class...<br>";

try {
    require_once __DIR__ . '/../api/includes/Database.php';
    echo "Database class loaded<br>";
    
    $db = Database::getInstance();
    echo "Database instance created<br>";
    
    $result = $db->execute("DROP TABLE IF EXISTS user_logins");
    echo "DROP TABLE executed<br>";
    echo "Result: " . var_export($result, true) . "<br>";
    
    echo "Done!<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?>







