<?php
/**
 * Check demo system error logs
 */

echo "Checking demo system logs...\n";

// Check if error log file exists and show recent entries
$logFile = '/home/sharipbf/public_html/error.log';
if (file_exists($logFile)) {
    echo "Recent error log entries:\n";
    $lines = file($logFile);
    $recentLines = array_slice($lines, -20); // Last 20 lines
    foreach ($recentLines as $line) {
        if (strpos($line, 'DEMO DEBUG') !== false) {
            echo $line;
        }
    }
} else {
    echo "Error log file not found at: $logFile\n";
}

// Also check PHP error log
$phpLogFile = '/var/log/php_errors.log';
if (file_exists($phpLogFile)) {
    echo "\nRecent PHP error log entries:\n";
    $lines = file($phpLogFile);
    $recentLines = array_slice($lines, -10); // Last 10 lines
    foreach ($recentLines as $line) {
        if (strpos($line, 'DEMO') !== false) {
            echo $line;
        }
    }
} else {
    echo "PHP error log file not found at: $phpLogFile\n";
}
?>
