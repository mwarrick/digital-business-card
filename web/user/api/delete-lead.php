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
    
    // Detect QR linkage column
    $hasIdCustomQr = false; $hasQrId = false;
    try { $col = $db->query("SHOW COLUMNS FROM leads LIKE 'id_custom_qr_code'"); $hasIdCustomQr = $col && $col->rowCount() > 0; } catch (Throwable $e) { $hasIdCustomQr = false; }
    if (!$hasIdCustomQr) {
        try { $col = $db->query("SHOW COLUMNS FROM leads LIKE 'qr_id'"); $hasQrId = $col && $col->rowCount() > 0; } catch (Throwable $e) { $hasQrId = false; }
    }

    // Get lead details and verify ownership via business card or custom QR or lead owner id
    $joinQr = $hasIdCustomQr ? " LEFT JOIN custom_qr_codes cqr ON l.id_custom_qr_code = cqr.id" : ($hasQrId ? " LEFT JOIN custom_qr_codes cqr ON l.qr_id = cqr.id" : "");
    $selectQrOwner = ($hasIdCustomQr || $hasQrId) ? ", cqr.user_id AS qr_owner_id" : ", NULL AS qr_owner_id";
    $ownershipWhere = "WHERE l.id = ? AND (l.id_user = ? OR bc.user_id = ?" . (($hasIdCustomQr || $hasQrId) ? " OR cqr.user_id = ?" : "") . ")";
    $stmt = $db->prepare("
        SELECT l.*, bc.user_id AS card_owner_id $selectQrOwner
        FROM leads l
        LEFT JOIN business_cards bc ON l.id_business_card = bc.id
        $joinQr
        $ownershipWhere
    ");
    $params = [$leadId, $userId, $userId];
    if ($hasIdCustomQr || $hasQrId) { $params[] = $userId; }
    $stmt->execute($params);
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
