<?php
/**
 * Media Delete API
 * DELETE /api/media/delete?business_card_id={id}&media_type={type}
 * 
 * Deletes uploaded media files from business cards
 */

require_once __DIR__ . '/../includes/Api.php';
require_once __DIR__ . '/../includes/Database.php';

class MediaDeleteApi extends Api {
    private $db;
    private $userId;
    
    // Storage directory
    private const STORAGE_DIR = __DIR__ . '/../../storage/media/';
    
    public function __construct() {
        parent::__construct();
        
        // Apply rate limiting: 50 deletes per hour
        $this->applyRateLimit(50, 3600, 'media-delete');
        
        $this->db = Database::getInstance();
        $this->userId = $this->requireAuth();
        $this->handleRequest();
    }
    
    private function handleRequest() {
        if ($this->method !== 'DELETE') {
            $this->error('Method not allowed', 405);
        }
        
        $this->delete();
    }
    
    private function delete() {
        // Validate required parameters
        if (!isset($this->params['business_card_id'])) {
            $this->error('business_card_id is required', 400);
        }
        
        if (!isset($this->params['media_type']) || !in_array($this->params['media_type'], ['profile_photo', 'company_logo', 'cover_graphic'])) {
            $this->error('media_type must be one of: profile_photo, company_logo, cover_graphic', 400);
        }
        
        $cardId = $this->params['business_card_id'];
        $mediaType = $this->params['media_type'];
        
        try {
            // Verify card belongs to user and get current file
            $card = $this->db->querySingle(
                "SELECT id, {$mediaType}_path FROM business_cards WHERE id = ? AND user_id = ?",
                [$cardId, $this->userId]
            );
            
            if (!$card) {
                $this->error('Business card not found or access denied', 404);
            }
            
            $filename = $card[$mediaType . '_path'];
            
            if (!$filename) {
                $this->error('No media file to delete', 404);
            }
            
            // Delete file from storage
            $filepath = self::STORAGE_DIR . $filename;
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            
            // Update database
            $this->db->execute(
                "UPDATE business_cards SET {$mediaType}_path = NULL, updated_at = NOW() WHERE id = ?",
                [$cardId]
            );
            
            $this->success([
                'business_card_id' => $cardId,
                'media_type' => $mediaType,
                'deleted_file' => $filename
            ], 'Media file deleted successfully');
            
        } catch (Exception $e) {
            error_log("Media delete error: " . $e->getMessage());
            $this->error('Failed to delete media file', 500);
        }
    }
}

// Initialize API
new MediaDeleteApi();

