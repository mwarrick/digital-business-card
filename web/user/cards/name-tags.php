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
        'include_signature' => 'profile',
        'include_name' => true,
        'include_title' => true,
        'include_phone' => true,
        'include_email' => true,
        'include_address' => false
    ];
}

$message = '';
$error = '';

// Handle form submission (save preferences)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save_preferences') {
        try {
            $newPreferences = [
                'include_signature' => $_POST['include_signature'] ?? 'profile',
                'include_name' => isset($_POST['include_name']) && $_POST['include_name'] === '1',
                'include_title' => isset($_POST['include_title']) && $_POST['include_title'] === '1',
                'include_phone' => isset($_POST['include_phone']) && $_POST['include_phone'] === '1',
                'include_email' => isset($_POST['include_email']) && $_POST['include_email'] === '1',
                'include_address' => isset($_POST['include_address']) && $_POST['include_address'] === '1'
            ];
            
            // Validate signature option
            if (!in_array($newPreferences['include_signature'], ['none', 'profile', 'logo'])) {
                throw new Exception('Invalid signature option');
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
                     include_signature = ?, include_name = ?, include_title = ?, 
                     include_phone = ?, include_email = ?, include_address = ?, 
                     updated_at = NOW()
                     WHERE card_id = ?",
                    [
                        $newPreferences['include_signature'],
                        $newPreferences['include_name'] ? 1 : 0,
                        $newPreferences['include_title'] ? 1 : 0,
                        $newPreferences['include_phone'] ? 1 : 0,
                        $newPreferences['include_email'] ? 1 : 0,
                        $newPreferences['include_address'] ? 1 : 0,
                        $cardId
                    ]
                );
            } else {
                // Create new preferences
                $id = bin2hex(random_bytes(16));
                $db->execute(
                    "INSERT INTO name_tag_preferences 
                     (id, card_id, include_signature, include_name, include_title, 
                      include_phone, include_email, include_address) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $id,
                        $cardId,
                        $newPreferences['include_signature'],
                        $newPreferences['include_name'] ? 1 : 0,
                        $newPreferences['include_title'] ? 1 : 0,
                        $newPreferences['include_phone'] ? 1 : 0,
                        $newPreferences['include_email'] ? 1 : 0,
                        $newPreferences['include_address'] ? 1 : 0
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
        
        .card-info {
            color: #666;
            font-size: 14px;
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
        
        .name-tag-preview {
            display: inline-block;
            border: 2px dashed #ccc;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .name-tag-preview img {
            max-width: 100%;
            height: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            background: white;
        }
        
        .preview-note {
            margin-top: 15px;
            color: #666;
            font-size: 13px;
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
        
        .control-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            background: white;
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
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
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
        
        <div class="preview-section">
            <h2>Live Preview</h2>
            <p style="color: #666; margin-bottom: 20px;">Preview of one name tag (PDF will contain 8 identical tags)</p>
            <div class="name-tag-preview">
                <img id="preview-image" src="/user/cards/preview-name-tag.php?card_id=<?php echo urlencode($cardId); ?>&include_signature=<?php echo urlencode($preferences['include_signature']); ?>&include_name=<?php echo $preferences['include_name'] ? '1' : '0'; ?>&include_title=<?php echo $preferences['include_title'] ? '1' : '0'; ?>&include_phone=<?php echo $preferences['include_phone'] ? '1' : '0'; ?>&include_email=<?php echo $preferences['include_email'] ? '1' : '0'; ?>&include_address=<?php echo $preferences['include_address'] ? '1' : '0'; ?>" alt="Name tag preview" style="width: 486px; height: auto;">
            </div>
            <div class="preview-note">
                Actual size: 3.375" × 2.33" (standard label size)
            </div>
        </div>
        
        <form id="preferences-form" method="POST">
            <input type="hidden" name="action" value="save_preferences">
            
            <div class="controls-section">
                <h2>Customize Name Tag</h2>
                
                <div class="control-group">
                    <label for="include_signature">Signature Image:</label>
                    <select name="include_signature" id="include_signature">
                        <option value="none" <?php echo $preferences['include_signature'] === 'none' ? 'selected' : ''; ?>>None</option>
                        <option value="profile" <?php echo $preferences['include_signature'] === 'profile' ? 'selected' : ''; ?>>Profile Photo</option>
                        <option value="logo" <?php echo $preferences['include_signature'] === 'logo' ? 'selected' : ''; ?>>Company Logo</option>
                    </select>
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
        
        // Update preview when any control changes
        function updatePreview() {
            const params = new URLSearchParams({
                card_id: cardId,
                include_signature: form.include_signature.value,
                include_name: form.include_name.checked ? '1' : '0',
                include_title: form.include_title.checked ? '1' : '0',
                include_phone: form.include_phone.checked ? '1' : '0',
                include_email: form.include_email.checked ? '1' : '0',
                include_address: form.include_address.checked ? '1' : '0'
            });
            
            document.getElementById('preview-image').src = 
                `/user/cards/preview-name-tag.php?${params.toString()}&t=${Date.now()}`;
        }
        
        // Save preferences
        function savePreferences() {
            form.submit();
        }
        
        // Download PDF
        function downloadPDF() {
            const params = new URLSearchParams({
                card_id: cardId,
                include_signature: form.include_signature.value,
                include_name: form.include_name.checked ? '1' : '0',
                include_title: form.include_title.checked ? '1' : '0',
                include_phone: form.include_phone.checked ? '1' : '0',
                include_email: form.include_email.checked ? '1' : '0',
                include_address: form.include_address.checked ? '1' : '0'
            });
            
            window.location.href = `/user/cards/download-name-tags.php?${params.toString()}`;
        }
        
        // Add event listeners to all controls
        form.include_signature.addEventListener('change', updatePreview);
        form.include_name.addEventListener('change', updatePreview);
        form.include_title.addEventListener('change', updatePreview);
        form.include_phone.addEventListener('change', updatePreview);
        form.include_email.addEventListener('change', updatePreview);
        form.include_address.addEventListener('change', updatePreview);
    </script>
</body>
</html>

