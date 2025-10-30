<?php
require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

UserAuth::requireAuth();
$db = Database::getInstance();
$userId = UserAuth::getUserId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$id = $_POST['id'] ?? '';
if (!$id) {
    http_response_code(400);
    echo 'Missing id';
    exit;
}

// Verify ownership
$row = $db->querySingle("SELECT id FROM custom_qr_codes WHERE id = ? AND user_id = ?", [$id, $userId]);
if (!$row) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

// Delete children first, then the parent
try {
    $db->beginTransaction();
    // custom_qr_events
    $db->execute("DELETE FROM custom_qr_events WHERE qr_id = ?", [$id]);
    // qr_leads mapping (if used)
    $db->execute("DELETE FROM qr_leads WHERE qr_id = ?", [$id]);
    // finally the QR itself
    $db->execute("DELETE FROM custom_qr_codes WHERE id = ? AND user_id = ?", [$id, $userId]);
    $db->commit();
} catch (Throwable $e) {
    if ($db->inTransaction()) { $db->rollBack(); }
    error_log('QR delete failed: ' . $e->getMessage());
    http_response_code(500);
    echo 'Delete failed';
    exit;
}

header('Location: /user/qr/?deleted=1');
exit;
?>


