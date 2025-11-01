<?php
/**
 * Name Tag PDF Download
 * Generates and downloads PDF with 8 name tags per sheet
 * Session-based authentication for web downloads
 */

require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../api/includes/NameTagGenerator.php';
require_once __DIR__ . '/../../api/includes/log-image-creation.php';

UserAuth::requireAuth();

$db = Database::getInstance();

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

// Validate parameters
if (empty($cardId)) {
    die('Card ID is required');
}

if (!in_array($fontFamily, ['helvetica', 'times', 'courier'])) {
    die('Invalid font family option');
}

if (!in_array($fontSize, ['8', '9', '10', '11', '12', '13', '14', '15', '16', '18', '20'])) {
    die('Invalid font size option');
}

if (!in_array($lineSpacing, ['-2', '-1.5', '-1', '-0.5', '0', '0.5', '1', '1.5', '2'])) {
    die('Invalid line spacing option');
}

// Verify card ownership
$card = $db->querySingle(
    "SELECT id, first_name, last_name, company_name, job_title FROM business_cards 
     WHERE id = ? AND user_id = ? AND is_active = 1",
    [$cardId, UserAuth::getUserId()]
);

if (!$card) {
    die('Card not found or access denied');
}

// Rate limiting disabled to prevent issues for legitimate iOS users
// $rateLimitKey = "name_tag_generation_" . UserAuth::getUserId();
// $rateLimitFile = __DIR__ . '/../../storage/rate-limits/' . md5($rateLimitKey) . '.json';
// 
// $rateLimitData = [];
// if (file_exists($rateLimitFile)) {
//     $rateLimitData = json_decode(file_get_contents($rateLimitFile), true) ?? [];
// }
// 
// $currentTime = time();
// $hourAgo = $currentTime - 3600;
// 
// // Clean old entries
// $rateLimitData = array_filter($rateLimitData, function($timestamp) use ($hourAgo) {
//     return $timestamp > $hourAgo;
// });
// 
// if (count($rateLimitData) >= 20) {
//     die('Rate limit exceeded. Maximum 20 generations per hour.');
// }
// 
// // Add current request
// $rateLimitData[] = $currentTime;
// 
// // Ensure directory exists
// $rateLimitDir = dirname($rateLimitFile);
// if (!is_dir($rateLimitDir)) {
//     mkdir($rateLimitDir, 0755, true);
// }
// 
// file_put_contents($rateLimitFile, json_encode($rateLimitData));

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
    
    // Generate PDF
    $generator = new NameTagGenerator();
    $pdf = $generator->generateNameTagSheet($cardId, $preferences);
    
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
    
    $filename = str_replace(' ', '_', implode('_', $filenameParts)) . '_NameTags.pdf';
    
    // Log image creation
    $imageFilename = "name-tag-sheet-{$cardId}.pdf";
    $imagePath = "/tmp/{$imageFilename}"; // PDF is streamed, not saved
    $dimensions = "612x792"; // Standard PDF page size
    
    logImageCreation(
        $imageFilename,
        $imagePath,
        'name_tag',
        'generated',
        null, // File size not available for streamed content
        $dimensions
    );
    
    // Output PDF for download
    $pdf->Output($filename, 'D'); // 'D' = download
    
    // Log generation for analytics
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => UserAuth::getUserId(),
        'card_id' => $cardId,
        'preferences' => $preferences
    ];
    
    $logFile = __DIR__ . '/../../storage/name-tags.log';
    file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    
} catch (Exception $e) {
    error_log('Name tag PDF generation failed: ' . $e->getMessage());
    die('Failed to generate PDF: ' . $e->getMessage());
}
?>

