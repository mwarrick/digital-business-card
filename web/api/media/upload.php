<?php
/**
 * Media Upload API
 * POST /api/media/upload
 * 
 * Handles uploading profile photos, company logos, and cover graphics
 * for business cards
 */

require_once __DIR__ . '/../includes/Api.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/log-image-creation.php';

class MediaUploadApi extends Api {
    private $db;
    private $userId;
    
    // Maximum file size: 25MB
    private const MAX_FILE_SIZE = 25 * 1024 * 1024;
    
    // Allowed MIME types
    private const ALLOWED_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp'
    ];
    
    // Storage directory
    private const STORAGE_DIR = __DIR__ . '/../../storage/media/';
    
    public function __construct() {
        parent::__construct();
        
        // Apply rate limiting: 20 uploads per hour
        $this->applyRateLimit(20, 3600, 'media-upload');
        
        $this->db = Database::getInstance();
        $this->userId = $this->requireAuth();
        $this->handleRequest();
    }
    
    private function handleRequest() {
        if ($this->method !== 'POST') {
            $this->error('Method not allowed', 405);
        }
        
        $this->upload();
    }
    
    private function upload() {
        // Validate required parameters
        if (!isset($_POST['business_card_id'])) {
            $this->error('business_card_id is required', 400);
        }
        
        if (!isset($_POST['media_type']) || !in_array($_POST['media_type'], ['profile_photo', 'company_logo', 'cover_graphic'])) {
            $this->error('media_type must be one of: profile_photo, company_logo, cover_graphic', 400);
        }
        
        $cardId = $_POST['business_card_id'];
        $mediaType = $_POST['media_type'];
        
        // Verify card belongs to user
        try {
            $card = $this->db->querySingle(
                "SELECT id FROM business_cards WHERE id = ? AND user_id = ?",
                [$cardId, $this->userId]
            );
            
            if (!$card) {
                $this->error('Business card not found or access denied', 404);
            }
        } catch (Exception $e) {
            error_log("Card verification error: " . $e->getMessage());
            $this->error('Failed to verify business card', 500);
        }
        
        // Validate file upload
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $error = $_FILES['file']['error'] ?? 'No file uploaded';
            $this->error('File upload failed: ' . $this->getUploadErrorMessage($error), 400);
        }
        
        $file = $_FILES['file'];
        
        // Check file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $this->error('File too large. Maximum size: 25MB', 400);
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, self::ALLOWED_TYPES)) {
            $this->error('Invalid file type. Allowed: JPEG, PNG, GIF, WebP', 400);
        }
        
        try {
            // Generate unique filename
            $extension = $this->getExtensionFromMime($mimeType);
            $filename = $this->generateUUID() . '.' . $extension;
            $filepath = self::STORAGE_DIR . $filename;
            
            // Create storage directory if it doesn't exist
            if (!is_dir(self::STORAGE_DIR)) {
                mkdir(self::STORAGE_DIR, 0755, true);
            }
            
            // Get old file to delete if updating
            $oldFile = $this->db->querySingle(
                "SELECT {$mediaType}_path FROM business_cards WHERE id = ?",
                [$cardId]
            );
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                $this->error('Failed to save file', 500);
            }
            
            // Update database
            $this->db->execute(
                "UPDATE business_cards SET {$mediaType}_path = ?, updated_at = NOW() WHERE id = ?",
                [$filename, $cardId]
            );
            
            // Log image creation
            $imageDimensions = null;
            if (function_exists('getimagesize')) {
                $imageInfo = getimagesize($filepath);
                if ($imageInfo) {
                    $imageDimensions = $imageInfo[0] . 'x' . $imageInfo[1];
                }
            }
            
            logImageCreation(
                $filename,
                $filepath,
                $mediaType,
                'upload',
                $file['size'],
                $imageDimensions
            );
            
            // Delete old file if it exists
            if ($oldFile && !empty($oldFile[$mediaType . '_path'])) {
                $oldFilePath = self::STORAGE_DIR . $oldFile[$mediaType . '_path'];
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }
            
            // Generate URL for the uploaded file
            $url = $this->getMediaUrl($filename);
            
            $this->success([
                'filename' => $filename,
                'url' => $url,
                'media_type' => $mediaType,
                'business_card_id' => $cardId,
                'size' => $file['size'],
                'mime_type' => $mimeType
            ], 'File uploaded successfully', 201);
            
        } catch (Exception $e) {
            error_log("Media upload error: " . $e->getMessage());
            $this->error('Failed to upload file', 500);
        }
    }
    
    /**
     * Get file extension from MIME type
     */
    private function getExtensionFromMime($mimeType) {
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
     * Get media URL
     */
    private function getMediaUrl($filename) {
        // In production, this would be the full URL
        // For now, return relative path
        return '/api/media/view?file=' . urlencode($filename);
    }
    
    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($error) {
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
}

// Initialize API
new MediaUploadApi();

