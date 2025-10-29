<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing Image Creation Logging...<br>";

try {
    require_once __DIR__ . '/../api/includes/log-image-creation.php';
    echo "✅ log-image-creation.php loaded<br>";
    
    // Test logging
    logImageCreation(
        'test-image.png',
        '/tmp/test-image.png',
        'other',
        'generated',
        1024,
        '100x100'
    );
    echo "✅ logImageCreation() called successfully<br>";
    
    // Check if it was logged
    require_once __DIR__ . '/../api/includes/Database.php';
    $db = Database::getInstance();
    
    $result = $db->querySingle("SELECT COUNT(*) as count FROM image_creation_log WHERE image_name = 'test-image.png'");
    echo "✅ Found {$result['count']} test log entries<br>";
    
    // Show recent entries
    $recent = $db->query("SELECT * FROM image_creation_log ORDER BY created_at DESC LIMIT 5");
    echo "<h3>Recent Image Creation Logs:</h3>";
    echo "<table border='1'><tr><th>ID</th><th>User</th><th>Image</th><th>Type</th><th>Method</th><th>Created</th></tr>";
    foreach ($recent as $log) {
        echo "<tr>";
        echo "<td>{$log['id']}</td>";
        echo "<td>{$log['user_email']}</td>";
        echo "<td>{$log['image_name']}</td>";
        echo "<td>{$log['image_type']}</td>";
        echo "<td>{$log['creation_method']}</td>";
        echo "<td>{$log['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?>


