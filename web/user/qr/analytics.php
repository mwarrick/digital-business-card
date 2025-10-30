<?php
require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

UserAuth::requireAuth();
$db = Database::getInstance();
$userId = UserAuth::getUserId();

$id = $_GET['id'] ?? '';
$qr = $db->querySingle("SELECT id, title FROM custom_qr_codes WHERE id = ? AND user_id = ?", [$id, $userId]);
if (!$qr) {
    http_response_code(404);
    echo 'QR code not found';
    exit;
}

// Date range (last 14 days default)
$days = max(1, min(60, intval($_GET['days'] ?? 14)));

// Enhanced analytics queries
$events = $db->query(
    "SELECT DATE(created_at) as d, 
            SUM(event='view') as views,
            SUM(event='redirect') as redirects,
            SUM(event='lead_submit') as leads
     FROM custom_qr_events 
     WHERE qr_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
     GROUP BY DATE(created_at) ORDER BY d",
    [$id, $days]
);

// Backfill daily series to ensure continuous chart for selected range
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
$end = new DateTime(date('Y-m-d'));
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

// Total stats
$totals = $db->querySingle(
    "SELECT 
        COUNT(*) as total_views,
        COUNT(DISTINCT ip_address) as unique_visitors,
        SUM(event='redirect') as total_redirects,
        SUM(event='lead_submit') as total_leads
     FROM custom_qr_events 
     WHERE qr_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)",
    [$id, $days]
);

// Device types
$devices = $db->query(
    "SELECT 
        CASE 
            WHEN user_agent LIKE '%Mobile%' OR user_agent LIKE '%Android%' OR user_agent LIKE '%iPhone%' THEN 'Mobile'
            WHEN user_agent LIKE '%Tablet%' OR user_agent LIKE '%iPad%' THEN 'Tablet'
            WHEN user_agent LIKE '%Windows%' OR user_agent LIKE '%Macintosh%' OR user_agent LIKE '%Linux%' THEN 'Desktop'
            ELSE 'Unknown'
        END as device_type,
        COUNT(*) as count
     FROM custom_qr_events 
     WHERE qr_id = ? AND event='view' AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
     GROUP BY 
        CASE 
            WHEN user_agent LIKE '%Mobile%' OR user_agent LIKE '%Android%' OR user_agent LIKE '%iPhone%' THEN 'Mobile'
            WHEN user_agent LIKE '%Tablet%' OR user_agent LIKE '%iPad%' THEN 'Tablet'
            WHEN user_agent LIKE '%Windows%' OR user_agent LIKE '%Macintosh%' OR user_agent LIKE '%Linux%' THEN 'Desktop'
            ELSE 'Unknown'
        END 
     ORDER BY count DESC",
    [$id, $days]
);

// Browsers
$browsers = $db->query(
    "SELECT 
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
     WHERE qr_id = ? AND event='view' AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
     GROUP BY 
        CASE 
            WHEN user_agent LIKE '%Chrome%' AND user_agent NOT LIKE '%Edge%' THEN 'Chrome'
            WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
            WHEN user_agent LIKE '%Safari%' AND user_agent NOT LIKE '%Chrome%' THEN 'Safari'
            WHEN user_agent LIKE '%Edge%' THEN 'Edge'
            WHEN user_agent LIKE '%Opera%' THEN 'Opera'
            ELSE 'Other'
        END 
     ORDER BY count DESC",
    [$id, $days]
);

// Geographic distribution (city, country when available)
$geo = $db->query(
    "SELECT 
        COALESCE(NULLIF(CONCAT_WS(', ', city, country), ''), 'Unknown') as location,
        COUNT(*) as count
     FROM custom_qr_events 
     WHERE qr_id = ? AND event='view' AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
     GROUP BY COALESCE(NULLIF(CONCAT_WS(', ', city, country), ''), 'Unknown')
     ORDER BY count DESC",
    [$id, $days]
);

