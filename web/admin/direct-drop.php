<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting direct database connection...<br>";

try {
    // Direct database connection
    $pdo = new PDO('mysql:host=localhost;dbname=sharipbf_sharemycard', 'sharipbf', 'your_password_here');
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

