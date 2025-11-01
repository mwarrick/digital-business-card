<?php
/**
 * Virtual Background Generation API
 * Generates and streams virtual background images with QR codes
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/VirtualBackgroundGenerator.php';
require_once __DIR__ . '/../includes/JWTHelper.php';
require_once __DIR__ . '/../includes/log-image-creation.php';

header('Content-Type: application/json');

try {
    // Get parameters
    $cardId = $_GET['card_id'] ?? '';
    $width = (int)($_GET['width'] ?? 1920);
    $height = (int)($_GET['height'] ?? 1080);
    $qrPosition = $_GET['qr_position'] ?? 'bottom-right';
    $qrSize = (int)($_GET['qr_size'] ?? 300);
    $paddingX = (int)($_GET['padding_x'] ?? 50);
    $paddingY = (int)($_GET['padding_y'] ?? 50);
    $textOption = $_GET['text_option'] ?? 'qr-only';
    
    // Validate parameters
    if (empty($cardId)) {
        throw new Exception('Card ID is required');
    }
    
    if ($width < 800 || $width > 4000 || $height < 600 || $height > 3000) {
        throw new Exception('Invalid dimensions');
    }
    
    if (!in_array($qrPosition, ['top-left', 'top-right', 'bottom-left', 'bottom-right'])) {
        throw new Exception('Invalid QR position');
    }
    
    if ($qrSize < 200 || $qrSize > 500) {
        throw new Exception('QR size must be between 200 and 500 pixels');
    }
    
    if ($paddingX < 20 || $paddingX > 200 || $paddingY < 20 || $paddingY > 200) {
        throw new Exception('Padding must be between 20 and 200 pixels');
    }
    
    if (!in_array($textOption, ['qr-only', 'qr-name', 'qr-name-company'])) {
        throw new Exception('Invalid text option');
    }
    
    // Check authentication
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
    
    // Verify card ownership
    $db = Database::getInstance();
    $card = $db->querySingle(
        "SELECT id FROM business_cards WHERE id = ? AND user_id = ? AND is_active = 1",
        [$cardId, $userId]
    );
    
    if (!$card) {
        throw new Exception('Card not found or access denied');
    }
    
    // Rate limiting disabled to prevent issues for legitimate iOS users
    // $rateLimitKey = "vb_generation_{$userId}";
    // $rateLimitFile = __DIR__ . '/../../storage/rate-limits/' . md5($rateLimitKey) . '.json';
    // 
    // $rateLimitData = [];
    // if (file_exists($rateLimitFile)) {
    //     $rateLimitData = json_decode(file_get_contents($rateLimitFile), true) ?? [];
    // }
    // 
    // $currentTime = time();
    // $hourAgo = $currentTime - 3600;
    // 
    // // Clean old entries
    // $rateLimitData = array_filter($rateLimitData, function($timestamp) use ($hourAgo) {
    //     return $timestamp > $hourAgo;
    // });
    // 
    // if (count($rateLimitData) >= 10) {
    //     throw new Exception('Rate limit exceeded. Maximum 10 generations per hour.');
    // }
    // 
    // // Add current request
    // $rateLimitData[] = $currentTime;
    // 
    // // Ensure directory exists
    // $rateLimitDir = dirname($rateLimitFile);
    // if (!is_dir($rateLimitDir)) {
    //     mkdir($rateLimitDir, 0755, true);
    // }
    // 
    // file_put_contents($rateLimitFile, json_encode($rateLimitData));
    
    // Prepare preferences
    $preferences = [
        'qr_position' => $qrPosition,
        'qr_size' => $qrSize,
        'padding_x' => $paddingX,
        'padding_y' => $paddingY,
        'text_option' => $textOption
    ];
    
    // Generate background
    $generator = new VirtualBackgroundGenerator();
    $image = $generator->generateBackground($cardId, $width, $height, $preferences);
    
    if (!$image) {
        throw new Exception('Failed to generate background image');
    }
    
    // Set headers for image download
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="virtual-background-' . $cardId . '-' . $width . 'x' . $height . '.png"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output image
    imagepng($image);
    
    // Log image creation
    $filename = "virtual-background-{$cardId}-{$width}x{$height}.png";
    $imagePath = "/tmp/{$filename}"; // Virtual background is streamed, not saved
    $dimensions = "{$width}x{$height}";
    
    logImageCreation(
        $filename,
        $imagePath,
        'virtual_background',
        'generated',
        null, // File size not available for streamed content
        $dimensions
    );
    
    imagedestroy($image);
    
    // Log generation for analytics
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $userId,
        'card_id' => $cardId,
        'width' => $width,
        'height' => $height,
        'qr_position' => $qrPosition,
        'qr_size' => $qrSize,
        'text_option' => $textOption
    ];
    
    $logFile = __DIR__ . '/../../storage/virtual-backgrounds.log';
    file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    
} catch (Exception $e) {
    // Return error as JSON if not already streaming image
    if (!headers_sent() || strpos(headers_list()[0] ?? '', 'image/png') === false) {
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
