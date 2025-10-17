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
    
    // Verify card belongs to user
    $card = $db->querySingle(
        "SELECT id, user_id, first_name, last_name FROM business_cards WHERE id = ?",
        [$cardId]
    );
    
    error_log("Card query result: " . json_encode($card));
    
    if (!$card) {
        error_log("Card not found for ID: $cardId");
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Card not found']);
        exit;
    }
    
    if ($card['user_id'] !== $userId) {
        error_log("Card ownership mismatch - Card user: {$card['user_id']}, Request user: $userId");
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized - card does not belong to you']);
        exit;
    }
    
    // Delete card (cascade will delete related data)
    $result = $db->execute(
        "DELETE FROM business_cards WHERE id = ?",
        [$cardId]
    );
    
    error_log("Delete query executed successfully");
    
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
