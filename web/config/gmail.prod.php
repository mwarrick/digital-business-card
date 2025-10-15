<?php
/**
 * Gmail API Configuration for Production
 * 
 * OAuth credentials and token storage path for production server
 * Tokens are stored OUTSIDE the web directory for security
 */

// Gmail OAuth 2.0 credentials (from Google Cloud Console)
define('GMAIL_CLIENT_ID', 'your-client-id.apps.googleusercontent.com');
define('GMAIL_CLIENT_SECRET', 'your-client-secret');
define('GMAIL_REDIRECT_URI', 'https://sharemycard.app/api/auth/gmail-callback.php');

// Token storage (SECURE - outside public_html)
define('GMAIL_TOKEN_FILE', '/home/sharipbf/sharemycard-config/gmail-token.json');

// Gmail settings
define('GMAIL_FROM_EMAIL', 'noreply@sharemycard.app');
define('GMAIL_FROM_NAME', 'ShareMyCard');

// Scopes required
define('GMAIL_SCOPES', 'https://www.googleapis.com/auth/gmail.send');
?>

