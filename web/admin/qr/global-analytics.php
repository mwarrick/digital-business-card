<?php
require_once __DIR__ . '/../includes/AdminAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

AdminAuth::requireAdmin();
$db = Database::getInstance();

// Date range (last 30 days default)
$days = max(1, min(90, intval($_GET['days'] ?? 30)));

// Global stats
$globalStats = $db->querySingle(
    "SELECT 
        COUNT(DISTINCT c.id) as total_qr_codes,
        COUNT(e.id) as total_views,
        COUNT(DISTINCT e.ip_address) as unique_visitors,
        SUM(e.event='redirect') as total_redirects,
        SUM(e.event='lead_submit') as total_leads
     FROM custom_qr_codes c
     LEFT JOIN custom_qr_events e ON c.id = e.qr_id 
     WHERE e.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) OR e.created_at IS NULL",
    [$days]
);

// Top performing QR codes
$topQrs = $db->query(
    "SELECT 
        c.id, c.title, c.type, c.user_id, u.email,
        COUNT(e.id) as view_count,
        SUM(e.event='redirect') as redirect_count,
        SUM(e.event='lead_submit') as lead_count
     FROM custom_qr_codes c
     LEFT JOIN custom_qr_events e ON c.id = e.qr_id AND e.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
     LEFT JOIN users u ON u.id = c.user_id
     GROUP BY c.id, c.title, c.type, c.user_id, u.email
     ORDER BY view_count DESC
     LIMIT 20",
    [$days]
);

// Device types across all QR codes
$devices = $db->query(
    "SELECT 
        CASE 
            WHEN user_agent LIKE '%iPhone%' OR user_agent LIKE '%iPad%' THEN 'iOS'
            WHEN user_agent LIKE '%Android%' THEN 'Android'
            WHEN user_agent LIKE '%Windows%' THEN 'Windows'
            WHEN user_agent LIKE '%Mac%' THEN 'Mac'
            WHEN user_agent LIKE '%Linux%' THEN 'Linux'
            ELSE 'Other'
        END as device_type,
        COUNT(*) as count
     FROM custom_qr_events e
     JOIN custom_qr_codes c ON c.id = e.qr_id
     WHERE e.event='view' AND e.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
     GROUP BY device_type ORDER BY count DESC",
    [$days]
);

// Browsers across all QR codes
$browsers = $db->query(
    "SELECT 
        CASE 
            WHEN user_agent LIKE '%Chrome%' THEN 'Chrome'
            WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
            WHEN user_agent LIKE '%Safari%' AND user_agent NOT LIKE '%Chrome%' THEN 'Safari'
            WHEN user_agent LIKE '%Edge%' THEN 'Edge'
            WHEN user_agent LIKE '%Opera%' THEN 'Opera'
            ELSE 'Other'
        END as browser,
        COUNT(*) as count
     FROM custom_qr_events e
     JOIN custom_qr_codes c ON c.id = e.qr_id
     WHERE e.event='view' AND e.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
     GROUP BY browser ORDER BY count DESC",
    [$days]
);

// QR type distribution
$qrTypes = $db->query(
    "SELECT 
        type,
        COUNT(*) as count,
        COUNT(e.id) as total_views
     FROM custom_qr_codes c
     LEFT JOIN custom_qr_events e ON c.id = e.qr_id AND e.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
     GROUP BY type ORDER BY count DESC",
    [$days]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global QR Analytics</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background:#f6f7fb; padding: 24px; }
        .container { max-width: 1200px; margin: 0 auto; background:#fff; border-radius:12px; box-shadow:0 10px 24px rgba(0,0,0,.08); padding: 24px; }
        h1 { margin:0 0 12px; }
        .muted{ color:#666; }
        table { width:100%; border-collapse: collapse; margin-top:14px; }
        th, td { padding: 8px; border-bottom:1px solid #eee; text-align:left; }
        .summary { display:grid; grid-template-columns: repeat(5, 1fr); gap:10px; margin: 12px 0; }
        .card { background:#f8fafc; border:1px solid #edf2f7; padding:12px; border-radius:8px; }
        .badge { display:inline-block; padding:2px 8px; border-radius:999px; background:#eef2ff; color:#333; font-size:12px; }
        a { color:#667eea; text-decoration:none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Global Custom QR Code Analytics</h1>
        <div class="muted">Last <?php echo $days; ?> days · <a href="?days=7">7d</a> · <a href="?days=30">30d</a> · <a href="?days=90">90d</a></div>

        <div class="summary">
            <div class="card"><strong>Total QR Codes</strong><div><?php echo $globalStats['total_qr_codes'] ?? 0; ?></div></div>
            <div class="card"><strong>Total Views</strong><div><?php echo $globalStats['total_views'] ?? 0; ?></div></div>
            <div class="card"><strong>Unique Visitors</strong><div><?php echo $globalStats['unique_visitors'] ?? 0; ?></div></div>
            <div class="card"><strong>Total Redirects</strong><div><?php echo $globalStats['total_redirects'] ?? 0; ?></div></div>
            <div class="card"><strong>Total Leads</strong><div><?php echo $globalStats['total_leads'] ?? 0; ?></div></div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
            <div>
                <h3>Device Types</h3>
                <table>
                    <thead><tr><th>Device</th><th>Views</th></tr></thead>
                    <tbody>
                        <?php foreach ($devices as $d): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($d['device_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo $d['count']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div>
                <h3>Browsers</h3>
                <table>
                    <thead><tr><th>Browser</th><th>Views</th></tr></thead>
                    <tbody>
                        <?php foreach ($browsers as $b): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($b['browser'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo $b['count']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="margin: 20px 0;">
            <h3>QR Type Distribution</h3>
            <table>
                <thead><tr><th>Type</th><th>Count</th><th>Total Views</th></tr></thead>
                <tbody>
                    <?php foreach ($qrTypes as $t): ?>
                        <tr>
                            <td><span class="badge"><?php echo htmlspecialchars($t['type'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo $t['count']; ?></td>
                            <td><?php echo $t['total_views']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h3>Top Performing QR Codes</h3>
        <table>
            <thead><tr><th>Title</th><th>Type</th><th>Owner</th><th>Views</th><th>Redirects</th><th>Leads</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($topQrs as $qr): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($qr['title'] ?: '(Untitled)', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><span class="badge"><?php echo htmlspecialchars($qr['type'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><?php echo htmlspecialchars($qr['email'] ?: ('User#'.$qr['user_id']), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo $qr['view_count']; ?></td>
                        <td><?php echo $qr['redirect_count']; ?></td>
                        <td><?php echo $qr['lead_count']; ?></td>
                        <td>
                            <a href="/qr/<?php echo urlencode($qr['id']); ?>" target="_blank">View</a> ·
                            <a href="/user/qr/analytics.php?id=<?php echo urlencode($qr['id']); ?>" target="_blank">Analytics</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
