<?php
/**
 * Business Cards API
 * GET    /api/cards/       - List all cards for authenticated user
 * GET    /api/cards/{id}   - Get specific card
 * POST   /api/cards/       - Create new card
 * PUT    /api/cards/{id}   - Update card
 * DELETE /api/cards/{id}   - Delete card
 */

require_once __DIR__ . '/../includes/Api.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/DebugLogger.php';

class CardsApi extends Api {
    private $db;
    private $userId;
    
    public function __construct() {
        parent::__construct();
        
        // Apply rate limiting: 100 requests per hour for authenticated users
        $this->applyRateLimit(100, 3600, 'cards');
        
        $this->db = Database::getInstance();
        $this->userId = $this->requireAuth();
        $this->handleRequest();
    }
    
    private function handleRequest() {
        $cardId = $this->params['id'] ?? null;
        
        switch ($this->method) {
            case 'GET':
                if ($cardId) {
                    $this->getCard($cardId);
                } else {
                    $this->listCards();
                }
                break;
                
            case 'POST':
                $this->createCard();
                break;
                
            case 'PUT':
                if (!$cardId) {
                    $this->error('Card ID required', 400);
                }
                $this->updateCard($cardId);
                break;
                
            case 'DELETE':
                if (!$cardId) {
                    $this->error('Card ID required', 400);
                }
                $this->deleteCard($cardId);
                break;
                
            default:
                $this->error('Method not allowed', 405);
        }
    }
    
    /**
     * List all cards for user
     */
    private function listCards() {
        try {
            error_log("Cards API: Listing cards for user_id: " . $this->userId);
            
            $cards = $this->db->query(
                "SELECT * FROM business_cards 
                 WHERE user_id = ? AND is_active = 1 AND is_deleted = 0 
                 ORDER BY created_at DESC",
                [$this->userId]
            );
            
            error_log("Cards API: Found " . count($cards) . " cards for user " . $this->userId);
            
            // Get related data for each card
            foreach ($cards as &$card) {
                $card = $this->enrichCard($card);
            }
            
            // Debug: Log the actual card data structure
            if (!empty($cards)) {
                error_log("Cards API: First card structure: " . json_encode($cards[0]));
                error_log("Cards API: Available fields: " . implode(', ', array_keys($cards[0])));
            }
            
            $response = [
                'success' => true,
                'message' => 'Cards retrieved successfully',
                'data' => $cards,
                'count' => count($cards)
            ];
            
            error_log("Cards API: GET response count = " . count($cards));
            error_log("Cards API: Response success = " . ($response['success'] ? 'true' : 'false'));
            
            $this->success($cards, 'Cards retrieved successfully');
            
        } catch (Exception $e) {
            error_log("List cards error: " . $e->getMessage());
            error_log("List cards error trace: " . $e->getTraceAsString());
            $this->error('Failed to retrieve cards', 500);
        }
    }
    
    /**
     * Get specific card
     */
    private function getCard($cardId) {
        try {
            $card = $this->db->querySingle(
                "SELECT * FROM business_cards 
                 WHERE id = ? AND user_id = ? AND is_active = 1 AND is_deleted = 0",
                [$cardId, $this->userId]
            );
            
            if (!$card) {
                $this->error('Card not found', 404);
            }
            
            $card = $this->enrichCard($card);
            
            $this->success($card, 'Card retrieved successfully');
            
        } catch (Exception $e) {
            error_log("Get card error: " . $e->getMessage());
            $this->error('Failed to retrieve card', 500);
        }
    }
    
    /**
     * Create new card
     */
    private function createCard() {
        // Validate required fields
        $required = ['first_name', 'last_name', 'phone_number'];
        $missing = $this->validateRequired($required, $this->data);
        
        if (!empty($missing)) {
            $this->error('Missing required fields', 400, ['missing_fields' => $missing]);
        }
        
        try {
            $this->db->beginTransaction();
            
            // Generate card ID
            $cardId = $this->generateUUID();
            
            // Insert business card
            $this->db->execute(
                "INSERT INTO business_cards 
                 (id, user_id, first_name, last_name, phone_number, company_name, job_title, bio, is_active, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())",
                [
                    $cardId,
                    $this->userId,
                    $this->data['first_name'],
                    $this->data['last_name'],
                    $this->data['phone_number'],
                    $this->data['company_name'] ?? null,
                    $this->data['job_title'] ?? null,
                    $this->data['bio'] ?? null
                ]
            );
            
            // Insert additional contacts if provided
            if (!empty($this->data['emails'])) {
                $this->insertEmails($cardId, $this->data['emails']);
            }
            
            if (!empty($this->data['phones'])) {
                $this->insertPhones($cardId, $this->data['phones']);
            }
            
            if (!empty($this->data['websites'])) {
                $this->insertWebsites($cardId, $this->data['websites']);
            }
            
            if (!empty($this->data['address'])) {
                $this->insertAddress($cardId, $this->data['address']);
            }
            
            $this->db->commit();
            
            // Retrieve the complete card
            $card = $this->db->querySingle(
                "SELECT * FROM business_cards WHERE id = ?",
                [$cardId]
            );
            $card = $this->enrichCard($card);
            
            $this->success($card, 'Card created successfully', 201);
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Create card error: " . $e->getMessage());
            $this->error('Failed to create card', 500);
        }
    }
    
