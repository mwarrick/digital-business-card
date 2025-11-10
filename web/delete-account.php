<?php
/**
 * Delete Account - Public Information Page
 * No login required - explains how to delete account
 */

// Check if user is logged in (optional - for conditional display)
require_once __DIR__ . '/user/includes/UserAuth.php';
require_once __DIR__ . '/api/includes/Database.php';

$isLoggedIn = UserAuth::isLoggedIn();

// If user is logged in, check if their account still exists
// If account was deleted, redirect to confirmation page
if ($isLoggedIn) {
    $db = Database::getInstance();
    $userId = UserAuth::getUserId();
    
    $userExists = $db->querySingle(
        "SELECT id FROM users WHERE id = ?",
        [$userId]
    );
    
    // If user doesn't exist (account was deleted), redirect to confirmation page
    if (!$userExists) {
        // Clear session and redirect
        UserAuth::logout();
        header('Location: /account-deleted.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account - ShareMyCard</title>
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
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .header .logo {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        h1 {
            font-size: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: transform 0.2s;
        }
        
        .back-link:hover {
            transform: translateX(-5px);
        }
        
        h2 {
            color: #333;
            font-size: 24px;
            margin-top: 40px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        h3 {
            color: #555;
            font-size: 18px;
            margin-top: 25px;
            margin-bottom: 12px;
        }
        
        p {
            color: #444;
            line-height: 1.8;
            margin-bottom: 15px;
        }
        
        ul {
            margin: 15px 0;
            padding-left: 30px;
        }
        
        li {
            color: #444;
            line-height: 1.8;
            margin-bottom: 10px;
        }
        
        strong {
            color: #333;
        }
        
        a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
        }
        
        .warning-box h3 {
            color: #856404;
            margin-top: 0;
        }
        
        .warning-box p {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .action-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin: 40px 0;
            text-align: center;
        }
        
        .action-box h2 {
            color: white;
            border-bottom: 2px solid rgba(255,255,255,0.3);
            margin-top: 0;
        }
        
        .action-box a {
            color: white;
            text-decoration: underline;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: white;
            color: #667eea !important;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 15px;
            transition: transform 0.2s;
        }
        
        .action-box .btn {
            color: #667eea !important;
            text-decoration: none;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
            color: #666;
            font-size: 14px;
        }
        
        .footer a {
            color: #667eea;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            h2 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/" class="back-link">← Back to Home</a>
        
        <div class="header">
            <div class="logo">🗑️</div>
            <h1>Delete Your Account</h1>
            <p class="text-muted">Learn how to permanently delete your ShareMyCard account</p>
        </div>
        
        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
            <div class="success-message" style="background: #d4edda; color: #155724; padding: 20px; border-radius: 12px; margin-bottom: 30px; border: 2px solid #c3e6cb;">
                <h2 style="color: #155724; margin-top: 0;">✓ Account Successfully Deleted</h2>
                <p>Your account and all associated data have been permanently deleted. Thank you for using ShareMyCard.</p>
            </div>
        <?php endif; ?>
        
        <div class="warning-box">
            <h3>⚠️ Important Warning</h3>
            <p><strong>Deleting your account is a permanent action.</strong> Once you confirm deletion, you will not be able to recover any of your data. This includes:</p>
            <ul>
                <li>All your business cards</li>
                <li>All contact information</li>
                <li>All leads and contacts</li>
                <li>All custom QR codes</li>
                <li>All analytics data</li>
                <li>All uploaded media files (photos, logos, graphics)</li>
                <li>Your account settings and preferences</li>
            </ul>
        </div>
        
        <h2>How to Delete Your Account</h2>
        
        <h3>Step 1: Log In</h3>
        <p>You must be logged into your ShareMyCard account to delete it. <?php if (!$isLoggedIn): ?>
            <a href="/user/login.php">Log in here</a> if you haven't already.
        <?php else: ?>
            You are currently logged in.
        <?php endif; ?></p>
        
        <h3>Step 2: Access Delete Account Page</h3>
        <p>Once logged in, you can access the delete account page from:</p>
        <ul>
            <li>Your dashboard - Look for the "Delete Account" card after the Security section</li>
            <li>Account Security page - There will be a link to delete your account</li>
            <li>Direct link: <a href="/user/delete-account.php">/user/delete-account.php</a></p>
        </ul>
        
        <h3>Step 3: Confirm Your Password</h3>
        <p>To ensure account security, you must enter your password to confirm deletion. The system will verify your password before proceeding.</p>
        
        <h3>Step 4: Confirm Deletion</h3>
        <p>After entering your password, click the "Confirm Deletion" button. This action cannot be undone.</p>
        
        <h2>What Happens When You Delete Your Account?</h2>
        <p>When you confirm account deletion, the following will occur:</p>
        <ol>
            <li>All your business cards and related information will be permanently deleted</li>
            <li>All your contacts and leads will be removed</li>
            <li>All custom QR codes and their analytics will be deleted</li>
            <li>All uploaded media files will be removed from our servers</li>
            <li>Your account credentials will be deleted</li>
            <li>You will be automatically logged out</li>
        </ol>
        
        <h2>Forgot Your Password?</h2>
        <p>If you have forgotten your password and cannot access your account to delete it, please send an email from the email address associated with your account to <a href="mailto:mark@sharemycard.app">mark@sharemycard.app</a> requesting that your password be reset or your account be deleted.</p>
        <p>For security reasons, we can only process account deletion requests sent from the email address associated with your account.</p>
        
        <div class="action-box">
            <h2>Ready to Delete Your Account?</h2>
            <?php if ($isLoggedIn): ?>
                <p>You are currently logged in. You can proceed to delete your account.</p>
                <a href="/user/delete-account.php" class="btn">Go to Delete Account Page</a>
            <?php else: ?>
                <p>Please log in to your account first.</p>
                <a href="/user/login.php" class="btn">Log In</a>
            <?php endif; ?>
        </div>
        
        <h2>Need Help?</h2>
        <p>If you have questions about account deletion or need assistance, please contact us at <a href="mailto:mark@sharemycard.app">mark@sharemycard.app</a>.</p>
        
        <div class="footer">
            <p><strong>ShareMyCard</strong> - Digital Business Cards Made Simple</p>
            <p><a href="/privacy.php">Privacy Policy</a> | <a href="/">Home</a></p>
        </div>
    </div>
</body>
</html>

