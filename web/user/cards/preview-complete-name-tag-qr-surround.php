<?php
/**
 * Generate complete name tag preview image (banners + QR code) for QR surround variant
 * This generates the full image with banners included, suitable for HTML embedding
 */

require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../api/includes/NameTagGenerator.php';
require_once __DIR__ . '/../includes/UserAuth.php';

// Start output buffering to prevent any output before headers
ob_start();

// Check authentication
$userAuth = new UserAuth();
if (!$userAuth->isLoggedIn()) {
    ob_clean();
    http_response_code(401);
    exit('Authentication required');
}

// Fix URL encoding issues (handle &amp; vs &)
if (isset($_SERVER['REQUEST_URI'])) {
    $requestUri = $_SERVER['REQUEST_URI'];
    $requestUri = str_replace('&amp;', '&', $requestUri);
    parse_str(parse_url($requestUri, PHP_URL_QUERY), $params);
    if (!empty($params)) {
        $_GET = array_merge($_GET, $params);
    }
}

// Get parameters
$cardId = $_GET['card_id'] ?? '';
$topBannerText = trim($_GET['top_banner_text'] ?? 'Hello My Name Is...');
$topBannerColor = $_GET['top_banner_color'] ?? '#000000';
$topBannerFontFamily = $_GET['top_banner_font_family'] ?? 'Caveat';
$topBannerFontSize = (int)($_GET['top_banner_font_size'] ?? 16);
$bottomBannerText = trim($_GET['bottom_banner_text'] ?? '');
$bottomBannerColor = $_GET['bottom_banner_color'] ?? '#000000';
$bottomBannerFontFamily = $_GET['bottom_banner_font_family'] ?? 'Caveat';
$bottomBannerFontSize = (int)($_GET['bottom_banner_font_size'] ?? 8);

// Validate card ID
if (empty($cardId)) {
    ob_clean();
    http_response_code(400);
    exit('Missing card ID');
}

$db = Database::getInstance();

// Verify card exists, is active, and belongs to user
$card = $db->querySingle(
    "SELECT id FROM business_cards WHERE id = ? AND is_active = 1 AND user_id = ?",
    [$cardId, $userAuth->getUserId()]
);

if (!$card) {
    ob_clean();
    http_response_code(404);
    exit('Card not found or access denied');
}

try {
    // Build preferences array
    $preferences = [
        'top_banner_text' => $topBannerText,
        'top_banner_color' => $topBannerColor,
        'top_banner_font_family' => $topBannerFontFamily,
        'top_banner_font_size' => $topBannerFontSize,
        'bottom_banner_text' => $bottomBannerText,
        'bottom_banner_color' => $bottomBannerColor,
        'bottom_banner_font_family' => $bottomBannerFontFamily,
        'bottom_banner_font_size' => $bottomBannerFontSize
    ];
    
    // Generate complete image using NameTagGenerator
    $generator = new NameTagGenerator();
    $imagePath = $generator->generateCompleteQRSurroundImage($cardId, $preferences);
    
    if (!$imagePath || !file_exists($imagePath)) {
        error_log("Complete QR surround preview - Image generation failed. Path: " . ($imagePath ?: 'NULL') . ", exists: " . ($imagePath && file_exists($imagePath) ? 'YES' : 'NO'));
        throw new Exception('Failed to generate complete name tag image. Path: ' . ($imagePath ?: 'NULL'));
    }
    
    error_log("Complete QR surround preview - Image generated successfully at: " . $imagePath . ", size: " . filesize($imagePath));
    
    // Set headers for image output
    ob_clean();
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=3600'); // Cache for 1 hour
    header('Content-Length: ' . filesize($imagePath));
    
    // Output image
    readfile($imagePath);
    
    // Clean up temporary file
    @unlink($imagePath);
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    $errorMsg = 'Complete QR surround name tag preview error: ' . $e->getMessage();
    error_log($errorMsg);
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Output error as image with more details
    if (function_exists('imagecreatetruecolor')) {
        $errorImage = imagecreatetruecolor(1013, 700);
        $white = imagecolorallocate($errorImage, 255, 255, 255);
        $red = imagecolorallocate($errorImage, 255, 0, 0);
        $black = imagecolorallocate($errorImage, 0, 0, 0);
        imagefill($errorImage, 0, 0, $white);
        
        // Draw error message (truncate if too long)
        $errorText = substr($e->getMessage(), 0, 50);
        imagestring($errorImage, 5, 10, 330, 'Error generating image', $red);
        imagestring($errorImage, 3, 10, 360, $errorText, $black);
        
        header('Content-Type: image/png');
        imagepng($errorImage);
        imagedestroy($errorImage);
    } else {
        echo 'Error: ' . $e->getMessage();
    }
    exit;
} catch (Throwable $e) {
    ob_clean();
    http_response_code(500);
    $errorMsg = 'Complete QR surround name tag preview fatal error: ' . $e->getMessage();
    error_log($errorMsg);
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Output error as image
    if (function_exists('imagecreatetruecolor')) {
        $errorImage = imagecreatetruecolor(1013, 700);
        $white = imagecolorallocate($errorImage, 255, 255, 255);
        $red = imagecolorallocate($errorImage, 255, 0, 0);
        imagefill($errorImage, 0, 0, $white);
        imagestring($errorImage, 5, 10, 350, 'Fatal Error', $red);
        header('Content-Type: image/png');
        imagepng($errorImage);
        imagedestroy($errorImage);
    } else {
        echo 'Fatal Error: ' . $e->getMessage();
    }
    exit;
}
?>

