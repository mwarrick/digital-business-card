<?php
/**
 * Delete Contact - Server-side authenticated endpoint
 * Deletes a contact and reverts the originating lead status back to "New" (if any)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/UserAuth.php';

header('Content-Type: application/json');

// Check if user is logged in using UserAuth
if (!UserAuth::isLoggedIn()) {
    error_log("Delete contact API - User not logged in");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please log in']);
    exit;
}

$userId = UserAuth::getUserId();
$contactId = $_POST['contact_id'] ?? null;

if (!$contactId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Contact ID required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    error_log("Delete contact - Starting deletion for contact ID: " . $contactId . ", User ID: " . $userId);
    
    // Start transaction
    $db->beginTransaction();
    
    // Get contact details and verify ownership
    $stmt = $db->prepare("
        SELECT c.id, c.first_name, c.last_name, c.id_lead, c.id_user
        FROM contacts c
        WHERE c.id = ? AND c.id_user = ?
    ");
    $stmt->execute([$contactId, $userId]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contact) {
        $db->rollBack();
        error_log("Delete contact - Contact not found or access denied for contact ID: " . $contactId . ", User ID: " . $userId);
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Contact not found or access denied']);
        exit;
    }
    
    $contactName = trim($contact['first_name'] . ' ' . $contact['last_name']);
    $leadId = $contact['id_lead'];
    
    // If contact was converted from a lead, revert the lead status
    if ($leadId) {
        error_log("Delete contact - Contact was converted from lead ID: " . $leadId . ", reverting lead status");
        
        // Update the lead's notes to remove conversion information
        // We'll remove the "Converted to contact" line from the notes
        $stmt = $db->prepare("
            UPDATE leads 
            SET notes = TRIM(REPLACE(REPLACE(notes, CONCAT('Converted to contact: ', NOW(), ' (Contact ID: ', ?, ')'), ''), '\n\n', '\n'))
            WHERE id = ?
        ");
        $stmt->execute([$contactId, $leadId]);
        
        error_log("Delete contact - Lead status reverted for lead ID: " . $leadId);
    }
    
    // Delete the contact
    $stmt = $db->prepare("DELETE FROM contacts WHERE id = ?");
    $result = $stmt->execute([$contactId]);
    
    if (!$result) {
        $db->rollBack();
        throw new Exception('Failed to delete contact');
    }
    
    // Commit transaction
    $db->commit();
    
    error_log("Delete contact - Successfully deleted contact ID: " . $contactId . (($leadId) ? " and reverted lead ID: " . $leadId : ""));
    
    echo json_encode([
        'success' => true,
        'message' => 'Contact deleted successfully',
        'contact_name' => $contactName,
        'lead_reverted' => $leadId ? true : false,
        'lead_id' => $leadId
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Delete contact error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the contact. Please try again.']);
}
?>
