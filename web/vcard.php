<?php
/**
 * Public vCard Download Endpoint
 * Generates a .vcf file for direct import - no authentication required
 */

require_once __DIR__ . '/api/includes/Database.php';
require_once __DIR__ . '/api/includes/Analytics.php';

$cardId = $_GET['id'] ?? '';
$src = $_GET['src'] ?? '';

if (empty($cardId)) {
    header('Location: /');
    exit;
}

$db = Database::getInstance();

// Get card (public - no user_id check)
$card = $db->querySingle(
    "SELECT * FROM business_cards WHERE id = ? AND is_active = 1",
    [$cardId]
);

if (!$card) {
    header('Location: /?error=card_not_found');
    exit;
}

// Get additional contact info
$emails = $db->query(
    "SELECT * FROM email_contacts WHERE business_card_id = ? ORDER BY created_at",
    [$cardId]
);

$phones = $db->query(
    "SELECT * FROM phone_contacts WHERE business_card_id = ? ORDER BY created_at",
    [$cardId]
);

$websites = $db->query(
    "SELECT * FROM website_links WHERE business_card_id = ? ORDER BY created_at",
    [$cardId]
);

$address = $db->querySingle(
    "SELECT * FROM addresses WHERE business_card_id = ?",
    [$cardId]
);

// Generate vCard data (Version 3.0 for better Android compatibility)
$vcard = "BEGIN:VCARD\r\n";
$vcard .= "VERSION:3.0\r\n";
$vcard .= "FN:" . $card['first_name'] . " " . $card['last_name'] . "\r\n";
$vcard .= "N:" . $card['last_name'] . ";" . $card['first_name'] . ";;;\r\n";

// Primary phone
if (!empty($card['phone_number'])) {
    $vcard .= "TEL;TYPE=CELL:" . $card['phone_number'] . "\r\n";
}

// Additional emails
foreach ($emails as $email) {
    $type = strtoupper($email['type']);
    if ($type === 'PERSONAL') $type = 'HOME';
    if ($type === 'WORK') $type = 'WORK';
    if ($type === 'OTHER') $type = 'INTERNET';
    $vcard .= "EMAIL;TYPE=" . $type . ":" . $email['email'] . "\r\n";
}

// Additional phones
foreach ($phones as $phone) {
    $type = strtoupper($phone['type']);
    if ($type === 'MOBILE') $type = 'CELL';
    $vcard .= "TEL;TYPE=" . $type . ":" . $phone['phone_number'] . "\r\n";
}

// Website links
foreach ($websites as $website) {
    $vcard .= "URL:" . $website['url'] . "\r\n";
}

// Address
if ($address) {
    // ADR format: ;;street;city;state;zip;country
    $vcard .= "ADR;TYPE=WORK:;;" . 
              ($address['street'] ?? '') . ";" . 
              ($address['city'] ?? '') . ";" . 
              ($address['state'] ?? '') . ";" . 
              ($address['zip_code'] ?? '') . ";" . 
              ($address['country'] ?? '') . "\r\n";
}

// Job title
if (!empty($card['job_title'])) {
    $vcard .= "TITLE:" . $card['job_title'] . "\r\n";
}

// Company name
if (!empty($card['company_name'])) {
    $vcard .= "ORG:" . $card['company_name'] . "\r\n";
}

// Bio as note
if (!empty($card['bio'])) {
    $vcard .= "NOTE:" . str_replace("\n", "\\n", $card['bio']) . "\r\n";
}

// Profile Photo URL (if available)
if (!empty($card['profile_photo_path'])) {
    // vCard 3.0 format - TYPE parameter for better compatibility
    $photoURL = "https://sharemycard.app/api/media/view?filename=" . $card['profile_photo_path'];
    $vcard .= "PHOTO;TYPE=JPEG:" . $photoURL . "\r\n";
}

$vcard .= "END:VCARD\r\n";

// Track vCard download and optional QR scans
try {
    $analytics = new Analytics($db->getConnection());
    if ($src === 'qr' || $src === 'qr-app') {
        $analytics->trackEvent($cardId, 'qr_scan', 'vcard_qr', $_SERVER);
    }
    $analytics->trackEvent($cardId, 'download', 'vcard', $_SERVER);
} catch (Exception $e) {
    // Silently fail - don't interrupt download
    error_log("Analytics tracking failed: " . $e->getMessage());
}

// Set headers for vCard download
header('Content-Type: text/vcard');
header('Content-Disposition: attachment; filename="' . urlencode($card['first_name'] . ' ' . $card['last_name']) . '.vcf"');
header('Content-Length: ' . strlen($vcard));

echo $vcard;
exit;
?>

