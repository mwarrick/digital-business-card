<?php
/**
 * Lead Capture API Endpoint
 * Handles lead capture from public forms
 */

// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Log the request
error_log("Lead capture API called - Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Lead capture API - POST data: " . print_r($_POST, true));
error_log("Lead capture API - Raw input: " . file_get_contents('php://input'));

require_once __DIR__ . '/../includes/Database.php';

header('Content-Type: application/json');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Lead capture API - Invalid method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get and validate input
$data = $_POST;
error_log("Lead capture API - Processed data: " . print_r($data, true));

// Basic validation
if (empty($data['business_card_id'])) {
    error_log("Lead capture API - Missing business_card_id");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Business card ID is required']);
    exit;
}

if (empty($data['first_name'])) {
    error_log("Lead capture API - Missing first_name");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'First name is required']);
    exit;
}

if (empty($data['last_name'])) {
    error_log("Lead capture API - Missing last_name");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Last name is required']);
    exit;
}

if (empty($data['email_primary'])) {
    error_log("Lead capture API - Missing email_primary");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

try {
    error_log("Lead capture API - Attempting database connection");
    $db = Database::getInstance()->getConnection();
    error_log("Lead capture API - Database connection successful");
    
    // Verify business card exists and is active, get owner details
    error_log("Lead capture API - Looking up business card: " . $data['business_card_id']);
    $stmt = $db->prepare("
        SELECT bc.id, bc.user_id, bc.first_name, bc.last_name, u.email as owner_email
        FROM business_cards bc
        JOIN users u ON bc.user_id = u.id
        WHERE bc.id = ? AND bc.is_active = 1
    ");
    $stmt->execute([$data['business_card_id']]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Lead capture API - Business card lookup result: " . print_r($card, true));
    
    if (!$card) {
        error_log("Lead capture API - Business card not found");
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Business card not found']);
        exit;
    }
    
    // Insert lead using existing table structure with ALL fields (exact match)
    error_log("Lead capture API - Preparing insert statement");
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
        $card['user_id'], // Get from business card lookup
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
    
    error_log("Lead capture API - Insert data: " . print_r($insertData, true));
    
    $result = $stmt->execute($insertData);
    error_log("Lead capture API - Insert result: " . ($result ? 'success' : 'failed'));
    
    if ($result) {
        error_log("Lead capture API - Lead saved successfully");
        
        // Skip confirmation email for demo accounts
        if ($card['owner_email'] !== 'demo@sharemycard.app') {
            // Send confirmation email to the lead
            try {
                sendLeadConfirmationEmail($data, $card);
                error_log("Lead capture API - Confirmation email sent successfully");
            } catch (Exception $emailError) {
                error_log("Lead capture API - Email sending failed: " . $emailError->getMessage());
                // Don't fail the lead capture if email fails
            }
        } else {
            error_log("Lead capture API - Skipping confirmation email for demo account");
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for your interest! We\'ll be in touch soon.',
            'lead_id' => $db->lastInsertId()
        ]);
    } else {
        error_log("Lead capture API - Failed to save lead");
        throw new Exception('Failed to save lead');
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
        'personal_message' => '', // No personal message for lead confirmations
        'email_type' => 'lead_confirmation'
    ];
    
    // Send the email
    $result = $emailService->sendLeadConfirmationEmail($emailData);
    
    if (!$result) {
        throw new Exception('Failed to send confirmation email');
    }
    
    return true;
}
?>
