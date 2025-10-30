<?php
/**
 * Wi-Fi landing template for Custom QR Codes
 * Expects variables: $qr, $payload, $qrId
 */

$ssid = $payload['ssid'] ?? '';
$password = $payload['password'] ?? '';
$security = $payload['security'] ?? 'WPA/WPA2';
$hidden = isset($payload['hidden']) ? (bool)$payload['hidden'] : false;
$title = $qr['title'] ?: 'Wi-Fi Access';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .wifi-icon {
            width: 80px;
            height: 80px;
            background: #667eea;
            border-radius: 16px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
        }
        h1 {
            color: #333;
            margin-bottom: 24px;
            font-size: 24px;
            font-weight: 600;
        }
        .wifi-details {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
            text-align: left;
        }
        .wifi-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .wifi-item:last-child {
            border-bottom: none;
        }
        .wifi-label {
            font-weight: 600;
            color: #333;
        }
        .wifi-value {
            color: #666;
            font-family: 'Monaco', 'Menlo', monospace;
            background: white;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .password-value {
            position: relative;
        }
        .password-toggle {
            background: none;
            border: none;
            color: #667eea;
            cursor: pointer;
            font-size: 12px;
            margin-left: 8px;
        }
        .copy-button {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 8px;
        }
        .copy-button:hover {
            background: #218838;
        }
        .instructions {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
            text-align: left;
            font-size: 14px;
            color: #1565c0;
        }
        .instructions h3 {
            margin-bottom: 8px;
            color: #0d47a1;
        }
        .instructions ol {
            margin-left: 16px;
        }
        .instructions li {
            margin-bottom: 4px;
        }
        .lead-button {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .lead-button:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="wifi-icon">📶</div>
        <h1><?= htmlspecialchars($title) ?></h1>
        
        <div class="wifi-details">
            <div class="wifi-item">
                <span class="wifi-label">Network Name (SSID):</span>
                <span class="wifi-value" id="ssid"><?= htmlspecialchars($ssid) ?></span>
                <button class="copy-button" onclick="copyToClipboard('ssid')">Copy</button>
            </div>
            
            <div class="wifi-item">
                <span class="wifi-label">Password:</span>
                <span class="wifi-value password-value">
                    <span id="password" style="display: none;"><?= htmlspecialchars($password) ?></span>
                    <span id="password-dots">••••••••</span>
                    <button class="password-toggle" onclick="togglePassword()">Show</button>
                    <button class="copy-button" onclick="copyToClipboard('password')">Copy</button>
                </span>
            </div>
            
            <div class="wifi-item">
                <span class="wifi-label">Security:</span>
                <span class="wifi-value"><?= htmlspecialchars($security) ?></span>
            </div>
            
            <?php if ($hidden): ?>
            <div class="wifi-item">
                <span class="wifi-label">Hidden Network:</span>
                <span class="wifi-value">Yes</span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="instructions">
            <h3>How to connect:</h3>
            <ol>
                <li>Go to your device's Wi-Fi settings</li>
                <li>Look for "<?= htmlspecialchars($ssid) ?>"</li>
                <?php if ($hidden): ?>
                <li>Select "Other" or "Add Network" and enter the SSID manually</li>
                <?php endif; ?>
                <li>Enter the password when prompted</li>
                <li>Enjoy your internet connection!</li>
            </ol>
        </div>
        
        <?php if ($qr['show_lead_form']): ?>
            <a href="/capture-lead.php?qr_id=<?= urlencode($qrId) ?>" class="lead-button">
                Contact Us
            </a>
        <?php endif; ?>
    </div>
    
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const dots = document.getElementById('password-dots');
            const button = document.querySelector('.password-toggle');
            
            if (password.style.display === 'none') {
                password.style.display = 'inline';
                dots.style.display = 'none';
                button.textContent = 'Hide';
            } else {
                password.style.display = 'none';
                dots.style.display = 'inline';
                button.textContent = 'Show';
            }
        }
        
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            navigator.clipboard.writeText(text).then(function() {
                const button = event.target;
                const originalText = button.textContent;
                button.textContent = 'Copied!';
                button.style.background = '#28a745';
                
                setTimeout(function() {
                    button.textContent = originalText;
                    button.style.background = '#28a745';
                }, 2000);
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
            });
        }
    </script>
</body>
</html>
