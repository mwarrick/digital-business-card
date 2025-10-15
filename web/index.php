<?php
/**
 * ShareMyCard - Landing Page
 * Digital Business Card System
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShareMyCard - Digital Business Cards Made Simple</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .landing-container {
            background: white;
            padding: 60px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        
        .logo {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        h1 {
            font-size: 36px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
        }
        
        .tagline {
            color: #666;
            font-size: 18px;
            margin-bottom: 40px;
            line-height: 1.6;
        }
        
        .features {
            text-align: left;
            margin: 40px 0;
            display: grid;
            gap: 20px;
        }
        
        .feature {
            display: flex;
            align-items: start;
            gap: 15px;
        }
        
        .feature-icon {
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .feature-content h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .feature-content p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .cta-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 40px;
        }
        
        .btn {
            padding: 16px 40px;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            border: none;
            display: inline-block;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:hover {
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .btn-secondary:hover {
            background: #f8f9ff;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e0e0e0;
            color: #999;
            font-size: 14px;
        }
        
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 600px) {
            .landing-container {
                padding: 40px 30px;
            }
            
            h1 {
                font-size: 28px;
            }
            
            .tagline {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="landing-container">
        <div class="logo">📱</div>
        <h1>ShareMyCard</h1>
        <p class="tagline">
            Share your digital business card instantly with QR codes. 
            No more paper cards, no more typing contact info.
        </p>
        
        <div class="features">
            <div class="feature">
                <div class="feature-icon">⚡</div>
                <div class="feature-content">
                    <h3>Instant Sharing</h3>
                    <p>Share your contact info with a simple QR code scan</p>
                </div>
            </div>
            
            <div class="feature">
                <div class="feature-icon">✉️</div>
                <div class="feature-content">
                    <h3>Email Signature Generator</h3>
                    <p>Create professional email signatures with your photo or company logo - fully customizable</p>
                </div>
            </div>
            
            <div class="feature">
                <div class="feature-icon">🎴</div>
                <div class="feature-content">
                    <h3>Multiple Cards</h3>
                    <p>Create different cards for personal, business, or different roles - each with unique signatures</p>
                </div>
            </div>
            
            <div class="feature">
                <div class="feature-icon">📲</div>
                <div class="feature-content">
                    <h3>iOS App Available</h3>
                    <p>Create and manage your cards on your iPhone</p>
                </div>
            </div>
            
            <div class="feature">
                <div class="feature-icon">🌐</div>
                <div class="feature-content">
                    <h3>Web & Mobile</h3>
                    <p>Edit from anywhere - web browser or iOS app</p>
                </div>
            </div>
        </div>
        
        <div class="cta-buttons">
            <a href="/user/register.php" class="btn btn-primary">
                Get Started - Create Account
            </a>
            <a href="/user/login.php" class="btn btn-secondary">
                Login to Your Account
            </a>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="/admin/login.php" style="color: #667eea; text-decoration: none; font-size: 14px;">
                Admin Login →
            </a>
        </div>
        
        <div class="footer">
            <p>Created by <a href="https://warrick.net" target="_blank">Mark Warrick</a></p>
            <p style="margin-top: 10px; font-size: 13px;"><a href="/privacy.php" style="color: #667eea;">Privacy Policy</a></p>
        </div>
    </div>
</body>
</html>
