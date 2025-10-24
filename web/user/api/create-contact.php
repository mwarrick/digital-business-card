<?php
/**
 * Create Contact - Server-side authenticated endpoint
 * Creates a new contact for the logged-in user
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/UserAuth.php';

header('Content-Type: application/json');

// Check if user is logged in using UserAuth
if (!UserAuth::isLoggedIn()) {
    error_log("Create contact API - User not logged in");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please log in']);
    exit;
}

$userId = UserAuth::getUserId();

try {
    $db = Database::getInstance()->getConnection();
    
    error_log("Create contact - Starting creation for User ID: " . $userId);
    
    // Get form data
    $data = $_POST;
    
    // Validate required fields
    if (empty($data['first_name'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'First name is required']);
        exit;
    }
    
    if (empty($data['last_name'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Last name is required']);
        exit;
    }
    
    if (empty($data['email_primary'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit;
    }
    
    // Validate email format
    if (!filter_var($data['email_primary'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    // Start transaction
    $db->beginTransaction();
    
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
        0, // id_lead - 0 for manual contacts (not converted from lead)
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
    
    if (!$result) {
        $db->rollBack();
        throw new Exception('Failed to create contact');
    }
    
    $contactId = $db->lastInsertId();
    
    // Commit transaction
    $db->commit();
    
    error_log("Create contact - Successfully created contact ID: " . $contactId . " for User ID: " . $userId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Contact created successfully',
        'contact_id' => $contactId,
        'contact_name' => $fullName
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Create contact error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while creating the contact. Please try again.']);
}
?>
