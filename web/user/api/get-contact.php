<?php
/**
 * Get Contact Details - Server-side authenticated endpoint
 * Returns detailed information for a specific contact
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/UserAuth.php';

header('Content-Type: application/json');

// Check if user is logged in using UserAuth
if (!UserAuth::isLoggedIn()) {
    error_log("Get contact API - User not logged in");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please log in']);
    exit;
}

$userId = UserAuth::getUserId();
$contactId = $_GET['id'] ?? null;

if (!$contactId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Contact ID required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get contact details with lead and business card information
    $stmt = $db->prepare("
        SELECT c.*, 
               l.id as lead_id, l.created_at as lead_created_at,
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
        'data' => $contact
    ]);
    
} catch (Exception $e) {
    error_log("Get contact details error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>
