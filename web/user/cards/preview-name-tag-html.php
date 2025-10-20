<?php
/**
 * Name Tag Preview Generator (HTML/CSS Approach)
 * Generates HTML preview of single name tag for live preview
 * No authentication required
 */

require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../api/includes/NameTagGenerator.php';

// Get parameters
$cardId = $_GET['card_id'] ?? '';
$includeName = ($_GET['include_name'] ?? '1') === '1';
$includeTitle = ($_GET['include_title'] ?? '1') === '1';
$includePhone = ($_GET['include_phone'] ?? '1') === '1';
$includeEmail = ($_GET['include_email'] ?? '1') === '1';
$includeWebsite = ($_GET['include_website'] ?? '1') === '1';
$includeAddress = ($_GET['include_address'] ?? '0') === '1';
$fontFamily = $_GET['font_family'] ?? 'helvetica';
$fontSize = $_GET['font_size'] ?? '12';
$lineSpacing = $_GET['line_spacing'] ?? '0';

// Validate parameters
if (empty($cardId)) {
    http_response_code(400);
    exit('Missing card ID');
}

if (!in_array($fontFamily, ['helvetica', 'times', 'courier'])) {
    http_response_code(400);
    exit('Invalid font family');
}

if (!is_numeric($fontSize) || $fontSize < 8 || $fontSize > 20) {
    http_response_code(400);
    exit('Invalid font size');
}

if (!is_numeric($lineSpacing) || $lineSpacing < -2 || $lineSpacing > 2) {
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
    
    // Generate HTML preview
    $generator = new NameTagGenerator();
    $htmlFile = $generator->generatePreviewImageHTML($cardId, $preferences);
    
    if (!$htmlFile || !file_exists($htmlFile)) {
        throw new Exception('Failed to generate HTML preview');
    }
    
    // Set headers for HTML output
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: public, max-age=300'); // Cache for 5 minutes
    
    // Output HTML content
    readfile($htmlFile);
    
    // Clean up temporary file
    unlink($htmlFile);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('Name tag HTML preview error: ' . $e->getMessage());
    exit('Error generating preview: ' . $e->getMessage());
}
?>
