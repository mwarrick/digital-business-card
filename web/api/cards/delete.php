<?php
/**
 * Delete Business Card Endpoint
 * POST /api/cards/delete
 * Deletes a business card and all related data (cascade delete)
 */

require_once __DIR__ . '/../includes/Api.php';
require_once __DIR__ . '/../includes/Database.php';

class DeleteCard extends Api {
    private $db;
    private $userId;
    
    public function __construct() {
        parent::__construct();
        
        // Require authentication
        $this->requireAuth();
        
        $this->db = Database::getInstance();
        $this->userId = $this->getUserId();
        $this->handleRequest();
    }
    
    private function handleRequest() {
        if ($this->method !== 'POST') {
            $this->error('Method not allowed', 405);
        }
        
        $this->deleteCard();
    }
    
    private function deleteCard() {
        // Validate required fields
        $cardId = $this->data['card_id'] ?? null;
        
        if (!$cardId) {
            $this->error('Card ID required', 400);
        }
        
        try {
            // Verify card belongs to user
            $card = $this->db->querySingle(
                "SELECT id, user_id, first_name, last_name FROM business_cards WHERE id = ?",
                [$cardId]
            );
            
            if (!$card) {
                $this->error('Card not found', 404);
            }
            
            if ($card['user_id'] !== $this->userId) {
                $this->error('Unauthorized - card does not belong to you', 403);
            }
            
            // Delete card (cascade will delete related data)
            $this->db->execute(
                "DELETE FROM business_cards WHERE id = ?",
                [$cardId]
            );
            
            $this->success([
                'message' => 'Card deleted successfully',
                'deleted_card' => [
                    'id' => $cardId,
                    'name' => $card['first_name'] . ' ' . $card['last_name']
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Card deletion error: " . $e->getMessage());
            $this->error('Failed to delete card: ' . $e->getMessage(), 500);
        }
    }
}

// Initialize the API
new DeleteCard();
