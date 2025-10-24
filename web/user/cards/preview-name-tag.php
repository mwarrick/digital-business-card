<?php
/**
 * Name Tag Preview Generator
 * Generates PNG preview of single name tag for live preview
 * No authentication required (similar to virtual background preview)
 */

require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../api/includes/NameTagGenerator.php';
require_once __DIR__ . '/../../api/includes/log-image-creation.php';

// Get parameters
$cardId = $_GET['card_id'] ?? '';
$includeName = ($_GET['include_name'] ?? '1') === '1';
$includeTitle = ($_GET['include_title'] ?? '1') === '1';
$includePhone = ($_GET['include_phone'] ?? '1') === '1';
$includeEmail = ($_GET['include_email'] ?? '1') === '1';
$includeWebsite = ($_GET['include_website'] ?? '0') === '1';
$includeAddress = ($_GET['include_address'] ?? '0') === '1';
$fontFamily = $_GET['font_family'] ?? 'helvetica';
$fontSize = $_GET['font_size'] ?? '12';
$lineSpacing = $_GET['line_spacing'] ?? '0';

if (empty($cardId)) {
    http_response_code(400);
    exit('Missing card ID');
}

// Validate parameters
if (!in_array($fontFamily, ['helvetica', 'times', 'courier'])) {
    http_response_code(400);
    exit('Invalid font family option');
}

if (!in_array($fontSize, ['8', '9', '10', '11', '12', '13', '14', '15', '16', '18', '20'])) {
    http_response_code(400);
    exit('Invalid font size option');
}

if (!in_array($lineSpacing, ['-2', '-1.5', '-1', '-0.5', '0', '0.5', '1', '1.5', '2'])) {
    http_response_code(400);
    exit('Invalid line spacing option');
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
        http_response_code(404);
        exit('Card not found');
    }
}

try {
    // Build preferences array
    $preferences = [
        'include_name' => $includeName,
        'include_title' => $includeTitle,
        'include_phone' => $includePhone,
        'include_email' => $includeEmail,
        'include_website' => $includeWebsite,
        'include_address' => $includeAddress,
        'font_family' => $fontFamily,
        'font_size' => $fontSize,
        'line_spacing' => $lineSpacing
    ];
    
    // Generate preview image
    $generator = new NameTagGenerator();
    $image = $generator->generatePreviewImage($cardId, $preferences);
    
    if (!$image) {
        throw new Exception('Failed to generate preview image');
    }
    
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
    http_response_code(500);
    error_log('Name tag preview generation failed: ' . $e->getMessage());
    exit('Failed to generate preview');
}
?>

