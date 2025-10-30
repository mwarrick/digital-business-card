<?php
/**
 * Lead Capture API Endpoint
 * Handles lead capture from public forms
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/RateLimiter.php';

header('Content-Type: application/json');

// Rate limiting: 10 submissions per minute per IP
$rateLimiter = new RateLimiter();
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!$rateLimiter->isAllowed($clientIp, 10, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many submissions. Please try again later.']);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get and validate input
$data = $_POST;

// Basic validation
if (empty($data['first_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'First name is required']);
    exit;
}

if (empty($data['last_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Last name is required']);
    exit;
}

if (empty($data['email_primary'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Handle business card leads
    if (!empty($data['business_card_id'])) {
        // Verify business card exists and is active
        $stmt = $db->prepare("
            SELECT bc.id, bc.user_id, bc.first_name, bc.last_name, u.email as owner_email
            FROM business_cards bc
            JOIN users u ON bc.user_id = u.id
            WHERE bc.id = ? AND bc.is_active = 1
        ");
        $stmt->execute([$data['business_card_id']]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$card) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Business card not found']);
            exit;
        }
        
        // Insert lead using existing table structure
        $stmt = $db->prepare("
            INSERT INTO leads (
                id_business_card, id_user, first_name, last_name, full_name,
                work_phone, mobile_phone, email_primary, street_address, city, state, 
                zip_code, country, organization_name, job_title, birthdate, 
                website_url, photo_url, comments_from_lead, ip_address, user_agent, referrer
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $fullName = trim($data['first_name'] . ' ' . $data['last_name']);
        
        $insertData = [
            $data['business_card_id'],
            $card['user_id'],
            $data['first_name'],
            $data['last_name'],
            $fullName,
            $data['work_phone'] ?? null,
            $data['mobile_phone'] ?? null,
            $data['email_primary'],
            $data['street_address'] ?? null,
            $data['city'] ?? null,
            $data['state'] ?? null,
            $data['zip_code'] ?? null,
            $data['country'] ?? null,
            $data['organization_name'] ?? null,
            $data['job_title'] ?? null,
            $data['birthdate'] ?? null,
            $data['website_url'] ?? null,
            $data['photo_url'] ?? null,
            $data['comments_from_lead'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $_SERVER['HTTP_REFERER'] ?? null
        ];
        
        $result = $stmt->execute($insertData);
        $leadId = $db->lastInsertId();
        
        if ($result) {
            // Send confirmation email to the lead (skip for demo accounts)
            if ($card['owner_email'] !== 'demo@sharemycard.app') {
                try {
                    sendLeadConfirmationEmail($data, $card);
                } catch (Exception $emailError) {
                    // Don't fail the lead capture if email fails
                }
            }
        }
    }
    
    // Handle custom QR code leads
    if (!empty($data['qr_id'])) {
        // Verify QR code exists and is active
        $stmt = $db->prepare("
            SELECT cqr.id, cqr.user_id, cqr.title, u.email as owner_email
            FROM custom_qr_codes cqr
            JOIN users u ON cqr.user_id = u.id
            WHERE cqr.id = ? AND cqr.status = 'active'
        ");
        $stmt->execute([$data['qr_id']]);
        $qr = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$qr) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'QR code not found']);
            exit;
        }
        
        // Detect if new leads.id_custom_qr_code exists, else fall back
        $hasIdCustomQr = false;
        $hasQrId = false;
        try {
            $col = $db->query("SHOW COLUMNS FROM leads LIKE 'id_custom_qr_code'");
            $hasIdCustomQr = $col && $col->rowCount() > 0;
        } catch (Throwable $e) { $hasIdCustomQr = false; }
        if (!$hasIdCustomQr) {
            try {
                $col = $db->query("SHOW COLUMNS FROM leads LIKE 'qr_id'");
                $hasQrId = $col && $col->rowCount() > 0;
            } catch (Throwable $e) { $hasQrId = false; }
        }
        
        $fullName = trim($data['first_name'] . ' ' . $data['last_name']);
        
        if ($hasIdCustomQr) {
            $stmt = $db->prepare("
                INSERT INTO leads (
                    id_business_card, id_custom_qr_code, id_user, first_name, last_name, full_name,
                    work_phone, mobile_phone, email_primary, street_address, city, state, 
                    zip_code, country, organization_name, job_title, birthdate, 
                    website_url, photo_url, comments_from_lead, ip_address, user_agent, referrer
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertData = [
                null,
                $data['qr_id'],
                $qr['user_id'],
                $data['first_name'],
                $data['last_name'],
                $fullName,
                $data['work_phone'] ?? null,
                $data['mobile_phone'] ?? null,
                $data['email_primary'],
                $data['street_address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? null,
                $data['zip_code'] ?? null,
                $data['country'] ?? null,
                $data['organization_name'] ?? null,
                $data['job_title'] ?? null,
                $data['birthdate'] ?? null,
                $data['website_url'] ?? null,
                $data['photo_url'] ?? null,
                $data['comments_from_lead'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $_SERVER['HTTP_REFERER'] ?? null
            ];
        } elseif ($hasQrId) {
            $stmt = $db->prepare("
                INSERT INTO leads (
                    id_business_card, qr_id, id_user, first_name, last_name, full_name,
                    work_phone, mobile_phone, email_primary, street_address, city, state, 
                    zip_code, country, organization_name, job_title, birthdate, 
                    website_url, photo_url, comments_from_lead, ip_address, user_agent, referrer
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertData = [
                null, // id_business_card
                $data['qr_id'],
                $qr['user_id'],
                $data['first_name'],
                $data['last_name'],
                $fullName,
                $data['work_phone'] ?? null,
                $data['mobile_phone'] ?? null,
                $data['email_primary'],
                $data['street_address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? null,
                $data['zip_code'] ?? null,
                $data['country'] ?? null,
                $data['organization_name'] ?? null,
                $data['job_title'] ?? null,
                $data['birthdate'] ?? null,
                $data['website_url'] ?? null,
                $data['photo_url'] ?? null,
                $data['comments_from_lead'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $_SERVER['HTTP_REFERER'] ?? null
            ];
        } else {
            // Insert without qr_id column (older schema)
            $stmt = $db->prepare("
                INSERT INTO leads (
                    id_business_card, id_user, first_name, last_name, full_name,
                    work_phone, mobile_phone, email_primary, street_address, city, state, 
                    zip_code, country, organization_name, job_title, birthdate, 
                    website_url, photo_url, comments_from_lead, ip_address, user_agent, referrer
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertData = [
                null, // id_business_card
                $qr['user_id'],
                $data['first_name'],
                $data['last_name'],
                $fullName,
                $data['work_phone'] ?? null,
                $data['mobile_phone'] ?? null,
                $data['email_primary'],
                $data['street_address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? null,
                $data['zip_code'] ?? null,
                $data['country'] ?? null,
                $data['organization_name'] ?? null,
                $data['job_title'] ?? null,
                $data['birthdate'] ?? null,
                $data['website_url'] ?? null,
                $data['photo_url'] ?? null,
                $data['comments_from_lead'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $_SERVER['HTTP_REFERER'] ?? null
            ];
        }

        $result = $stmt->execute($insertData);
        $leadId = $db->lastInsertId();
        
        if ($result) {
            // Also insert into qr_leads mapping table
            $stmt = $db->prepare("INSERT INTO qr_leads (qr_id, lead_id) VALUES (?, ?)");
            $stmt->execute([$data['qr_id'], $leadId]);

            // Send confirmation email to the lead (skip for demo accounts)
            if (($qr['owner_email'] ?? '') !== 'demo@sharemycard.app') {
                try {
                    sendQrLeadConfirmationEmail($data, $qr);
                } catch (Exception $emailError) {
                    // Do not fail lead capture on email issues
                }
            }
        }
    }
    
    if (!empty($data['business_card_id']) || !empty($data['qr_id'])) {
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for your interest! We\'ll be in touch soon.',
            'lead_id' => $leadId
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Either business card ID or QR ID is required']);
    }
    
} catch (Exception $e) {
    error_log("Lead capture error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}

/**
 * Send confirmation email to lead
 */