    /**
     * Update card
     */
    private function updateCard($cardId) {
        DebugLogger::log("🔄 UPDATE CARD API - Card ID: $cardId, User ID: {$this->userId}");
        DebugLogger::log("📥 Request data keys: " . implode(", ", array_keys($this->data)));
        error_log("🔄 UPDATE CARD API - Card ID: $cardId, User ID: {$this->userId}");
        error_log("📥 Request data keys: " . implode(", ", array_keys($this->data)));
        
        try {
            // Verify card belongs to user
            $card = $this->db->querySingle(
                "SELECT id FROM business_cards WHERE id = ? AND user_id = ?",
                [$cardId, $this->userId]
            );
            
            if (!$card) {
                DebugLogger::log("⚠️ UPDATE CARD - Card not found or doesn't belong to user");
                error_log("⚠️ UPDATE CARD - Card not found or doesn't belong to user");
                $this->error('Card not found', 404);
            }
            
            DebugLogger::log("✅ UPDATE CARD - Card found, beginning update");
            error_log("✅ UPDATE CARD - Card found, beginning update");
            
            $this->db->beginTransaction();
            
            // Update business card
            $this->db->execute(
                "UPDATE business_cards 
                 SET first_name = ?, last_name = ?, phone_number = ?, 
                     company_name = ?, job_title = ?, bio = ?, updated_at = NOW()
                 WHERE id = ?",
                [
                    $this->data['first_name'] ?? null,
                    $this->data['last_name'] ?? null,
                    $this->data['phone_number'] ?? null,
                    $this->data['company_name'] ?? null,
                    $this->data['job_title'] ?? null,
                    $this->data['bio'] ?? null,
                    $cardId
                ]
            );
            
            DebugLogger::log("✅ UPDATE CARD - Basic fields updated");
            error_log("✅ UPDATE CARD - Basic fields updated");
            
            // Update additional contacts if provided
            if (isset($this->data['emails'])) {
                $this->db->execute("DELETE FROM email_contacts WHERE business_card_id = ?", [$cardId]);
                if (!empty($this->data['emails'])) {
                    $this->insertEmails($cardId, $this->data['emails']);
                }
                error_log("✅ UPDATE CARD - Emails updated");
            }
            
            if (isset($this->data['phones'])) {
                $this->db->execute("DELETE FROM phone_contacts WHERE business_card_id = ?", [$cardId]);
                if (!empty($this->data['phones'])) {
                    $this->insertPhones($cardId, $this->data['phones']);
                }
                error_log("✅ UPDATE CARD - Phones updated");
            }
            
            if (isset($this->data['websites'])) {
                $this->db->execute("DELETE FROM website_links WHERE business_card_id = ?", [$cardId]);
                if (!empty($this->data['websites'])) {
                    $this->insertWebsites($cardId, $this->data['websites']);
                }
                error_log("✅ UPDATE CARD - Websites updated");
            }
            
            if (isset($this->data['address'])) {
                $this->db->execute("DELETE FROM addresses WHERE business_card_id = ?", [$cardId]);
                if (!empty($this->data['address'])) {
                    $this->insertAddress($cardId, $this->data['address']);
                }
                error_log("✅ UPDATE CARD - Address updated");
            }
            
            $this->db->commit();
            
            error_log("✅ UPDATE CARD - Transaction committed");
            
            // Retrieve the updated card
            $card = $this->db->querySingle(
                "SELECT * FROM business_cards WHERE id = ?",
                [$cardId]
            );
            $card = $this->enrichCard($card);
            
            error_log("✅ UPDATE CARD - Card enriched and ready to return");
            
            $this->success($card, 'Card updated successfully');
            
        } catch (Exception $e) {
            $this->db->rollback();
            $errorMsg = "❌ UPDATE CARD ERROR: " . $e->getMessage();
            $errorTrace = "❌ UPDATE CARD TRACE: " . $e->getTraceAsString();
            
            DebugLogger::log($errorMsg);
            DebugLogger::log($errorTrace);
            error_log($errorMsg);
            error_log($errorTrace);
            
            $this->error('Failed to update card: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Delete card (soft delete)
     */
    private function deleteCard($cardId) {
        error_log("🗑️ DELETE CARD API CALLED - Card ID: $cardId, User ID: {$this->userId}");
        
        try {
            // Verify card belongs to user and is not already deleted
            $card = $this->db->querySingle(
                "SELECT id FROM business_cards WHERE id = ? AND user_id = ? AND is_deleted = 0",
                [$cardId, $this->userId]
            );
            
            if (!$card) {
                error_log("⚠️ DELETE CARD - Card not found or doesn't belong to user");
                $this->error('Card not found', 404);
            }
            
            error_log("✅ DELETE CARD - Card found, performing soft delete");
            
            // Soft delete using is_deleted field
            $this->db->execute(
                "UPDATE business_cards SET is_deleted = 1, updated_at = NOW() WHERE id = ?",
                [$cardId]
            );
            
            error_log("✅ DELETE CARD - Successfully soft deleted card");
            
            $this->success([], 'Card deleted successfully');
            
        } catch (Exception $e) {
            error_log("❌ DELETE CARD ERROR: " . $e->getMessage());
            $this->error('Failed to delete card', 500);
        }
    }
    
    /**
     * Enrich card with related data
     */
    private function enrichCard($card) {
        $card['emails'] = $this->db->query(
            "SELECT id, email, type, label, is_primary FROM email_contacts WHERE business_card_id = ? ORDER BY is_primary DESC, created_at ASC",
            [$card['id']]
        );
        
        $card['phones'] = $this->db->query(
            "SELECT id, phone_number, type, label FROM phone_contacts WHERE business_card_id = ? ORDER BY created_at ASC",
            [$card['id']]
        );
        
        $card['websites'] = $this->db->query(
            "SELECT id, url, name, description, is_primary FROM website_links WHERE business_card_id = ? ORDER BY is_primary DESC, created_at ASC",
            [$card['id']]
        );
        
        $address = $this->db->querySingle(
            "SELECT * FROM addresses WHERE business_card_id = ?",
            [$card['id']]
        );
        $card['address'] = $address ?: null;
        
        return $card;
    }
    
    /**
     * Insert email contacts
     */
    private function insertEmails($cardId, $emails) {
        // Ensure only one primary email
        $hasPrimary = false;
        foreach ($emails as $email) {
            if (($email['is_primary'] ?? false) && !$hasPrimary) {
                $hasPrimary = true;
            } elseif (($email['is_primary'] ?? false) && $hasPrimary) {
                $email['is_primary'] = false; // Only allow one primary
            }
        }
        
        foreach ($emails as $email) {
            $this->db->execute(
                "INSERT INTO email_contacts (id, business_card_id, email, type, label, is_primary, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $this->generateUUID(),
                    $cardId,
                    $email['email'],
                    $email['type'] ?? 'work',
                    $email['label'] ?? null,
                    $email['is_primary'] ?? false
                ]
            );
        }
    }
    
    /**
     * Insert phone contacts
     */
    private function insertPhones($cardId, $phones) {
        foreach ($phones as $phone) {
            $this->db->execute(
                "INSERT INTO phone_contacts (id, business_card_id, phone_number, type, label, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $this->generateUUID(),
                    $cardId,
                    $phone['phone_number'],
                    $phone['type'] ?? 'mobile',
                    $phone['label'] ?? null
                ]
            );
        }
    }
    
    /**
     * Insert website links
     */
    private function insertWebsites($cardId, $websites) {
        // Ensure only one primary website
        $hasPrimary = false;
        foreach ($websites as $website) {
            if (($website['is_primary'] ?? false) && !$hasPrimary) {
                $hasPrimary = true;
            } elseif (($website['is_primary'] ?? false) && $hasPrimary) {
                $website['is_primary'] = false; // Only allow one primary
            }
        }
        
        foreach ($websites as $website) {
            $this->db->execute(
                "INSERT INTO website_links (id, business_card_id, url, name, description, is_primary, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $this->generateUUID(),
                    $cardId,
                    $website['url'],
                    $website['name'] ?? null,
                    $website['description'] ?? null,
                    $website['is_primary'] ?? false
                ]
            );
        }
    }
    
    /**
     * Insert address
     */
    private function insertAddress($cardId, $address) {
        DebugLogger::log("📍 INSERT ADDRESS - Card ID: $cardId, Address data: " . json_encode($address));
        
        $this->db->execute(
            "INSERT INTO addresses (id, business_card_id, street, city, state, zip_code, country, created_at, updated_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $this->generateUUID(),
                $cardId,
                $address['street'] ?? null,
                $address['city'] ?? null,
                $address['state'] ?? null,
                $address['postal_code'] ?? $address['zip_code'] ?? null,
                $address['country'] ?? null
            ]
        );
        
        DebugLogger::log("✅ INSERT ADDRESS - Success");
    }
}

// Initialize API
new CardsApi();

