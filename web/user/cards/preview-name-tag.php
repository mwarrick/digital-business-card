<?php
/**
 * Name Tag Preview Generator
 * Generates PNG preview of single name tag for live preview
 * No authentication required (similar to virtual background preview)
 */

// Start output buffering to prevent any output before headers
ob_start();

// Turn off error display for production (errors will be logged)
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../api/includes/NameTagGenerator.php';
require_once __DIR__ . '/../../api/includes/log-image-creation.php';

// Fix URL parameter parsing for HTML-encoded ampersands (&amp;)
// This happens when the URL is generated in JavaScript and embedded in HTML
if (isset($_SERVER['REQUEST_URI'])) {
    $requestUri = $_SERVER['REQUEST_URI'];
    // Replace &amp; with & in the query string
    if (strpos($requestUri, '&amp;') !== false) {
        $requestUri = str_replace('&amp;', '&', $requestUri);
        // Parse the corrected URI
        $parts = parse_url($requestUri);
        if (isset($parts['query'])) {
            parse_str($parts['query'], $correctedParams);
            // Merge corrected parameters into $_GET (corrected params take precedence)
            $_GET = array_merge($_GET, $correctedParams);
        }
    }
}

// Debug logging (only in development - remove in production if needed)
error_log("Preview name tag - card_id: " . ($_GET['card_id'] ?? 'NOT SET'));
error_log("Preview name tag - font_size: " . ($_GET['font_size'] ?? 'NOT SET'));
error_log("Preview name tag - include_name: " . ($_GET['include_name'] ?? 'NOT SET'));

// Get parameters
$cardId = $_GET['card_id'] ?? '';
$includeName = ($_GET['include_name'] ?? '1') === '1';
$includeTitle = ($_GET['include_title'] ?? '1') === '1';
$includeCompany = ($_GET['include_company'] ?? '0') === '1';
$includePhone = ($_GET['include_phone'] ?? '1') === '1';
$includeEmail = ($_GET['include_email'] ?? '1') === '1';
$includeWebsite = ($_GET['include_website'] ?? '1') === '1';
$includeAddress = ($_GET['include_address'] ?? '0') === '1';
$fontSize = $_GET['font_size'] ?? '12';
$fontFamily = trim($_GET['font_family'] ?? 'Arial');
$lineSpacing = isset($_GET['line_spacing']) ? (string)round((float)$_GET['line_spacing'], 1) : '0';
$messageAbove = trim($_GET['message_above'] ?? '');
$messageBelow = trim($_GET['message_below'] ?? '');
$qrSizePercentage = isset($_GET['qr_size_percentage']) ? (int)$_GET['qr_size_percentage'] : 100;

if (empty($cardId)) {
    ob_clean();
    http_response_code(400);
    header('Content-Type: image/png');
    // Create error image
    $errorImage = @imagecreatetruecolor(400, 200) ?: @imagecreate(400, 200);
    if ($errorImage) {
        $white = imagecolorallocate($errorImage, 255, 255, 255);
        $red = imagecolorallocate($errorImage, 255, 0, 0);
        imagefill($errorImage, 0, 0, $white);
        imagestring($errorImage, 5, 10, 90, 'Missing card ID', $red);
        imagepng($errorImage);
        imagedestroy($errorImage);
    }
    exit;
}

// Validate parameters
if (!in_array($fontSize, ['8', '9', '10', '11', '12', '13', '14', '15', '16', '18', '20'])) {
    ob_clean();
    http_response_code(400);
    header('Content-Type: image/png');
    $errorImage = @imagecreatetruecolor(400, 200) ?: @imagecreate(400, 200);
    if ($errorImage) {
        $white = imagecolorallocate($errorImage, 255, 255, 255);
        $red = imagecolorallocate($errorImage, 255, 0, 0);
        imagefill($errorImage, 0, 0, $white);
        imagestring($errorImage, 5, 10, 90, 'Invalid font size', $red);
        imagepng($errorImage);
        imagedestroy($errorImage);
    }
    exit;
}

if (strlen($messageAbove) > 100) {
    ob_clean();
    http_response_code(400);
    header('Content-Type: image/png');
    $errorImage = @imagecreatetruecolor(400, 200) ?: @imagecreate(400, 200);
    if ($errorImage) {
        $white = imagecolorallocate($errorImage, 255, 255, 255);
        $red = imagecolorallocate($errorImage, 255, 0, 0);
        imagefill($errorImage, 0, 0, $white);
        imagestring($errorImage, 5, 10, 90, 'Message above too long', $red);
        imagepng($errorImage);
        imagedestroy($errorImage);
    }
    exit;
}