function sendLeadConfirmationEmail($leadData, $cardData) {
    require_once __DIR__ . '/../includes/EmailService.php';
    
    $emailService = new EmailService();
    
    // Prepare email data
    $emailData = [
        'to_email' => $leadData['email_primary'],
        'to_name' => trim($leadData['first_name'] . ' ' . $leadData['last_name']),
        'from_email' => $cardData['owner_email'],
        'from_name' => trim($cardData['first_name'] . ' ' . $cardData['last_name']),
        'subject' => 'Thanks for connecting with ' . trim($cardData['first_name'] . ' ' . $cardData['last_name']),
        'card_id' => $cardData['id'],
        'card_url' => 'https://sharemycard.app/card.php?id=' . $cardData['id'],
        'personal_message' => '',
        'email_type' => 'lead_confirmation'
    ];
    
    // Send the email
    $result = $emailService->sendLeadConfirmationEmail($emailData);
    
    if (!$result) {
        throw new Exception('Failed to send confirmation email');
    }
    
    return true;
}

/**
 * Send confirmation email for QR-origin leads
 */
function sendQrLeadConfirmationEmail($leadData, $qrData) {
    require_once __DIR__ . '/../includes/EmailService.php';
    $emailService = new EmailService();

    $emailData = [
        'to_email' => $leadData['email_primary'],
        'to_name'  => trim(($leadData['first_name'] ?? '') . ' ' . ($leadData['last_name'] ?? '')),
        'qr_title' => $qrData['title'] ?? null
    ];

    $result = $emailService->sendLeadConfirmationQr($emailData);
    if (!$result) {
        throw new Exception('Failed to send confirmation email');
    }
    return true;
}
?>