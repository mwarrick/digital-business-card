<?php
/**
 * Name Tag PDF Download (HTML/CSS Approach)
 * Generates and downloads PDF with 8 name tags per sheet using HTML/CSS
 * Requires authentication
 */

// Start output buffering with maximum compression to prevent any output
// Use output buffering to catch any accidental output before headers
if (!ob_get_level()) {
    ob_start();
}

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
    
    // Generate PDF using HTML/CSS approach
    // Note: All debug logging is done in NameTagGenerator, not here
    $generator = new NameTagGenerator();
    
    // Generate PDF - capture any accidental output
    ob_start();
    try {
        $pdf = $generator->generateNameTagSheetHTML($cardId, $preferences);
        $capturedOutput = ob_get_contents();
        ob_end_clean();
        
        if (!empty($capturedOutput)) {
            // Log what was captured but don't output it
            error_log("CRITICAL: Output captured during PDF generation: " . substr($capturedOutput, 0, 1000));
            // If there's output, we can't send a PDF
            throw new Exception('PDF generation produced unexpected output - cannot send PDF');
        }
        
        if (!$pdf || !is_object($pdf) || !method_exists($pdf, 'Output')) {
            throw new Exception('Failed to generate PDF - invalid PDF object');
        }
    } catch (Exception $e) {
        if (ob_get_level()) {
            ob_end_clean();
        }
        throw $e;
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
    
    // Clear ALL output before sending headers
    // End all output buffers to ensure clean PDF output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Ensure no output has been sent
    if (headers_sent($file, $line)) {
        error_log("CRITICAL: Headers already sent in {$file} at line {$line}");
        throw new Exception("Cannot send PDF - headers already sent");
    }
    
    // Set headers for PDF download BEFORE calling Output()
    // TCPDF's Output() with 'D' parameter will send its own headers if not already sent
    // But we want to ensure clean headers
    header('Content-Type: application/pdf', true);
    header('Content-Disposition: attachment; filename="' . $filename . '"', true);
    header('Cache-Control: private, max-age=0, must-revalidate', true);
    header('Pragma: public', true);
    
    // Output PDF using 'I' (inline) but with Content-Disposition: attachment it will download
    // Or use 'D' which forces download
    // 'D' sends the file to the browser and forces a download
    $pdf->Output($filename, 'D');
    
    // Exit immediately after output to prevent any trailing output
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('Name tag HTML PDF generation error: ' . $e->getMessage());
    exit('Error generating PDF: ' . $e->getMessage());
}
?>
