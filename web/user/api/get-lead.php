<?php
/**
 * Get Lead Details - Server-side authenticated endpoint
 * Returns detailed information for a specific lead
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/UserAuth.php';

header('Content-Type: application/json');

// Check if user is logged in using UserAuth
if (!UserAuth::isLoggedIn()) {
    error_log("Get lead API - User not logged in");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please log in']);
    exit;
}

$userId = UserAuth::getUserId();
$leadId = $_GET['id'] ?? null;

if (!$leadId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Lead ID required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Detect QR columns
    $hasIdCustomQr = false; $hasQrId = false;
    try { $col = $db->query("SHOW COLUMNS FROM leads LIKE 'id_custom_qr_code'"); $hasIdCustomQr = $col && $col->rowCount() > 0; } catch (Throwable $e) { $hasIdCustomQr = false; }
    try { $col = $db->query("SHOW COLUMNS FROM leads LIKE 'qr_id'"); $hasQrId = $col && $col->rowCount() > 0; } catch (Throwable $e) { $hasQrId = false; }

    // Get lead details with either business card or custom QR info (support both id_custom_qr_code and legacy qr_id)
    $selectQr = ($hasIdCustomQr || $hasQrId) ? ", cqr.title AS qr_title, cqr.type AS qr_type" : ", NULL AS qr_title, NULL AS qr_type";
    $joinQr = '';
    if ($hasIdCustomQr || $hasQrId) {
        $onParts = [];
        if ($hasIdCustomQr) { $onParts[] = "l.id_custom_qr_code = cqr.id"; }
        if ($hasQrId) { $onParts[] = "l.qr_id = cqr.id"; }
        $joinQr = " LEFT JOIN custom_qr_codes cqr ON (" . implode(' OR ', $onParts) . ")";
    }

    $stmt = $db->prepare("
        SELECT l.*, 
               bc.first_name as card_first_name, bc.last_name as card_last_name,
               bc.company_name as card_company, bc.job_title as card_job_title,
               bc.phone_number as card_phone, bc.bio as card_bio
               $selectQr,
               CASE WHEN EXISTS (SELECT 1 FROM contacts c WHERE c.id_lead = l.id) 
                    THEN 'converted' ELSE 'new' END as status
        FROM leads l
        LEFT JOIN business_cards bc ON l.id_business_card = bc.id
        $joinQr
        WHERE l.id = ? AND (l.id_user = ? OR bc.user_id = ?" . (($hasIdCustomQr || $hasQrId) ? " OR cqr.user_id = ?" : "") . ")
    ");
    $params = [$leadId, $userId, $userId];
    if ($hasIdCustomQr || $hasQrId) { $params[] = $userId; }
    $stmt->execute($params);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lead) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Lead not found']);
        exit;
    }
    
    // Check if lead has been converted to contact
    $stmt = $db->prepare("
        SELECT c.id, c.first_name, c.last_name, c.email_primary, c.created_at
        FROM contacts c
        WHERE c.id_lead = ?
    ");
    $stmt->execute([$leadId]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contact) {
        $lead['converted_contact'] = $contact;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $lead
    ]);
    
} catch (Exception $e) {
    error_log("Get lead details error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>
