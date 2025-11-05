<?php
/**
 * Name Tag Creator - "Hello My Name Is" Variant
 * Generate printable name tag PDFs with QR code surrounding the name
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
$userId = UserAuth::getUserId();

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
    [$cardId, $userId]
);

if (!$card) {
    header('Location: /user/dashboard.php');
    exit;
}

// Get current preferences
$preferences = $db->querySingle(
    "SELECT * FROM name_tag_qr_surround_preferences WHERE card_id = ? AND user_id = ?",
    [$cardId, $userId]
);

// Set defaults if no preferences exist
if (!$preferences) {
    $preferences = [
        'top_banner_text' => 'Hello My Name Is...',
        'top_banner_color' => '#000000',
        'top_banner_font_family' => 'Caveat',
        'top_banner_font_size' => 16,
        'bottom_banner_text' => '',
        'bottom_banner_color' => '#000000',
        'bottom_banner_font_family' => 'Caveat',
        'bottom_banner_font_size' => 8,
        'top_margin' => 45,
        'vertical_gap' => 13,
        'horizontal_gap' => 30,
        'left_margin' => 45
    ];
}

$message = '';
$error = '';

// Handle form submission (save preferences)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save_preferences') {
        try {
            // Validate banner text lengths
            $topBannerText = trim($_POST['top_banner_text'] ?? 'Hello My Name Is...');
            $bottomBannerText = trim($_POST['bottom_banner_text'] ?? '');
            
            if (strlen($topBannerText) > 100) {
                throw new Exception('Top banner text is too long (max 100 characters)');
            }
            
            if (strlen($bottomBannerText) > 100) {
                throw new Exception('Bottom banner text is too long (max 100 characters)');
            }
            
            // Validate colors (hex format)
            $topBannerColor = $_POST['top_banner_color'] ?? '#000000';
            $bottomBannerColor = $_POST['bottom_banner_color'] ?? '#000000';
            
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $topBannerColor)) {
                throw new Exception('Invalid top banner color format');
            }
            
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $bottomBannerColor)) {
                throw new Exception('Invalid bottom banner color format');
            }
            
            // Validate font families
            $topBannerFontFamily = $_POST['top_banner_font_family'] ?? 'Caveat';
            $bottomBannerFontFamily = $_POST['bottom_banner_font_family'] ?? 'Caveat';
            $allowedFonts = ['Dancing Script', 'Caveat', 'Kalam'];
            if (!in_array($topBannerFontFamily, $allowedFonts)) {
                throw new Exception('Invalid top banner font family');
            }
            if (!in_array($bottomBannerFontFamily, $allowedFonts)) {
                throw new Exception('Invalid bottom banner font family');
            }
            
            // Validate font sizes
            $topBannerFontSize = (int)($_POST['top_banner_font_size'] ?? 16);
            $bottomBannerFontSize = (int)($_POST['bottom_banner_font_size'] ?? 8);
            
            if ($topBannerFontSize < 8 || $topBannerFontSize > 100) {
                throw new Exception('Top banner font size must be between 8 and 100');
            }
            if ($bottomBannerFontSize < 6 || $bottomBannerFontSize > 100) {
                throw new Exception('Bottom banner font size must be between 6 and 100');
            }
            
            // Validate spacing values
            $topMargin = (int)($_POST['top_margin'] ?? 45);
            $verticalGap = (int)($_POST['vertical_gap'] ?? 13);
            $horizontalGap = (int)($_POST['horizontal_gap'] ?? 30);
            $leftMargin = isset($_POST['left_margin']) && $_POST['left_margin'] !== '' ? (int)$_POST['left_margin'] : null;
            
            if ($topMargin < 0 || $topMargin > 100) {
                throw new Exception('Top margin must be between 0 and 100');
            }
            if ($verticalGap < 0 || $verticalGap > 50) {
                throw new Exception('Vertical gap must be between 0 and 50');
            }
            if ($horizontalGap < 0 || $horizontalGap > 100) {
                throw new Exception('Horizontal gap must be between 0 and 100');
            }
            if ($leftMargin !== null && ($leftMargin < 0 || $leftMargin > 200)) {
                throw new Exception('Left margin must be between 0 and 200');
            }
            
            // Check if preferences exist
            $existing = $db->querySingle(
                "SELECT id FROM name_tag_qr_surround_preferences WHERE card_id = ? AND user_id = ?",
                [$cardId, $userId]
            );
            
            $prefId = $existing ? $existing['id'] : bin2hex(random_bytes(16));
            
            if ($existing) {
                // Update existing preferences
                $db->execute(
                    "UPDATE name_tag_qr_surround_preferences SET 
                     top_banner_text = ?, top_banner_color = ?, top_banner_font_family = ?, top_banner_font_size = ?,
                     bottom_banner_text = ?, bottom_banner_color = ?, bottom_banner_font_family = ?, bottom_banner_font_size = ?,
                     top_margin = ?, vertical_gap = ?, horizontal_gap = ?, left_margin = ?,
                     updated_at = NOW()
                     WHERE card_id = ? AND user_id = ?",
                    [
                        $topBannerText,
                        $topBannerColor,
                        $topBannerFontFamily,
                        $topBannerFontSize,
                        $bottomBannerText,
                        $bottomBannerColor,
                        $bottomBannerFontFamily,
                        $bottomBannerFontSize,
                        $topMargin,
                        $verticalGap,
                        $horizontalGap,
                        $leftMargin,
                        $cardId,
                        $userId
                    ]
                );
            } else {
                // Create new preferences
                $db->execute(
                    "INSERT INTO name_tag_qr_surround_preferences 
                     (id, user_id, card_id, top_banner_text, top_banner_color, top_banner_font_family, top_banner_font_size,
                      bottom_banner_text, bottom_banner_color, bottom_banner_font_family, bottom_banner_font_size,
                      top_margin, vertical_gap, horizontal_gap, left_margin) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $prefId,
                        $userId,
                        $cardId,
                        $topBannerText,
                        $topBannerColor,
                        $topBannerFontFamily,
                        $topBannerFontSize,
                        $bottomBannerText,
                        $bottomBannerColor,
                        $bottomBannerFontFamily,
                        $bottomBannerFontSize,
                        $topMargin,
                        $verticalGap,
                        $horizontalGap,
                        $leftMargin
                    ]
                );
            }
            
            $preferences = [
                'top_banner_text' => $topBannerText,
                'top_banner_color' => $topBannerColor,
                'top_banner_font_family' => $topBannerFontFamily,
                'top_banner_font_size' => $topBannerFontSize,
                'bottom_banner_text' => $bottomBannerText,
                'bottom_banner_color' => $bottomBannerColor,
                'bottom_banner_font_family' => $bottomBannerFontFamily,
                'bottom_banner_font_size' => $bottomBannerFontSize,
                'top_margin' => $topMargin,
                'vertical_gap' => $verticalGap,
                'horizontal_gap' => $horizontalGap,
                'left_margin' => $leftMargin
            ];
            
            $message = 'Preferences saved successfully!';
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get name from POST or use default (first name)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hello My Name Is - ShareMyCard</title>
    <!-- Google Fonts for handwriting style -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600;700&family=Caveat:wght@400;600;700&family=Kalam:wght@300;400;700&display=swap" rel="stylesheet">
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
        
        .control-group input[type="text"],
        .control-group input[type="color"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            background: white;
        }
        
        .control-group input[type="color"] {
            height: 50px;
            cursor: pointer;
        }
        
        .help-text {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            color: #666;
            font-style: italic;
        }
        
        .name-validation {
            margin-top: 5px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .name-validation.error {
            color: #dc3545;
        }
        
        .name-validation.success {
            color: #28a745;
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
        
        .preview-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 20px 0;
            text-align: center;
        }
        
        .preview-section h2 {
            margin-top: 0;
            color: #333;
        }
        
        .preview-wrapper {
            display: inline-block;
            background: white;
            border: 2px dashed #ccc;
            padding: 0;
            text-align: center;
        }
        
        .preview-banner {
            width: 100%;
            padding: 0;
            text-align: center;
            color: white;
            font-weight: bold;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .preview-banner.top {
            border-top-left-radius: 4px;
            border-top-right-radius: 4px;
        }
        
        .preview-banner.bottom {
            border-bottom-left-radius: 4px;
            border-bottom-right-radius: 4px;
        }
        
        .preview-container {
            border: 2px dashed #ddd;
            padding: 20px;
            margin: 20px 0;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .preview-placeholder {
            color: #999;
            font-style: italic;
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
            <h1>👋 Hello My Name Is</h1>
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
                <a href="/user/cards/name-tags.php?id=<?php echo htmlspecialchars($cardId); ?>" 
                   style="display: inline-block; padding: 8px 16px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-size: 14px; font-weight: 500;">
                    🏷️ Standard Name Tags
                </a>
            </div>
        </div>
        
        <div class="usage-instructions">
            <h3>📋 How to Use "Hello My Name Is" Name Tags</h3>
            <p><strong>Step 1:</strong> Customize the top and bottom banners with your text.</p>
            <p><strong>Step 2:</strong> Click "Preview" to see how your name tag will look.</p>
            <p><strong>Step 3:</strong> Click "Download PDF" to get a printable sheet with 8 identical name tags.</p>
            <p><strong>Step 4:</strong> Print on standard 8.5" x 11" paper or use <a href="https://a.co/d/5d1q5ZK" target="_blank">Avery Flexible Printable Name Tags</a>.</p>
        </div>
        
        <form id="preferences-form" method="POST">
            <input type="hidden" name="action" value="save_preferences">
            
            <div class="controls-section">
                <h2>Customize Name Tag</h2>
                
                <div class="control-group">
                    <label for="top_banner_text">Top Banner Text:</label>
                    <input type="text" name="top_banner_text" id="top_banner_text" 
                           value="<?php echo htmlspecialchars($preferences['top_banner_text'] ?? 'Hello My Name Is...'); ?>" 
                           placeholder="Hello My Name Is..." maxlength="100">
                    <small class="help-text">Text that appears at the top of the name tag</small>
                </div>
                
                <div class="control-group">
                    <label for="top_banner_color">Top Banner Color:</label>
                    <input type="color" name="top_banner_color" id="top_banner_color" 
                           value="<?php echo htmlspecialchars($preferences['top_banner_color'] ?? '#000000'); ?>">
                    <small class="help-text">Background color for the top banner</small>
                </div>
                
                <div class="control-group">
                    <label for="top_banner_font_family">Top Banner Font:</label>
                    <select name="top_banner_font_family" id="top_banner_font_family" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                        <optgroup label="Available Fonts">
                            <option value="Dancing Script" <?php echo (($preferences['top_banner_font_family'] ?? 'Caveat') === 'Dancing Script') ? 'selected' : ''; ?>>Dancing Script (Elegant)</option>
                            <option value="Caveat" <?php echo (($preferences['top_banner_font_family'] ?? 'Caveat') === 'Caveat') ? 'selected' : ''; ?>>Caveat (Casual)</option>
                            <option value="Kalam" <?php echo (($preferences['top_banner_font_family'] ?? 'Caveat') === 'Kalam') ? 'selected' : ''; ?>>Kalam (Playful)</option>
                        </optgroup>
                    </select>
                </div>
                
                <div class="control-group">
                    <label for="top_banner_font_size">Top Banner Font Size:</label>
                    <input type="number" name="top_banner_font_size" id="top_banner_font_size" 
                           value="<?php echo htmlspecialchars($preferences['top_banner_font_size'] ?? '16'); ?>" 
                           min="8" max="100" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    <small class="help-text">Font size in pixels (8-100)</small>
                </div>
                
                <div class="control-group">
                    <label for="bottom_banner_text">Bottom Banner Text (Optional):</label>
                    <input type="text" name="bottom_banner_text" id="bottom_banner_text" 
                           value="<?php echo htmlspecialchars($preferences['bottom_banner_text'] ?? ''); ?>" 
                           placeholder="e.g., Company Name, Event Name" maxlength="100">
                    <small class="help-text">Optional text that appears at the bottom of the name tag</small>
                </div>
                
                <div class="control-group">
                    <label for="bottom_banner_color">Bottom Banner Color:</label>
                    <input type="color" name="bottom_banner_color" id="bottom_banner_color" 
                           value="<?php echo htmlspecialchars($preferences['bottom_banner_color'] ?? '#000000'); ?>">
                    <small class="help-text">Background color for the bottom banner</small>
                </div>
                
                <div class="control-group">
                    <label for="bottom_banner_font_family">Bottom Banner Font:</label>
                    <select name="bottom_banner_font_family" id="bottom_banner_font_family" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                        <optgroup label="Available Fonts">
                            <option value="Dancing Script" <?php echo (($preferences['bottom_banner_font_family'] ?? 'Caveat') === 'Dancing Script') ? 'selected' : ''; ?>>Dancing Script (Elegant)</option>
                            <option value="Caveat" <?php echo (($preferences['bottom_banner_font_family'] ?? 'Caveat') === 'Caveat') ? 'selected' : ''; ?>>Caveat (Casual)</option>
                            <option value="Kalam" <?php echo (($preferences['bottom_banner_font_family'] ?? 'Caveat') === 'Kalam') ? 'selected' : ''; ?>>Kalam (Playful)</option>
                        </optgroup>
                    </select>
                </div>
                
                <div class="control-group">
                    <label for="bottom_banner_font_size">Bottom Banner Font Size:</label>
                    <input type="number" name="bottom_banner_font_size" id="bottom_banner_font_size" 
                           value="<?php echo htmlspecialchars($preferences['bottom_banner_font_size'] ?? '8'); ?>" 
                           min="6" max="100" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    <small class="help-text">Font size in pixels (6-100). Top banner will be automatically 2x this size.</small>
                </div>
                
                <div class="control-group" style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
                    <h3 style="margin-top: 0; color: #333;">PDF Layout Spacing</h3>
                    <small class="help-text" style="display: block; margin-bottom: 15px;">Adjust spacing for the PDF layout (in points, 1 inch = 72 points)</small>
                </div>
                
                <div class="control-group">
                    <label for="top_margin">Top Margin:</label>
                    <input type="number" name="top_margin" id="top_margin" 
                           value="<?php echo htmlspecialchars($preferences['top_margin'] ?? '45'); ?>" 
                           min="0" max="100" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    <small class="help-text">Space from top of page to first row (0-100 points)</small>
                </div>
                
                <div class="control-group">
                    <label for="vertical_gap">Vertical Gap (Between Rows):</label>
                    <input type="number" name="vertical_gap" id="vertical_gap" 
                           value="<?php echo htmlspecialchars($preferences['vertical_gap'] ?? '13'); ?>" 
                           min="0" max="50" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    <small class="help-text">Space between rows of name tags (0-50 points)</small>
                </div>
                
                <div class="control-group">
                    <label for="horizontal_gap">Horizontal Gap (Between Columns):</label>
                    <input type="number" name="horizontal_gap" id="horizontal_gap" 
                           value="<?php echo htmlspecialchars($preferences['horizontal_gap'] ?? '30'); ?>" 
                           min="0" max="100" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    <small class="help-text">Space between the two columns (0-100 points)</small>
                </div>
                
                <div class="control-group">
                    <label for="left_margin">Left Margin:</label>
                    <input type="number" name="left_margin" id="left_margin" 
                           value="<?php echo htmlspecialchars($preferences['left_margin'] ?? '45'); ?>" 
                           min="0" max="200" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                    <small class="help-text">Space from left edge of page to first column (0-200 points). Leave empty to auto-center.</small>
                </div>
            </div>
        </form>
        
        <div class="preview-section">
            <h2>Preview</h2>
            <div class="preview-container" id="preview-container">
                <div class="preview-placeholder">Click "Preview" to see your name tag</div>
            </div>
        </div>
        
        <div class="actions">
            <button onclick="savePreferences()" class="btn btn-secondary">💾 Save Settings</button>
            <button onclick="previewNameTag()" class="btn btn-primary">👁️ Preview</button>
            <button onclick="downloadPDF()" class="btn btn-primary">📄 Download PDF (8 tags)</button>
            <button onclick="copyHTML()" class="btn btn-secondary">📋 Copy HTML for Word/Google Docs</button>
        </div>
    </div>
    
    <script>
        const cardId = '<?php echo addslashes($cardId); ?>';
        const form = document.getElementById('preferences-form');
        
        // Save preferences
        function savePreferences() {
            form.submit();
        }
        
        // Preview name tag
        function previewNameTag() {
            const params = new URLSearchParams({
                card_id: cardId,
                top_banner_text: document.getElementById('top_banner_text').value,
                top_banner_color: document.getElementById('top_banner_color').value,
                top_banner_font_family: document.getElementById('top_banner_font_family').value,
                top_banner_font_size: document.getElementById('top_banner_font_size').value,
                bottom_banner_text: document.getElementById('bottom_banner_text').value,
                bottom_banner_color: document.getElementById('bottom_banner_color').value,
                bottom_banner_font_family: document.getElementById('bottom_banner_font_family').value,
                bottom_banner_font_size: document.getElementById('bottom_banner_font_size').value
            });
            
            // Add cache-busting parameter to force refresh
            params.append('_t', Date.now());
            const previewUrl = `/user/cards/preview-name-tag-qr-surround.php?${params.toString()}`;
            const previewContainer = document.getElementById('preview-container');
            
            // Get banner values
            const topBannerText = document.getElementById('top_banner_text').value;
            const topBannerColor = document.getElementById('top_banner_color').value;
            const topBannerFontFamily = document.getElementById('top_banner_font_family').value;
            const topBannerFontSize = parseInt(document.getElementById('top_banner_font_size').value) || 16;
            
            const bottomBannerText = document.getElementById('bottom_banner_text').value;
            const bottomBannerColor = document.getElementById('bottom_banner_color').value;
            const bottomBannerFontFamily = document.getElementById('bottom_banner_font_family').value;
            const bottomBannerFontSize = parseInt(document.getElementById('bottom_banner_font_size').value) || 8;
            
            // Top banner is twice the size of bottom banner
            const effectiveTopFontSize = bottomBannerFontSize * 2;
            
            // Scale preview to max 860px wide (container is ~940px)
            const printWidth = 1013;
            const printHeight = 700;
            const maxPreviewWidth = 860;
            const scaleFactor = maxPreviewWidth / printWidth;
            const previewWidth = Math.round(printWidth * scaleFactor);
            const previewHeight = Math.round(printHeight * scaleFactor);
            
            // Calculate banner heights to match image generation
            // Formula: fontSize * 2.5 + padding, with top = 2x bottom
            const bottomBannerHeightPx = Math.round(bottomBannerFontSize * 2.5 + 40) * scaleFactor;
            const topBannerHeightPx = Math.round(effectiveTopFontSize * 2.5 + 80) * scaleFactor;
            // Ensure top is 2x bottom
            const finalTopBannerHeight = Math.max(topBannerHeightPx, bottomBannerHeightPx * 2);

            // Build HTML with CSS banners, using scaled width
            let html = `<div class="preview-wrapper" style="width:${previewWidth}px">`;
            
            // Top banner (CSS) - match exact height from image
            // Use the actual font size from the input, not the calculated effective size
            const actualTopFontSize = parseInt(document.getElementById('top_banner_font_size').value) || 16;
            if (topBannerText) {
                html += `<div class="preview-banner top" style="background-color: ${topBannerColor}; font-family: ${topBannerFontFamily}; font-size: ${actualTopFontSize}px; height: ${finalTopBannerHeight}px; line-height: ${finalTopBannerHeight}px; padding: 0;">${topBannerText}</div>`;
            }
            
            // QR code image - scaled to match
            html += `<img src="${previewUrl}" alt="Name Tag Preview" style="display: block; width: ${previewWidth}px; height: auto;" />`;
            
            // Bottom banner (CSS) - match exact height from image
            // Use the actual font size from the input
            const actualBottomFontSize = parseInt(document.getElementById('bottom_banner_font_size').value) || 8;
            if (bottomBannerText) {
                html += `<div class="preview-banner bottom" style="background-color: ${bottomBannerColor}; font-family: ${bottomBannerFontFamily}; font-size: ${actualBottomFontSize}px; height: ${bottomBannerHeightPx}px; line-height: ${bottomBannerHeightPx}px; padding: 0;">${bottomBannerText}</div>`;
            }
            
            html += '</div>';
            
            previewContainer.innerHTML = html;
        }
        
        // Download PDF
        function downloadPDF() {
            const params = new URLSearchParams({
                card_id: cardId,
                top_banner_text: document.getElementById('top_banner_text').value,
                top_banner_color: document.getElementById('top_banner_color').value,
                top_banner_font_family: document.getElementById('top_banner_font_family').value,
                top_banner_font_size: document.getElementById('top_banner_font_size').value,
                bottom_banner_text: document.getElementById('bottom_banner_text').value,
                bottom_banner_color: document.getElementById('bottom_banner_color').value,
                bottom_banner_font_family: document.getElementById('bottom_banner_font_family').value,
                bottom_banner_font_size: document.getElementById('bottom_banner_font_size').value,
                top_margin: document.getElementById('top_margin').value,
                vertical_gap: document.getElementById('vertical_gap').value,
                horizontal_gap: document.getElementById('horizontal_gap').value,
                left_margin: document.getElementById('left_margin').value || ''
            });
            
            const downloadUrl = `/user/cards/download-name-tags-qr-surround.php?${params.toString()}`;
            window.location.href = downloadUrl;
        }
        
        // Copy HTML for Word/Google Docs
        function copyHTML() {
            const params = new URLSearchParams({
                card_id: cardId,
                top_banner_text: document.getElementById('top_banner_text').value,
                top_banner_color: document.getElementById('top_banner_color').value,
                top_banner_font_family: document.getElementById('top_banner_font_family').value,
                top_banner_font_size: document.getElementById('top_banner_font_size').value,
                bottom_banner_text: document.getElementById('bottom_banner_text').value,
                bottom_banner_color: document.getElementById('bottom_banner_color').value,
                bottom_banner_font_family: document.getElementById('bottom_banner_font_family').value,
                bottom_banner_font_size: document.getElementById('bottom_banner_font_size').value
            });
            
            const htmlUrl = `/user/cards/generate-name-tags-qr-surround-html.php?${params.toString()}`;
            window.open(htmlUrl, '_blank');
        }
    </script>
    <script src="/user/includes/user-script.js"></script>
</body>
</html>

