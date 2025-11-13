<?php
/**
 * Bulk Delete Contacts - Authenticated endpoint
 * Deletes multiple contacts owned by the user and reverts associated leads
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/UserAuth.php';

header('Content-Type: application/json');

if (!UserAuth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = UserAuth::getUserId();
$ids = $_POST['contact_ids'] ?? [];
if (!is_array($ids) || empty($ids)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No contact IDs provided']);
    exit;
}

// Sanitize to integers and dedupe
$ids = array_values(array_unique(array_map(function($v){ return (int)$v; }, $ids)));
$ids = array_filter($ids, function($v){ return $v > 0; });
if (empty($ids)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid contact IDs']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    // Fetch only contacts that belong to this user and are not deleted
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = $ids;
    $params[] = $userId;
    $stmt = $db->prepare("SELECT id, id_lead FROM contacts WHERE id IN ($placeholders) AND id_user = ? AND is_deleted = 0 FOR UPDATE");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No matching contacts found']);
        exit;
    }

    $foundIds = array_map(function($r){ return (int)$r['id']; }, $rows);
    $leadsToRevert = array_filter(array_map(function($r){ return $r['id_lead'] ? (int)$r['id_lead'] : null; }, $rows));

    // Revert associated leads' notes for those contacts
    // Note: Keep it simple; remove conversion notes entry similar to single delete
    $leadRevertedCount = 0;
    if (!empty($leadsToRevert)) {
        $leadsToRevert = array_values(array_unique($leadsToRevert));
        $leadPlaceholders = implode(',', array_fill(0, count($leadsToRevert), '?'));
        // We cannot know exact NOW() timestamps used when converted; clear generic phrase safely
        // Safer approach: just leave notes as-is or append a note. Here, we keep parity with single delete by trimming potential remnants.
        $stmt = $db->prepare("UPDATE leads SET notes = TRIM(REPLACE(REPLACE(notes, 'Converted to contact:', ''), '\n\n', '\n')) WHERE id IN ($leadPlaceholders)");
        if ($stmt->execute($leadsToRevert)) {
            $leadRevertedCount = $stmt->rowCount();
        }
    }

    // Soft delete contacts
    $stmt = $db->prepare("UPDATE contacts SET is_deleted = 1, updated_at = NOW() WHERE id IN ($placeholders)");
    $stmt->execute($foundIds);
    $deletedCount = $stmt->rowCount();

    $db->commit();

    echo json_encode([
        'success' => true,
        'deleted_count' => $deletedCount,
        'requested_count' => count($ids),
        'processed_ids' => $foundIds,
        'leads_reverted_count' => $leadRevertedCount,
    ]);
} catch (Exception $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Bulk delete contacts error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting contacts']);
}


