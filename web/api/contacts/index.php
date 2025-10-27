<?php
/**
 * Contacts API - Main endpoint
 * Handles GET (list contacts), POST (create contact), PUT (update contact), DELETE (delete contact)
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/AuthHelper.php';
require_once __DIR__ . '/../includes/InputValidator.php';

header('Content-Type: application/json');

// Check authentication
if (!AuthHelper::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = AuthHelper::getUserId();
$db = Database::getInstance()->getConnection();

// Log the request for debugging
error_log("Contacts API: User ID = $userId, Method = " . $_SERVER['REQUEST_METHOD']);

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // List all contacts for user
            error_log("Contacts API: Executing GET request for user $userId");
            
            // First check if contacts table exists
            $tableCheck = $db->query("SHOW TABLES LIKE 'contacts'");
            if ($tableCheck->rowCount() == 0) {
                error_log("Contacts API: contacts table does not exist");
                echo json_encode([
                    'success' => false,
                    'message' => 'Contacts table not found',
                    'data' => [],
                    'count' => 0
                ]);
                exit;
            }
            
            $stmt = $db->prepare("
                SELECT c.*, l.id as lead_id, bc.first_name as card_first_name, 
                       bc.last_name as card_last_name
                FROM contacts c
                LEFT JOIN leads l ON c.id_lead = l.id
                LEFT JOIN business_cards bc ON l.id_business_card = bc.id
                WHERE c.id_user = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$userId]);
            $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Contacts API: Found " . count($contacts) . " contacts for user $userId");
            
            echo json_encode([
                'success' => true,
                'data' => $contacts,
                'count' => count($contacts)
            ]);
            break;
            
        case 'POST':
            // Create new contact
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            
            $validator = new InputValidator();
            $validator->required('first_name', $data['first_name'] ?? null);
            $validator->required('last_name', $data['last_name'] ?? null);
            $validator->email('email_primary', $data['email_primary'] ?? null);
            
            if (!$validator->isValid()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'errors' => $validator->getErrors()]);
                exit;
            }
            
            // Create contact using existing table structure with ALL fields
            $stmt = $db->prepare("
                INSERT INTO contacts (
                    id_user, id_lead, first_name, last_name, full_name,
                    work_phone, mobile_phone, email_primary, street_address, city, state, 
                    zip_code, country, organization_name, job_title, birthdate, 
                    website_url, photo_url, comments_from_lead, ip_address, user_agent, referrer
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $fullName = trim($data['first_name'] . ' ' . $data['last_name']);
            
            $result = $stmt->execute([
                $userId,
                $data['id_lead'] ?? null, // Optional lead ID if converting from lead
                $data['first_name'],
                $data['last_name'],
                $fullName,
                $data['work_phone'] ?? null,
                $data['mobile_phone'] ?? null,
                $data['email_primary'],
                $data['street_address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? null,
                $data['zip_code'] ?? null,
                $data['country'] ?? null,
                $data['organization_name'] ?? null,
                $data['job_title'] ?? null,
                $data['birthdate'] ?? null,
                $data['website_url'] ?? null,
                $data['photo_url'] ?? null,
                $data['comments_from_lead'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $_SERVER['HTTP_REFERER'] ?? null
            ]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Contact created successfully',
                    'contact_id' => $db->lastInsertId()
                ]);
            } else {
                throw new Exception('Failed to create contact');
            }
            break;
            
        case 'PUT':
            // Update contact
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Contact ID required']);
                exit;
            }
            
            // Verify contact belongs to user
            $stmt = $db->prepare("SELECT id FROM contacts WHERE id = ? AND id_user = ?");
            $stmt->execute([$data['id'], $userId]);
            $contact = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$contact) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Contact not found']);
                exit;
            }
            
            // Update contact with provided fields
            $updateFields = [];
            $updateValues = [];
            
            $allowedFields = [
                'first_name', 'last_name', 'work_phone', 'mobile_phone', 'email_primary',
                'street_address', 'city', 'state', 'zip_code', 'country',
                'organization_name', 'job_title', 'birthdate', 'website_url',
                'photo_url', 'comments_from_lead'
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
                $stmt = $db->prepare("SELECT first_name, last_name FROM contacts WHERE id = ?");
                $stmt->execute([$data['id']]);
                $currentContact = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $firstName = $data['first_name'] ?? $currentContact['first_name'];
                $lastName = $data['last_name'] ?? $currentContact['last_name'];
                $fullName = trim($firstName . ' ' . $lastName);
                
                $updateFields[] = "full_name = ?";
                $updateValues[] = $fullName;
            }
            
            $updateFields[] = "updated_at = NOW()";
            $updateValues[] = $data['id'];
            
            $stmt = $db->prepare("
                UPDATE contacts SET " . implode(', ', $updateFields) . " WHERE id = ?
            ");
            
            $result = $stmt->execute($updateValues);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Contact updated successfully'
                ]);
            } else {
                throw new Exception('Failed to update contact');
            }
            break;
            
        case 'DELETE':
            // Delete contact
            $contactId = $_GET['id'] ?? null;
            
            if (!$contactId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Contact ID required']);
                exit;
            }
            
            // Verify contact belongs to user
            $stmt = $db->prepare("SELECT id FROM contacts WHERE id = ? AND id_user = ?");
            $stmt->execute([$contactId, $userId]);
            $contact = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$contact) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Contact not found']);
                exit;
            }
            
            // Delete contact
            $stmt = $db->prepare("DELETE FROM contacts WHERE id = ?");
            $result = $stmt->execute([$contactId]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Contact deleted successfully'
                ]);
            } else {
                throw new Exception('Failed to delete contact');
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Contacts API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>
