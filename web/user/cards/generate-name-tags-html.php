<?php
/**
 * Generate HTML Name Tags for Word/Google Docs
 * Creates copy-paste HTML that users can paste into Word or Google Docs
 */

require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../api/includes/NameTagGenerator.php';
require_once __DIR__ . '/../includes/UserAuth.php';

// Check authentication
$userAuth = new UserAuth();
if (!$userAuth->isLoggedIn()) {
    http_response_code(401);
    exit('Authentication required');
}

// Get parameters
$cardId = $_GET['card_id'] ?? '';
$includeName = ($_GET['include_name'] ?? '1') === '1';
$includeTitle = ($_GET['include_title'] ?? '1') === '1';
$includePhone = ($_GET['include_phone'] ?? '1') === '1';
$includeEmail = ($_GET['include_email'] ?? '1') === '1';
$includeWebsite = ($_GET['include_website'] ?? '1') === '1';
$includeAddress = ($_GET['include_address'] ?? '0') === '1';
$fontSize = $_GET['font_size'] ?? '12';
$messageAbove = trim($_GET['message_above'] ?? '');
$messageBelow = trim($_GET['message_below'] ?? '');

// Validate parameters
if (empty($cardId)) {
    http_response_code(400);
    exit('Missing card ID');
}

if (!is_numeric($fontSize) || $fontSize < 8 || $fontSize > 20) {
    http_response_code(400);
    exit('Invalid font size');
}

if (strlen($messageAbove) > 100) {
    http_response_code(400);
    exit('Message above is too long');
}

if (strlen($messageBelow) > 100) {
    http_response_code(400);
    exit('Message below is too long');
}

$db = Database::getInstance();

// Verify card exists, is active, and belongs to user
$card = $db->querySingle(
    "SELECT id, first_name, last_name FROM business_cards WHERE id = ? AND is_active = 1 AND user_id = ?",
    [$cardId, $userAuth->getUserId()]
);

if (!$card) {
    http_response_code(404);
    exit('Card not found or access denied');
}

try {
    // Build preferences array
    $preferences = [
        'include_name' => $includeName,
        'include_title' => $includeTitle,
        'include_phone' => $includePhone,
        'include_email' => $includeEmail,
        'include_website' => $includeWebsite,
        'include_address' => $includeAddress,
        'font_size' => $fontSize,
        'message_above' => $messageAbove,
        'message_below' => $messageBelow
    ];
    
    // Generate HTML for name tags
    $generator = new NameTagGenerator();
    $html = $generator->generateNameTagHTML($cardId, $preferences);
    
    if (!$html) {
        throw new Exception('Failed to generate HTML');
    }
    
    // Create a complete HTML page with copy-paste instructions
    $fullHTML = generateCopyPasteHTML($html, $card['first_name'] . ' ' . $card['last_name']);
    
    // Set headers for HTML display
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: private, max-age=0, must-revalidate');
    
    // Output HTML
    echo $fullHTML;
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('Name tag HTML generation error: ' . $e->getMessage());
    exit('Error generating HTML: ' . $e->getMessage());
}

/**
 * Generate complete HTML page with copy-paste instructions
 */
function generateCopyPasteHTML($nameTagHTML, $cardName) {
    return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Name Tag HTML - ShareMyCard</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f7fa;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0 0 10px 0;
        }
        .header p {
            color: #7f8c8d;
            margin: 0;
        }
        .instructions {
            background: #e8f4fd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #3498db;
        }
        .instructions h3 {
            margin-top: 0;
            color: #2980b9;
        }
        .instructions ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .instructions li {
            margin: 8px 0;
        }
        .copy-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .copy-section h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .html-code {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 20px;
            border-radius: 5px;
            font-family: "Courier New", monospace;
            font-size: 14px;
            line-height: 1.4;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-all;
            margin: 15px 0;
        }
        .copy-button {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 10px 0;
        }
        .copy-button:hover {
            background: linear-gradient(135deg, #2980b9 0%, #1f618d 100%);
            transform: translateY(-1px);
        }
        .preview-section {
            margin-top: 30px;
        }
        .preview-section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .preview-container {
            border: 2px dashed #bdc3c7;
            padding: 20px;
            border-radius: 8px;
            background: #f8f9fa;
            text-align: center;
        }
        .preview-nametag {
            display: inline-block;
            margin: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        .tips {
            background: #fff3cd;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            border-left: 4px solid #ffc107;
        }
        .tips h3 {
            margin-top: 0;
            color: #856404;
        }
        .tips ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .tips li {
            margin: 5px 0;
        }
        .formatted-content {
            background: white;
            border: 2px solid #3498db;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .formatted-content:hover {
            border-color: #2980b9;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.2);
        }
        .formatted-content .nametag {
            display: inline-block;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Name Tag HTML Generator</h1>
            <p>Copy the HTML below into Word or Google Docs to create your name tags</p>
        </div>
        
        <div class="instructions">
            <h3>📝 How to Use This Content</h3>
            <ol>
                <li><strong>Click "Copy Formatted Content"</strong> button below</li>
                <li><strong>Open Word or Google Docs</strong> and create a new document</li>
                <li><strong>Paste the content</strong> (Ctrl+V or Cmd+V) - it will paste as formatted text, not HTML code</li>
                <li><strong>Adjust spacing and layout</strong> to match your label sheets</li>
                <li><strong>Copy and paste multiple times</strong> to create multiple name tags</li>
                <li><strong>Print and test</strong> on a regular sheet first</li>
                <li><strong>Print on your label sheets</strong> once spacing is perfect</li>
            </ol>
            <p><strong>💡 Pro Tip:</strong> For professional name tag labels, download the <a href="https://www.premiumlabelsupply.com/templates/pls389/" target="_blank" style="color: #3498db; text-decoration: underline;">Microsoft Word template for 3.375" x 2.33" labels</a> to get perfect spacing and alignment.</p>
        </div>
        
        <div class="copy-section">
            <h3>📋 Formatted Content to Copy</h3>
            <p>Click the button below to copy the formatted content, then paste it into Word or Google Docs:</p>
            <button class="copy-button" onclick="copyFormatted()">📋 Copy Formatted Content</button>
        </div>
        
        <div class="preview-section">
            <h3>👀 Preview</h3>
            <p>Here\'s how your name tag will look:</p>
            <div class="preview-container">
                <div class="preview-nametag">
                    ' . $nameTagHTML . '
                </div>
            </div>
        </div>
        
    </div>
    
    <script>
        function copyFormatted() {
            // Get the name tag HTML from the preview section
            const previewNametag = document.querySelector(".preview-nametag");
            
            // Create a range to select the formatted content
            const range = document.createRange();
            range.selectNode(previewNametag);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
            
            try {
                // Try to copy the formatted content
                document.execCommand("copy");
                window.getSelection().removeAllRanges();
                alert("Formatted content copied to clipboard! Now paste it into Word or Google Docs.");
            } catch (err) {
                // Fallback: copy as plain text
                const textContent = previewNametag.textContent;
                navigator.clipboard.writeText(textContent).then(function() {
                    alert("Content copied to clipboard! Now paste it into Word or Google Docs.");
                }).catch(function(err) {
                    alert("Please manually select and copy the content above, then paste it into Word or Google Docs.");
                });
            }
        }
    </script>
</body>
</html>';
}
?>
