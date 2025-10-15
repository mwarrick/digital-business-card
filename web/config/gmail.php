<?php
/**
 * Gmail API Configuration
 * This file includes the secure configuration from outside the web root
 */

// Include the secure configuration file (outside web root)
$secureConfigPath = dirname(dirname(__DIR__)) . '/sharemycard-config/gmail.php';
if (file_exists($secureConfigPath)) {
    require_once $secureConfigPath;
} else {
    // Fallback to template values for development
    define('GMAIL_CLIENT_ID', 'your-client-id');
    define('GMAIL_CLIENT_SECRET', 'your-client-secret');
    define('GMAIL_REDIRECT_URI', 'http://localhost:8000/api/auth/gmail-callback.php');
    define('GMAIL_SCOPES', 'https://www.googleapis.com/auth/gmail.send');
    define('GMAIL_FROM_EMAIL', 'noreply@sharemycard.app');
    define('GMAIL_FROM_NAME', 'ShareMyCard');
}

// Token storage path (in web storage directory)
define('GMAIL_TOKEN_FILE', __DIR__ . '/../storage/gmail-token.json');

// Email sending method: 'gmail' to use Gmail API tokens, 'native' to use server mail()
if (!defined('EMAIL_SENDER')) {
    define('EMAIL_SENDER', 'gmail');
}
?>

