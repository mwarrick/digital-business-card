<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting direct database connection...<br>";

try {
    // Direct database connection
    // Note: Database credentials should be loaded from sharemycard-config/.env
    $dbName = getenv('DB_NAME') ?: 'your_database_name';
    $dbUser = getenv('DB_USER') ?: 'your_database_user';
    $pdo = new PDO("mysql:host=localhost;dbname=$dbName", $dbUser, 'your_password_here');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connected directly<br>";
    
    $result = $pdo->exec("DROP TABLE IF EXISTS user_logins");
    echo "DROP TABLE executed<br>";
    echo "Result: " . $result . "<br>";
    
    echo "Done!<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>









