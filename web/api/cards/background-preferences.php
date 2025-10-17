<?php
/**
 * Virtual Background Preferences API
 * CRUD operations for virtual background preferences
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/VirtualBackgroundGenerator.php';
require_once __DIR__ . '/../includes/JWTHelper.php';

header('Content-Type: application/json');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $db = Database::getInstance();
    $generator = new VirtualBackgroundGenerator();
    
    // Get authentication token
    $token = $_GET['token'] ?? '';
    if (empty($token)) {
        // Try to get token from Authorization header
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }
    }
    
    if (empty($token)) {
        throw new Exception('Authentication required');
    }
    
    // Verify JWT token
    $payload = JWTHelper::verify($token);
    if (!$payload) {
        throw new Exception('Invalid token');
    }
    
    $userId = $payload['user_id'] ?? '';
    if (empty($userId)) {
        throw new Exception('Invalid token payload');
    }
    
    switch ($method) {
        case 'GET':
            // Get preferences for a card
            $cardId = $_GET['card_id'] ?? '';
            if (empty($cardId)) {
                throw new Exception('Card ID is required');
            }
            
            // Verify card ownership
            $card = $db->querySingle(
                "SELECT id FROM business_cards WHERE id = ? AND user_id = ? AND is_active = 1",
                [$cardId, $userId]
            );
            
            if (!$card) {
                throw new Exception('Card not found or access denied');
            }
            
            // Get preferences
            $preferences = $db->querySingle(
                "SELECT * FROM virtual_background_preferences WHERE card_id = ?",
                [$cardId]
            );
            
            if (!$preferences) {
                // Return default preferences
                $preferences = [
                    'qr_position' => 'bottom-right',
                    'qr_size' => 300,
                    'padding_x' => 50,
                    'padding_y' => 50,
                    'text_option' => 'qr-only'
                ];
            }
            
            echo json_encode([
                'success' => true,
                'preferences' => $preferences
            ]);
            break;
            
        case 'POST':
        case 'PUT':
            // Save preferences for a card
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            $cardId = $input['card_id'] ?? '';
            if (empty($cardId)) {
                throw new Exception('Card ID is required');
            }
            
            // Verify card ownership
            $card = $db->querySingle(
                "SELECT id FROM business_cards WHERE id = ? AND user_id = ? AND is_active = 1",
                [$cardId, $userId]
            );
            
            if (!$card) {
                throw new Exception('Card not found or access denied');
            }
            
            // Validate preferences
            $preferences = [
                'qr_position' => $input['qr_position'] ?? 'bottom-right',
                'qr_size' => (int)($input['qr_size'] ?? 300),
                'padding_x' => (int)($input['padding_x'] ?? 50),
                'padding_y' => (int)($input['padding_y'] ?? 50),
                'text_option' => $input['text_option'] ?? 'qr-only'
            ];
            
            // Validate inputs
            if (!in_array($preferences['qr_position'], ['top-left', 'top-right', 'bottom-left', 'bottom-right'])) {
                throw new Exception('Invalid QR position');
            }
            
            if ($preferences['qr_size'] < 200 || $preferences['qr_size'] > 500) {
                throw new Exception('QR size must be between 200 and 500 pixels');
            }
            
            if ($preferences['padding_x'] < 20 || $preferences['padding_x'] > 200) {
                throw new Exception('Padding X must be between 20 and 200 pixels');
            }
            
            if ($preferences['padding_y'] < 20 || $preferences['padding_y'] > 200) {
                throw new Exception('Padding Y must be between 20 and 200 pixels');
            }
            
            if (!in_array($preferences['text_option'], ['qr-only', 'qr-name', 'qr-name-company'])) {
                throw new Exception('Invalid text option');
            }
            
            // Save preferences
            $generator->savePreferences($cardId, $preferences);
            
            echo json_encode([
                'success' => true,
                'message' => 'Preferences saved successfully',
                'preferences' => $preferences
            ]);
            break;
            
        case 'DELETE':
            // Delete preferences for a card
            $cardId = $_GET['card_id'] ?? '';
            if (empty($cardId)) {
                throw new Exception('Card ID is required');
            }
            
            // Verify card ownership
            $card = $db->querySingle(
                "SELECT id FROM business_cards WHERE id = ? AND user_id = ? AND is_active = 1",
                [$cardId, $userId]
            );
            
            if (!$card) {
                throw new Exception('Card not found or access denied');
            }
            
            // Delete preferences
            $db->execute(
                "DELETE FROM virtual_background_preferences WHERE card_id = ?",
                [$cardId]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Preferences deleted successfully'
            ]);
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
