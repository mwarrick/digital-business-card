<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting...<br>";

try {
    require_once __DIR__ . '/../config/database.php';
    echo "Database connected<br>";
    
    $result = $db->execute("DROP TABLE IF EXISTS user_logins");
    echo "DROP TABLE executed<br>";
    echo "Result: " . var_export($result, true) . "<br>";
    
    echo "Done!<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>








