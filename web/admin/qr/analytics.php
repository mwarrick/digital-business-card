<?php
require_once __DIR__ . '/../includes/AdminAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

AdminAuth::requireAdmin();
$db = Database::getInstance();

$days = max(1, min(60, intval($_GET['days'] ?? 14)));

$agg = $db->querySingle(
    "SELECT 
        SUM(event='view') as views,
        SUM(event='redirect') as redirects,
        SUM(event='lead_submit') as leads
     FROM custom_qr_events 
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)",
    [$days]
);

$top = $db->query(
    "SELECT qr_id, COUNT(*) as cnt FROM custom_qr_events 
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND event='view'
     GROUP BY qr_id ORDER BY cnt DESC LIMIT 20",
    [$days]
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – QR Analytics</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background:#f6f7fb; padding: 24px; }
        .container { max-width: 1100px; margin: 0 auto; background:#fff; border-radius:12px; box-shadow:0 10px 24px rgba(0,0,0,.08); padding: 24px; }
        h1 { margin:0 0 12px; }
        .summary { display:grid; grid-template-columns: repeat(3, 1fr); gap:10px; margin: 12px 0; }
        .card { background:#f8fafc; border:1px solid #edf2f7; padding:12px; border-radius:8px; }
        table { width:100%; border-collapse: collapse; }
        th, td { padding:8px; border-bottom:1px solid #eee; text-align:left; }
        a { color:#667eea; text-decoration:none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Global QR Analytics</h1>
        <div>Last <?php echo $days; ?> days · <a href="?days=7">7d</a> · <a href="?days=14">14d</a> · <a href="?days=30">30d</a></div>
        <div class="summary">
            <div class="card"><strong>Views</strong><div><?php echo (int)($agg['views'] ?? 0); ?></div></div>
            <div class="card"><strong>Redirects</strong><div><?php echo (int)($agg['redirects'] ?? 0); ?></div></div>
            <div class="card"><strong>Leads</strong><div><?php echo (int)($agg['leads'] ?? 0); ?></div></div>
        </div>

        <h3>Top QR Codes (by views)</h3>
        <table>
            <thead><tr><th>QR ID</th><th>Views</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($top as $t): ?>
                <tr>
                    <td><?php echo htmlspecialchars($t['qr_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo (int)$t['cnt']; ?></td>
                    <td>
                        <a href="/qr/<?php echo urlencode($t['qr_id']); ?>" target="_blank">Open</a> ·
                        <a href="/user/qr/analytics.php?id=<?php echo urlencode($t['qr_id']); ?>" target="_blank">User Analytics</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div style="margin-top:10px;"><a href="/admin/qr/">← Back to QR list</a></div>
    </div>
</body>
</html>


