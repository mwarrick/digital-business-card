<?php
/**
 * Leads API - Main endpoint
 * Handles GET (list leads), PUT (update lead), DELETE (delete lead)
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display, but log

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
error_log("Leads API: User ID from token: " . ($userId ?? 'NULL'));

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User ID not found in token']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // List all leads for user
            // Leads can be from business cards OR custom QR codes
            error_log("Leads API: Fetching leads for user_id: $userId");
            
            // Detect QR columns and mapping table
            $hasIdCustomQr = false;
            $hasQrId = false;
            $hasQrLeads = false;
            try {
                $colCheck = $db->prepare("SHOW COLUMNS FROM leads LIKE 'id_custom_qr_code'");
                $colCheck->execute();
                $hasIdCustomQr = ($colCheck->rowCount() > 0);
            } catch (Exception $e) {
                $hasIdCustomQr = false;
            }
            if (!$hasIdCustomQr) {
                try {
                    $colCheck = $db->prepare("SHOW COLUMNS FROM leads LIKE 'qr_id'");
                    $colCheck->execute();
                    $hasQrId = ($colCheck->rowCount() > 0);
                } catch (Exception $e) {
                    $hasQrId = false;
                }
            }
            try {
                $tblCheck = $db->prepare("SHOW TABLES LIKE 'qr_leads'");
                $tblCheck->execute();
                $hasQrLeads = ($tblCheck->rowCount() > 0);
            } catch (Exception $e) {
                $hasQrLeads = false;
            }
            
            // Build QR joins similar to website
            $selectQr = ", NULL AS qr_title, NULL AS qr_type";
            $joinQrLead = "";
            $joinQr = "";
            $whereQr = "";
            
            if ($hasIdCustomQr || $hasQrId || $hasQrLeads) {
                $selectQr = ", cqr.title AS qr_title, cqr.type AS qr_type";
                $onParts = [];
                if ($hasQrLeads) {
                    $joinQrLead = " LEFT JOIN qr_leads ql ON ql.lead_id = l.id";
                    $onParts[] = "ql.qr_id = cqr.id";
                }
                if ($hasIdCustomQr) {
                    $onParts[] = "l.id_custom_qr_code = cqr.id";
                }
                if ($hasQrId) {
                    $onParts[] = "l.qr_id = cqr.id";
                }
                $onClause = implode(' OR ', $onParts);
                $joinQr = " LEFT JOIN custom_qr_codes cqr ON (" . $onClause . ")";
                $whereQr = " OR (cqr.user_id = ?)";
            }
            
            // Try query with id_user and QR support
            try {
                $params = [$userId, $userId];
                if ($whereQr) {
                    $params[] = $userId;
                }
                
                $stmt = $db->prepare("
                    SELECT l.*, 
                           bc.first_name as card_first_name, 
                           bc.last_name as card_last_name,
                           bc.company_name as card_company, 
                           bc.job_title as card_job_title
                           $selectQr,
                           CASE WHEN EXISTS (SELECT 1 FROM contacts c WHERE c.id_lead = l.id) 
                                THEN 'converted' ELSE 'new' END as status
                    FROM leads l
                    LEFT JOIN business_cards bc ON l.id_business_card = bc.id
                    $joinQrLead
                    $joinQr
                    WHERE (bc.user_id = ? OR l.id_user = ? $whereQr)
                    ORDER BY l.created_at DESC
                ");
                $stmt->execute($params);
            } catch (PDOException $e) {
                // If id_user column doesn't exist, fall back to business cards only
                error_log("Leads API: Query with id_user failed, trying business cards only: " . $e->getMessage());
                $stmt = $db->prepare("
                    SELECT l.*, 
                           bc.first_name as card_first_name, 
                           bc.last_name as card_last_name,
                           bc.company_name as card_company, 
                           bc.job_title as card_job_title,
                           NULL AS qr_title,
                           NULL AS qr_type,
                           CASE WHEN EXISTS (SELECT 1 FROM contacts c WHERE c.id_lead = l.id) 
                                THEN 'converted' ELSE 'new' END as status
                    FROM leads l
                    INNER JOIN business_cards bc ON l.id_business_card = bc.id
                    WHERE bc.user_id = ?
                    ORDER BY l.created_at DESC
                ");
                $stmt->execute([$userId]);
            }
            
            $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Leads API: Found " . count($leads) . " leads for user_id: $userId");
            
            $response = [
                'success' => true,
                'message' => 'Leads retrieved successfully',
                'data' => $leads,
                'count' => count($leads)
            ];
            
            echo json_encode($response);
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
    error_log("Leads API error trace: " . $e->getTraceAsString());
    
    // Ensure output buffering is off
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code(500);
    $errorResponse = json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
    error_log("Leads API: Error response: " . $errorResponse);
    echo $errorResponse;
    exit;
}
?>
