<?php
/**
 * Get Lead Details API
 * Returns detailed information for a specific lead
 */

// Enable error reporting and output buffering
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../includes/AuthHelper.php';

    header('Content-Type: application/json');
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Configuration error: ' . $e->getMessage()]);
    exit;
}

// Check authentication
if (!AuthHelper::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = AuthHelper::getUserId();
$leadId = $_GET['id'] ?? null;

if (!$leadId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Lead ID required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Debug logging
    error_log("Get lead details - Lead ID: " . $leadId . ", User ID: " . $userId);
    
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
    error_log("Get lead details error trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
} catch (Error $e) {
    error_log("Get lead details fatal error: " . $e->getMessage());
    error_log("Get lead details fatal error trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A fatal error occurred. Please try again.']);
} catch (Throwable $e) {
    error_log("Get lead details throwable error: " . $e->getMessage());
    error_log("Get lead details throwable error trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again.']);
}

// Clean any output buffer and ensure JSON response
ob_clean();
?>
