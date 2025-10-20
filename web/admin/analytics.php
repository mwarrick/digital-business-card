<?php
/**
 * Global Analytics Dashboard for Admins
 * Shows comprehensive analytics across all business cards
 */

require_once __DIR__ . '/includes/AdminAuth.php';
require_once __DIR__ . '/../api/includes/Database.php';

AdminAuth::requireAuth();

$db = Database::getInstance();
$period = $_GET['period'] ?? '30';

// Get global statistics
$stats = [];

// Total cards
$stats['total_cards'] = $db->querySingle("SELECT COUNT(*) as count FROM business_cards WHERE is_active = 1")['count'];

// Total users
$stats['total_users'] = $db->querySingle("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'];

// Total views in period
$stats['total_views'] = $db->querySingle(
    "SELECT COUNT(*) as count FROM analytics_events WHERE event_type = 'view' AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)",
    [$period]
)['count'];

// Total QR scans in period
$stats['total_scans'] = $db->querySingle(
    "SELECT COUNT(*) as count FROM analytics_events WHERE event_type = 'qr_scan' AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)",
    [$period]
)['count'];

// Total downloads in period
$stats['total_downloads'] = $db->querySingle(
    "SELECT COUNT(*) as count FROM analytics_events WHERE event_type = 'download' AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)",
    [$period]
)['count'];

// Total email opens in period
$stats['total_email_opens'] = $db->querySingle(
    "SELECT COUNT(*) as count FROM analytics_events WHERE event_type = 'email_open' AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)",
    [$period]
)['count'];

// Top performing cards
$top_cards = $db->query(
    "SELECT 
        bc.id,
        bc.first_name,
        bc.last_name,
        bc.job_title,
        bc.company_name,
        COUNT(ae.id) as total_events,
        SUM(CASE WHEN ae.event_type = 'view' THEN 1 ELSE 0 END) as views,
        SUM(CASE WHEN ae.event_type = 'qr_scan' THEN 1 ELSE 0 END) as scans,
        SUM(CASE WHEN ae.event_type = 'download' THEN 1 ELSE 0 END) as downloads,
        SUM(CASE WHEN ae.event_type = 'email_open' THEN 1 ELSE 0 END) as email_opens
     FROM business_cards bc
     LEFT JOIN analytics_events ae ON bc.id = ae.card_id 
         AND ae.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
     WHERE bc.is_active = 1
     GROUP BY bc.id, bc.first_name, bc.last_name, bc.job_title, bc.company_name
     ORDER BY total_events DESC
     LIMIT 10",
    [$period]
);

// Recent activity
$recent_activity = $db->query(
    "SELECT 
        ae.event_type,
        ae.created_at,
        bc.first_name,
        bc.last_name,
        bc.company_name,
        ae.ip_address,
        ae.user_agent
     FROM analytics_events ae
     JOIN business_cards bc ON ae.card_id = bc.id
     WHERE ae.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY ae.created_at DESC
     LIMIT 20",
    []
);

// Daily stats for chart
$daily_stats = $db->query(
    "SELECT 
        DATE(created_at) as date,
        SUM(CASE WHEN event_type = 'view' THEN 1 ELSE 0 END) as views,
        SUM(CASE WHEN event_type = 'qr_scan' THEN 1 ELSE 0 END) as scans,
        SUM(CASE WHEN event_type = 'download' THEN 1 ELSE 0 END) as downloads,
        SUM(CASE WHEN event_type = 'email_open' THEN 1 ELSE 0 END) as email_opens
     FROM analytics_events 
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
     GROUP BY DATE(created_at)
     ORDER BY date ASC",
    [$period]
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global Analytics - ShareMyCard Admin</title>
    <link rel="stylesheet" href="/admin/includes/admin-style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #4CAF50;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .analytics-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        
        .top-cards-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .top-cards-table th,
        .top-cards-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .top-cards-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        
        .top-cards-table tr:hover {
            background: #f8f9fa;
        }
        
        .activity-list {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #eee;
            border-radius: 8px;
        }
        
        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-item:hover {
            background: #f8f9fa;
        }
        
        .activity-info {
            flex: 1;
        }
        
        .activity-meta {
            color: #666;
            font-size: 12px;
        }
        
        .activity-type {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .activity-type.view {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .activity-type.qr_scan {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .activity-type.download {
            background: #e8f5e8;
            color: #388e3c;
        }
        
        .filter-controls {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .filter-controls label {
            font-weight: bold;
            color: #333;
        }
        
        .filter-controls select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <h1>📊 Global Analytics</h1>
        <p class="subtitle">Comprehensive analytics across all business cards</p>
        
        <!-- Period Filter -->
        <div class="filter-controls">
            <label for="period-select">Time Period:</label>
            <select id="period-select" onchange="window.location.href='?period=' + this.value">
                <option value="7" <?php echo $period === '7' ? 'selected' : ''; ?>>Last 7 Days</option>
                <option value="30" <?php echo $period === '30' ? 'selected' : ''; ?>>Last 30 Days</option>
                <option value="90" <?php echo $period === '90' ? 'selected' : ''; ?>>Last 90 Days</option>
            </select>
        </div>
        
        <!-- Daily Activity Chart -->
        <div class="analytics-section">
            <h2 class="section-title">📈 Daily Activity (<?php echo $period; ?> days)</h2>
            <canvas id="dailyChart" width="400" height="200"></canvas>
        </div>
        
        <!-- Global Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📇</div>
                <div class="stat-value"><?php echo number_format($stats['total_cards']); ?></div>
                <div class="stat-label">Total Business Cards</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">👁️</div>
                <div class="stat-value"><?php echo number_format($stats['total_views']); ?></div>
                <div class="stat-label">Total Views (<?php echo $period; ?>d)</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📱</div>
                <div class="stat-value"><?php echo number_format($stats['total_scans']); ?></div>
                <div class="stat-label">QR Scans (<?php echo $period; ?>d)</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⬇️</div>
                <div class="stat-value"><?php echo number_format($stats['total_downloads']); ?></div>
                <div class="stat-label">Downloads (<?php echo $period; ?>d)</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📧</div>
                <div class="stat-value"><?php echo number_format($stats['total_email_opens']); ?></div>
                <div class="stat-label">Email Opens (<?php echo $period; ?>d)</div>
            </div>
        </div>
        
        <!-- Top Performing Cards -->
        <div class="analytics-section">
            <h2 class="section-title">🏆 Top Performing Cards (<?php echo $period; ?> days)</h2>
            <table class="top-cards-table">
                <thead>
                    <tr>
                        <th>Card</th>
                        <th>Company</th>
                        <th>Views</th>
                        <th>QR Scans</th>
                        <th>Downloads</th>
                        <th>Email Opens</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_cards as $card): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($card['first_name'] . ' ' . $card['last_name']); ?></strong>
                            <?php if (!empty($card['job_title'])): ?>
                                <br><small><?php echo htmlspecialchars($card['job_title']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($card['company_name'] ?: 'N/A'); ?></td>
                        <td><?php echo number_format($card['views']); ?></td>
                        <td><?php echo number_format($card['scans']); ?></td>
                        <td><?php echo number_format($card['downloads']); ?></td>
                        <td><?php echo number_format($card['email_opens']); ?></td>
                        <td><strong><?php echo number_format($card['total_events']); ?></strong></td>
                        <td>
                            <a href="/admin/cards/analytics.php?card_id=<?php echo urlencode($card['id']); ?>" class="btn-small btn-primary">
                                View Details
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Recent Activity -->
        <div class="analytics-section">
            <h2 class="section-title">🕒 Recent Activity (Last 7 Days)</h2>
            <div class="activity-list">
                <?php foreach ($recent_activity as $activity): ?>
                <div class="activity-item">
                    <div class="activity-info">
                        <div>
                            <span class="activity-type <?php echo $activity['event_type']; ?>">
                                <?php echo $activity['event_type']; ?>
                            </span>
                            <strong><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></strong>
                            <?php if (!empty($activity['company_name'])): ?>
                                (<?php echo htmlspecialchars($activity['company_name']); ?>)
                            <?php endif; ?>
                        </div>
                        <div class="activity-meta">
                            <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?>
                            • IP: <?php echo htmlspecialchars($activity['ip_address']); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Daily activity chart
        const dailyData = <?php echo json_encode($daily_stats); ?>;
        
        const ctx = document.getElementById('dailyChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dailyData.map(d => d.date),
                datasets: [
                    {
                        label: 'Views',
                        data: dailyData.map(d => d.views),
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'QR Scans',
                        data: dailyData.map(d => d.scans),
                        borderColor: '#2196F3',
                        backgroundColor: 'rgba(33, 150, 243, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Downloads',
                        data: dailyData.map(d => d.downloads),
                        borderColor: '#FF9800',
                        backgroundColor: 'rgba(255, 152, 0, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Email Opens',
                        data: dailyData.map(d => d.email_opens),
                        borderColor: '#9C27B0',
                        backgroundColor: 'rgba(156, 39, 176, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    </script>
</body>
</html>