$recent = $db->query(
    "SELECT event, event_target, created_at, referrer, ip_address, user_agent FROM custom_qr_events WHERE qr_id = ? ORDER BY created_at DESC LIMIT 50",
    [$id]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Analytics</title>
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
        
        .date-selector-links {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .date-selector-links a {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 5px;
            background: #f0f0f0;
            color: #333;
            text-decoration: none;
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        
        .date-selector-links a:hover,
        .date-selector-links a.active {
            background: #667eea;
            color: white;
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
        
        @media (min-width: 768px) {
            .analytics-charts {
                grid-template-columns: 1fr 1fr;
            }
            
            .chart-container:first-child {
                grid-column: span 2;
            }
        }
        
        .chart-container h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
            font-size: 18px;
            font-weight: 600;
        }
        
        .geo-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .geo-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-size: 14px;
            border-bottom: 2px solid #e9ecef;
            color: #495057;
        }
        
        .geo-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
        }
        
        .geo-table tr:hover {
            background: #f8f9fa;
        }
        
        .recent-events {
            margin-top: 30px;
        }
        
        .recent-events h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 600;
        }
        
        .events-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .events-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-size: 14px;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #e9ecef;
        }
        
        .events-table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
        }
        
        .events-table tr:hover {
            background: #f8f9fa;
        }
        
        .muted {
            color: #6c757d;
        }
        
        .event-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .event-badge.view {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .event-badge.redirect {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .event-badge.lead_submit {
            background: #fce4ec;
            color: #c2185b;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navigation.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1>QR Analytics</h1>
        </div>
    </div>
    
    <div class="container">
        <div class="analytics-context">
            <h2>Viewing Analytics For:</h2>
            <div class="context-info">
                <div class="context-item">
                    <div class="context-label">QR Title</div>
                    <div class="context-value"><?php echo htmlspecialchars($qr['title'] ?: 'Untitled', ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="context-item">
                    <div class="context-label">QR ID</div>
                    <div class="context-value"><?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="context-item">
                    <div class="date-selector">
                        <span class="context-label">Time Period:</span>
                        <select onchange="window.location.href='?id=<?php echo urlencode($id); ?>&days='+this.value">
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
                                    <td><?php echo htmlspecialchars($g['location'] ?? $g['location_type'], ENT_QUOTES, 'UTF-8'); ?></td>
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
                    <tr><th>Time</th><th>Event</th><th>Target</th><th>IP Address</th><th>Referrer</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="event-badge <?php echo htmlspecialchars($r['event'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($r['event'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo htmlspecialchars($r['event_target'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="muted"><?php echo htmlspecialchars($r['ip_address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="muted"><?php echo htmlspecialchars($r['referrer'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Views over time data
        const viewsData = <?php echo json_encode(array_map(function($e) { return ['date' => $e['d'], 'views' => (int)$e['views'], 'redirects' => (int)$e['redirects'], 'leads' => (int)$e['leads']]; }, $events)); ?>;
        
        // Device data
        const deviceData = <?php echo json_encode(array_map(function($d) { return ['label' => $d['device_type'], 'value' => (int)$d['count']]; }, $devices)); ?>;
        
        // Browser data
        const browserData = <?php echo json_encode(array_map(function($b) { return ['label' => $b['browser'], 'value' => (int)$b['count']]; }, $browsers)); ?>;
        
        // Views over time chart
        const viewsCtx = document.getElementById('viewsChart').getContext('2d');
        new Chart(viewsCtx, {
            type: 'line',
            data: {
                labels: viewsData.map(d => d.date),
                datasets: [{
                    label: 'Views',
                    data: viewsData.map(d => d.views),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Redirects',
                    data: viewsData.map(d => d.redirects),
                    borderColor: '#34a853',
                    backgroundColor: 'rgba(52, 168, 83, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Leads',
                    data: viewsData.map(d => d.leads),
                    borderColor: '#f5576c',
                    backgroundColor: 'rgba(245, 87, 108, 0.1)',
                    tension: 0.4,
                    fill: true
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
        
        // Device chart
        const deviceCtx = document.getElementById('deviceChart').getContext('2d');
        new Chart(deviceCtx, {
            type: 'doughnut',
            data: {
                labels: deviceData.map(d => d.label),
                datasets: [{
                    data: deviceData.map(d => d.value),
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#f5576c',
                        '#4ecdc4',
                        '#45b7d1'
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
        
        // Browser chart
        const browserCtx = document.getElementById('browserChart').getContext('2d');
        new Chart(browserCtx, {
            type: 'doughnut',
            data: {
                labels: browserData.map(b => b.label),
                datasets: [{
                    data: browserData.map(b => b.value),
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#f5576c',
                        '#4ecdc4',
                        '#45b7d1'
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