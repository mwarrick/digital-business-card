<?php
/**
 * Export Contact as VCF (vCard 3.0)
 * Requires authenticated user and contact ownership
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/UserAuth.php';

UserAuth::requireAuth();

header('Content-Type: text/vcard; charset=utf-8');

$contactId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($contactId <= 0) {
    http_response_code(400);
    echo "Invalid contact id";
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    // Verify contact belongs to the authenticated user and fetch details
    $stmt = $db->prepare("SELECT * FROM contacts WHERE id = ? AND id_user = ?");
    $stmt->execute([$contactId, UserAuth::getUserId()]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contact) {
        http_response_code(404);
        echo "Contact not found";
        exit;
    }

    // Helper to safely get field
    $get = function($key) use ($contact) {
        return isset($contact[$key]) ? trim((string)$contact[$key]) : '';
    };

    $first = $get('first_name');
    $last = $get('last_name');
    $fullName = trim($first . ' ' . $last);

    $org = $get('organization_name');
    $title = $get('job_title');
    $email = $get('email_primary');
    $workPhone = $get('work_phone');
    $mobilePhone = $get('mobile_phone');

    $street = $get('street_address');
    $city = $get('city');
    $state = $get('state');
    $zip = $get('zip_code');
    $country = $get('country');

    $website = $get('website_url');
    $notes = $get('notes');
    $comments = $get('comments_from_lead');
    $bday = $get('birthdate'); // expected YYYY-MM-DD

    // Build vCard 3.0 with CRLF line endings
    $lines = [];
    $lines[] = 'BEGIN:VCARD';
    $lines[] = 'VERSION:3.0';
    $lines[] = 'PRODID:-//ShareMyCard//Contacts Export//EN';

    // Names
    $lines[] = 'N:' . vcfEscape($last) . ';' . vcfEscape($first) . ';;;';
    $lines[] = 'FN:' . vcfEscape($fullName ?: ($email ?: 'Contact'));

    if ($org !== '') $lines[] = 'ORG:' . vcfEscape($org);
    if ($title !== '') $lines[] = 'TITLE:' . vcfEscape($title);

    if ($email !== '') $lines[] = 'EMAIL;TYPE=INTERNET,WORK:' . vcfEscape($email);
    if ($workPhone !== '') $lines[] = 'TEL;TYPE=WORK,VOICE:' . vcfEscape($workPhone);
    if ($mobilePhone !== '') $lines[] = 'TEL;TYPE=CELL,VOICE:' . vcfEscape($mobilePhone);

    // Address (PO Box and Extended fields left empty)
    if ($street !== '' || $city !== '' || $state !== '' || $zip !== '' || $country !== '') {
        $adr = 'ADR;TYPE=WORK:;;' . vcfEscape($street) . ';' . vcfEscape($city) . ';' . vcfEscape($state) . ';' . vcfEscape($zip) . ';' . vcfEscape($country);
        $lines[] = $adr;
    }

    if ($website !== '') $lines[] = 'URL:' . vcfEscape($website);

    // Birthday (YYYY-MM-DD)
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $bday)) {
        $lines[] = 'BDAY:' . $bday;
    }

    // Notes
    $noteParts = [];
    if ($notes !== '') $noteParts[] = $notes;
    if ($comments !== '') $noteParts[] = 'Comments: ' . $comments;
    if (!empty($noteParts)) {
        $lines[] = 'NOTE:' . vcfEscape(implode("\\n\\n", $noteParts));
    }

    $lines[] = 'END:VCARD';

    $content = implode("\r\n", $lines) . "\r\n";

    // Filename
    $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $fullName !== '' ? $fullName : ('contact-' . $contactId));
    $filename = $safeName . '.vcf';

    // Headers to force download
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));

    echo $content;
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo 'Error generating VCF';
}

// Escape function for vCard
function vcfEscape($value) {
    // Escape commas, semicolons, and line breaks
    $value = str_replace(["\\", ",", ";", "\n", "\r"], ["\\\\", "\\,", "\\;", "\\n", ""], $value);
    return $value;
}
