<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Checking user_logins table...<br>";

try {
    require_once __DIR__ . '/../api/includes/Database.php';
    $db = Database::getInstance();
    
    // Check raw data in user_logins
    echo "<h3>Raw user_logins data:</h3>";
    $logins = $db->query("SELECT * FROM user_logins ORDER BY login_timestamp DESC");
    foreach ($logins as $login) {
        echo "<pre>" . print_r($login, true) . "</pre>";
    }
    
    // Check JOIN with users table
    echo "<h3>JOIN with users table:</h3>";
    $joinResults = $db->query("
        SELECT 
            ul.user_id, 
            ul.email as login_email, 
            u.email as user_email, 
            u.is_active, 
            u.is_admin
        FROM user_logins ul
        LEFT JOIN users u ON ul.user_id = u.id
        ORDER BY ul.login_timestamp DESC
    ");
    
    foreach ($joinResults as $row) {
        echo "<pre>" . print_r($row, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>

