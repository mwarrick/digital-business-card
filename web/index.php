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
            max-width: 1000px;
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
            margin: 40px 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .feature-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #e9ecef;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }
        
        .feature-card h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .feature-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .feature-icon a img:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
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
        
        .btn-demo {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            box-shadow: 0 10px 20px rgba(240, 147, 251, 0.3);
        }
        
        .btn-demo:hover {
            box-shadow: 0 15px 30px rgba(240, 147, 251, 0.4);
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
            
            .features {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .feature-card {
                padding: 20px;
            }
            
            .feature-icon {
                font-size: 40px;
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
            Create email signatures, virtual backgrounds, and more. 
            No more paper cards, no more typing contact info.
        </p>
        
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">
                    <a href="https://sharemycard.app/card.php?id=550e8400-e29b-41d4-a716-446655440001" target="_blank" style="text-decoration: none;">
                        <img src="/images/warrick-ai-qr.png" alt="Warrick AI QR Code - Click to view card" style="width: 80px; height: 80px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;">
                    </a>
                </div>
                <h3>Instant Sharing</h3>
                <p>Share your contact info with a simple QR code scan - no more typing contact details</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">✉️</div>
                <h3>Email Signatures</h3>
                <p>Create professional email signatures with your photo or company logo - fully customizable</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🖼️</div>
                <h3>Virtual Backgrounds</h3>
                <p>Generate custom virtual backgrounds for video calls with your QR code embedded</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🎴</div>
                <h3>Multiple Cards</h3>
                <p>Create different cards for personal, business, or different roles - each with unique signatures</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📲</div>
                <h3>iOS App</h3>
                <p>Create and manage your cards on your iPhone with our native iOS app</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🌐</div>
                <h3>Web & Mobile</h3>
                <p>Edit from anywhere - web browser or iOS app with full synchronization</p>
            </div>
        </div>
        
        <div class="cta-buttons">
            <a href="/user/register.php" class="btn btn-primary">
                Get Started - Create Account
            </a>
            <a href="/user/login.php" class="btn btn-secondary">
                Login to Your Account
            </a>
            <a href="/user/login.php?demo=1" class="btn btn-demo">
                🎭 Demo Login - Try It Now!
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
