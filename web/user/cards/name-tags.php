<?php
/**
 * Name Tag Creator
 * Generate printable name tag PDFs with 8 tags per sheet
 */

// Start output buffering
ob_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

// Require authentication
try {
    UserAuth::requireAuth();
} catch (Exception $e) {
    ob_clean();
    header('Location: /user/login.php');
    exit;
}

$db = Database::getInstance();

// Get card ID
$cardId = $_GET['id'] ?? '';
if (empty($cardId)) {
    header('Location: /user/dashboard.php');
    exit;
}

// Verify card ownership
$card = $db->querySingle(
    "SELECT id, first_name, last_name, company_name, job_title, theme FROM business_cards 
     WHERE id = ? AND user_id = ? AND is_active = 1",
    [$cardId, UserAuth::getUserId()]
);

if (!$card) {
    header('Location: /user/dashboard.php');
    exit;
}

// Get current preferences
$preferences = $db->querySingle(
    "SELECT * FROM name_tag_preferences WHERE card_id = ?",
    [$cardId]
);

// Get available fonts from NameTagGenerator
require_once __DIR__ . '/../../api/includes/NameTagGenerator.php';
$generator = new NameTagGenerator();
$availableFonts = $generator->getAvailableFonts();

// Set defaults if no preferences exist
if (!$preferences) {
    $preferences = [
        'include_name' => true,
        'include_title' => true,
        'include_company' => false,
        'include_phone' => true,
        'include_email' => true,
        'include_website' => true,
        'include_address' => false,
        'font_size' => '12',
        'font_family' => !empty($availableFonts) ? $availableFonts[0] : 'Arial',
        'line_spacing' => '0',
        'message_above' => '',
        'message_below' => '',
        'qr_size_percentage' => 100
    ];
}

$message = '';
$error = '';

