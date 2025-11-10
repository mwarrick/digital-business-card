<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>Dropping User Logins Table</h2>";

try {
    echo "<p>Dropping user_logins table...</p>";
    $db->execute("DROP TABLE IF EXISTS user_logins");
    echo "<p>✅ user_logins table dropped successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>








