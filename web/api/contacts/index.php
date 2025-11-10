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
$authPayload = AuthHelper::getAuthPayload();
error_log("Contacts API: User ID (raw) = " . var_export($userId, true) . " (type: " . gettype($userId) . ")");
error_log("Contacts API: Method = " . $_SERVER['REQUEST_METHOD']);
error_log("Contacts API: Request URI = " . $_SERVER['REQUEST_URI']);
error_log("Contacts API: Auth payload = " . json_encode($authPayload));
error_log("Contacts API: Auth email = " . ($authPayload['email'] ?? 'NOT SET'));

// Validate user ID
if (!$userId) {
    error_log("Contacts API: ERROR - No user ID extracted from token!");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Invalid token']);
    exit;
}

// Convert user ID to string (id_user in contacts table is VARCHAR)
$userId = (string)$userId;
error_log("Contacts API: User ID (converted to string) = '$userId'");

// Check if this is a demo user and reject if so (for security)
if (isset($authPayload['is_demo']) && $authPayload['is_demo'] === true) {
    error_log("Contacts API: WARNING - Demo user attempting to access contacts API");
    // Allow demo users but log it
}
// Read raw input ONCE and reuse it for logging and JSON parsing (avoids draining the stream)
$rawInput = file_get_contents('php://input');
if ($rawInput !== false && $rawInput !== '') {
    error_log("Contacts API: Raw input = " . $rawInput);
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
				// List contacts or get single contact for user
				error_log("Contacts API: Executing GET request for user $userId");
				error_log("Contacts API: GET request URI = " . $_SERVER['REQUEST_URI']);

				// If an ID is provided (as query ?id= or in the path /contacts/{id}), return a single contact
				$contactId = $_GET['id'] ?? null;
				if (!$contactId && isset($_SERVER['REQUEST_URI'])) {
					// Match trailing integer id after /contacts/
					if (preg_match('#/contacts/([0-9]+)(?:/)?$#', $_SERVER['REQUEST_URI'], $m)) {
						$contactId = $m[1];
					}
				}

				if ($contactId) {
					// Return single contact details (with joins similar to standalone get.php)
					$stmt = $db->prepare("\n\t\t\t\t\tSELECT c.*, l.id as lead_id, l.created_at as lead_created_at,\n\t\t\t\t\t\t\t   bc.first_name as card_first_name, bc.last_name as card_last_name,\n\t\t\t\t\t\t\t   bc.company_name as card_company, bc.job_title as card_job_title,\n\t\t\t\t\t\t\t   bc.phone_number as card_phone, bc.bio as card_bio,\n\t\t\t\t\t\t\t   CASE WHEN c.id_lead IS NOT NULL AND c.id_lead != 0 AND c.id_lead != '' THEN 'converted' ELSE COALESCE(c.source, 'manual') END as source_type\n\t\t\t\t\tFROM contacts c\n\t\t\t\t\tLEFT JOIN leads l ON c.id_lead = l.id\n\t\t\t\t\tLEFT JOIN business_cards bc ON l.id_business_card = bc.id\n\t\t\t\t\tWHERE c.id = ? AND c.id_user = ?\n\t\t\t\t");
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
					exit;
				}
            
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
                       bc.last_name as card_last_name,
                       CASE WHEN c.id_lead IS NOT NULL AND c.id_lead != 0 AND c.id_lead != '' THEN 'converted' ELSE COALESCE(c.source, 'manual') END as source_type
                FROM contacts c
                LEFT JOIN leads l ON c.id_lead = l.id
                LEFT JOIN business_cards bc ON l.id_business_card = bc.id
                WHERE c.id_user = ?
                ORDER BY c.created_at DESC
            ");
            // Execute with userId as string (id_user is VARCHAR in database)
            $stmt->execute([$userId]);
            $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Contacts API: Found " . count($contacts) . " contacts for user $userId");
            
            // Verify all contacts belong to the correct user (safety check)
            $wrongUserContacts = array_filter($contacts, function($contact) use ($userId) {
                return isset($contact['id_user']) && $contact['id_user'] != $userId;
            });
            if (!empty($wrongUserContacts)) {
                error_log("Contacts API: ERROR - Found " . count($wrongUserContacts) . " contacts with wrong user_id!");
                error_log("Contacts API: Expected user_id = $userId, but found: " . json_encode(array_column($wrongUserContacts, 'id_user')));
                // Filter out wrong user contacts for safety
                $contacts = array_filter($contacts, function($contact) use ($userId) {
                    return !isset($contact['id_user']) || $contact['id_user'] == $userId;
                });
            }
            
            // Debug: Log the actual contact data structure
            if (!empty($contacts)) {
                error_log("Contacts API: First contact structure: " . json_encode($contacts[0]));
                error_log("Contacts API: Available fields: " . implode(', ', array_keys($contacts[0])));
            }
            
            $response = [
                'success' => true,
                'message' => 'Contacts retrieved successfully',
                'data' => $contacts,
                'count' => count($contacts)
            ];
            error_log("Contacts API: GET response = " . json_encode($response));
            echo json_encode($response);
            break;
            
        case 'POST':
            // Create new contact
            $data = json_decode($rawInput, true) ?? $_POST;
            
            // Debug: Log the received data
            error_log("Contacts API POST: Received data: " . json_encode($data));
            
            $validator = new InputValidator();
            $validator->required('first_name', $data['first_name'] ?? null);
            $validator->required('last_name', $data['last_name'] ?? null);
            // Email is optional, so only validate format if provided
            if (!empty($data['email_primary'])) {
                $validator->email('email_primary', $data['email_primary']);
            }
            
            if (!$validator->isValid()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'errors' => $validator->getErrors()]);
                exit;
            }
            
            // Check if source columns exist
            $columns = $db->query("SHOW COLUMNS FROM contacts LIKE 'source'");
            $hasSourceColumn = $columns->rowCount() > 0;
            
            $columns = $db->query("SHOW COLUMNS FROM contacts LIKE 'source_metadata'");
            $hasSourceMetadataColumn = $columns->rowCount() > 0;
            
            if ($hasSourceColumn && $hasSourceMetadataColumn) {
                // Use new schema with source tracking
                $stmt = $db->prepare("
                    INSERT INTO contacts (
                        id_user, id_lead, first_name, last_name, full_name,
                        work_phone, mobile_phone, email_primary, street_address, city, state, 
                        zip_code, country, organization_name, job_title, birthdate, 
                        website_url, photo_url, comments_from_lead, ip_address, user_agent, referrer,
                        source, source_metadata
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
            } else {
                // Use old schema without source tracking
                $stmt = $db->prepare("
                    INSERT INTO contacts (
                        id_user, id_lead, first_name, last_name, full_name,
                        work_phone, mobile_phone, email_primary, street_address, city, state, 
                        zip_code, country, organization_name, job_title, birthdate, 
                        website_url, photo_url, comments_from_lead, ip_address, user_agent, referrer
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
            }
            
            $fullName = trim($data['first_name'] . ' ' . $data['last_name']);
            
            if ($hasSourceColumn && $hasSourceMetadataColumn) {
                // Include source fields
                $executeData = [
                    $userId,
                    $data['id_lead'] ?? 0, // Set to 0 if not provided (database requires non-null)
                    $data['first_name'],
                    $data['last_name'],
                    $fullName,
                    $data['work_phone'] ?? '', // Set to empty string if not provided
                    $data['mobile_phone'] ?? '', // Set to empty string if not provided
                    $data['email_primary'] ?? '', // Set to empty string if not provided
                    $data['street_address'] ?? '', // Set to empty string if not provided
                    $data['city'] ?? '', // Set to empty string if not provided
                    $data['state'] ?? '', // Set to empty string if not provided
                    $data['zip_code'] ?? '', // Set to empty string if not provided
                    $data['country'] ?? '', // Set to empty string if not provided
                    $data['organization_name'] ?? '', // Set to empty string if not provided
                    $data['job_title'] ?? '', // Set to empty string if not provided
                    $data['birthdate'] ?? '', // Set to empty string if not provided
                    $data['website_url'] ?? '', // Set to empty string if not provided
                    $data['photo_url'] ?? '', // Set to empty string if not provided
                    $data['comments_from_lead'] ?? '', // Set to empty string if not provided (database requires non-null)
                    $_SERVER['REMOTE_ADDR'] ?? '', // Set to empty string if not provided
                    $_SERVER['HTTP_USER_AGENT'] ?? '', // Set to empty string if not provided
                    $_SERVER['HTTP_REFERER'] ?? '', // Set to empty string if not provided
                    $data['source'] ?? 'manual', // Default to 'manual' for iOS app contacts
                    $data['source_metadata'] ?? '' // Set to empty string if not provided
                ];
            } else {
                // Exclude source fields for old schema
                $executeData = [
                    $userId,
                    $data['id_lead'] ?? 0, // Set to 0 if not provided (database requires non-null)
                    $data['first_name'],
                    $data['last_name'],
                    $fullName,
                    $data['work_phone'] ?? '', // Set to empty string if not provided
                    $data['mobile_phone'] ?? '', // Set to empty string if not provided
                    $data['email_primary'] ?? '', // Set to empty string if not provided
                    $data['street_address'] ?? '', // Set to empty string if not provided
                    $data['city'] ?? '', // Set to empty string if not provided
                    $data['state'] ?? '', // Set to empty string if not provided
                    $data['zip_code'] ?? '', // Set to empty string if not provided
                    $data['country'] ?? '', // Set to empty string if not provided
                    $data['organization_name'] ?? '', // Set to empty string if not provided
                    $data['job_title'] ?? '', // Set to empty string if not provided
                    $data['birthdate'] ?? '', // Set to empty string if not provided
                    $data['website_url'] ?? '', // Set to empty string if not provided
                    $data['photo_url'] ?? '', // Set to empty string if not provided
                    $data['comments_from_lead'] ?? '', // Set to empty string if not provided (database requires non-null)
                    $_SERVER['REMOTE_ADDR'] ?? '', // Set to empty string if not provided
                    $_SERVER['HTTP_USER_AGENT'] ?? '', // Set to empty string if not provided
                    $_SERVER['HTTP_REFERER'] ?? '' // Set to empty string if not provided
                ];
            }
            
            // Debug: Log the execute data
            error_log("Contacts API POST: Execute data: " . json_encode($executeData));
            
            $result = $stmt->execute($executeData);
            
            if ($result) {
                $contactId = $db->lastInsertId();
                
                // Fetch the created contact to return full data
                $stmt = $db->prepare("
                    SELECT c.*, l.id as lead_id, bc.first_name as card_first_name, 
                           bc.last_name as card_last_name,
                           CASE WHEN c.id_lead IS NOT NULL AND c.id_lead != 0 AND c.id_lead != '' THEN 'converted' ELSE COALESCE(c.source, 'manual') END as source_type
                    FROM contacts c
                    LEFT JOIN leads l ON c.id_lead = l.id
                    LEFT JOIN business_cards bc ON l.id_business_card = bc.id
                    WHERE c.id = ? AND c.id_user = ?
                ");
                $stmt->execute([$contactId, $userId]);
                $contact = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($contact) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Contact created successfully',
                        'data' => $contact
                    ]);
                } else {
                    throw new Exception('Failed to fetch created contact');
                }
            } else {
                throw new Exception('Failed to create contact');
            }
            break;
            
        case 'PUT':
            // Update contact
            error_log("Contacts API: Processing PUT request");
            $data = json_decode($rawInput, true);
            error_log("Contacts API: PUT data = " . json_encode($data));
            
            if (!isset($data['id'])) {
                error_log("Contacts API: PUT request missing contact ID");
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
                // Fetch the updated contact to return
                $stmt = $db->prepare("SELECT * FROM contacts WHERE id = ?");
                $stmt->execute([$data['id']]);
                $updatedContact = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($updatedContact) {
                    // Return the contact data in the format expected by both iOS and web
                    // The iOS app will handle the field mapping on its side
                    $response = [
                        'success' => true,
                        'message' => 'Contact updated successfully',
                        'data' => $updatedContact
                    ];
                    error_log("Contacts API: PUT response with data = " . json_encode($response));
                    echo json_encode($response);
                } else {
                    $response = [
                        'success' => true,
                        'message' => 'Contact updated successfully'
                    ];
                    error_log("Contacts API: PUT response without data = " . json_encode($response));
                    echo json_encode($response);
                }
            } else {
                throw new Exception('Failed to update contact');
            }
            break;
            
        case 'DELETE':
            // Delete contact
            // Accept id from query (?id=), path (/contacts/{id}) or JSON body {"id":...}
            error_log("Contacts API DELETE: Request URI = " . $_SERVER['REQUEST_URI']);
            error_log("Contacts API DELETE: GET params = " . json_encode($_GET));
            
            $contactId = $_GET['id'] ?? null;
            
            // Try to parse id from path if not provided as query
            if (!$contactId && isset($_SERVER['REQUEST_URI'])) {
                // Match trailing integer id after /contacts/
                if (preg_match('#/contacts/([0-9]+)(?:/)?$#', $_SERVER['REQUEST_URI'], $m)) {
                    $contactId = $m[1];
                }
            }
            
            // Try to parse id from JSON body
            if (!$contactId) {
                $raw = file_get_contents('php://input');
                if ($raw) {
                    $json = json_decode($raw, true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($json['id'])) {
                        $contactId = $json['id'];
                    }
                }
            }
            
            if (!$contactId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Contact ID required']);
                exit;
            }
            
            // Verify contact belongs to user and get lead info (handle both string and integer IDs)
            $stmt = $db->prepare("SELECT id, id_lead FROM contacts WHERE (id = ? OR CAST(id AS CHAR) = ?) AND id_user = ?");
            $stmt->execute([$contactId, $contactId, $userId]);
            $contact = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$contact) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Contact not found']);
                exit;
            }
            
            // Use the actual database ID
            $actualContactId = $contact['id'];
            $leadId = $contact['id_lead'] ?? null;
            
            // If contact came from a lead, revert it back to a lead instead of deleting
            if ($leadId) {
                error_log("Contacts API DELETE: Contact $actualContactId came from lead $leadId, reverting to lead");
                
                // Delete the contact (this reverts it back to lead)
                $stmt = $db->prepare("DELETE FROM contacts WHERE id = ?");
                $result = $stmt->execute([$actualContactId]);
                
                if ($result) {
                    // Update lead status to indicate it's no longer converted
                    // Remove any conversion markers
                    $stmt = $db->prepare("
                        UPDATE leads SET 
                            notes = REPLACE(COALESCE(notes, ''), ' [CONVERTED TO CONTACT]', ''),
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$leadId]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Contact reverted to lead successfully',
                        'reverted_to_lead' => true,
                        'lead_id' => $leadId
                    ]);
                } else {
                    throw new Exception('Failed to revert contact to lead');
                }
            } else {
                // Contact has no lead, delete it permanently
                error_log("Contacts API DELETE: Contact $actualContactId has no lead, deleting permanently");
                
                $stmt = $db->prepare("DELETE FROM contacts WHERE id = ?");
                $result = $stmt->execute([$actualContactId]);
                
                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Contact deleted successfully',
                        'reverted_to_lead' => false
                    ]);
                } else {
                    throw new Exception('Failed to delete contact');
                }
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
