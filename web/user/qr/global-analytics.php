<?php
require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

UserAuth::requireAuth();
$db = Database::getInstance();
$userId = UserAuth::getUserId();

// Get date range
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
$days = in_array($days, [7, 14, 30, 60]) ? $days : 30;

// Get global analytics for all user's QR codes
$qrIdRows = $db->query("SELECT id FROM custom_qr_codes WHERE user_id = ?", [$userId]);
$qrIds = array_map(function($r){ return $r['id']; }, $qrIdRows);

// Debug: Check what data we have
if (!empty($qrIds)) {
    $place = str_repeat('?,', count($qrIds) - 1) . '?';
    $debugEvents = $db->query("SELECT COUNT(*) as total_events FROM custom_qr_events WHERE qr_id IN ($place)", $qrIds);
    echo "<!-- Debug: Total events found: " . ($debugEvents[0]['total_events'] ?? 0) . " -->\n";
} else {
    echo "<!-- Debug: No QR IDs for user -->\n";
}

if (empty($qrIds)) {
    $totals = [
        'total_views' => 0,
        'unique_visitors' => 0,
        'total_redirects' => 0,
        'total_leads' => 0
    ];
    $events = [];
    $devices = [];
    $browsers = [];
    $geo = [];
    $recent = [];
} else {
    $placeholders = str_repeat('?,', count($qrIds) - 1) . '?';
    
    // Get totals
    $totals = $db->querySingle("
        SELECT 
            COUNT(*) as total_views,
            COUNT(DISTINCT ip_address) as unique_visitors,
            SUM(CASE WHEN event = 'redirect' THEN 1 ELSE 0 END) as total_redirects,
            (SELECT COUNT(*) FROM qr_leads WHERE qr_id IN ($placeholders)) as total_leads
        FROM custom_qr_events 
        WHERE qr_id IN ($placeholders) 
        AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    ", array_merge($qrIds, $qrIds, [$days]));

    // Get daily events for chart
    $events = $db->query("
        SELECT 
            DATE(created_at) as d,
            COUNT(*) as views,
            SUM(CASE WHEN event = 'redirect' THEN 1 ELSE 0 END) as redirects,
            (SELECT COUNT(*) FROM qr_leads ql WHERE ql.qr_id = custom_qr_events.qr_id AND DATE(ql.created_at) = DATE(custom_qr_events.created_at)) as leads
        FROM custom_qr_events 
        WHERE qr_id IN ($placeholders) 
        AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY DATE(created_at)
        ORDER BY d ASC
    ", array_merge($qrIds, [$days]));

    // Backfill the last N days with zeros so chart shows a full series
    $byDate = [];
    foreach ($events as $row) {
        $byDate[$row['d']] = [
            'views' => (int)$row['views'],
            'redirects' => (int)$row['redirects'],
            'leads' => (int)$row['leads'],
        ];
    }
    $filled = [];
    $start = new DateTime(date('Y-m-d', strtotime('-' . ($days - 1) . ' days')));
    $end = new DateTime(date('Y-m-d')); // inclusive
    for ($d = $start; $d <= $end; $d->modify('+1 day')) {
        $key = $d->format('Y-m-d');
        $filled[] = [
            'd' => $key,
            'views' => $byDate[$key]['views'] ?? 0,
            'redirects' => $byDate[$key]['redirects'] ?? 0,
            'leads' => $byDate[$key]['leads'] ?? 0,
        ];
    }
    $events = $filled;

    // Get device types (parse from user_agent for now)
    $devices = $db->query("
        SELECT 
            CASE 
                WHEN user_agent LIKE '%Mobile%' OR user_agent LIKE '%Android%' OR user_agent LIKE '%iPhone%' THEN 'Mobile'
                WHEN user_agent LIKE '%Tablet%' OR user_agent LIKE '%iPad%' THEN 'Tablet'
                WHEN user_agent LIKE '%Windows%' OR user_agent LIKE '%Macintosh%' OR user_agent LIKE '%Linux%' THEN 'Desktop'
                ELSE 'Unknown'
            END as device_type, 
            COUNT(*) as count
        FROM custom_qr_events 
        WHERE qr_id IN ($placeholders) 
        AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY 
            CASE 
                WHEN user_agent LIKE '%Mobile%' OR user_agent LIKE '%Android%' OR user_agent LIKE '%iPhone%' THEN 'Mobile'
                WHEN user_agent LIKE '%Tablet%' OR user_agent LIKE '%iPad%' THEN 'Tablet'
                WHEN user_agent LIKE '%Windows%' OR user_agent LIKE '%Macintosh%' OR user_agent LIKE '%Linux%' THEN 'Desktop'
                ELSE 'Unknown'
            END
        ORDER BY count DESC
    ", array_merge($qrIds, [$days]));

    // Get browsers (parse from user_agent for now)
    $browsers = $db->query("
        SELECT 
            CASE 
                WHEN user_agent LIKE '%Chrome%' AND user_agent NOT LIKE '%Edge%' THEN 'Chrome'
                WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
                WHEN user_agent LIKE '%Safari%' AND user_agent NOT LIKE '%Chrome%' THEN 'Safari'
                WHEN user_agent LIKE '%Edge%' THEN 'Edge'
                WHEN user_agent LIKE '%Opera%' THEN 'Opera'
                ELSE 'Other'
            END as browser, 
            COUNT(*) as count
        FROM custom_qr_events 
        WHERE qr_id IN ($placeholders) 
        AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY 
            CASE 
                WHEN user_agent LIKE '%Chrome%' AND user_agent NOT LIKE '%Edge%' THEN 'Chrome'
                WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
                WHEN user_agent LIKE '%Safari%' AND user_agent NOT LIKE '%Chrome%' THEN 'Safari'
                WHEN user_agent LIKE '%Edge%' THEN 'Edge'
                WHEN user_agent LIKE '%Opera%' THEN 'Opera'
                ELSE 'Other'
            END
        ORDER BY count DESC
    ", array_merge($qrIds, [$days]));

    // Get geographic distribution (group by city,country when available)
    $geo = $db->query("
        SELECT 
            COALESCE(NULLIF(CONCAT_WS(', ', city, country), ''), 'Unknown') as location,
            COUNT(*) as count
        FROM custom_qr_events 
        WHERE qr_id IN ($placeholders) 
        AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY COALESCE(NULLIF(CONCAT_WS(', ', city, country), ''), 'Unknown')
        ORDER BY count DESC
    ", array_merge($qrIds, [$days]));

    // Get recent events
    $recent = $db->query("
        SELECT 
            cqe.created_at,
            cqe.event,
            cqe.event_target,
            cqe.ip_address,
            cqe.referrer,
            cqc.title as qr_title
        FROM custom_qr_events cqe
        JOIN custom_qr_codes cqc ON cqe.qr_id = cqc.id
        WHERE cqe.qr_id IN ($placeholders) 
        ORDER BY cqe.created_at DESC
        LIMIT 20
    ", $qrIds);
}

// Get QR codes list for context
$qrCodes = $db->query("
    SELECT id, title, type, status, created_at
    FROM custom_qr_codes 
    WHERE user_id = ? 
    ORDER BY created_at DESC
", [$userId]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global QR Analytics</title>
    <link rel="stylesheet" href="/user/includes/user-style.css">
    <style>
        body {
            background: #f5f5f5;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            padding: 20px 0;
            margin-bottom: 20px;
        }
        
        .page-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            text-align: center;
            color: #333;
        }
        
        .analytics-context {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .analytics-context h2 {
            margin: 0 0 15px;
            color: #333;
            font-size: 18px;
        }
        
        .context-info {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            align-items: center;
        }
        
        .context-item {
            display: flex;
            flex-direction: column;
        }
        
        .context-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .context-value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        
        .date-selector {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .date-selector select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
        }
        
        .export-btn {
            background: #2d5a27;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .analytics-charts {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .chart-container.half-width {
            grid-column: span 1;
        }
        
        .chart-container h3 {
            margin: 0 0 20px;
            color: #333;
            font-size: 18px;
        }
        
        .geo-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .geo-table th,
        .geo-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .geo-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .recent-events {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .recent-events h3 {
            margin: 0 0 20px;
            color: #333;
            font-size: 18px;
        }
        
        .events-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .events-table th,
        .events-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .events-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .event-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .event-badge.view {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .event-badge.redirect {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .event-badge.lead {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .muted {
            color: #999;
            font-size: 12px;
        }
        
        .qr-codes-list {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .qr-codes-list h3 {
            margin: 0 0 20px;
            color: #333;
            font-size: 18px;
        }
        
        .qr-codes-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .qr-codes-table th,
        .qr-codes-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .qr-codes-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .qr-codes-table a {
            color: #2d5a27;
            text-decoration: none;
        }
        
        .qr-codes-table a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navigation.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1>Global QR Analytics</h1>
        </div>
    </div>
    
    <div class="container">
        <div class="analytics-context">
            <h2>Viewing Analytics For All QR Codes:</h2>
            <div class="context-info">
                <div class="context-item">
                    <div class="context-label">Total QR Codes</div>
                    <div class="context-value"><?php echo count($qrCodes); ?></div>
                </div>
                <div class="context-item">
                    <div class="context-label">Active QR Codes</div>
                    <div class="context-value"><?php echo count(array_filter($qrCodes, function($qr) { return $qr['status'] === 'active'; })); ?></div>
                </div>
                <div class="context-item">
                    <div class="date-selector">
                        <span class="context-label">Time Period:</span>
                        <select onchange="window.location.href='?days='+this.value">
                            <option value="7" <?php echo $days == 7 ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="14" <?php echo $days == 14 ? 'selected' : ''; ?>>Last 14 Days</option>
                            <option value="30" <?php echo $days == 30 ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="60" <?php echo $days == 60 ? 'selected' : ''; ?>>Last 60 Days</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $totals['total_views'] ?? 0; ?></div>
                <div class="stat-label">QR Scans</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $totals['unique_visitors'] ?? 0; ?></div>
                <div class="stat-label">Unique Visitors</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $totals['total_redirects'] ?? 0; ?></div>
                <div class="stat-label">Redirects</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $totals['total_leads'] ?? 0; ?></div>
                <div class="stat-label">Lead Submissions</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count($qrCodes); ?></div>
                <div class="stat-label">Total QR Codes</div>
            </div>
        </div>

        <div class="analytics-charts">
            <!-- Views Over Time -->
            <div class="chart-container">
                <h3>Views Over Time</h3>
                <canvas id="viewsChart"></canvas>
            </div>
            
            <!-- Device Types -->
            <div class="chart-container half-width">
                <h3>Device Types</h3>
                <canvas id="deviceChart"></canvas>
            </div>
            
            <!-- Browsers -->
            <div class="chart-container half-width">
                <h3>Browsers</h3>
                <canvas id="browserChart"></canvas>
            </div>
            
            <!-- Geographic Distribution -->
            <div class="chart-container">
                <h3>Geographic Distribution</h3>
                <table class="geo-table">
                    <thead>
                        <tr><th>Location</th><th>Views</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($geo as $g): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($g['location'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo $g['count']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="recent-events">
            <h3>Recent Events</h3>
            <table class="events-table">
                <thead>
                    <tr><th>Time</th><th>Event</th><th>QR Code</th><th>Target</th><th>IP Address</th><th>Referrer</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="event-badge <?php echo htmlspecialchars($r['event'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($r['event'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo htmlspecialchars($r['qr_title'] ?: 'Untitled', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($r['event_target'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="muted"><?php echo htmlspecialchars($r['ip_address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="muted"><?php echo htmlspecialchars($r['referrer'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- QR codes list removed on request -->
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Views over time data
        const viewsData = <?php echo json_encode(array_map(function($e) { return ['date' => $e['d'], 'views' => (int)$e['views'], 'redirects' => (int)$e['redirects'], 'leads' => (int)$e['leads']]; }, $events)); ?>;
        
        // Views over time chart
        const viewsCtx = document.getElementById('viewsChart').getContext('2d');
        new Chart(viewsCtx, {
            type: 'line',
            data: {
                labels: viewsData.map(d => new Date(d.date).toLocaleDateString()),
                datasets: [{
                    label: 'QR Scans',
                    data: viewsData.map(d => d.views),
                    borderColor: '#1976d2',
                    backgroundColor: 'rgba(25, 118, 210, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Redirects',
                    data: viewsData.map(d => d.redirects),
                    borderColor: '#7b1fa2',
                    backgroundColor: 'rgba(123, 31, 162, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Lead Submissions',
                    data: viewsData.map(d => d.leads),
                    borderColor: '#c2185b',
                    backgroundColor: 'rgba(194, 24, 91, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Device types data
        const deviceData = <?php echo json_encode($devices); ?>;
        const deviceCtx = document.getElementById('deviceChart').getContext('2d');
        new Chart(deviceCtx, {
            type: 'doughnut',
            data: {
                labels: deviceData.map(d => d.device_type),
                datasets: [{
                    data: deviceData.map(d => d.count),
                    backgroundColor: [
                        '#1976d2',
                        '#7b1fa2',
                        '#388e3c',
                        '#f57c00',
                        '#d32f2f'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });

        // Browsers data
        const browserData = <?php echo json_encode($browsers); ?>;
        const browserCtx = document.getElementById('browserChart').getContext('2d');
        new Chart(browserCtx, {
            type: 'doughnut',
            data: {
                labels: browserData.map(b => b.browser),
                datasets: [{
                    data: browserData.map(b => b.count),
                    backgroundColor: [
                        '#1976d2',
                        '#7b1fa2',
                        '#388e3c',
                        '#f57c00',
                        '#d32f2f'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    </script>
</body>
</html>
