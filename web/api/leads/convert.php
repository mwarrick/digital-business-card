<?php
/**
 * Lead to Contact Conversion API
 * Converts a lead into a contact
 */

require_once __DIR__ . '/../../config/database.php';
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
    
    // Verify lead belongs to user (check if not already converted)
    $stmt = $db->prepare("
        SELECT l.*, bc.id as business_card_id
        FROM leads l
        JOIN business_cards bc ON l.id_business_card = bc.id
        WHERE l.id = ? AND bc.user_id = ? 
        AND NOT EXISTS (SELECT 1 FROM contacts c WHERE c.id_lead = l.id)
    ");
    $stmt->execute([$leadId, $userId]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lead) {
        throw new Exception('Lead not found or already converted');
    }
    
    // Create contact from lead using existing table structure with ALL fields
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
        $lead['first_name'],
        $lead['last_name'],
        $lead['full_name'],
        $lead['work_phone'],
        $lead['mobile_phone'],
        $lead['email_primary'],
        $lead['street_address'],
        $lead['city'],
        $lead['state'],
        $lead['zip_code'],
        $lead['country'],
        $lead['organization_name'],
        $lead['job_title'],
        $lead['birthdate'],
        $lead['website_url'],
        $lead['photo_url'],
        $lead['comments_from_lead'],
        $lead['ip_address'],
        $lead['user_agent'],
        $lead['referrer']
    ]);
    
    if (!$result) {
        throw new Exception('Failed to create contact');
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
    
    echo json_encode([
        'success' => true, 
        'message' => 'Lead converted to contact successfully',
        'contact_id' => $contactId
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Lead conversion error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