if (strlen($messageBelow) > 100) {
    ob_clean();
    http_response_code(400);
    header('Content-Type: image/png');
    $errorImage = @imagecreatetruecolor(400, 200) ?: @imagecreate(400, 200);
    if ($errorImage) {
        $white = imagecolorallocate($errorImage, 255, 255, 255);
        $red = imagecolorallocate($errorImage, 255, 0, 0);
        imagefill($errorImage, 0, 0, $white);
        imagestring($errorImage, 5, 10, 90, 'Message below too long', $red);
        imagepng($errorImage);
        imagedestroy($errorImage);
    }
    exit;
}

// Validate QR size percentage
if ($qrSizePercentage < 25 || $qrSizePercentage > 150) {
    ob_clean();
    http_response_code(400);
    header('Content-Type: image/png');
    $errorImage = @imagecreatetruecolor(400, 200) ?: @imagecreate(400, 200);
    if ($errorImage) {
        $white = imagecolorallocate($errorImage, 255, 255, 255);
        $red = imagecolorallocate($errorImage, 255, 0, 0);
        imagefill($errorImage, 0, 0, $white);
        imagestring($errorImage, 5, 10, 90, 'Invalid QR size percentage', $red);
        imagepng($errorImage);
        imagedestroy($errorImage);
    }
    exit;
}

$db = Database::getInstance();

// For testing purposes, allow 'test' card ID
if ($cardId === 'test') {
    // Use a dummy card for testing
    $card = ['id' => 'test'];
} else {
    // Verify card exists and is active
    $card = $db->querySingle(
        "SELECT id FROM business_cards WHERE id = ? AND is_active = 1",
        [$cardId]
    );
    
    if (!$card) {
        ob_clean();
        http_response_code(404);
        header('Content-Type: image/png');
        $errorImage = @imagecreatetruecolor(400, 200) ?: @imagecreate(400, 200);
        if ($errorImage) {
            $white = imagecolorallocate($errorImage, 255, 255, 255);
            $red = imagecolorallocate($errorImage, 255, 0, 0);
            imagefill($errorImage, 0, 0, $white);
            imagestring($errorImage, 5, 10, 90, 'Card not found', $red);
            imagepng($errorImage);
            imagedestroy($errorImage);
        }
        exit;
    }
}

try {
    // Build preferences array
    $preferences = [
        'include_name' => $includeName,
        'include_title' => $includeTitle,
        'include_company' => $includeCompany,
        'include_phone' => $includePhone,
        'include_email' => $includeEmail,
        'include_website' => $includeWebsite,
        'include_address' => $includeAddress,
        'font_size' => $fontSize,
        'font_family' => $fontFamily,
        'line_spacing' => $lineSpacing,
        'message_above' => $messageAbove,
        'message_below' => $messageBelow,
        'qr_size_percentage' => $qrSizePercentage
    ];
    
    // Generate preview image
    $generator = new NameTagGenerator();
    $image = $generator->generatePreviewImage($cardId, $preferences);
    
    if (!$image) {
        throw new Exception('Failed to generate preview image');
    }
    
    // Clear any output before sending headers
    ob_clean();
    
    // Set headers for image output
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=300'); // Cache for 5 minutes
    
    // Log image creation
    $filename = "name-tag-preview-{$cardId}.png";
    $imagePath = "/tmp/{$filename}"; // Preview is streamed, not saved
    $dimensions = "243x168"; // Standard name tag dimensions
    
    logImageCreation(
        $filename,
        $imagePath,
        'name_tag',
        'generated',
        null, // File size not available for streamed content
        $dimensions
    );
    
    // Output image
    imagepng($image);
    imagedestroy($image);
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    header('Content-Type: image/png');
    error_log('Name tag preview generation failed: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Create error image
    $errorImage = @imagecreatetruecolor(500, 200) ?: @imagecreate(500, 200);
    if ($errorImage) {
        $white = imagecolorallocate($errorImage, 255, 255, 255);
        $red = imagecolorallocate($errorImage, 255, 0, 0);
        imagefill($errorImage, 0, 0, $white);
        $errorMsg = 'Error: ' . substr($e->getMessage(), 0, 60);
        imagestring($errorImage, 5, 10, 90, $errorMsg, $red);
        imagepng($errorImage);
        imagedestroy($errorImage);
    }
    exit;
}
?>

