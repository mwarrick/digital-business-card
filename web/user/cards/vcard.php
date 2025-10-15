<?php
/**
 * vCard File Download (User)
 * Generates a .vcf file for direct import (iOS will fetch remote photos this way)
 */

require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

UserAuth::requireAuth();

$cardId = $_GET['id'] ?? '';

if (empty($cardId)) {
    header('Location: /user/dashboard.php');
    exit;
}

$db = Database::getInstance();

// Get card
$card = $db->querySingle(
    "SELECT * FROM business_cards WHERE id = ? AND user_id = ? AND is_active = 1",
    [$cardId, UserAuth::getUserId()]
);

if (!$card) {
    header('Location: /user/dashboard.php?error=not_found');
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

// Generate vCard data (Version 4.0)
$vcard = "BEGIN:VCARD\r\n";
$vcard .= "VERSION:4.0\r\n";
$vcard .= "FN:" . $card['first_name'] . " " . $card['last_name'] . "\r\n";
$vcard .= "N:" . $card['last_name'] . ";" . $card['first_name'] . ";;;\r\n";

// Primary phone
$vcard .= "TEL;TYPE=CELL:" . $card['phone_number'] . "\r\n";

// Additional emails
foreach ($emails as $email) {
    $type = strtoupper($email['type']);
    if ($type === 'PERSONAL') $type = 'HOME';
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
    // vCard 4.0 format - iOS should fetch this when importing the .vcf file
    $photoURL = "https://sharemycard.app/api/media/view?filename=" . $card['profile_photo_path'];
    $vcard .= "PHOTO;MEDIATYPE=image/jpeg:" . $photoURL . "\r\n";
}

$vcard .= "END:VCARD";

// Set headers for file download
header('Content-Type: text/vcard; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $card['first_name'] . '-' . $card['last_name'] . '.vcf"');
header('Content-Length: ' . strlen($vcard));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Output vCard
echo $vcard;
exit;

