<?php
/**
 * Delete Lead - Server-side authenticated endpoint
 * Permanently deletes a lead and any associated data
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/UserAuth.php';

header('Content-Type: application/json');

// Check if user is logged in using UserAuth
if (!UserAuth::isLoggedIn()) {
    error_log("Delete lead API - User not logged in");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please log in']);
    exit;
}

$userId = UserAuth::getUserId();
$leadId = $_POST['lead_id'] ?? null;

if (!$leadId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Lead ID required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    error_log("Delete lead - Starting deletion for lead ID: " . $leadId . ", User ID: " . $userId);
    
    // Start transaction
    $db->beginTransaction();
    
    // Get lead details and verify ownership
    $stmt = $db->prepare("
        SELECT l.*, bc.user_id as card_owner_id
        FROM leads l
        JOIN business_cards bc ON l.id_business_card = bc.id
        WHERE l.id = ? AND bc.user_id = ?
    ");
    $stmt->execute([$leadId, $userId]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Delete lead - Lead lookup result: " . print_r($lead, true));
    
    if (!$lead) {
        $db->rollBack();
        error_log("Delete lead - Lead not found or access denied for lead ID: " . $leadId . ", User ID: " . $userId);
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Lead not found or access denied']);
        exit;
    }
    
    // Check if lead has been converted to contact
    $stmt = $db->prepare("SELECT id, first_name, last_name FROM contacts WHERE id_lead = ?");
    $stmt->execute([$leadId]);
    $existingContact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingContact) {
        $db->rollBack();
        error_log("Delete lead - Cannot delete lead that has been converted to contact: " . $leadId);
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot delete lead that has been converted to contact. Contact: ' . $existingContact['first_name'] . ' ' . $existingContact['last_name']
        ]);
        exit;
    }
    
    // Delete the lead
    $stmt = $db->prepare("DELETE FROM leads WHERE id = ?");
    $result = $stmt->execute([$leadId]);
    
    if (!$result) {
        $db->rollBack();
        throw new Exception('Failed to delete lead');
    }
    
    $deletedRows = $stmt->rowCount();
    
    if ($deletedRows === 0) {
        $db->rollBack();
        error_log("Delete lead - No rows affected when deleting lead: " . $leadId);
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Lead not found or already deleted']);
        exit;
    }
    
    // Commit transaction
    $db->commit();
    
    error_log("Delete lead - Successfully deleted lead: " . $leadId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Lead deleted successfully',
        'lead_name' => $lead['first_name'] . ' ' . $lead['last_name']
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Delete lead error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the lead. Please try again.']);
}
?>
