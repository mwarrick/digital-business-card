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
require_once __DIR__ . '/../../api/includes/log-image-creation.php';

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
            if (!in_array($newPreferences['qr_position'], ['top-left', 'top-right', 'top-center', 'bottom-left', 'bottom-right', 'bottom-center'])) {
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
            
            // Get background_image from form (if just uploaded) or from existing preferences
            if (isset($_POST['background_image']) && !empty($_POST['background_image'])) {
                // Use the filename from the upload
                $newPreferences['background_image'] = $_POST['background_image'];
            } else {
                // Preserve existing background_image if not being changed
                $currentPrefs = $db->querySingle(
                    "SELECT background_image FROM virtual_background_preferences WHERE card_id = ?",
                    [$cardId]
                );
                if ($currentPrefs && !empty($currentPrefs['background_image'])) {
                    $newPreferences['background_image'] = $currentPrefs['background_image'];
                }
            }
            
            $generator->savePreferences($cardId, $newPreferences);
            
            // Reload full preferences after save
            $preferences = $db->querySingle(
                "SELECT * FROM virtual_background_preferences WHERE card_id = ?",
                [$cardId]
            );
            if (!$preferences) {
                $preferences = $newPreferences;
            }
            
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
        
        .preview-image .qr-overlay-img {
            position: absolute;
            top: 0;
            left: 0;
            z-index: 10;
        }
        
        .preview-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 6px;
        }
        .preview-image .bg-layer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        .preview-image .bg-layer img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .preview-image .overlay-placeholder {
            position: relative;
            z-index: 1;
        }
        .dropzone {
            border: 2px solid #cbd5e1;
            border-radius: 8px;
            padding: 14px;
            text-align: center;
            color: #64748b;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .dropzone:hover {
            background: #eef2ff;
            border-color: #667eea;
            color: #4f46e5;
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
            grid-template-columns: repeat(3, 1fr);
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
                    <div class="bg-layer"><img id="bgPreviewImage" alt="" style="display:none;"></div>
                    <div class="overlay-placeholder">Preview will appear here</div>
                </div>
                <div style="text-align: center; margin-top: 16px;">
                    <button class="btn btn-secondary" onclick="updatePreview()">🔄 Update Preview</button>
                </div>
            </div>
            
            <div class="controls-panel">
                <h3>Customization</h3>
                
                <form method="POST" id="preferencesForm">
                    <input type="hidden" name="action" value="save_preferences">
                    <input type="hidden" name="background_image_data" id="backgroundImageDataInput" value="">

                    <div class="control-group">
                        <label>Background Image</label>
                        <div class="dropzone" id="bgDropzone" title="Click to select an image">Click to select background image</div>
                        <input type="file" id="bgFileInput" accept="image/png,image/jpeg,image/webp" style="display:none;">
                        <div style="font-size: 12px; color:#64748b; margin-top:8px;">JPG/PNG/WebP up to 20 MB. Final output uses fixed resolutions; you can crop/fit in next steps.</div>
                    </div>
                    
                    <div class="control-group">
                        <label>QR Code Position</label>
                        <div class="position-buttons">
                            <button type="button" class="position-btn" data-position="top-left">↖️ Top Left</button>
                            <button type="button" class="position-btn" data-position="top-center">⬆️ Top Center</button>
                            <button type="button" class="position-btn" data-position="top-right">↗️ Top Right</button>
                            <button type="button" class="position-btn" data-position="bottom-left">↙️ Bottom Left</button>
                            <button type="button" class="position-btn" data-position="bottom-center">⬇️ Bottom Center</button>
                            <button type="button" class="position-btn" data-position="bottom-right">↘️ Bottom Right</button>
                        </div>
                        <input type="hidden" name="qr_position" value="<?php echo htmlspecialchars($preferences['qr_position']); ?>">
                    </div>
                    
                    <div class="control-group">
                        <label>QR Code Size: <span class="slider-value" id="qrSizeValue"><?php echo $preferences['qr_size']; ?>px</span></label>
                        <div class="slider-container">
                            <input type="range" name="qr_size" class="slider" min="200" max="500" step="5"
                                   value="<?php echo $preferences['qr_size']; ?>" id="qrSizeSlider">
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <label>Padding X: <span class="slider-value" id="paddingXValue"><?php echo $preferences['padding_x']; ?>px</span></label>
                        <div class="slider-container">
                            <input type="range" name="padding_x" class="slider" min="0" max="300" step="1"
                                   value="<?php echo $preferences['padding_x']; ?>" id="paddingXSlider">
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <label>Padding Y: <span class="slider-value" id="paddingYValue"><?php echo $preferences['padding_y']; ?>px</span></label>
                        <div class="slider-container">
                            <input type="range" name="padding_y" class="slider" min="0" max="300" step="1"
                                   value="<?php echo $preferences['padding_y']; ?>" id="paddingYSlider">
                        </div>
                    </div>
                    
                    <div class="control-group" id="backgroundColorsGroup">
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
                        <button type="submit" class="btn btn-secondary" onclick="saveSettings(event)">💾 Save Settings</button>
                        <button type="button" class="btn btn-primary" onclick="downloadBackground()">⬇️ Download Background</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Global storage for background image
        let backgroundImageData = null;
        
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
            
            // Set active position button - first remove all active classes, then set the correct one
            positionButtons.forEach(btn => btn.classList.remove('active'));
            positionButtons.forEach(btn => {
                if (btn.dataset.position === positionInput.value) {
                    btn.classList.add('active');
                }
                
                btn.addEventListener('click', function() {
                    positionButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    positionInput.value = this.dataset.position;
                    updatePreview(); // Update preview when position changes
                });
            });
            
            // Update slider values on page load - ensure px is always shown
            // Use setTimeout to ensure DOM is fully ready
            setTimeout(function() {
                const qrSizeSlider = document.getElementById('qrSizeSlider');
                const paddingXSlider = document.getElementById('paddingXSlider');
                const paddingYSlider = document.getElementById('paddingYSlider');
                
                if (qrSizeSlider && paddingXSlider && paddingYSlider) {
                    // Initialize with px labels
                    updateSliderValue('qrSizeSlider', 'qrSizeValue');
                    updateSliderValue('paddingXSlider', 'paddingXValue');
                    updateSliderValue('paddingYSlider', 'paddingYValue');
                    
                    // Also ensure initial values have px (in case PHP didn't output it)
                    const qrSizeValue = document.getElementById('qrSizeValue');
                    const paddingXValue = document.getElementById('paddingXValue');
                    const paddingYValue = document.getElementById('paddingYValue');
                    
                    if (qrSizeValue && !qrSizeValue.textContent.includes('px')) {
                        qrSizeValue.textContent = qrSizeSlider.value + 'px';
                    }
                    if (paddingXValue && !paddingXValue.textContent.includes('px')) {
                        paddingXValue.textContent = paddingXSlider.value + 'px';
                    }
                    if (paddingYValue && !paddingYValue.textContent.includes('px')) {
                        paddingYValue.textContent = paddingYSlider.value + 'px';
                    }
                    
                    // Add event listeners to sliders
                    qrSizeSlider.addEventListener('input', function() {
                        updateSliderValue('qrSizeSlider', 'qrSizeValue');
                    });
                    
                    paddingXSlider.addEventListener('input', function() {
                        updateSliderValue('paddingXSlider', 'paddingXValue');
                    });
                    
                    paddingYSlider.addEventListener('input', function() {
                        updateSliderValue('paddingYSlider', 'paddingYValue');
                    });
                }
            }, 100);
            
            // Restore background image from sessionStorage if available (new upload not yet saved)
            const storageKey = 'vb_bg_' + '<?php echo $cardId; ?>';
            try {
                const storedBg = sessionStorage.getItem(storageKey);
                if (storedBg) {
                    backgroundImageData = storedBg;
                    const bgImg = document.getElementById('bgPreviewImage');
                    const dropzone = document.getElementById('bgDropzone');
                    const colorsGroup = document.getElementById('backgroundColorsGroup');
                    
                    if (bgImg) {
                        bgImg.src = backgroundImageData;
                        bgImg.style.display = 'block';
                        bgImg.style.width = '100%';
                        bgImg.style.height = '100%';
                        bgImg.style.objectFit = 'cover';
                    }
                    if (dropzone) {
                        dropzone.textContent = 'Image selected - click to change';
                        dropzone.style.background = '#e8f5e9';
                    }
                    if (colorsGroup) {
                        colorsGroup.style.display = 'none';
                    }
                } else if (<?php echo !empty($preferences['background_image']) ? 'true' : 'false'; ?>) {
                    // Restore from server (saved background_image)
                    const bgFilename = <?php echo json_encode($preferences['background_image'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                    const bgUrl = '/api/media/view?file=' + encodeURIComponent(bgFilename) + '&type=background';
                    const bgImg = document.getElementById('bgPreviewImage');
                    const dropzone = document.getElementById('bgDropzone');
                    const colorsGroup = document.getElementById('backgroundColorsGroup');
                    
                    if (bgImg) {
                        bgImg.src = bgUrl;
                        bgImg.style.display = 'block';
                        bgImg.style.width = '100%';
                        bgImg.style.height = '100%';
                        bgImg.style.objectFit = 'cover';
                    }
                    if (dropzone) {
                        dropzone.textContent = 'Image selected - click to change';
                        dropzone.style.background = '#e8f5e9';
                    }
                    if (colorsGroup) {
                        colorsGroup.style.display = 'none';
                    }
                }
            } catch(err) {
                console.warn('Could not restore background image:', err);
            }
            
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
            if (slider && value) {
                value.textContent = slider.value + 'px';
            }
        }
        
        function updatePreview() {
            const previewImage = document.getElementById('previewImage');
            if (!previewImage) return;
            
            // Ensure the structure exists - never destroy it
            let bgLayer = previewImage.querySelector('.bg-layer');
            if (!bgLayer) {
                bgLayer = document.createElement('div');
                bgLayer.className = 'bg-layer';
                const bgImg = document.createElement('img');
                bgImg.id = 'bgPreviewImage';
                bgImg.alt = '';
                bgImg.style.display = 'none';
                bgLayer.appendChild(bgImg);
                previewImage.insertBefore(bgLayer, previewImage.firstChild);
            }
            
            let bgImg = document.getElementById('bgPreviewImage') || bgLayer.querySelector('img');
            
            // Restore background image if we have stored data (check sessionStorage if not in memory)
            if (!backgroundImageData) {
                const storageKey = 'vb_bg_' + '<?php echo $cardId; ?>';
                try {
                    const storedBg = sessionStorage.getItem(storageKey);
                    if (storedBg) {
                        backgroundImageData = storedBg;
                    }
                } catch(err) {
                    // Ignore
                }
            }
            
            // Restore background image if we have stored data
            if (backgroundImageData && bgImg) {
                bgImg.src = backgroundImageData;
                bgImg.style.display = 'block';
                bgImg.style.width = '100%';
                bgImg.style.height = '100%';
                bgImg.style.objectFit = 'cover';
            }
            
            const hasBg = backgroundImageData || (bgImg && bgImg.style.display !== 'none' && bgImg.src);
            
            // Show/hide background colors based on image state
            const colorsGroup = document.getElementById('backgroundColorsGroup');
            if (colorsGroup) {
                if (hasBg) {
                    colorsGroup.style.display = 'none';
                } else {
                    colorsGroup.style.display = 'block';
                }
            }
            
            const overlay = previewImage.querySelector('.overlay-placeholder');
            if (overlay && !hasBg) overlay.textContent = 'Generating preview...';
            
            // Generate a simple preview using the current settings
            const form = document.getElementById('preferencesForm');
            if (!form) return;
            
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
            const colorPreset = document.querySelector('input[name="color_preset"]:checked');
            if (colorPreset && colorPreset.value === 'custom') {
                const colorTop = document.getElementById('colorTop');
                const colorBottom = document.getElementById('colorBottom');
                if (colorTop) params.append('color_top', colorTop.value);
                if (colorBottom) params.append('color_bottom', colorBottom.value);
            }
            
            // Create preview image
            const previewUrl = '/user/cards/preview-background.php?' + params.toString();
            
            // Hide/show gradient preview based on background image
            let gradientImg = previewImage.querySelector('.gradient-preview-img');
            
            if (hasBg) {
                // Hide gradient preview completely - we're using uploaded image
                if (gradientImg) {
                    gradientImg.style.display = 'none';
                    gradientImg.remove(); // Remove from DOM to ensure it doesn't interfere
                }
                
                // Ensure background image is visible and styled correctly
                if (bgImg) {
                    bgImg.style.display = 'block';
                    bgImg.style.width = '100%';
                    bgImg.style.height = '100%';
                    bgImg.style.objectFit = 'cover';
                    bgImg.style.position = 'absolute';
                    bgImg.style.top = '0';
                    bgImg.style.left = '0';
                    bgImg.style.zIndex = '1';
                }
                
                // Generate QR code URL for this card and overlay it on the custom background
                const cardPublicUrl = 'https://sharemycard.app/card.php?id=' + encodeURIComponent('<?php echo $cardId; ?>');
                const qrSize = Math.max(60, parseInt(form.qr_size.value) * 0.2); // Scale down for preview
                const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=' + Math.round(qrSize) + 'x' + Math.round(qrSize) + '&data=' + encodeURIComponent(cardPublicUrl) + '&format=png';
                
                // Create or update QR overlay
                let qrOverlay = previewImage.querySelector('.qr-overlay-img');
                if (!qrOverlay) {
                    qrOverlay = document.createElement('img');
                    qrOverlay.className = 'qr-overlay-img';
                    qrOverlay.style.cssText = 'position: absolute; z-index: 10; pointer-events: none;';
                    previewImage.appendChild(qrOverlay);
                }
                
                // Position QR code based on settings
                const paddingX = Math.max(10, parseInt(form.padding_x.value) * 0.2);
                const paddingY = Math.max(10, parseInt(form.padding_y.value) * 0.2);
                const position = form.qr_position.value;
                
                qrOverlay.src = qrUrl;
                qrOverlay.style.width = qrSize + 'px';
                qrOverlay.style.height = qrSize + 'px';
                
                // Position the QR code
                if (position === 'top-center') {
                    qrOverlay.style.top = paddingY + 'px';
                    qrOverlay.style.left = '50%';
                    qrOverlay.style.right = 'auto';
                    qrOverlay.style.bottom = 'auto';
                    qrOverlay.style.transform = 'translateX(-50%)';
                } else if (position === 'bottom-center') {
                    qrOverlay.style.bottom = paddingY + 'px';
                    qrOverlay.style.left = '50%';
                    qrOverlay.style.right = 'auto';
                    qrOverlay.style.top = 'auto';
                    qrOverlay.style.transform = 'translateX(-50%)';
                } else {
                    // Corner positions
                    if (position.includes('top')) {
                        qrOverlay.style.top = paddingY + 'px';
                        qrOverlay.style.bottom = 'auto';
                    } else if (position.includes('bottom')) {
                        qrOverlay.style.bottom = paddingY + 'px';
                        qrOverlay.style.top = 'auto';
                    }
                    
                    if (position.includes('left')) {
                        qrOverlay.style.left = paddingX + 'px';
                        qrOverlay.style.right = 'auto';
                        qrOverlay.style.transform = 'none';
                    } else if (position.includes('right')) {
                        qrOverlay.style.right = paddingX + 'px';
                        qrOverlay.style.left = 'auto';
                        qrOverlay.style.transform = 'none';
                    }
                }
                
                qrOverlay.style.display = 'block';
                if (overlay) overlay.style.display = 'none';
            } else {
                // No background image - hide QR overlay, show gradient preview
                const overlayImg = previewImage.querySelector('.qr-overlay-img');
                if (overlayImg) overlayImg.style.display = 'none';
                
                // Hide uploaded background image
                if (bgImg) {
                    bgImg.style.display = 'none';
                }
                
                // Show gradient preview image
                if (!gradientImg) {
                    gradientImg = document.createElement('img');
                    gradientImg.className = 'gradient-preview-img';
                    gradientImg.style.cssText = 'width: 100%; height: 100%; object-fit: cover; border-radius: 6px; position: relative; z-index: 1;';
                    previewImage.appendChild(gradientImg);
                }
                gradientImg.src = previewUrl;
                gradientImg.style.display = 'block';
                gradientImg.onerror = function() {
                    if (overlay) {
                        overlay.textContent = 'Preview unavailable';
                        overlay.style.display = 'block';
                    }
                };
                if (overlay) overlay.style.display = 'none';
            }
        }
        
        async function saveSettings(event) {
            event.preventDefault(); // Prevent default form submission
            
            const form = document.getElementById('preferencesForm');
            
            // Upload background image if present
            if (backgroundImageData) {
                try {
                    // Convert data URL to blob
                    const response = await fetch(backgroundImageData);
                    const blob = await response.blob();
                    
                    // Create form data
                    const formData = new FormData();
                    formData.append('file', blob, 'background.jpg');
                    formData.append('card_id', '<?php echo $cardId; ?>');
                    
                    // Upload to server
                    const uploadResponse = await fetch('/user/api/upload-background.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const uploadResult = await uploadResponse.json();
                    
                    if (!uploadResult.success) {
                        alert('Failed to upload background image: ' + uploadResult.message);
                        return;
                    }
                    
                    // Store the uploaded filename in a hidden field so it gets saved with preferences
                    let bgFilenameInput = document.getElementById('backgroundImageFilename');
                    if (!bgFilenameInput) {
                        // Create hidden input if it doesn't exist
                        bgFilenameInput = document.createElement('input');
                        bgFilenameInput.type = 'hidden';
                        bgFilenameInput.id = 'backgroundImageFilename';
                        bgFilenameInput.name = 'background_image';
                        form.appendChild(bgFilenameInput);
                    }
                    bgFilenameInput.value = uploadResult.filename;
                    
                    // Clear backgroundImageData since it's now on server
                    backgroundImageData = null;
                    const storageKey = 'vb_bg_' + '<?php echo $cardId; ?>';
                    try {
                        sessionStorage.removeItem(storageKey);
                    } catch(err) {
                        console.warn('Could not clear sessionStorage:', err);
                    }
                    
                } catch(err) {
                    console.error('Error uploading background:', err);
                    alert('Failed to upload background image. Please try again.');
                    return;
                }
            }
            
            // Submit form normally (this saves preferences)
            form.submit();
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

        // Global handler for file selection (works even if init hasn't run)
        function handleBgFileSelect(input) {
            if (!input || !input.files || !input.files[0]) return;
            
            const file = input.files[0];
            const maxSize = 20 * 1024 * 1024; // 20 MB
            const accept = ['image/jpeg','image/png','image/webp'];
            
            if (!accept.includes(file.type)) {
                alert('Unsupported file type. Use JPG/PNG/WebP.');
                return;
            }
            if (file.size > maxSize) {
                alert('File too large. Max 20 MB.');
                return;
            }
            
            const previewImage = document.getElementById('previewImage');
            const dropzone = document.getElementById('bgDropzone');
            
            if (!previewImage || !dropzone) {
                console.error('Missing previewImage or dropzone elements');
                return;
            }
            
            // Ensure bg-layer structure exists
            let bgLayer = previewImage.querySelector('.bg-layer');
            if (!bgLayer) {
                bgLayer = document.createElement('div');
                bgLayer.className = 'bg-layer';
                previewImage.insertBefore(bgLayer, previewImage.firstChild);
            }
            
            // Get or create bgImg
            let bgImg = document.getElementById('bgPreviewImage');
            if (!bgImg) {
                bgImg = document.createElement('img');
                bgImg.id = 'bgPreviewImage';
                bgImg.alt = '';
                bgLayer.appendChild(bgImg);
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                // Store globally and in sessionStorage for persistence
                backgroundImageData = e.target.result;
                const storageKey = 'vb_bg_' + '<?php echo $cardId; ?>';
                try {
                    sessionStorage.setItem(storageKey, backgroundImageData);
                } catch(err) {
                    console.warn('Could not store in sessionStorage:', err);
                }
                
                // Also update hidden input (though it may be too large)
                const hiddenInput = document.getElementById('backgroundImageDataInput');
                if (hiddenInput && backgroundImageData.length < 100000) { // Only if reasonably sized
                    hiddenInput.value = backgroundImageData;
                }
                
                bgImg.src = backgroundImageData;
                bgImg.style.display = 'block';
                bgImg.style.width = '100%';
                bgImg.style.height = '100%';
                bgImg.style.objectFit = 'cover';
                
                dropzone.textContent = 'Image selected - click to change';
                dropzone.style.background = '#e8f5e9';
                
                // Hide background colors when image is uploaded
                const colorsGroup = document.getElementById('backgroundColorsGroup');
                if (colorsGroup) {
                    colorsGroup.style.display = 'none';
                }
                
                const placeholder = previewImage.querySelector('.overlay-placeholder');
                if (placeholder) {
                    placeholder.style.display = 'none';
                }
                
                // Don't call updatePreview immediately - it will be called when user interacts with controls
                // The background image is now visible, QR will overlay on next preview update
            };
            reader.onerror = function() {
                alert('Error reading file. Please try again.');
            };
            reader.readAsDataURL(file);
        }

        // Background upload handlers (client-side preview)
        function backgroundUploadInit() {
            const dropzone = document.getElementById('bgDropzone');
            const fileInput = document.getElementById('bgFileInput');
            const bgImg = document.getElementById('bgPreviewImage');
            const previewImage = document.getElementById('previewImage');
            
            console.log('Background upload init:', {
                dropzone: !!dropzone,
                fileInput: !!fileInput,
                bgImg: !!bgImg,
                previewImage: !!previewImage,
                dropzoneEl: dropzone,
                fileInputEl: fileInput,
                bgImgEl: bgImg,
                previewImageEl: previewImage
            });
            
            if (!dropzone) {
                console.error('Missing bgDropzone element');
                return;
            }
            if (!fileInput) {
                console.error('Missing bgFileInput element');
                return;
            }
            if (!bgImg) {
                console.error('Missing bgPreviewImage element');
                return;
            }
            if (!previewImage) {
                console.error('Missing previewImage element');
                return;
            }

            const maxSize = 20 * 1024 * 1024; // 20 MB
            const accept = ['image/jpeg','image/png','image/webp'];

            function setBg(file) {
                if (!accept.includes(file.type)) { 
                    alert('Unsupported file type. Use JPG/PNG/WebP.'); 
                    return; 
                }
                if (file.size > maxSize) { 
                    alert('File too large. Max 20 MB.'); 
                    return; 
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    bgImg.src = e.target.result;
                    bgImg.style.display = 'block';
                    bgImg.style.width = '100%';
                    bgImg.style.height = '100%';
                    bgImg.style.objectFit = 'cover';
                    
                    // Update dropzone text
                    dropzone.textContent = 'Image selected - click to change';
                    dropzone.style.background = '#e8f5e9';
                    
                    // Hide placeholder text
                    const placeholder = previewImage.querySelector('.overlay-placeholder');
                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }
                    
                    // Trigger preview update to show QR over image
                    setTimeout(function() {
                        updatePreview();
                    }, 100);
                };
                reader.onerror = function() {
                    alert('Error reading file. Please try again.');
                };
                reader.readAsDataURL(file);
            }

            // Single click handler - prevent duplicates
            dropzone.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                fileInput.click();
            };
            
            // Single change handler
            fileInput.onchange = function(e) {
                if (e.target.files && e.target.files[0]) {
                    handleBgFileSelect(e.target);
                }
            };
            
            console.log('Event listeners attached successfully');
        }

        // Ensure init after DOM is ready
        function initBackgroundUpload() {
            console.log('Attempting to initialize background upload, readyState:', document.readyState);
            setTimeout(function() {
                backgroundUploadInit();
            }, 500);
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initBackgroundUpload);
        } else {
            initBackgroundUpload();
        }
    </script>
</body>
</html>
