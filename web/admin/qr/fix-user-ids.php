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
    $doFix = isset($_GET['fix']) && $_GET['fix'] === '1';

    // Find rows where user_id is not a UUID (previously stored as 0 or numeric)
    $bad = $pdo->query("SELECT id, user_id, type, status, created_at FROM custom_qr_codes WHERE user_id REGEXP '^[0-9]+$' OR user_id = '' OR user_id IS NULL")->fetchAll();

    if ($doFix && $currentUserId && $bad) {
        $stmt = $pdo->prepare("UPDATE custom_qr_codes SET user_id = ? WHERE id = ?");
        foreach ($bad as $row) {
            $stmt->execute([$currentUserId, $row['id']]);
        }
        header('Location: /admin/qr/fix-user-ids.php');
        exit;
    }
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
    <title>Fix QR User IDs</title>
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
        <h1>Fix Custom QR User IDs</h1>
        <p>Current admin user: <code><?php echo htmlspecialchars((string)$currentUserId); ?></code></p>
        <?php if (!$bad): ?>
            <p>No rows require fixing.</p>
        <?php else: ?>
            <p>Found <?php echo count($bad); ?> QR rows with numeric/empty user_id. You can reassign them to the current admin user.</p>
            <p><a class="btn" href="/admin/qr/fix-user-ids.php?fix=1" onclick="return confirm('Reassign all listed QR codes to your user ID?')">Fix Now</a></p>
            <table>
                <thead>
                    <tr><th>ID</th><th>User ID</th><th>Type</th><th>Status</th><th>Created</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($bad as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['id']); ?></td>
                            <td><?php echo htmlspecialchars($r['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($r['type']); ?></td>
                            <td><?php echo htmlspecialchars($r['status']); ?></td>
                            <td><?php echo htmlspecialchars($r['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>


