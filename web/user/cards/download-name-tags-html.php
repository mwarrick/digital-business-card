<?php
/**
 * Name Tag PDF Download (HTML/CSS Approach)
 * Generates and downloads PDF with 8 name tags per sheet using HTML/CSS
 * Requires authentication
 */

require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../api/includes/NameTagGenerator.php';
require_once __DIR__ . '/../includes/UserAuth.php';

// Check authentication
$userAuth = new UserAuth();
if (!$userAuth->isLoggedIn()) {
    http_response_code(401);
    exit('Authentication required');
}

// Get parameters
$cardId = $_GET['card_id'] ?? '';
$includeName = ($_GET['include_name'] ?? '1') === '1';
$includeTitle = ($_GET['include_title'] ?? '1') === '1';
$includePhone = ($_GET['include_phone'] ?? '1') === '1';
$includeEmail = ($_GET['include_email'] ?? '1') === '1';
$includeWebsite = ($_GET['include_website'] ?? '1') === '1';
$includeAddress = ($_GET['include_address'] ?? '0') === '1';
$fontSize = $_GET['font_size'] ?? '12';
$messageAbove = trim($_GET['message_above'] ?? '');
$messageBelow = trim($_GET['message_below'] ?? '');

// Validate parameters
if (empty($cardId)) {
    http_response_code(400);
    exit('Missing card ID');
}

if (!is_numeric($fontSize) || $fontSize < 8 || $fontSize > 20) {
    http_response_code(400);
    exit('Invalid font size');
}

if (strlen($messageAbove) > 100) {
    http_response_code(400);
    exit('Message above is too long');
}

if (strlen($messageBelow) > 100) {
    http_response_code(400);
    exit('Message below is too long');
}

$db = Database::getInstance();

// Verify card exists, is active, and belongs to user
$card = $db->querySingle(
    "SELECT id, first_name, last_name FROM business_cards WHERE id = ? AND is_active = 1 AND user_id = ?",
    [$cardId, $userAuth->getUserId()]
);

if (!$card) {
    http_response_code(404);
    exit('Card not found or access denied');
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
        'font_size' => $fontSize,
        'message_above' => $messageAbove,
        'message_below' => $messageBelow
    ];
    
    // Generate PDF using HTML/CSS approach
    $generator = new NameTagGenerator();
    $pdf = $generator->generateNameTagSheetHTML($cardId, $preferences);
    
    if (!$pdf) {
        throw new Exception('Failed to generate PDF');
    }
    
    // Generate filename
    $filename = $card['first_name'] . '_' . $card['last_name'] . '_NameTags.pdf';
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
    
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Output PDF
    $pdf->Output($filename, 'D');
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('Name tag HTML PDF generation error: ' . $e->getMessage());
    exit('Error generating PDF: ' . $e->getMessage());
}
?>
