<?php
/**
 * Name Tag PDF Download (Image-Based Approach)
 * Generates and downloads PDF with 8 name tags per sheet
 * Requires authentication
 */

require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../api/includes/NameTagGenerator.php';
require_once __DIR__ . '/../../api/includes/UserAuth.php';

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

// Verify card exists, is active, and belongs to user
$card = $db->querySingle(
    "SELECT id, first_name, last_name, company_name, job_title FROM business_cards WHERE id = ? AND is_active = 1 AND user_id = ?",
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
        'font_family' => $fontFamily,
        'font_size' => $fontSize,
        'line_spacing' => $lineSpacing
    ];
    
    // Generate PDF using image-based approach
    $generator = new NameTagGenerator();
    $pdf = $generator->generateNameTagSheetImageBased($cardId, $preferences);
    
    if (!$pdf) {
        throw new Exception('Failed to generate PDF');
    }
    
    // Generate filename with company name and title
    $filenameParts = [$card['first_name'], $card['last_name']];
    
    // Add company name if available
    if (!empty($card['company_name'])) {
        $filenameParts[] = $card['company_name'];
    }
    
    // Add job title if available
    if (!empty($card['job_title'])) {
        $filenameParts[] = $card['job_title'];
    }
    
    $filename = implode('_', $filenameParts) . '_NameTags';
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
    $filename = $filename . '.pdf';
    
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Output PDF
    $pdf->Output($filename, 'D');
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('Name tag PDF generation error: ' . $e->getMessage());
    exit('Error generating PDF: ' . $e->getMessage());
}
?>
