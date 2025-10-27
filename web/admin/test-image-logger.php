<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing ImageCreationLogger...<br>";

try {
    require_once __DIR__ . '/../api/includes/ImageCreationLogger.php';
    echo "✅ ImageCreationLogger class loaded<br>";
    
    $logger = new ImageCreationLogger();
    echo "✅ ImageCreationLogger instantiated<br>";
    
    $history = $logger->getImageCreationHistory(10);
    echo "✅ getImageCreationHistory() returned: " . count($history) . " records<br>";
    
    $stats = $logger->getImageCreationStats();
    echo "✅ getImageCreationStats() returned: " . print_r($stats, true) . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?>


