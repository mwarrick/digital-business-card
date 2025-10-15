<?php
/**
 * Secure Configuration Loader
 * 
 * This file loads configuration from the secure directory outside web root.
 * It should be included by all PHP files that need database or API access.
 */

// Define the path to the secure configuration directory
// This should be outside the web root for security
$secureConfigPath = dirname(dirname(__DIR__)) . '/sharemycard-config/';

// Check if secure config directory exists
if (!is_dir($secureConfigPath)) {
    throw new Exception('Secure configuration directory not found: ' . $secureConfigPath);
}

// Load database configuration
$databaseConfig = $secureConfigPath . 'database.php';
if (file_exists($databaseConfig)) {
    require_once $databaseConfig;
} else {
    throw new Exception('Database configuration file not found: ' . $databaseConfig);
}

// Load Gmail API configuration
$gmailConfig = $secureConfigPath . 'gmail.php';
if (file_exists($gmailConfig)) {
    require_once $gmailConfig;
} else {
    throw new Exception('Gmail API configuration file not found: ' . $gmailConfig);
}

// Load environment variables if .env file exists
$envFile = $secureConfigPath . '.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\'');
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

// Validate that required constants are defined
$requiredConstants = [
    'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS',
    'GMAIL_CLIENT_ID', 'GMAIL_CLIENT_SECRET'
];

foreach ($requiredConstants as $constant) {
    if (!defined($constant)) {
        throw new Exception("Required configuration constant not defined: $constant");
    }
}

// Security check: Ensure we're not in a public directory
$currentPath = __FILE__;
$webRoot = dirname(dirname(__DIR__));
if (strpos($currentPath, $webRoot) === 0) {
    // We're inside the web root, which is expected for this loader
    // But the actual config files should be outside
}

// Log configuration load (in debug mode only)
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("Secure configuration loaded from: $secureConfigPath");
}
