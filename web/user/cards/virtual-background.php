<?php
/**
 * Virtual Background Creator
 * Generate downloadable background images with embedded QR codes
 */

// Start output buffering to prevent headers already sent errors
ob_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../api/includes/VirtualBackgroundGenerator.php';

// Require authentication with error handling
try {
    UserAuth::requireAuth();
} catch (Exception $e) {
    // If authentication fails, redirect to login
    ob_clean();
    header('Location: /user/login.php');
    exit;
}

$db = Database::getInstance();
$generator = new VirtualBackgroundGenerator();

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
    "SELECT * FROM virtual_background_preferences WHERE card_id = ?",
    [$cardId]
);

// Set defaults if no preferences exist
if (!$preferences) {
    $preferences = [
        'qr_position' => 'bottom-right',
        'qr_size' => 300,
        'padding_x' => 50,
        'padding_y' => 50,
        'text_option' => 'qr-only',
        'color_top' => null,
        'color_bottom' => null
    ];
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save_preferences') {
        try {
            $newPreferences = [
                'qr_position' => $_POST['qr_position'] ?? 'bottom-right',
                'qr_size' => (int)($_POST['qr_size'] ?? 300),
                'padding_x' => (int)($_POST['padding_x'] ?? 50),
                'padding_y' => (int)($_POST['padding_y'] ?? 50),
                'text_option' => $_POST['text_option'] ?? 'qr-only'
            ];
            
            // Add custom colors if provided
            if (isset($_POST['color_preset']) && $_POST['color_preset'] === 'custom') {
                if (isset($_POST['color_top']) && isset($_POST['color_bottom'])) {
                    $newPreferences['color_top'] = $_POST['color_top'];
                    $newPreferences['color_bottom'] = $_POST['color_bottom'];
                }
            }
            
            // Validate inputs
            if (!in_array($newPreferences['qr_position'], ['top-left', 'top-right', 'bottom-left', 'bottom-right'])) {
                throw new Exception('Invalid QR position');
            }
            
            if ($newPreferences['qr_size'] < 200 || $newPreferences['qr_size'] > 500) {
                throw new Exception('QR size must be between 200 and 500 pixels');
            }
            
            if ($newPreferences['padding_x'] < 20 || $newPreferences['padding_x'] > 200) {
                throw new Exception('Padding X must be between 20 and 200 pixels');
            }
            
            if ($newPreferences['padding_y'] < 20 || $newPreferences['padding_y'] > 200) {
                throw new Exception('Padding Y must be between 20 and 200 pixels');
            }
            
            if (!in_array($newPreferences['text_option'], ['qr-only', 'qr-name', 'qr-name-company'])) {
                throw new Exception('Invalid text option');
            }
            
            $generator->savePreferences($cardId, $newPreferences);
            $preferences = $newPreferences;
            $message = 'Preferences saved successfully!';
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Available resolutions
$resolutions = [
    '1920x1080' => ['width' => 1920, 'height' => 1080, 'label' => '1920×1080 (Full HD)'],
    '1366x768' => ['width' => 1366, 'height' => 768, 'label' => '1366×768 (HD)'],
    '2560x1440' => ['width' => 2560, 'height' => 1440, 'label' => '2560×1440 (2K)'],
    '3840x2160' => ['width' => 3840, 'height' => 2160, 'label' => '3840×2160 (4K)']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Background - <?php echo htmlspecialchars($card['first_name'] . ' ' . $card['last_name']); ?></title>
    <link rel="stylesheet" href="/user/includes/user-style.css">
    <style>
        .virtual-background-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .card-header {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .preview-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        
        .preview-area {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .preview-image {
            width: 100%;
            max-width: 600px;
            height: 338px; /* 16:9 aspect ratio */
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 18px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }
        
        .preview-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 6px;
        }
        
        .controls-panel {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .control-group {
            margin-bottom: 24px;
        }
        
        .control-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        
        .position-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        
        .position-btn {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            font-size: 14px;
        }
        
        .position-btn:hover {
            border-color: #3498db;
            background: #f8f9fa;
        }
        
        .position-btn.active {
            border-color: #3498db;
            background: #3498db;
            color: white;
        }
        
        .slider-container {
            position: relative;
        }
        
        .slider {
            width: 100%;
            height: 6px;
            border-radius: 3px;
            background: #e0e0e0;
            outline: none;
            -webkit-appearance: none;
        }
        
        .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #3498db;
            cursor: pointer;
        }
        
        .slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #3498db;
            cursor: pointer;
            border: none;
        }
        
        .slider-value {
            position: absolute;
            right: 0;
            top: -25px;
            font-weight: 600;
            color: #3498db;
        }
        
        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .radio-option:hover {
            background: #f8f9fa;
        }
        
        .radio-option input[type="radio"] {
            margin: 0;
        }
        
        .resolution-select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            background: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
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
        
        .color-options {
            margin-top: 8px;
        }
        
        .color-presets {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .color-preset {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: background 0.3s ease;
        }
        
        .color-preset:hover {
            background: #f8f9fa;
        }
        
        .color-preset input[type="radio"] {
            margin: 0;
        }
        
        .color-swatch {
            width: 40px;
            height: 30px;
            border-radius: 6px;
            border: 2px solid #e0e0e0;
            transition: border-color 0.3s ease;
        }
        
        .color-preset input[type="radio"]:checked + .color-swatch {
            border-color: #3498db;
        }
        
        .color-preset span {
            font-size: 12px;
            color: #666;
        }
        
        .custom-colors {
            display: flex;
            gap: 16px;
            align-items: center;
        }
        
        .color-input-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .color-input-group label {
            font-size: 12px;
            color: #666;
            margin-bottom: 0;
        }
        
        .color-input-group input[type="color"] {
            width: 50px;
            height: 40px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            cursor: pointer;
            padding: 0;
        }
        
        .color-input-group input[type="color"]:hover {
            border-color: #3498db;
        }
        
        @media (max-width: 768px) {
            .preview-section {
                grid-template-columns: 1fr;
            }
            
            .position-buttons {
                grid-template-columns: 1fr 1fr;
            }
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
    
    <div class="virtual-background-container">
        <div class="card-header">
            <h1>🖼️ Virtual Background Creator</h1>
            <p>Create downloadable background images with your QR code for video calls</p>
            <div style="margin-top: 16px; padding: 16px; background: #f8f9fa; border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <strong>Name:</strong> <?php echo htmlspecialchars($card['first_name'] . ' ' . $card['last_name']); ?>
                        <?php if (!empty($card['job_title'])): ?>
                            <br><strong>Title:</strong> <?php echo htmlspecialchars($card['job_title']); ?>
                        <?php endif; ?>
                        <?php if (!empty($card['company_name'])): ?>
                            <br><strong>Company:</strong> <?php echo htmlspecialchars($card['company_name']); ?>
                        <?php endif; ?>
                        <br><strong>Theme:</strong> <?php echo ucwords(str_replace('-', ' ', $card['theme'])); ?>
                    </div>
                    <div>
                        <a href="/user/cards/view.php?id=<?php echo urlencode($card['id']); ?>" 
                           class="btn btn-secondary" style="text-decoration: none;">
                            👁️ View Card
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="preview-section">
            <div class="preview-area">
                <h3>Live Preview</h3>
                <div class="preview-image" id="previewImage">
                    <div>Preview will appear here</div>
                </div>
                <div style="text-align: center; margin-top: 16px;">
                    <button class="btn btn-secondary" onclick="updatePreview()">🔄 Update Preview</button>
                </div>
            </div>
            
            <div class="controls-panel">
                <h3>Customization</h3>
                
                <form method="POST" id="preferencesForm">
                    <input type="hidden" name="action" value="save_preferences">
                    
                    <div class="control-group">
                        <label>QR Code Position</label>
                        <div class="position-buttons">
                            <button type="button" class="position-btn" data-position="top-left">↖️ Top Left</button>
                            <button type="button" class="position-btn" data-position="top-right">↗️ Top Right</button>
                            <button type="button" class="position-btn" data-position="bottom-left">↙️ Bottom Left</button>
                            <button type="button" class="position-btn active" data-position="bottom-right">↘️ Bottom Right</button>
                        </div>
                        <input type="hidden" name="qr_position" value="<?php echo htmlspecialchars($preferences['qr_position']); ?>">
                    </div>
                    
                    <div class="control-group">
                        <label>QR Code Size: <span class="slider-value" id="qrSizeValue"><?php echo $preferences['qr_size']; ?>px</span></label>
                        <div class="slider-container">
                            <input type="range" name="qr_size" class="slider" min="200" max="500" 
                                   value="<?php echo $preferences['qr_size']; ?>" id="qrSizeSlider">
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <label>Padding X: <span class="slider-value" id="paddingXValue"><?php echo $preferences['padding_x']; ?>px</span></label>
                        <div class="slider-container">
                            <input type="range" name="padding_x" class="slider" min="20" max="200" 
                                   value="<?php echo $preferences['padding_x']; ?>" id="paddingXSlider">
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <label>Padding Y: <span class="slider-value" id="paddingYValue"><?php echo $preferences['padding_y']; ?>px</span></label>
                        <div class="slider-container">
                            <input type="range" name="padding_y" class="slider" min="20" max="200" 
                                   value="<?php echo $preferences['padding_y']; ?>" id="paddingYSlider">
                        </div>
                    </div>
                    
                    
                    <div class="control-group">
                        <label>Background Colors</label>
                        <div class="color-options">
                            <div class="color-presets">
                                <label class="color-preset">
                                    <input type="radio" name="color_preset" value="theme" 
                                           <?php echo (empty($preferences['color_top']) && empty($preferences['color_bottom'])) ? 'checked' : ''; ?>>
                                    <div class="color-swatch" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
                                    <span>Theme Colors</span>
                                </label>
                                <label class="color-preset">
                                    <input type="radio" name="color_preset" value="custom"
                                           <?php echo (!empty($preferences['color_top']) || !empty($preferences['color_bottom'])) ? 'checked' : ''; ?>>
                                    <div class="color-swatch" style="background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%);"></div>
                                    <span>Custom</span>
                                </label>
                            </div>
                            <div class="custom-colors" id="customColors" 
                                 style="display: <?php echo (!empty($preferences['color_top']) || !empty($preferences['color_bottom'])) ? 'flex' : 'none'; ?>;">
                                <div class="color-input-group">
                                    <label>Top Color:</label>
                                    <input type="color" name="color_top" 
                                           value="<?php echo htmlspecialchars($preferences['color_top'] ?? '#667eea'); ?>" 
                                           id="colorTop">
                                </div>
                                <div class="color-input-group">
                                    <label>Bottom Color:</label>
                                    <input type="color" name="color_bottom" 
                                           value="<?php echo htmlspecialchars($preferences['color_bottom'] ?? '#764ba2'); ?>" 
                                           id="colorBottom">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <label>Resolution</label>
                        <select class="resolution-select" id="resolutionSelect">
                            <?php foreach ($resolutions as $key => $resolution): ?>
                                <option value="<?php echo $key; ?>" 
                                        data-width="<?php echo $resolution['width']; ?>" 
                                        data-height="<?php echo $resolution['height']; ?>">
                                    <?php echo $resolution['label']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-secondary">💾 Save Settings</button>
                        <button type="button" class="btn btn-primary" onclick="downloadBackground()">⬇️ Download Background</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Navigation toggle functionality
        document.addEventListener("DOMContentLoaded", function() {
            const navToggle = document.querySelector(".hamburger");
            const navMenu = document.querySelector(".nav-links.mobile");
            
            if (navToggle && navMenu) {
                navToggle.addEventListener("click", function() {
                    navMenu.classList.toggle("active");
                    navToggle.classList.toggle("active");
                });
            }
        });
        
        // Initialize position buttons
        document.addEventListener('DOMContentLoaded', function() {
            const positionButtons = document.querySelectorAll('.position-btn');
            const positionInput = document.querySelector('input[name="qr_position"]');
            
            // Set active position button
            positionButtons.forEach(btn => {
                if (btn.dataset.position === positionInput.value) {
                    btn.classList.add('active');
                }
                
                btn.addEventListener('click', function() {
                    positionButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    positionInput.value = this.dataset.position;
                });
            });
            
            // Update slider values
            updateSliderValue('qrSizeSlider', 'qrSizeValue');
            updateSliderValue('paddingXSlider', 'paddingXValue');
            updateSliderValue('paddingYSlider', 'paddingYValue');
            
            // Add event listeners to sliders
            document.getElementById('qrSizeSlider').addEventListener('input', function() {
                updateSliderValue('qrSizeSlider', 'qrSizeValue');
            });
            
            document.getElementById('paddingXSlider').addEventListener('input', function() {
                updateSliderValue('paddingXSlider', 'paddingXValue');
            });
            
            document.getElementById('paddingYSlider').addEventListener('input', function() {
                updateSliderValue('paddingYSlider', 'paddingYValue');
            });
            
            // Load initial preview
            updatePreview();
            
            // Handle color preset changes
            document.querySelectorAll('input[name="color_preset"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const customColors = document.getElementById('customColors');
                    if (this.value === 'custom') {
                        customColors.style.display = 'flex';
                    } else {
                        customColors.style.display = 'none';
                    }
                    updatePreview();
                });
            });
            
            // Handle custom color changes
            document.getElementById('colorTop').addEventListener('change', updatePreview);
            document.getElementById('colorBottom').addEventListener('change', updatePreview);
        });
        
        function updateSliderValue(sliderId, valueId) {
            const slider = document.getElementById(sliderId);
            const value = document.getElementById(valueId);
            value.textContent = slider.value + 'px';
        }
        
        function updatePreview() {
            const previewImage = document.getElementById('previewImage');
            previewImage.innerHTML = '<div>Generating preview...</div>';
            
            // Generate a simple preview using the current settings
            const form = document.getElementById('preferencesForm');
            const resolutionSelect = document.getElementById('resolutionSelect');
            const selectedOption = resolutionSelect.options[resolutionSelect.selectedIndex];
            const width = selectedOption.dataset.width;
            const height = selectedOption.dataset.height;
            
            // Create a simple preview URL
            const params = new URLSearchParams({
                card_id: '<?php echo $cardId; ?>',
                width: 400, // Small preview size
                height: 225, // 16:9 aspect ratio
                qr_position: form.qr_position.value,
                qr_size: Math.max(60, parseInt(form.qr_size.value) * 0.2), // Scale down QR size for preview
                padding_x: Math.max(10, parseInt(form.padding_x.value) * 0.2),
                padding_y: Math.max(10, parseInt(form.padding_y.value) * 0.2),
                text_option: 'qr-only' // Always QR only
            });
            
            // Add color parameters
            const colorPreset = document.querySelector('input[name="color_preset"]:checked').value;
            if (colorPreset === 'custom') {
                params.append('color_top', document.getElementById('colorTop').value);
                params.append('color_bottom', document.getElementById('colorBottom').value);
            }
            
            // Create preview image
            const previewUrl = '/user/cards/preview-background.php?' + params.toString();
            previewImage.innerHTML = '<img src="' + previewUrl + '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;" onerror="this.parentElement.innerHTML=\'<div>Preview unavailable<br><small>Click \\\'Download Background\\\' to get the full image.</small></div>\'">';
        }
        
        function downloadBackground() {
            const form = document.getElementById('preferencesForm');
            const resolutionSelect = document.getElementById('resolutionSelect');
            const selectedOption = resolutionSelect.options[resolutionSelect.selectedIndex];
            const width = selectedOption.dataset.width;
            const height = selectedOption.dataset.height;
            
            // Create download URL using session-based endpoint
            const params = new URLSearchParams({
                card_id: '<?php echo $cardId; ?>',
                width: width,
                height: height,
                qr_position: form.qr_position.value,
                qr_size: form.qr_size.value,
                padding_x: form.padding_x.value,
                padding_y: form.padding_y.value,
                text_option: 'qr-only' // Always QR only
            });
            
            // Add color parameters
            const colorPreset = document.querySelector('input[name="color_preset"]:checked').value;
            if (colorPreset === 'custom') {
                params.append('color_top', document.getElementById('colorTop').value);
                params.append('color_bottom', document.getElementById('colorBottom').value);
            }
            
            // Open download in new window
            window.open('/user/cards/download-background.php?' + params.toString(), '_blank');
        }
    </script>
</body>
</html>
