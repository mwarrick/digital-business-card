<?php
/**
 * Virtual Background Image Upload API
 * POST /user/api/upload-background.php
 * 
 * Handles uploading custom background images for virtual backgrounds
 */

// Disable error display to prevent breaking JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Start output buffering
ob_start();

require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

// Set JSON header first
header('Content-Type: application/json');

// Require authentication (don't use requireAuth() as it redirects)
if (!UserAuth::isLoggedIn()) {
    ob_clean();
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Maximum file size: 10MB (backgrounds can be large)
define('MAX_FILE_SIZE', 10 * 1024 * 1024);

// Allowed MIME types
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

// Storage directory - use absolute path
$storageDir = __DIR__ . '/../../storage/media/backgrounds/';

try {
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }
    
    // Get card ID
    if (!isset($_POST['card_id'])) {
        throw new Exception('card_id is required', 400);
    }
    
    $cardId = $_POST['card_id'];
    $userId = UserAuth::getUserId();
    
    // Verify card ownership
    $db = Database::getInstance();
    $card = $db->querySingle(
        "SELECT id FROM business_cards WHERE id = ? AND user_id = ? AND is_active = 1",
        [$cardId, $userId]
    );
    
    if (!$card) {
        throw new Exception('Business card not found or access denied', 404);
    }
    
    // Validate file upload
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $error = $_FILES['file']['error'] ?? 'No file uploaded';
        throw new Exception('File upload failed: ' . getUploadErrorMessage($error), 400);
    }
    
    $file = $_FILES['file'];
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('File too large. Maximum size: 10MB', 400);
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Invalid file type. Allowed: JPEG, PNG, GIF, WebP', 400);
    }
    
    // Get old background to delete if updating
    $oldPreferences = $db->querySingle(
        "SELECT background_image FROM virtual_background_preferences WHERE card_id = ?",
        [$cardId]
    );
    
    // Generate unique filename
    $extension = getExtensionFromMime($mimeType);
    $filename = generateUUID() . '.' . $extension;
    $filepath = $storageDir . $filename;
    
    // Create storage directory if it doesn't exist
    if (!is_dir($storageDir)) {
        if (!mkdir($storageDir, 0755, true)) {
            throw new Exception('Failed to create storage directory', 500);
        }
    }
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to save file', 500);
    }
    
    // Update or insert preferences
    $existing = $db->querySingle(
        "SELECT id FROM virtual_background_preferences WHERE card_id = ?",
        [$cardId]
    );
    
    if ($existing) {
        // Update existing preferences
        $db->execute(
            "UPDATE virtual_background_preferences SET background_image = ?, updated_at = NOW() WHERE card_id = ?",
            [$filename, $cardId]
        );
    } else {
        // Create new preferences with default values
        $prefId = generateUUID();
        $db->execute(
            "INSERT INTO virtual_background_preferences 
             (id, card_id, qr_position, qr_size, padding_x, padding_y, text_option, background_image) 
             VALUES (?, ?, 'bottom-right', 300, 50, 50, 'qr-only', ?)",
            [$prefId, $cardId, $filename]
        );
    }
    
    // Delete old file if it exists
    if ($oldPreferences && !empty($oldPreferences['background_image']) && $oldPreferences['background_image'] !== $filename) {
        $oldFilePath = $storageDir . $oldPreferences['background_image'];
        if (file_exists($oldFilePath)) {
            @unlink($oldFilePath);
        }
    }
    
    // Generate URL for the uploaded file
    $url = '/api/media/view?file=' . urlencode($filename) . '&type=background';
    
    // Clean any previous output and send JSON response
    ob_clean();
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Background image uploaded successfully',
        'filename' => $filename,
        'url' => $url
    ]);
    exit;
    
} catch (Exception $e) {
    ob_clean();
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}

/**
 * Get file extension from MIME type
 */
function getExtensionFromMime($mimeType) {
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    
    return $extensions[$mimeType] ?? 'jpg';
}

/**
 * Get upload error message
 */
function getUploadErrorMessage($error) {
    $messages = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
    ];
    
    return $messages[$error] ?? 'Unknown upload error';
}

/**
 * Generate UUID
 */
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

