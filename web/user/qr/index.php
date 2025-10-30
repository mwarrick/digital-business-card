<?php
require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';
require_once __DIR__ . '/../../api/includes/qr/Generator.php';

UserAuth::requireAuth();
$db = Database::getInstance();
$userId = UserAuth::getUserId();

$rows = $db->query(
    "SELECT id, title, type, status, created_at FROM custom_qr_codes WHERE user_id = ? ORDER BY created_at DESC",
    [$userId]
);
$rowCount = is_array($rows) ? count($rows) : (empty($rows) ? 0 : 0);

$createdId = $_GET['created'] ?? null;
$deleted = isset($_GET['deleted']) && $_GET['deleted'] === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom QR Codes</title>
    <link rel="stylesheet" href="/user/includes/user-style.css">
    <style>
        .container { max-width: 1100px; margin: 30px auto; background: #fff; border-radius: 12px; box-shadow: 0 10px 24px rgba(0,0,0,.08); padding: 24px; }
        h1 { margin: 0 0 12px; }
        .actions { margin: 10px 0 20px; }
        .btn { display: inline-block; padding: 10px 14px; border-radius: 8px; text-decoration: none; }
        .btn-primary { background: #667eea; color: #fff; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #eee; text-align: left; }
        th { color: #555; font-weight: 600; }
        .muted { color: #777; font-size: 12px; }
        .badge { display:inline-block; padding: 2px 8px; border-radius: 999px; background:#f3f4ff; color:#444; font-size: 12px; }
        .empty { padding: 30px; text-align: center; color: #666; }
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 16px; border: 1px solid #c3e6cb; }
        .qr-thumb { width: 80px; height: 80px; object-fit: contain; display:block; }
        .qr-actions { margin-top: 6px; font-size: 12px; }
        .qr-actions a { color:#667eea; margin-right:8px; text-decoration:none; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navigation.php'; ?>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 style="margin: 0;">Custom QR Codes</h1>
            <a href="/user/qr/global-analytics.php" class="btn" style="background: #2d5a27; color: white; margin-right: 10px;">Global Analytics</a>
        </div>
        <?php if ($deleted): ?>
            <div class="success">🗑️ QR code and analytics deleted.</div>
        <?php endif; ?>
        
        <?php if ($createdId): ?>
            <div class="success">
                ✅ QR code created successfully! 
                <a href="/user/qr/edit.php?id=<?php echo urlencode($createdId); ?>">Edit it here</a> or 
                <a href="/qr/<?php echo urlencode($createdId); ?>" target="_blank">view the public page</a>.
            </div>
        <?php endif; ?>
        
        <div class="actions">
            <a class="btn btn-primary" href="/user/qr/create.php">➕ New QR Code</a>
        </div>
        <?php if (!$rows || (is_array($rows) && count($rows)===0)): ?>
            <div class="empty">No QR codes yet. Click “New QR Code” to create your first one.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>QR</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Share</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <?php
                                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                $host = $_SERVER['HTTP_HOST'] ?? 'sharemycard.app';
                                $publicUrl = $scheme . '://' . $host . '/qr/' . urlencode($r['id']);
                                $img = \QRCard\QR\Generator::buildImageUrl($publicUrl, 120, 'png');
                            ?>
                            <td>
                                <img class="qr-thumb" src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" alt="QR">
                                <div class="qr-actions">
                                    <a href="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" download>Download PNG</a>
                                    <a href="<?php echo htmlspecialchars(\QRCard\QR\Generator::buildImageUrl($publicUrl, 120, 'svg'), ENT_QUOTES, 'UTF-8'); ?>" download>SVG</a>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($r['title'] ?: '(Untitled)', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="badge"><?php echo htmlspecialchars($r['type'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="muted"><?php echo htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><a href="/qr/<?php echo urlencode($r['id']); ?>" target="_blank">Open</a></td>
                            <td>
                                <a href="/user/qr/edit.php?id=<?php echo urlencode($r['id']); ?>">Edit</a> ·
                                <a href="/user/qr/analytics.php?id=<?php echo urlencode($r['id']); ?>">Analytics</a> ·
                                <a href="#" class="delete-link" data-id="<?php echo htmlspecialchars($r['id'], ENT_QUOTES, 'UTF-8'); ?>">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <div id="delete-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:12px;max-width:420px;width:92%;padding:18px;box-shadow:0 10px 24px rgba(0,0,0,.2)">
            <h3 style="margin:0 0 8px;">Delete QR Code?</h3>
            <p style="margin:0 0 12px;color:#444;">This will permanently delete this QR code and all its analytics. This action cannot be undone.</p>
            <form method="post" action="/user/qr/delete.php" id="delete-form">
                <input type="hidden" name="id" id="delete-id" value="">
                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px;">
                    <button type="button" id="cancel-delete" class="btn">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background:#e53935;">Delete</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    (function(){
        const modal = document.getElementById('delete-modal');
        const input = document.getElementById('delete-id');
        const cancel = document.getElementById('cancel-delete');
        document.querySelectorAll('.delete-link').forEach(a => {
            a.addEventListener('click', function(e){
                e.preventDefault();
                input.value = this.dataset.id;
                modal.style.display = 'flex';
            });
        });
        cancel && cancel.addEventListener('click', function(){ modal.style.display = 'none'; });
        modal && modal.addEventListener('click', function(e){ if (e.target === modal) modal.style.display = 'none'; });
    })();
    </script>
</body>
</html>


