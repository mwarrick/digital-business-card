<?php
/**
 * Leads API - Main endpoint
 * Handles GET (list leads), PUT (update lead), DELETE (delete lead)
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

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // List all leads for user
            $stmt = $db->prepare("
                SELECT l.*, bc.first_name as card_first_name, bc.last_name as card_last_name,
                       bc.company_name as card_company, bc.job_title as card_job_title,
                       CASE WHEN EXISTS (SELECT 1 FROM contacts c WHERE c.id_lead = l.id) 
                            THEN 'converted' ELSE 'new' END as status
                FROM leads l
                JOIN business_cards bc ON l.id_business_card = bc.id
                WHERE bc.user_id = ?
                ORDER BY l.created_at DESC
            ");
            $stmt->execute([$userId]);
            $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $leads,
                'count' => count($leads)
            ]);
            break;
            
        case 'PUT':
            // Update lead
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Lead ID required']);
                exit;
            }
            
            // Verify lead belongs to user
            $stmt = $db->prepare("
                SELECT l.id FROM leads l
                JOIN business_cards bc ON l.id_business_card = bc.id
                WHERE l.id = ? AND bc.user_id = ?
            ");
            $stmt->execute([$data['id'], $userId]);
            $lead = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$lead) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Lead not found']);
                exit;
            }
            
            // Update lead with provided fields
            $updateFields = [];
            $updateValues = [];
            
            $allowedFields = [
                'first_name', 'last_name', 'work_phone', 'mobile_phone', 'email_primary',
                'street_address', 'city', 'state', 'zip_code', 'country',
                'organization_name', 'job_title', 'birthdate', 'website_url',
                'comments_from_lead'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = ?";
                    $updateValues[] = $data[$field];
                }
            }
            
            if (empty($updateFields)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
                exit;
            }
            
            // Update full_name if first_name or last_name changed
            if (isset($data['first_name']) || isset($data['last_name'])) {
                $stmt = $db->prepare("SELECT first_name, last_name FROM leads WHERE id = ?");
                $stmt->execute([$data['id']]);
                $currentLead = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $firstName = $data['first_name'] ?? $currentLead['first_name'];
                $lastName = $data['last_name'] ?? $currentLead['last_name'];
                $fullName = trim($firstName . ' ' . $lastName);
                
                $updateFields[] = "full_name = ?";
                $updateValues[] = $fullName;
            }
            
            $updateFields[] = "updated_at = NOW()";
            $updateValues[] = $data['id'];
            
            $stmt = $db->prepare("
                UPDATE leads SET " . implode(', ', $updateFields) . " WHERE id = ?
            ");
            
            $result = $stmt->execute($updateValues);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Lead updated successfully'
                ]);
            } else {
                throw new Exception('Failed to update lead');
            }
            break;
            
        case 'DELETE':
            // Delete lead
            $leadId = $_GET['id'] ?? null;
            
            if (!$leadId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Lead ID required']);
                exit;
            }
            
            // Verify lead belongs to user
            $stmt = $db->prepare("
                SELECT l.id FROM leads l
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
            $stmt = $db->prepare("SELECT id FROM contacts WHERE id_lead = ?");
            $stmt->execute([$leadId]);
            $contact = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($contact) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cannot delete lead that has been converted to contact']);
                exit;
            }
            
            // Delete lead
            $stmt = $db->prepare("DELETE FROM leads WHERE id = ?");
            $result = $stmt->execute([$leadId]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Lead deleted successfully'
                ]);
            } else {
                throw new Exception('Failed to delete lead');
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Leads API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>
