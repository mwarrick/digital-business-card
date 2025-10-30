<?php
require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

UserAuth::requireAuth();
$db = Database::getInstance();
$userId = UserAuth::getUserId();

$rows = $db->query(
    "SELECT id, user_id, type, status, title, created_at FROM custom_qr_codes WHERE user_id = ? ORDER BY created_at DESC",
    [$userId]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your QR Codes (Debug)</title>
    <link rel="stylesheet" href="/user/includes/user-style.css">
    <style>
        .container { max-width: 900px; margin: 30px auto; background: #fff; border-radius: 12px; box-shadow: 0 10px 24px rgba(0,0,0,.08); padding: 24px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #eee; text-align: left; }
        .muted { color:#666; font-size: 12px; }
        .btn { display:inline-block; padding:8px 12px; border-radius:8px; background:#667eea; color:#fff; text-decoration:none; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navigation.php'; ?>
    <div class="container">
        <h1>Your QR Codes (Debug)</h1>
        <p class="muted">Current user ID: <code><?php echo htmlspecialchars((string)$userId); ?></code></p>
        <?php if (!$rows): ?>
            <p>No QR codes found for your user.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Title</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['id']); ?></td>
                            <td><?php echo htmlspecialchars($r['type']); ?></td>
                            <td><?php echo htmlspecialchars($r['status']); ?></td>
                            <td><?php echo htmlspecialchars($r['title'] ?: '(Untitled)'); ?></td>
                            <td><?php echo htmlspecialchars($r['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <p style="margin-top:12px;"><a class="btn" href="/user/qr/">Back</a></p>
    </div>
</body>
</html>
