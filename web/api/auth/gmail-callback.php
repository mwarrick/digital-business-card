<?php
/**
 * Gmail OAuth2 Callback Handler
 * Handles the OAuth2 callback from Google
 */

require_once '../includes/GmailClient.php';

// Check for authorization code
if (!isset($_GET['code'])) {
    if (isset($_GET['error'])) {
        die('Authorization failed: ' . htmlspecialchars($_GET['error']));
    }
    die('No authorization code received');
}

try {
    // Exchange code for tokens
    $tokens = GmailClient::exchangeCodeForTokens($_GET['code']);
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gmail API Authorization Success</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                max-width: 600px;
                margin: 50px auto;
                padding: 20px;
                background-color: #f5f5f5;
            }
            .container {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                text-align: center;
            }
            .success {
                color: #4caf50;
                font-size: 48px;
                margin-bottom: 20px;
            }
            h1 {
                color: #333;
                margin-bottom: 10px;
            }
            p {
                color: #666;
                line-height: 1.6;
            }
            .token-info {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 5px;
                margin: 20px 0;
                text-align: left;
            }
            .token-info pre {
                margin: 0;
                overflow-x: auto;
            }
            .button {
                display: inline-block;
                padding: 12px 24px;
                background: #2196f3;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin-top: 20px;
            }
            .button:hover {
                background: #1976d2;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="success">✅</div>
            <h1>Authorization Successful!</h1>
            <p>Gmail API has been successfully authorized for ShareMyCard.</p>
            <p>You can now send verification emails and notifications through Gmail.</p>
            
            <div class="token-info">
                <strong>Token Information:</strong>
                <pre><?php echo json_encode([
                    'access_token' => substr($tokens['access_token'], 0, 20) . '...',
                    'expires_in' => $tokens['expires_in'] . ' seconds',
                    'refresh_token' => isset($tokens['refresh_token']) ? 'Available' : 'Not available',
                    'scope' => $tokens['scope'] ?? 'N/A'
                ], JSON_PRETTY_PRINT); ?></pre>
            </div>
            
            <a href="/" class="button">Return to Home</a>
        </div>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gmail API Authorization Failed</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                max-width: 600px;
                margin: 50px auto;
                padding: 20px;
                background-color: #f5f5f5;
            }
            .container {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                text-align: center;
            }
            .error {
                color: #f44336;
                font-size: 48px;
                margin-bottom: 20px;
            }
            h1 {
                color: #333;
                margin-bottom: 10px;
            }
            p {
                color: #666;
                line-height: 1.6;
            }
            .error-message {
                background: #ffebee;
                padding: 20px;
                border-radius: 5px;
                margin: 20px 0;
                color: #c62828;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="error">❌</div>
            <h1>Authorization Failed</h1>
            <p>There was an error authorizing Gmail API access.</p>
            
            <div class="error-message">
                <?php echo htmlspecialchars($e->getMessage()); ?>
            </div>
        </div>
    </body>
    </html>
    <?php
}

