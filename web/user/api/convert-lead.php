<?php
/**
 * Convert Lead to Contact - Server-side authenticated endpoint
 * Converts a lead into a contact and links them
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../api/includes/Database.php';
    require_once __DIR__ . '/../includes/UserAuth.php';

    header('Content-Type: application/json');
} catch (Exception $e) {
    error_log("Convert lead - Configuration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Configuration error: ' . $e->getMessage()]);
    exit;
}

// Check if user is logged in using UserAuth
if (!UserAuth::isLoggedIn()) {
    error_log("Convert lead API - User not logged in");
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
    
    error_log("Convert lead - Starting conversion for lead ID: " . $leadId . ", User ID: " . $userId);
    
    // Start transaction
    $db->beginTransaction();
    
    // Determine if leads.qr_id exists for QR-origin leads
    $hasQrId = false;
    try {
        $col = $db->query("SHOW COLUMNS FROM leads LIKE 'qr_id'");
        $hasQrId = $col && $col->rowCount() > 0; // PDOStatement when using ->query
    } catch (Throwable $e) { $hasQrId = false; }

    // Get lead details and verify ownership via either card or QR ownership
    $params = [$leadId, $userId, $userId];
    $joinQr = $hasQrId ? " LEFT JOIN custom_qr_codes cqr ON l.qr_id = cqr.id" : "";
    $whereQr = $hasQrId ? " OR cqr.user_id = ?" : "";
    if ($hasQrId) { $params[] = $userId; }

    $selectQrOwner = $hasQrId ? ", cqr.user_id AS qr_owner_id" : ", NULL AS qr_owner_id";

    $sql = "
        SELECT l.*, bc.user_id AS card_owner_id $selectQrOwner
        FROM leads l
        LEFT JOIN business_cards bc ON l.id_business_card = bc.id
        $joinQr
        WHERE l.id = ? AND (l.id_user = ? OR bc.user_id = ? $whereQr)
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Convert lead - Lead lookup result: " . print_r($lead, true));
    
    if (!$lead) {
        $db->rollBack();
        error_log("Convert lead - Lead not found or access denied for lead ID: " . $leadId . ", User ID: " . $userId);
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Lead not found or access denied']);
        exit;
    }
    
    // Check if lead is already converted
    $stmt = $db->prepare("SELECT id FROM contacts WHERE id_lead = ?");
    $stmt->execute([$leadId]);
    $existingContact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingContact) {
        $db->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Lead has already been converted to contact']);
        exit;
    }
    
    // Create contact from lead data
    $stmt = $db->prepare("
        INSERT INTO contacts (
            id_user, id_lead,
            first_name, last_name, full_name,
            work_phone, mobile_phone, email_primary,
            street_address, city, state, zip_code, country,
            organization_name, job_title, birthdate,
            website_url, photo_url,
            comments_from_lead, ip_address, user_agent, referrer
        ) VALUES (
            ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?
        )
    ");
    
    $fullName = trim($lead['first_name'] . ' ' . $lead['last_name']);
    
    $result = $stmt->execute([
        $userId, $leadId,
        $lead['first_name'], $lead['last_name'], $fullName,
        $lead['work_phone'], $lead['mobile_phone'], $lead['email_primary'],
        $lead['street_address'], $lead['city'], $lead['state'], $lead['zip_code'], $lead['country'],
        $lead['organization_name'], $lead['job_title'], $lead['birthdate'],
        $lead['website_url'], $lead['photo_url'],
        $lead['comments_from_lead'], $lead['ip_address'], $lead['user_agent'], $lead['referrer']
    ]);
    
    if (!$result) {
        $db->rollBack();
        throw new Exception('Failed to create contact');
    }
    
    $contactId = $db->lastInsertId();
    
    // Update lead to mark as converted (using notes field for status tracking)
    $stmt = $db->prepare("
        UPDATE leads 
        SET notes = CONCAT(COALESCE(notes, ''), '|CONVERTED_TO_CONTACT:', ?)
        WHERE id = ?
    ");
    $stmt->execute([$contactId, $leadId]);
    
    // Commit transaction
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Lead successfully converted to contact',
        'contact_id' => $contactId,
        'contact_name' => $fullName
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Convert lead error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while converting the lead. Please try again.']);
}
?>
