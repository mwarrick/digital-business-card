<?php
/**
 * Virtual Background Download for Web Interface
 * Session-based authentication for web downloads
 */

// Start output buffering
ob_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../api/includes/VirtualBackgroundGenerator.php';
require_once __DIR__ . '/../../api/includes/log-image-creation.php';

try {
    // Check authentication
    UserAuth::requireAuth();
    
    $db = Database::getInstance();
    $generator = new VirtualBackgroundGenerator();
    
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
    
    // Verify card ownership
    $card = $db->querySingle(
        "SELECT id, first_name, last_name, company_name, job_title, theme FROM business_cards 
         WHERE id = ? AND user_id = ? AND is_active = 1",
        [$cardId, UserAuth::getUserId()]
    );
    
    if (!$card) {
        throw new Exception('Card not found or access denied');
    }
    
    // Check rate limiting (10 generations per hour per user)
    $rateLimitKey = "vb_generation_" . UserAuth::getUserId();
    $rateLimitFile = __DIR__ . '/../../storage/rate-limits/' . md5($rateLimitKey) . '.json';
    
    $rateLimitData = [];
    if (file_exists($rateLimitFile)) {
        $rateLimitData = json_decode(file_get_contents($rateLimitFile), true) ?? [];
    }
    
    $currentTime = time();
    $hourAgo = $currentTime - 3600;
    
    // Clean old entries
    $rateLimitData = array_filter($rateLimitData, function($timestamp) use ($hourAgo) {
        return $timestamp > $hourAgo;
    });
    
    if (count($rateLimitData) >= 10) {
        throw new Exception('Rate limit exceeded. Maximum 10 generations per hour.');
    }
    
    // Add current request
    $rateLimitData[] = $currentTime;
    
    // Ensure directory exists
    $rateLimitDir = dirname($rateLimitFile);
    if (!is_dir($rateLimitDir)) {
        mkdir($rateLimitDir, 0755, true);
    }
    
    file_put_contents($rateLimitFile, json_encode($rateLimitData));
    
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
    
    // Generate filename from card information
    $name = trim($card['first_name'] . ' ' . $card['last_name']);
    $title = !empty($card['job_title']) ? ' - ' . $card['job_title'] : '';
    $company = !empty($card['company_name']) ? ' - ' . $card['company_name'] : '';
    
    // Clean filename (remove special characters, limit length)
    $filename = $name . $title . $company;
    $filename = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $filename);
    $filename = preg_replace('/\s+/', '_', trim($filename));
    $filename = substr($filename, 0, 100); // Limit length
    $filename = $filename . '_' . $width . 'x' . $height . '.png';
    
    // Set headers for image download
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Log image creation
    $imageFilename = "virtual-background-{$cardId}-{$width}x{$height}.png";
    $imagePath = "/tmp/{$imageFilename}"; // Virtual background is streamed, not saved
    $dimensions = "{$width}x{$height}";
    
    logImageCreation(
        $imageFilename,
        $imagePath,
        'virtual_background',
        'generated',
        null, // File size not available for streamed content
        $dimensions
    );
    
    // Output image
    imagepng($image);
    imagedestroy($image);
    
    // Log generation for analytics
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => UserAuth::getUserId(),
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
