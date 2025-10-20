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

// Set defaults if no preferences exist
if (!$preferences) {
    $preferences = [
        'include_name' => true,
        'include_title' => true,
        'include_phone' => true,
        'include_email' => true,
        'include_website' => true,
        'include_address' => false,
        'font_size' => '12',
        'message_above' => '',
        'message_below' => ''
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
                'include_phone' => isset($_POST['include_phone']) && $_POST['include_phone'] === '1',
                'include_email' => isset($_POST['include_email']) && $_POST['include_email'] === '1',
                'include_website' => isset($_POST['include_website']) && $_POST['include_website'] === '1',
                'include_address' => isset($_POST['include_address']) && $_POST['include_address'] === '1',
                'font_size' => $_POST['font_size'] ?? '12',
                'message_above' => trim($_POST['message_above'] ?? ''),
                'message_below' => trim($_POST['message_below'] ?? '')
            ];
            
            // Validate font size option
            if (!in_array($newPreferences['font_size'], ['8', '9', '10', '11', '12', '13', '14', '15', '16', '18', '20'])) {
                throw new Exception('Invalid font size option');
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
                     include_name = ?, include_title = ?, 
                     include_phone = ?, include_email = ?, include_website = ?, include_address = ?, 
                     font_size = ?, message_above = ?, message_below = ?, updated_at = NOW()
                     WHERE card_id = ?",
                    [
                        $newPreferences['include_name'] ? 1 : 0,
                        $newPreferences['include_title'] ? 1 : 0,
                        $newPreferences['include_phone'] ? 1 : 0,
                        $newPreferences['include_email'] ? 1 : 0,
                        $newPreferences['include_website'] ? 1 : 0,
                        $newPreferences['include_address'] ? 1 : 0,
                        $newPreferences['font_size'],
                        $newPreferences['message_above'],
                        $newPreferences['message_below'],
                        $cardId
                    ]
                );
            } else {
                // Create new preferences
                $id = bin2hex(random_bytes(16));
                $db->execute(
                    "INSERT INTO name_tag_preferences 
                     (id, card_id, include_name, include_title, 
                      include_phone, include_email, include_website, include_address, font_size, message_above, message_below) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $id,
                        $cardId,
                        $newPreferences['include_name'] ? 1 : 0,
                        $newPreferences['include_title'] ? 1 : 0,
                        $newPreferences['include_phone'] ? 1 : 0,
                        $newPreferences['include_email'] ? 1 : 0,
                        $newPreferences['include_website'] ? 1 : 0,
                        $newPreferences['include_address'] ? 1 : 0,
                        $newPreferences['font_size'],
                        $newPreferences['message_above'],
                        $newPreferences['message_below']
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
            <a href="/user/dashboard.php">📱 ShareMyCard</a>
        </div>
        <button class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div class="nav-links">
            <a href="/user/dashboard.php" class="nav-link">Dashboard</a>
            <a href="#" onclick="openAccountSecurity()" class="nav-link">🔒 Security</a>
            <a href="/user/logout.php" class="nav-link">Logout</a>
        </div>
        <div class="nav-links mobile">
            <a href="/user/dashboard.php" class="nav-link">Dashboard</a>
            <a href="#" onclick="openAccountSecurity()" class="nav-link">🔒 Security</a>
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
        </div>
        
        <div class="usage-instructions">
            <h3>📋 How to Use Name Tags</h3>
            <p><strong>Step 1:</strong> Customize your name tag using the options below.</p>
            <p><strong>Step 2:</strong> Click "Download PDF" to get a printable sheet with 8 identical name tags.</p>
            <p><strong>Step 3:</strong> Print on a standard 8.5" x 11" piece of paper as a test overlay for your labels.</p>
            <p><strong>Step 4:</strong> For final printing, use <a href="https://a.co/d/1XAnVlK" target="_blank">Premium Label Supply Name Tag Stickers (2-1/3" x 3-3/8")</a>.</p>
        </div>
        
        <form id="preferences-form" method="POST">
            <input type="hidden" name="action" value="save_preferences">
            
            <div class="controls-section">
                <h2>Customize Name Tag</h2>
                
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
        
        <div class="actions">
            <button onclick="savePreferences()" class="btn btn-secondary">💾 Save Settings</button>
            <button onclick="downloadPDF()" class="btn btn-primary">📄 Download PDF (8 tags)</button>
        </div>
    </div>
    
    <script>
        const cardId = '<?php echo addslashes($cardId); ?>';
        const form = document.getElementById('preferences-form');
        
        // Debug: Check if form elements are found
        console.log('Form found:', form);
        console.log('Font family element:', form?.font_family);
        console.log('Font size element:', form?.font_size);
        console.log('Line spacing element:', form?.line_spacing);
        
        // Save preferences
        function savePreferences() {
            form.submit();
        }
        
        // Download PDF
        function downloadPDF() {
            const params = new URLSearchParams({
                card_id: cardId,
                font_size: document.getElementById('font_size').value,
                message_above: document.getElementById('message_above').value,
                message_below: document.getElementById('message_below').value,
                include_website: document.getElementById('include_website').checked ? '1' : '0',
                include_name: document.getElementById('include_name').checked ? '1' : '0',
                include_title: document.getElementById('include_title').checked ? '1' : '0',
                include_phone: document.getElementById('include_phone').checked ? '1' : '0',
                include_email: document.getElementById('include_email').checked ? '1' : '0',
                include_address: document.getElementById('include_address').checked ? '1' : '0'
            });
            
            const downloadUrl = `/user/cards/download-name-tags-html.php?${params.toString()}`;
            console.log('Downloading PDF with URL:', downloadUrl);
            window.location.href = downloadUrl;
        }
        
        // No auto-save - users must click "Save Settings" button to save changes
    </script>
    <script src="/user/includes/user-script.js"></script>
</body>
</html>

