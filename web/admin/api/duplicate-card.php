<?php
/**
 * Duplicate Business Card API (Admin)
 * Creates a complete copy of a business card with all related records
 */

require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../includes/AdminAuth.php';

AdminAuth::requireAuth();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$cardId = $input['card_id'] ?? '';

if (empty($cardId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Card ID is required']);
    exit;
}

try {
    $db = Database::getInstance();
    $userId = AdminAuth::getUserId();
    
    // Begin transaction
    $db->beginTransaction();
    
    // Get the original card
    $originalCard = $db->querySingle(
        "SELECT * FROM business_cards WHERE id = ? AND user_id = ? AND is_active = 1",
        [$cardId, $userId]
    );
    
    if (!$originalCard) {
        throw new Exception('Card not found or access denied');
    }
    
    // Generate new UUID for the duplicate
    $newCardId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    // Create the duplicate card
    $db->execute(
        "INSERT INTO business_cards (id, user_id, first_name, last_name, phone_number, job_title, company_name, bio, created_at, updated_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
        [
            $newCardId,
            $userId,
            $originalCard['first_name'],
            $originalCard['last_name'],
            $originalCard['phone_number'],
            $originalCard['job_title'],
            $originalCard['company_name'],
            $originalCard['bio']
        ]
    );
    
    // Duplicate email contacts
    $emails = $db->query(
        "SELECT * FROM email_contacts WHERE business_card_id = ? ORDER BY created_at ASC",
        [$cardId]
    );
    
    foreach ($emails as $email) {
        $newEmailId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        $db->execute(
            "INSERT INTO email_contacts (id, business_card_id, email, type, label, is_primary, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [
                $newEmailId,
                $newCardId,
                $email['email'],
                $email['type'],
                $email['label'],
                $email['is_primary']
            ]
        );
    }
    
    // Duplicate phone contacts
    $phones = $db->query(
        "SELECT * FROM phone_contacts WHERE business_card_id = ? ORDER BY created_at ASC",
        [$cardId]
    );
    
    foreach ($phones as $phone) {
        $newPhoneId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        $db->execute(
            "INSERT INTO phone_contacts (id, business_card_id, phone_number, type, label, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())",
            [
                $newPhoneId,
                $newCardId,
                $phone['phone_number'],
                $phone['type'],
                $phone['label']
            ]
        );
    }
    
    // Duplicate website links
    $websites = $db->query(
        "SELECT * FROM website_links WHERE business_card_id = ? ORDER BY created_at ASC",
        [$cardId]
    );
    
    foreach ($websites as $website) {
        $newWebsiteId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        $db->execute(
            "INSERT INTO website_links (id, business_card_id, name, url, description, is_primary, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [
                $newWebsiteId,
                $newCardId,
                $website['name'],
                $website['url'],
                $website['description'],
                $website['is_primary']
            ]
        );
    }
    
    // Duplicate address
    $address = $db->querySingle(
        "SELECT * FROM addresses WHERE business_card_id = ?",
        [$cardId]
    );
    
    if ($address) {
        $newAddressId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        $db->execute(
            "INSERT INTO addresses (id, business_card_id, street, city, state, zip_code, country, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $newAddressId,
                $newCardId,
                $address['street'],
                $address['city'],
                $address['state'],
                $address['zip_code'],
                $address['country']
            ]
        );
    }
    
    // Commit transaction
    $db->commit();
    
    // Return success with the new card ID
    echo json_encode([
        'success' => true,
        'new_card_id' => $newCardId,
        'message' => 'Card duplicated successfully'
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
