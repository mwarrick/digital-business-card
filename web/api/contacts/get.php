<?php
/**
 * Get Single Contact by ID - API endpoint
 * Returns a single contact for the authenticated user
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
$contactId = $_GET['id'] ?? null;

if (!$contactId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Contact ID required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get contact details
    $stmt = $db->prepare("
        SELECT c.*, l.id as lead_id, l.created_at as lead_created_at,
               bc.first_name as card_first_name, bc.last_name as card_last_name,
               bc.company_name as card_company, bc.job_title as card_job_title,
               bc.phone_number as card_phone, bc.bio as card_bio,
               CASE WHEN c.id_lead IS NOT NULL THEN 'converted' ELSE 'manual' END as source_type
        FROM contacts c
        LEFT JOIN leads l ON c.id_lead = l.id
        LEFT JOIN business_cards bc ON l.id_business_card = bc.id
        WHERE c.id = ? AND c.id_user = ?
    ");
    $stmt->execute([$contactId, $userId]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contact) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Contact not found or access denied']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Contact retrieved successfully',
        'data' => $contact
    ]);
    
} catch (Exception $e) {
    error_log("Get contact API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>
