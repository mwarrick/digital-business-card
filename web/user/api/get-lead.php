<?php
/**
 * Get Lead Details - Server-side authenticated endpoint
 * Returns detailed information for a specific lead
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/UserAuth.php';

header('Content-Type: application/json');

// Check if user is logged in using UserAuth
if (!UserAuth::isLoggedIn()) {
    error_log("Get lead API - User not logged in");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please log in']);
    exit;
}

$userId = UserAuth::getUserId();
$leadId = $_GET['id'] ?? null;

if (!$leadId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Lead ID required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get lead details with business card information
    $stmt = $db->prepare("
        SELECT l.*, 
               bc.first_name as card_first_name, bc.last_name as card_last_name,
               bc.company_name as card_company, bc.job_title as card_job_title,
               bc.phone_number as card_phone, bc.bio as card_bio,
               CASE WHEN EXISTS (SELECT 1 FROM contacts c WHERE c.id_lead = l.id) 
                    THEN 'converted' ELSE 'new' END as status
        FROM leads l
        JOIN business_cards bc ON l.id_business_card = bc.id
        WHERE l.id = ? AND bc.user_id = ?
    ");
    $stmt->execute([$leadId, $userId]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lead) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Lead not found']);
        exit;
    }
    
    // Check if lead has been converted to contact
    $stmt = $db->prepare("
        SELECT c.id, c.first_name, c.last_name, c.email_primary, c.created_at
        FROM contacts c
        WHERE c.id_lead = ?
    ");
    $stmt->execute([$leadId]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contact) {
        $lead['converted_contact'] = $contact;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $lead
    ]);
    
} catch (Exception $e) {
    error_log("Get lead details error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>
