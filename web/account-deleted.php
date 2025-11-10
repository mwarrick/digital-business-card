<?php
/**
 * Account Deleted Confirmation Page
 * Public page shown after account deletion
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Deleted - ShareMyCard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
        }
        
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        h1 {
            font-size: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }
        
        .message {
            color: #444;
            line-height: 1.8;
            margin-bottom: 30px;
            font-size: 16px;
        }
        
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
            text-align: left;
        }
        
        .info-box p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .info-box ul {
            margin: 10px 0 0 20px;
            color: #666;
            font-size: 14px;
        }
        
        .info-box li {
            margin-bottom: 5px;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
            color: #666;
            font-size: 14px;
        }
        
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .icon {
                font-size: 60px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">✓</div>
        <h1>Account Successfully Deleted</h1>
        
        <div class="message">
            <p>Your ShareMyCard account and all associated data have been permanently deleted.</p>
            <p>We're sorry to see you go, but we respect your decision.</p>
        </div>
        
        <div class="info-box">
            <p><strong>What was deleted:</strong></p>
            <ul>
                <li>All your business cards</li>
                <li>All contact information</li>
                <li>All leads and contacts</li>
                <li>All custom QR codes and analytics</li>
                <li>All uploaded media files</li>
                <li>Your account credentials</li>
            </ul>
        </div>
        
        <div class="message">
            <p><strong>Confirmation email sent</strong></p>
            <p style="font-size: 14px; color: #666;">We've sent a confirmation email to the address associated with your account.</p>
        </div>
        
        <div class="message">
            <p>If you change your mind, you're always welcome to come back and create a new account anytime!</p>
        </div>
        
        <div style="margin-top: 40px;">
            <a href="/user/register.php" class="btn">Create a New Account</a>
            <a href="/" class="btn btn-secondary">Visit Homepage</a>
        </div>
        
        <div class="footer">
            <p><strong>ShareMyCard</strong> - Digital Business Cards Made Simple</p>
            <p>Thank you for using ShareMyCard!</p>
        </div>
    </div>
</body>
</html>

