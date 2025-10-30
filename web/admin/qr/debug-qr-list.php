<?php
require_once __DIR__ . '/../../config/secure-config.php';
require_once __DIR__ . '/../../user/includes/UserAuth.php';

UserAuth::requireAuth();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $currentUserId = $_SESSION['user_id'] ?? null;
    $allQrs = $pdo->query("SELECT id, user_id, type, status, title, created_at FROM custom_qr_codes ORDER BY created_at DESC")->fetchAll();
    $userQrs = $pdo->prepare("SELECT id, user_id, type, status, title, created_at FROM custom_qr_codes WHERE user_id = ? ORDER BY created_at DESC");
    $userQrs->execute([$currentUserId]);
    $userQrs = $userQrs->fetchAll();

} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug QR List</title>
    <link rel="stylesheet" href="/user/includes/user-style.css">
    <style>
        .container { max-width: 900px; margin: 30px auto; background: #fff; border-radius: 12px; box-shadow: 0 10px 24px rgba(0,0,0,.08); padding: 24px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #eee; text-align: left; }
        .btn { display: inline-block; padding: 10px 14px; border-radius: 8px; text-decoration: none; background:#667eea; color:#fff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Debug QR List</h1>
        <p>Current user ID: <code><?php echo htmlspecialchars((string)$currentUserId); ?></code></p>
        
        <h2>All QR Codes (<?php echo count($allQrs); ?>)</h2>
        <table>
            <thead>
                <tr><th>ID</th><th>User ID</th><th>Type</th><th>Status</th><th>Title</th><th>Created</th></tr>
            </thead>
            <tbody>
                <?php foreach ($allQrs as $r): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['id']); ?></td>
                        <td><?php echo htmlspecialchars($r['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($r['type']); ?></td>
                        <td><?php echo htmlspecialchars($r['status']); ?></td>
                        <td><?php echo htmlspecialchars($r['title'] ?: '(Untitled)'); ?></td>
                        <td><?php echo htmlspecialchars($r['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2>Your QR Codes (<?php echo count($userQrs); ?>)</h2>
        <table>
            <thead>
                <tr><th>ID</th><th>User ID</th><th>Type</th><th>Status</th><th>Title</th><th>Created</th></tr>
            </thead>
            <tbody>
                <?php foreach ($userQrs as $r): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['id']); ?></td>
                        <td><?php echo htmlspecialchars($r['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($r['type']); ?></td>
                        <td><?php echo htmlspecialchars($r['status']); ?></td>
                        <td><?php echo htmlspecialchars($r['title'] ?: '(Untitled)'); ?></td>
                        <td><?php echo htmlspecialchars($r['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p><a class="btn" href="/user/qr/">Back to QR List</a></p>
    </div>
</body>
</html>
