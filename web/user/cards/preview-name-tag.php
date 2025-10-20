<?php
/**
 * Name Tag Preview Generator
 * Generates PNG preview of single name tag for live preview
 * No authentication required (similar to virtual background preview)
 */

require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../api/includes/NameTagGenerator.php';

// Get parameters
$cardId = $_GET['card_id'] ?? '';
$includeSignature = $_GET['include_signature'] ?? 'profile';
$includeName = ($_GET['include_name'] ?? '1') === '1';
$includeTitle = ($_GET['include_title'] ?? '1') === '1';
$includePhone = ($_GET['include_phone'] ?? '1') === '1';
$includeEmail = ($_GET['include_email'] ?? '1') === '1';
$includeAddress = ($_GET['include_address'] ?? '0') === '1';

if (empty($cardId)) {
    http_response_code(400);
    exit('Missing card ID');
}

// Validate parameters
if (!in_array($includeSignature, ['none', 'profile', 'logo'])) {
    http_response_code(400);
    exit('Invalid signature option');
}

$db = Database::getInstance();

// Verify card exists and is active
$card = $db->querySingle(
    "SELECT id FROM business_cards WHERE id = ? AND is_active = 1",
    [$cardId]
);

if (!$card) {
    http_response_code(404);
    exit('Card not found');
}

try {
    // Build preferences array
    $preferences = [
        'include_signature' => $includeSignature,
        'include_name' => $includeName,
        'include_title' => $includeTitle,
        'include_phone' => $includePhone,
        'include_email' => $includeEmail,
        'include_address' => $includeAddress
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
    
    // Output image
    imagepng($image);
    imagedestroy($image);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('Name tag preview generation failed: ' . $e->getMessage());
    exit('Failed to generate preview');
}
?>

