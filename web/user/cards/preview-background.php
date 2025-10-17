<?php
/**
 * Virtual Background Preview (No Authentication Required)
 * Generates small preview images for the virtual background page
 */

// Start output buffering
ob_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../api/includes/VirtualBackgroundGenerator.php';

try {
    $db = Database::getInstance();
    $generator = new VirtualBackgroundGenerator();
    
    // Get parameters
    $cardId = $_GET['card_id'] ?? '';
    $width = (int)($_GET['width'] ?? 400);
    $height = (int)($_GET['height'] ?? 225);
    $qrPosition = $_GET['qr_position'] ?? 'bottom-right';
    $qrSize = (int)($_GET['qr_size'] ?? 60);
    $paddingX = (int)($_GET['padding_x'] ?? 10);
    $paddingY = (int)($_GET['padding_y'] ?? 10);
    $textOption = $_GET['text_option'] ?? 'qr-only';
    
    // Validate parameters
    if (empty($cardId)) {
        throw new Exception('Card ID is required');
    }
    
    // Limit preview size to prevent abuse
    if ($width > 800 || $height > 600) {
        throw new Exception('Preview size too large');
    }
    
    if (!in_array($qrPosition, ['top-left', 'top-right', 'bottom-left', 'bottom-right'])) {
        throw new Exception('Invalid QR position');
    }
    
    if ($qrSize < 20 || $qrSize > 200) {
        throw new Exception('QR size must be between 20 and 200 pixels for preview');
    }
    
    if ($paddingX < 5 || $paddingX > 100 || $paddingY < 5 || $paddingY > 100) {
        throw new Exception('Padding must be between 5 and 100 pixels for preview');
    }
    
    if (!in_array($textOption, ['qr-only', 'qr-name', 'qr-name-company'])) {
        throw new Exception('Invalid text option');
    }
    
    // Get card data (no authentication required for preview)
    $card = $db->querySingle(
        "SELECT id, first_name, last_name, company_name, theme FROM business_cards 
         WHERE id = ? AND is_active = 1",
        [$cardId]
    );
    
    if (!$card) {
        throw new Exception('Card not found or inactive');
    }
    
    // Prepare preferences
    $preferences = [
        'qr_position' => $qrPosition,
        'qr_size' => $qrSize,
        'padding_x' => $paddingX,
        'padding_y' => $paddingY,
        'text_option' => $textOption
    ];
    
    // Add custom colors if provided
    if (isset($_GET['color_top']) && isset($_GET['color_bottom'])) {
        $preferences['color_top'] = $_GET['color_top'];
        $preferences['color_bottom'] = $_GET['color_bottom'];
    }
    
    // Generate background
    $image = $generator->generateBackground($cardId, $width, $height, $preferences);
    
    if (!$image) {
        throw new Exception('Failed to generate background image');
    }
    
    // Clear any previous output
    ob_clean();
    
    // Set headers for image
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=300'); // Cache for 5 minutes
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');
    
    // Output image
    imagepng($image);
    imagedestroy($image);
    
} catch (Exception $e) {
    // Return error as JSON if not already streaming image
    if (!headers_sent() || strpos(headers_list()[0] ?? '', 'image/png') === false) {
        ob_clean();
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    } else {
        // If we're already streaming, just stop
        exit;
    }
}
?>