// Handle form submission (save preferences)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save_preferences') {
        try {
            $newPreferences = [
                'include_name' => isset($_POST['include_name']) && $_POST['include_name'] === '1',
                'include_title' => isset($_POST['include_title']) && $_POST['include_title'] === '1',
                'include_company' => isset($_POST['include_company']) && $_POST['include_company'] === '1',
                'include_phone' => isset($_POST['include_phone']) && $_POST['include_phone'] === '1',
                'include_email' => isset($_POST['include_email']) && $_POST['include_email'] === '1',
                'include_website' => isset($_POST['include_website']) && $_POST['include_website'] === '1',
                'include_address' => isset($_POST['include_address']) && $_POST['include_address'] === '1',
                'font_size' => $_POST['font_size'] ?? '12',
                'font_family' => trim($_POST['font_family'] ?? 'Arial'),
                'line_spacing' => isset($_POST['line_spacing']) ? (string)round((float)$_POST['line_spacing'], 1) : '0',
                'message_above' => trim($_POST['message_above'] ?? ''),
                'message_below' => trim($_POST['message_below'] ?? ''),
                'qr_size_percentage' => isset($_POST['qr_size_percentage']) ? (int)$_POST['qr_size_percentage'] : 100
            ];
            
            // Validate font size option
            if (!in_array($newPreferences['font_size'], ['8', '9', '10', '11', '12', '13', '14', '15', '16', '18', '20'])) {
                throw new Exception('Invalid font size option');
            }
            
            // Validate font family
            if (empty($newPreferences['font_family'])) {
                $newPreferences['font_family'] = !empty($availableFonts) ? $availableFonts[0] : 'Arial';
            }
            
            // Validate line spacing
            $lineSpacingFloat = (float)$newPreferences['line_spacing'];
            if ($lineSpacingFloat < -5.0 || $lineSpacingFloat > 5.0) {
                throw new Exception('Line spacing must be between -5.0 and 5.0');
            }
            
            // Validate QR size percentage
            if ($newPreferences['qr_size_percentage'] < 25 || $newPreferences['qr_size_percentage'] > 150) {
                throw new Exception('QR code size must be between 25% and 150%');
            }
            
            // Validate message fields
            if (strlen($newPreferences['message_above']) > 100) {
                throw new Exception('Message above is too long (max 100 characters)');
            }
            if (strlen($newPreferences['message_below']) > 100) {
                throw new Exception('Message below is too long (max 100 characters)');
            }
            
            // Check if preferences exist
            $existing = $db->querySingle(
                "SELECT id FROM name_tag_preferences WHERE card_id = ?",
                [$cardId]
            );
            
            if ($existing) {
                // Update existing preferences
                $db->execute(
                    "UPDATE name_tag_preferences SET 
                     include_name = ?, include_title = ?, include_company = ?,
                     include_phone = ?, include_email = ?, include_website = ?, include_address = ?, 
                     font_size = ?, font_family = ?, line_spacing = ?, message_above = ?, message_below = ?, qr_size_percentage = ?, updated_at = NOW()
                     WHERE card_id = ?",
                    [
                        $newPreferences['include_name'] ? 1 : 0,
                        $newPreferences['include_title'] ? 1 : 0,
                        $newPreferences['include_company'] ? 1 : 0,
                        $newPreferences['include_phone'] ? 1 : 0,
                        $newPreferences['include_email'] ? 1 : 0,
                        $newPreferences['include_website'] ? 1 : 0,
                        $newPreferences['include_address'] ? 1 : 0,
                        $newPreferences['font_size'],
                        $newPreferences['font_family'],
                        $newPreferences['line_spacing'],
                        $newPreferences['message_above'],
                        $newPreferences['message_below'],
                        $newPreferences['qr_size_percentage'],
                        $cardId
                    ]
                );
            } else {
                // Create new preferences
                $id = bin2hex(random_bytes(16));
                $db->execute(
                    "INSERT INTO name_tag_preferences 
                     (id, card_id, include_name, include_title, include_company,
                      include_phone, include_email, include_website, include_address, font_size, font_family, line_spacing, message_above, message_below, qr_size_percentage) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $id,
                        $cardId,
                        $newPreferences['include_name'] ? 1 : 0,
                        $newPreferences['include_title'] ? 1 : 0,
                        $newPreferences['include_company'] ? 1 : 0,
                        $newPreferences['include_phone'] ? 1 : 0,
                        $newPreferences['include_email'] ? 1 : 0,
                        $newPreferences['include_website'] ? 1 : 0,
                        $newPreferences['include_address'] ? 1 : 0,
                        $newPreferences['font_size'],
                        $newPreferences['font_family'],
                        $newPreferences['line_spacing'],
                        $newPreferences['message_above'],
                        $newPreferences['message_below'],
                        $newPreferences['qr_size_percentage']
                    ]
                );
            }
            
            $preferences = $newPreferences;
            $message = 'Preferences saved successfully!';
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Name Tag Creator - ShareMyCard</title>
    <link rel="stylesheet" href="/user/includes/user-style.css">
    <style>
        .name-tag-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .card-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card-header h1 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .usage-instructions {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        
        .usage-instructions h3 {
            margin-top: 0;
            color: #007bff;
        }
        
        .usage-instructions p {
            margin: 10px 0;
            color: #666;
        }
        
        .usage-instructions a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        
        .usage-instructions a:hover {
            text-decoration: underline;
        }
        
        .card-info {
            color: #666;
            font-size: 14px;
        }
        
        
        
        .controls-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        
        .controls-section h2 {
            margin-top: 0;
            color: #333;
        }
        
        .control-group {
            margin: 20px 0;
        }
        
        .control-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }
        
        .control-group select,
        .control-group input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            background: white;
        }
        
        .help-text {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            color: #666;
            font-style: italic;
        }
        
        .control-group input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .checkbox-label:hover {
            background: #f9f9f9;
        }
        
        .checkbox-label input {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .actions {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 20px 0;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <a href="/user/dashboard.php" class="brand-link">
                <span class="brand-icon">📇</span>
                <span class="brand-text">ShareMyCard</span>
            </a>
        </div>
        <button class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div class="nav-links mobile">
            <a href="/user/dashboard.php" class="nav-link">Dashboard</a>
            <a href="/user/cards/invitation-analytics.php" class="nav-link">📧 Invitations</a>
            <a href="#" onclick="openAccountSecurity()" class="nav-link">🔒 Security</a>
            <a href="https://github.com/mwarrick/digital-business-card/issues" target="_blank" class="nav-link">🐛 Report Issues</a>
            <a href="/user/logout.php" class="nav-link">Logout</a>
        </div>
    </nav>
    
    <div class="name-tag-container">
        <a href="/user/cards/view.php?id=<?php echo htmlspecialchars($cardId); ?>" class="back-link">← Back to Card</a>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="card-header">
            <h1>🏷️ Name Tag Creator</h1>
            <div class="card-info">
                <strong>Card:</strong> <?php echo htmlspecialchars($card['first_name'] . ' ' . $card['last_name']); ?>
                <?php if (!empty($card['job_title'])): ?>
                    | <?php echo htmlspecialchars($card['job_title']); ?>
                <?php endif; ?>
                <?php if (!empty($card['company_name'])): ?>
                    | <?php echo htmlspecialchars($card['company_name']); ?>
                <?php endif; ?>
            </div>
            <div style="margin-top: 15px;">
                <a href="/user/cards/name-tags-qr-surround.php?id=<?php echo htmlspecialchars($cardId); ?>" 
                   style="display: inline-block; padding: 8px 16px; background: #e67e22; color: white; text-decoration: none; border-radius: 5px; font-size: 14px; font-weight: 500;">
                    👋 Try "Hello My Name Is" Variant
                </a>
            </div>
        </div>
        
        <div class="usage-instructions">
            <h3>📋 How to Use Name Tags</h3>
            <p><strong>Step 1:</strong> Customize your name tag using the options below.</p>
            <p><strong>Step 2:</strong> Click "Download PDF" to get a printable sheet with 8 identical name tags.</p>
            <p><strong>Step 3:</strong> Print on a standard 8.5" x 11" piece of paper as a test overlay for your labels.</p>
            <p><strong>Step 4:</strong> For final printing, use <a href="https://a.co/d/5d1q5ZK" target="_blank">Avery Flexible Printable Name Tags, 2-1/3" x 3-3/8", Matte White, 80 Removable Name Badges (25395)</a>.</p>
        </div>
        
        <form id="preferences-form" method="POST">
            <input type="hidden" name="action" value="save_preferences">
            
            <div class="controls-section">
                <h2>Customize Name Tag</h2>
                
                <div class="control-group">
                    <label for="font_family">Font Family:</label>
                    <select name="font_family" id="font_family" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                        <?php if (empty($availableFonts)): ?>
                            <option value="Arial">Arial (System Default)</option>
                        <?php else: ?>
                            <?php foreach ($availableFonts as $font): ?>
                                <option value="<?php echo htmlspecialchars($font); ?>" 
                                        <?php echo (($preferences['font_family'] ?? 'Arial') === $font) ? 'selected' : ''; ?>
                                        style="font-family: '<?php echo htmlspecialchars($font); ?>', cursive;">
                                    <?php echo htmlspecialchars($font); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <small class="help-text">Select a font for the name tag text</small>
                </div>
                
                <div class="control-group">
                    <label for="font_size">Font Size:</label>
                    <select name="font_size" id="font_size">
                        <option value="8" <?php echo $preferences['font_size'] === '8' ? 'selected' : ''; ?>>8pt (Very Small)</option>
                        <option value="9" <?php echo $preferences['font_size'] === '9' ? 'selected' : ''; ?>>9pt (Small)</option>
                        <option value="10" <?php echo $preferences['font_size'] === '10' ? 'selected' : ''; ?>>10pt (Small)</option>
                        <option value="11" <?php echo $preferences['font_size'] === '11' ? 'selected' : ''; ?>>11pt (Small)</option>
                        <option value="12" <?php echo $preferences['font_size'] === '12' ? 'selected' : ''; ?>>12pt (Normal)</option>
                        <option value="13" <?php echo $preferences['font_size'] === '13' ? 'selected' : ''; ?>>13pt (Medium)</option>
                        <option value="14" <?php echo $preferences['font_size'] === '14' ? 'selected' : ''; ?>>14pt (Medium)</option>
                        <option value="15" <?php echo $preferences['font_size'] === '15' ? 'selected' : ''; ?>>15pt (Large)</option>
                        <option value="16" <?php echo $preferences['font_size'] === '16' ? 'selected' : ''; ?>>16pt (Large)</option>
                        <option value="18" <?php echo $preferences['font_size'] === '18' ? 'selected' : ''; ?>>18pt (Very Large)</option>
                        <option value="20" <?php echo $preferences['font_size'] === '20' ? 'selected' : ''; ?>>20pt (Extra Large)</option>
                    </select>
                </div>
                
                <div class="control-group">
                    <label for="line_spacing">Line Spacing:</label>
                    <input type="number" name="line_spacing" id="line_spacing" 
                           value="<?php echo htmlspecialchars($preferences['line_spacing'] ?? '0'); ?>" 
                           min="-5.0" max="5.0" step="0.1" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    <small class="help-text">Adjust spacing between lines (-5.0 to 5.0, 0 = normal, positive = more space, negative = less space)</small>
                </div>
                
                <div class="control-group">
                    <label for="message_above">Message Above Card Data:</label>
                    <input type="text" name="message_above" id="message_above" 
                           value="<?php echo htmlspecialchars($preferences['message_above'] ?? ''); ?>" 
                           placeholder="e.g., Welcome to our event!" maxlength="100">
                    <small class="help-text">Optional message that appears above the card information</small>
                </div>
                
                <div class="control-group">
                    <label for="message_below">Message Below Card Data:</label>
                    <input type="text" name="message_below" id="message_below" 
                           value="<?php echo htmlspecialchars($preferences['message_below'] ?? ''); ?>" 
                           placeholder="e.g., Thank you for visiting!" maxlength="100">
                    <small class="help-text">Optional message that appears below the card information</small>
                </div>
                
                <div class="control-group">
                    <label for="qr_size_percentage">QR Code Size:</label>
                    <input type="number" name="qr_size_percentage" id="qr_size_percentage" 
                           value="<?php echo htmlspecialchars($preferences['qr_size_percentage'] ?? '100'); ?>" 
                           min="25" max="150" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    <small class="help-text">Size of QR code as percentage (25-150%, default 100%). Lower values make the QR code smaller.</small>
                </div>
                
                <div class="control-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="include_name" id="include_name" value="1" <?php echo $preferences['include_name'] ? 'checked' : ''; ?>>
                        <span>Include Name</span>
                    </label>
                </div>
                
                <div class="control-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="include_title" id="include_title" value="1" <?php echo $preferences['include_title'] ? 'checked' : ''; ?>>
                        <span>Include Job Title</span>
                    </label>
                </div>
                
                <div class="control-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="include_company" id="include_company" value="1" <?php echo $preferences['include_company'] ? 'checked' : ''; ?>>
                        <span>Include Company Name</span>
                    </label>
                </div>
                
                <div class="control-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="include_phone" id="include_phone" value="1" <?php echo $preferences['include_phone'] ? 'checked' : ''; ?>>
                        <span>Include Primary Phone</span>
                    </label>
                </div>
                
                <div class="control-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="include_email" id="include_email" value="1" <?php echo $preferences['include_email'] ? 'checked' : ''; ?>>
                        <span>Include Primary Email</span>
                    </label>
                </div>
                
                <div class="control-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="include_website" id="include_website" value="1" <?php echo $preferences['include_website'] ? 'checked' : ''; ?>>
                        <span>Include Primary Website</span>
                    </label>
                </div>
                
                <div class="control-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="include_address" id="include_address" value="1" <?php echo $preferences['include_address'] ? 'checked' : ''; ?>>
                        <span>Include Address</span>
                    </label>
                </div>
            </div>
        </form>
        
        <div class="preview-section" style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: 20px 0;">
            <h2 style="margin-top: 0; color: #333;">Preview</h2>
            <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; overflow: hidden;">
                <div id="preview-container" style="text-align: center; min-height: 200px; display: flex; align-items: center; justify-content: center;">
                    <p style="color: #666;">Click "Update Preview" to see your name tag</p>
                </div>
            </div>
            <div style="text-align: center; margin-top: 15px;">
                <button onclick="updatePreview()" class="btn btn-secondary" style="background: #6c757d; color: white;">🔄 Update Preview</button>
            </div>
        </div>
        
        <div class="actions">
            <button onclick="savePreferences()" class="btn btn-secondary">💾 Save Settings</button>
            <button onclick="downloadPDF()" class="btn btn-primary">📄 Download PDF (8 tags)</button>
            <button onclick="generateHTML()" class="btn btn-primary" style="background: #e67e22; color: white;">📋 Copy HTML for Word/Google Docs</button>
        </div>
    </div>
    
    <script>
        const cardId = '<?php echo addslashes($cardId); ?>';
        const form = document.getElementById('preferences-form');
        const previewContainer = document.getElementById('preview-container');
        
        // Build preview URL parameters
        function getPreviewParams() {
            return new URLSearchParams({
                card_id: cardId,
                font_family: document.getElementById('font_family').value,
                font_size: document.getElementById('font_size').value,
                line_spacing: document.getElementById('line_spacing').value,
                message_above: document.getElementById('message_above').value,
                message_below: document.getElementById('message_below').value,
                qr_size_percentage: document.getElementById('qr_size_percentage').value,
                include_website: document.getElementById('include_website').checked ? '1' : '0',
                include_name: document.getElementById('include_name').checked ? '1' : '0',
                include_title: document.getElementById('include_title').checked ? '1' : '0',
                include_company: document.getElementById('include_company').checked ? '1' : '0',
                include_phone: document.getElementById('include_phone').checked ? '1' : '0',
                include_email: document.getElementById('include_email').checked ? '1' : '0',
                include_address: document.getElementById('include_address').checked ? '1' : '0',
                _t: Date.now() // Cache busting
            });
        }
        
        // Update preview
        function updatePreview() {
            const params = getPreviewParams();
            const previewUrl = `/user/cards/preview-name-tag.php?${params.toString()}`;
            
            // Standard name tag dimensions: 3.375" x 2.33" = 243pt x 168pt
            // Image is generated at 2x scale (486x336px)
            // Scale down for preview display - make it smaller to fit better
            const maxPreviewWidth = 300; // Further reduced to ensure it fits
            const imageWidth = 486; // Actual image width (243pt * 2)
            const imageHeight = 336; // Actual image height (168pt * 2)
            const scale = maxPreviewWidth / imageWidth;
            const previewWidth = Math.round(imageWidth * scale);
            const previewHeight = Math.round(imageHeight * scale);
            
            // Clear container first and remove flex display
            previewContainer.innerHTML = '';
            previewContainer.style.display = 'block';
            previewContainer.style.textAlign = 'center';
            
            // Create and style the image element with explicit sizing
            const img = document.createElement('img');
            img.src = previewUrl;
            img.alt = 'Name Tag Preview';
            img.width = previewWidth;
            img.height = previewHeight;
            img.style.cssText = `max-width: ${maxPreviewWidth}px !important; width: ${previewWidth}px !important; height: auto !important; border: 1px solid #ddd; border-radius: 4px; display: block; margin: 0 auto;`;
            
            // Add error handler
            img.onerror = function() {
                previewContainer.innerHTML = '<p style="color: #d32f2f;">Failed to load preview image. Please try again.</p>';
                previewContainer.style.display = 'flex';
                previewContainer.style.textAlign = 'center';
            };
            
            previewContainer.appendChild(img);
        }
        
        // Save preferences
        function savePreferences() {
            form.submit();
        }
        
        // Download PDF
        function downloadPDF() {
            const params = getPreviewParams();
            params.delete('_t'); // Remove cache busting parameter for PDF
            const downloadUrl = `/user/cards/download-name-tags-html.php?${params.toString()}`;
            console.log('Downloading PDF with URL:', downloadUrl);
            window.location.href = downloadUrl;
        }
        
        // Generate HTML for Word/Google Docs
        function generateHTML() {
            const params = getPreviewParams();
            params.delete('_t'); // Remove cache busting parameter for HTML
            const htmlUrl = `/user/cards/generate-name-tags-html.php?${params.toString()}`;
            window.open(htmlUrl, '_blank');
        }
        
        // Auto-update preview on page load
        window.addEventListener('DOMContentLoaded', function() {
            updatePreview();
        });
    </script>
    <script src="/user/includes/user-script.js"></script>
</body>
</html>

