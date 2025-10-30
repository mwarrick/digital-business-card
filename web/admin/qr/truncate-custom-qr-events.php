<?php
require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../includes/AdminAuth.php';

AdminAuth::requireAdmin();

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    try {
        $db->getConnection()->exec('TRUNCATE TABLE custom_qr_events');
        echo 'OK: custom_qr_events truncated';
    } catch (Throwable $e) {
        http_response_code(500);
        echo 'Error: ' . htmlspecialchars($e->getMessage());
    }
    exit;
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Truncate Custom QR Analytics</title></head>
<body>
    <h1>Truncate Custom QR Analytics</h1>
    <p>This will permanently delete all analytics in custom_qr_events.</p>
    <form method="post">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" style="background:#c00;color:#fff;padding:10px 16px;border:none;border-radius:6px;cursor:pointer;">Confirm Truncate</button>
    </form>
</body>
</html>
