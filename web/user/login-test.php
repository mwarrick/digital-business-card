<?php
/**
 * Login Test - Minimal version to debug
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Login test starting...\n";

try {
    require_once __DIR__ . '/includes/UserAuth.php';
    echo "UserAuth loaded successfully\n";
} catch (Exception $e) {
    echo "Error loading UserAuth: " . $e->getMessage() . "\n";
    exit;
}

try {
    require_once __DIR__ . '/../api/includes/Database.php';
    echo "Database loaded successfully\n";
} catch (Exception $e) {
    echo "Error loading Database: " . $e->getMessage() . "\n";
    exit;
}

echo "All includes loaded successfully\n";
echo "Login test completed\n";
?>
