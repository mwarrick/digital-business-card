<?php
require_once __DIR__ . '/../includes/AdminAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../api/includes/qr/Generator.php';

AdminAuth::requireAdmin();
$db = Database::getInstance();

$q = trim($_GET['q'] ?? '');
$params = [];
$sql = "SELECT c.id, c.title, c.type, c.status, c.user_id, c.created_at, u.email 
        FROM custom_qr_codes c 
        LEFT JOIN users u ON u.id = c.user_id 
        WHERE 1=1";
if ($q !== '') {
    $sql .= " AND (c.id LIKE ? OR c.title LIKE ? OR u.email LIKE ?)";
    $like = "%$q%";
    $params = [$like, $like, $like];
}
$sql .= " ORDER BY c.created_at DESC LIMIT 200";
$rows = $db->query($sql, $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – Custom QR Codes</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background:#f6f7fb; padding: 24px; }
        .container { max-width: 1200px; margin: 0 auto; background:#fff; border-radius:12px; box-shadow:0 10px 24px rgba(0,0,0,.08); padding: 24px; }
        h1 { margin:0 0 12px; }
        form { margin-bottom: 12px; }
        input[type=text] { padding: 8px 10px; border:1px solid #e5e7eb; border-radius:8px; width: 280px; }
        table { width:100%; border-collapse: collapse; }
        th, td { padding: 8px; border-bottom:1px solid #eee; text-align:left; }
        .badge { display:inline-block; padding:2px 8px; border-radius:999px; background:#eef2ff; color:#333; font-size:12px; }
        a { color:#667eea; text-decoration:none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Custom QR Codes</h1>
        <form method="get">
            <input type="text" name="q" value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search by id, title, owner email">
            <button type="submit">Search</button>
            <a href="/admin/qr/global-analytics.php" style="margin-left:10px;">Global Analytics</a>
        </form>
        <table>
            <thead><tr><th>QR</th><th>Title</th><th>Type</th><th>Status</th><th>Owner</th><th>Created</th><th>Public</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <?php
                        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $host = $_SERVER['HTTP_HOST'] ?? 'sharemycard.app';
                        $publicUrl = $scheme . '://' . $host . '/qr/' . urlencode($r['id']);
                        $img = \QRCard\QR\Generator::buildImageUrl($publicUrl, 100, 'png');
                    ?>
                    <td>
                        <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" alt="QR" style="width:64px;height:64px;object-fit:contain;display:block">
                        <div style="margin-top:4px;font-size:12px">
                            <a href="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" download>PNG</a>
                            ·
                            <a href="<?php echo htmlspecialchars(\QRCard\QR\Generator::buildImageUrl($publicUrl, 120, 'svg'), ENT_QUOTES, 'UTF-8'); ?>" download>SVG</a>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($r['title'] ?: '(Untitled)', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><span class="badge"><?php echo htmlspecialchars($r['type'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td><?php echo htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($r['email'] ?: ('User#'.$r['user_id']), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><a href="/qr/<?php echo urlencode($r['id']); ?>" target="_blank">Open</a></td>
                    <td><a href="/user/qr/edit.php?id=<?php echo urlencode($r['id']); ?>" target="_blank">Edit</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>


