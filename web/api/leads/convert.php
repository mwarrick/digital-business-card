<?php
/**
 * Lead to Contact Conversion API
 * Converts a lead into a contact
 */

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/AuthHelper.php';

header('Content-Type: application/json');

// Check authentication
if (!AuthHelper::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = AuthHelper::getUserId();
$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$leadId = $data['lead_id'] ?? null;

if (!$leadId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Lead ID required']);
    exit;
}

try {
    $db->beginTransaction();
    
    // Debug logging
    error_log("Lead convert: Attempting to convert lead ID: $leadId for user ID: $userId");
    
    // First, check if lead exists and get basic info
    $checkStmt = $db->prepare("SELECT l.*, bc.user_id as card_user_id FROM leads l LEFT JOIN business_cards bc ON l.id_business_card = bc.id WHERE l.id = ?");
    $checkStmt->execute([$leadId]);
    $leadCheck = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$leadCheck) {
        error_log("Lead convert: Lead ID $leadId does not exist in database");
        throw new Exception('Lead not found');
    }
    
    error_log("Lead convert: Lead found - id_user: " . ($leadCheck['id_user'] ?? 'NULL') . ", card_user_id: " . ($leadCheck['card_user_id'] ?? 'NULL') . ", is_deleted: " . ($leadCheck['is_deleted'] ?? 'NULL'));
    
    // Check if lead is deleted
    if (isset($leadCheck['is_deleted']) && $leadCheck['is_deleted'] == 1) {
        error_log("Lead convert: Lead ID $leadId is soft-deleted");
        throw new Exception('Lead not found');
    }
    
    // Check if already converted (checking for non-deleted contacts)
    $contactCheckStmt = $db->prepare("SELECT id FROM contacts WHERE id_lead = ? AND (is_deleted = 0 OR is_deleted IS NULL)");
    $contactCheckStmt->execute([$leadId]);
    $existingContact = $contactCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingContact) {
        error_log("Lead convert: Lead ID $leadId is already converted to contact ID: " . $existingContact['id']);
        throw new Exception('Lead already converted');
    }
    
    // Verify lead belongs to user (check if not already converted)
    // Use LEFT JOIN to handle leads from both business cards and custom QR codes
    try {
        $stmt = $db->prepare("
            SELECT l.*, bc.id as business_card_id
            FROM leads l
            LEFT JOIN business_cards bc ON l.id_business_card = bc.id
            WHERE l.id = ? 
            AND l.is_deleted = 0
            AND (bc.user_id = ? OR l.id_user = ?)
            AND NOT EXISTS (SELECT 1 FROM contacts c WHERE c.id_lead = l.id AND (c.is_deleted = 0 OR c.is_deleted IS NULL))
        ");
        $stmt->execute([$leadId, $userId, $userId]);
    } catch (PDOException $e) {
        // Fallback if id_user column doesn't exist
        error_log("Lead convert: Query with id_user failed, trying business cards only: " . $e->getMessage());
        $stmt = $db->prepare("
            SELECT l.*, bc.id as business_card_id
            FROM leads l
            INNER JOIN business_cards bc ON l.id_business_card = bc.id
            WHERE l.id = ? 
            AND l.is_deleted = 0
            AND bc.user_id = ? 
            AND NOT EXISTS (SELECT 1 FROM contacts c WHERE c.id_lead = l.id AND (c.is_deleted = 0 OR c.is_deleted IS NULL))
        ");
        $stmt->execute([$leadId, $userId]);
    }
    
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lead) {
        error_log("Lead convert: Query returned no results - Lead ID: $leadId, User ID: $userId");
        error_log("Lead convert: Lead check showed - id_user: " . ($leadCheck['id_user'] ?? 'NULL') . ", card_user_id: " . ($leadCheck['card_user_id'] ?? 'NULL'));
        throw new Exception('Lead not found or already converted');
    }
    
    error_log("Lead convert: Lead found and validated - proceeding with conversion");
    
    // Create contact from lead using existing table structure with ALL fields
    // Handle NULL values properly
    $stmt = $db->prepare("
        INSERT INTO contacts (
            id_user, id_lead, first_name, last_name, full_name,
            work_phone, mobile_phone, email_primary, street_address, city, state, 
            zip_code, country, organization_name, job_title, birthdate, 
            website_url, photo_url, comments_from_lead, ip_address, user_agent, referrer
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $userId,
        $leadId,
        $lead['first_name'] ?? null,
        $lead['last_name'] ?? null,
        $lead['full_name'] ?? null,
        $lead['work_phone'] ?? null,
        $lead['mobile_phone'] ?? null,
        $lead['email_primary'] ?? null,
        $lead['street_address'] ?? null,
        $lead['city'] ?? null,
        $lead['state'] ?? null,
        $lead['zip_code'] ?? null,
        $lead['country'] ?? null,
        $lead['organization_name'] ?? null,
        $lead['job_title'] ?? null,
        $lead['birthdate'] ?? null,
        $lead['website_url'] ?? null,
        $lead['photo_url'] ?? null,
        $lead['comments_from_lead'] ?? null,
        $lead['ip_address'] ?? null,
        $lead['user_agent'] ?? null,
        $lead['referrer'] ?? null
    ]);
    
    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        error_log("Lead conversion - Failed to create contact. Error: " . json_encode($errorInfo));
        throw new Exception('Failed to create contact: ' . ($errorInfo[2] ?? 'Unknown database error'));
    }
    
    $contactId = $db->lastInsertId();
    
    // Update lead as converted (using existing fields creatively)
    $stmt = $db->prepare("
        UPDATE leads SET 
            notes = CONCAT(COALESCE(notes, ''), ' [CONVERTED TO CONTACT]'),
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$leadId]);
    
    if (!$result) {
        throw new Exception('Failed to update lead');
    }
    
    $db->commit();
    
    $response = [
        'success' => true, 
        'message' => 'Lead converted to contact successfully',
        'data' => [
            'contact_id' => $contactId
        ]
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Lead conversion PDO error: " . $e->getMessage());
    error_log("Lead conversion PDO error code: " . $e->getCode());
    error_log("Lead conversion PDO error trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Lead conversion error: " . $e->getMessage());
    error_log("Lead conversion error trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
