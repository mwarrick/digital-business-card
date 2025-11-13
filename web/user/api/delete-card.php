<?php
/**
 * Delete Business Card - Session Authenticated
 * POST /user/api/delete-card.php
 * Deletes a business card using session authentication
 */

require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

// Require authentication
UserAuth::requireAuth();

$userId = UserAuth::getUserId();
$db = Database::getInstance();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get card ID from POST data
$cardId = $_POST['card_id'] ?? null;

if (!$cardId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Card ID required']);
    exit;
}

try {
    // Debug logging
    error_log("Delete card request - Card ID: $cardId, User ID: $userId");
    
    // Verify card belongs to user and is not already deleted
    $card = $db->querySingle(
        "SELECT id, user_id, first_name, last_name, is_deleted FROM business_cards WHERE id = ? AND user_id = ? AND (is_deleted = 0 OR is_deleted IS NULL)",
        [$cardId, $userId]
    );
    
    error_log("Card query result: " . json_encode($card));
    
    if (!$card) {
        error_log("Card not found for ID: $cardId, User ID: $userId, or card is already deleted");
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Card not found or already deleted']);
        exit;
    }
    
    // Check if card is already deleted (shouldn't happen due to query, but double-check)
    if (isset($card['is_deleted']) && $card['is_deleted'] == 1) {
        error_log("Card is already deleted - Card ID: $cardId");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Card is already deleted']);
        exit;
    }
    
    // Soft delete card
    $result = $db->execute(
        "UPDATE business_cards SET is_deleted = 1, updated_at = NOW() WHERE id = ? AND user_id = ?",
        [$cardId, $userId]
    );
    
    // Check if the update actually affected any rows
    // Note: PDO rowCount() may not work for UPDATE in all databases, but we'll try
    try {
        $rowsAffected = $db->rowCount();
        error_log("Delete query executed - Rows affected: $rowsAffected");
        
        if ($rowsAffected === 0) {
            error_log("⚠️ WARNING: Delete query affected 0 rows - card may have been deleted already or doesn't exist");
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Card not found or already deleted']);
            exit;
        }
    } catch (Exception $e) {
        // rowCount() might not be available, but the execute should have worked
        error_log("Delete query executed (rowCount check skipped: " . $e->getMessage() . ")");
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Card deleted successfully',
        'deleted_card' => [
            'id' => $cardId,
            'name' => $card['first_name'] . ' ' . $card['last_name']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Card deletion error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete card: ' . $e->getMessage()]);
}
