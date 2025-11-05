<?php
/**
 * Name Tag PDF Download - QR Surround Variant
 * Generates and downloads PDF with 8 name tags per sheet
 * QR code surrounds the name on each tag
 */

// Start output buffering to prevent any output before PDF
ob_start();

require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../api/includes/NameTagGenerator.php';
require_once __DIR__ . '/../../api/includes/log-image-creation.php';

UserAuth::requireAuth();

// Clear any output that may have been sent
ob_clean();

$db = Database::getInstance();
$userId = UserAuth::getUserId();

// Get parameters
$cardId = $_GET['card_id'] ?? '';
$topBannerText = $_GET['top_banner_text'] ?? 'Hello My Name Is...';
$topBannerColor = $_GET['top_banner_color'] ?? '#000000';
$topBannerFontFamily = $_GET['top_banner_font_family'] ?? 'Arial';
$topBannerFontSize = (int)($_GET['top_banner_font_size'] ?? 16);
$bottomBannerText = $_GET['bottom_banner_text'] ?? '';
$bottomBannerColor = $_GET['bottom_banner_color'] ?? '#000000';
$bottomBannerFontFamily = $_GET['bottom_banner_font_family'] ?? 'Arial';
$bottomBannerFontSize = (int)($_GET['bottom_banner_font_size'] ?? 8);

// Validate parameters
if (empty($cardId)) {
    ob_clean();
    http_response_code(400);
    die('Card ID is required');
}

// Validate colors (hex format)
if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $topBannerColor)) {
    ob_clean();
    http_response_code(400);
    die('Invalid top banner color format');
}

if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $bottomBannerColor)) {
    ob_clean();
    http_response_code(400);
    die('Invalid bottom banner color format');
}

// Verify card ownership
$card = $db->querySingle(
    "SELECT id, first_name, last_name, company_name, job_title FROM business_cards 
     WHERE id = ? AND user_id = ? AND is_active = 1",
    [$cardId, $userId]
);

if (!$card) {
    ob_clean();
    http_response_code(404);
    die('Card not found or access denied');
}

try {
    // Get spacing values from GET or preferences (with defaults)
    $topMargin = isset($_GET['top_margin']) ? (int)$_GET['top_margin'] : null;
    $verticalGap = isset($_GET['vertical_gap']) ? (int)$_GET['vertical_gap'] : null;
    $horizontalGap = isset($_GET['horizontal_gap']) ? (int)$_GET['horizontal_gap'] : null;
    $leftMargin = isset($_GET['left_margin']) && $_GET['left_margin'] !== '' ? (int)$_GET['left_margin'] : null;
    
    // If not in GET, try to get from saved preferences
    if ($topMargin === null || $verticalGap === null || $horizontalGap === null || $leftMargin === null) {
        $savedPrefs = $db->querySingle(
            "SELECT top_margin, vertical_gap, horizontal_gap, left_margin FROM name_tag_qr_surround_preferences 
             WHERE card_id = ? AND user_id = ?",
            [$cardId, $userId]
        );
        if ($savedPrefs) {
            $topMargin = $topMargin ?? (int)$savedPrefs['top_margin'];
            $verticalGap = $verticalGap ?? (int)$savedPrefs['vertical_gap'];
            $horizontalGap = $horizontalGap ?? (int)$savedPrefs['horizontal_gap'];
            $leftMargin = $leftMargin ?? ($savedPrefs['left_margin'] !== null ? (int)$savedPrefs['left_margin'] : null);
        }
    }
    
    // Use defaults if still null
    $topMargin = $topMargin ?? 45;
    $verticalGap = $verticalGap ?? 13;
    $horizontalGap = $horizontalGap ?? 30;
    $leftMargin = $leftMargin ?? 45; // Default left margin is 45, not auto-center
    
    // Build preferences array
    $preferences = [
        'top_banner_text' => $topBannerText,
        'top_banner_color' => $topBannerColor,
        'top_banner_font_family' => $topBannerFontFamily,
        'top_banner_font_size' => $topBannerFontSize,
        'bottom_banner_text' => $bottomBannerText,
        'bottom_banner_color' => $bottomBannerColor,
        'bottom_banner_font_family' => $bottomBannerFontFamily,
        'bottom_banner_font_size' => $bottomBannerFontSize,
        'top_margin' => $topMargin,
        'vertical_gap' => $verticalGap,
        'horizontal_gap' => $horizontalGap,
        'left_margin' => $leftMargin
    ];
    
    // Debug: log what we're sending
    error_log("Download PDF - Font preferences: top_family='$topBannerFontFamily', top_size=$topBannerFontSize, bottom_family='$bottomBannerFontFamily', bottom_size=$bottomBannerFontSize");
    
    // Generate PDF
    $generator = new NameTagGenerator();
    $pdf = $generator->generateQRSurroundNameTagSheet($cardId, $preferences);
    
    if (!$pdf) {
        throw new Exception('Failed to generate PDF');
    }
    
    // Generate filename
    $filename = 'NameTags_HelloMyNameIs.pdf';
    
    // Log image creation
    $imageFilename = "name-tag-qr-surround-sheet-{$cardId}.pdf";
    $imagePath = "/tmp/{$imageFilename}"; // PDF is streamed, not saved
    $dimensions = "612x792"; // Standard PDF page size
    
    logImageCreation(
        $imageFilename,
        $imagePath,
        'name_tag_qr_surround',
        'generated',
        null, // File size not available for streamed content
        $dimensions
    );
    
    // Clear any remaining output
    ob_clean();
    
    // Output PDF for download
    $pdf->Output($filename, 'D'); // 'D' = download
    
    // Log generation for analytics
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $userId,
        'card_id' => $cardId,
        'name' => $name,
        'preferences' => $preferences
    ];
    
    $logFile = __DIR__ . '/../../storage/name-tags-qr-surround.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    
} catch (Exception $e) {
    ob_clean();
    error_log('Name tag QR surround PDF generation failed: ' . $e->getMessage());
    http_response_code(500);
    die('Failed to generate PDF: ' . $e->getMessage());
}
?>

