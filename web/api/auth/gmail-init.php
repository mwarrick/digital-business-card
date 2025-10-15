<?php
/**
 * Gmail OAuth2 Initialization
 * Start the OAuth2 flow to authorize Gmail API
 */

require_once '../includes/GmailClient.php';

// Check if already authenticated
if (GmailClient::isAuthenticated()) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gmail API - Already Authorized</title>
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
            .button {
                display: inline-block;
                padding: 12px 24px;
                background: #2196f3;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin: 10px 5px;
            }
            .button:hover {
                background: #1976d2;
            }
            .button.secondary {
                background: #757575;
            }
            .button.secondary:hover {
                background: #616161;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="success">✅</div>
            <h1>Gmail API Already Authorized</h1>
            <p>ShareMyCard is already authorized to send emails via Gmail API.</p>
            <p>Email functionality is ready to use!</p>
            
            <a href="/" class="button">Return to Home</a>
            <a href="?reauthorize=1" class="button secondary">Re-authorize</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Get authorization URL
$authUrl = GmailClient::getAuthUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authorize Gmail API</title>
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
        }
        .icon {
            text-align: center;
            font-size: 64px;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 10px;
        }
        p {
            color: #666;
            line-height: 1.6;
            text-align: center;
        }
        .info-box {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info-box h3 {
            margin-top: 0;
            color: #1565c0;
        }
        .info-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .info-box li {
            margin: 5px 0;
            color: #555;
        }
        .button {
            display: block;
            width: 100%;
            padding: 15px;
            background: #4285f4;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            font-weight: 500;
            margin-top: 20px;
            box-sizing: border-box;
        }
        .button:hover {
            background: #357ae8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">📧</div>
        <h1>Authorize Gmail API</h1>
        <p>ShareMyCard needs permission to send emails on your behalf.</p>
        
        <div class="info-box">
            <h3>What we'll use Gmail for:</h3>
            <ul>
                <li>Send registration verification emails</li>
                <li>Send login verification codes</li>
                <li>Send password reset emails</li>
                <li>Send notification emails</li>
            </ul>
        </div>
        
        <div class="info-box">
            <h3>What you'll need to do:</h3>
            <ul>
                <li>Sign in with your Google account</li>
                <li>Grant permission to send emails</li>
                <li>You'll be redirected back here after authorization</li>
            </ul>
        </div>
        
        <a href="<?php echo htmlspecialchars($authUrl); ?>" class="button">
            Authorize with Google
        </a>
    </div>
</body>
</html>

