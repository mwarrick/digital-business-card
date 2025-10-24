<?php
/**
 * Update Contact - Server-side authenticated endpoint
 * Updates contact information
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/UserAuth.php';

header('Content-Type: application/json');

// Check if user is logged in using UserAuth
if (!UserAuth::isLoggedIn()) {
    error_log("Update contact API - User not logged in");
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
    
    error_log("Update contact - Starting update for contact ID: " . $contactId . ", User ID: " . $userId);
    
    // Start transaction
    $db->beginTransaction();
    
    // Verify contact belongs to user
    $stmt = $db->prepare("SELECT id FROM contacts WHERE id = ? AND id_user = ?");
    $stmt->execute([$contactId, $userId]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contact) {
        $db->rollBack();
        error_log("Update contact - Contact not found or access denied for contact ID: " . $contactId . ", User ID: " . $userId);
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Contact not found or access denied']);
        exit;
    }
    
    // Prepare update fields
    $updateFields = [];
    $updateValues = [];
    
    $allowedFields = [
        'first_name', 'last_name', 'work_phone', 'mobile_phone', 'email_primary',
        'street_address', 'city', 'state', 'zip_code', 'country',
        'organization_name', 'job_title', 'birthdate', 'website_url', 'photo_url',
        'comments_from_lead'
    ];
    
    foreach ($allowedFields as $field) {
        if (isset($_POST[$field])) {
            $updateFields[] = "$field = ?";
            $updateValues[] = $_POST[$field];
        }
    }
    
    if (empty($updateFields)) {
        $db->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
        exit;
    }
    
    // Update full_name if first_name or last_name changed
    if (isset($_POST['first_name']) || isset($_POST['last_name'])) {
        $stmt = $db->prepare("SELECT first_name, last_name FROM contacts WHERE id = ?");
        $stmt->execute([$contactId]);
        $currentContact = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $firstName = $_POST['first_name'] ?? $currentContact['first_name'];
        $lastName = $_POST['last_name'] ?? $currentContact['last_name'];
        $fullName = trim($firstName . ' ' . $lastName);
        
        $updateFields[] = "full_name = ?";
        $updateValues[] = $fullName;
    }
    
    $updateFields[] = "updated_at = NOW()";
    $updateValues[] = $contactId;
    
    $stmt = $db->prepare("
        UPDATE contacts SET " . implode(', ', $updateFields) . " WHERE id = ?
    ");
    
    $result = $stmt->execute($updateValues);
    
    if (!$result) {
        $db->rollBack();
        throw new Exception('Failed to update contact');
    }
    
    // Commit transaction
    $db->commit();
    
    error_log("Update contact - Successfully updated contact: " . $contactId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Contact updated successfully',
        'contact_name' => ($_POST['first_name'] ?? '') . ' ' . ($_POST['last_name'] ?? '')
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Update contact error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating the contact. Please try again.']);
}
?>
