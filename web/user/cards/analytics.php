<?php
/**
 * User Analytics Dashboard
 * View detailed analytics for business cards
 */

require_once __DIR__ . '/../includes/UserAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

// Get user's cards for dropdown
$db = Database::getInstance();
$cards = $db->query(
    "SELECT id, first_name, last_name FROM business_cards WHERE user_id = ? AND is_active = 1 ORDER BY created_at DESC",
    [$_SESSION['user_id']]
);

// Get selected card - accept both 'id' and 'card_id' parameters (for backward compatibility)
$selectedCardId = $_GET['card_id'] ?? $_GET['id'] ?? ($cards[0]['id'] ?? null);
$period = $_GET['period'] ?? '30';

// Get card details (users can only view their own cards)
$selectedCard = null;
if ($selectedCardId) {
    $selectedCard = $db->querySingle(
        "SELECT first_name, last_name, user_id FROM business_cards WHERE id = ? AND user_id = ? AND is_active = 1",
        [$selectedCardId, $_SESSION['user_id']]
    );
}

$pageTitle = 'Card Analytics';
$user = UserAuth::getUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - ShareMyCard</title>
    <link rel="stylesheet" href="/user/includes/user-style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">📱 ShareMyCard</div>
        <div class="nav-links">
            <a href="/user/dashboard.php" class="nav-link">Dashboard</a>
            <a href="/user/cards/create.php" class="nav-link">Create Card</a>
            <?php if ($user['is_admin']): ?>
                <a href="/admin/dashboard.php" class="nav-link" style="background: rgba(255,255,255,0.2);">⚙️ Admin Panel</a>
            <?php endif; ?>
            <a href="/user/logout.php" class="nav-link">Logout</a>
        </div>
    </nav>
    
    <div class="dashboard-container" style="max-width: 1400px; margin: 0 auto; padding: 40px 20px;">
        <h1>📊 Card Analytics</h1>
        
        <?php if (!$selectedCardId || !$selectedCard): ?>
            <div class="info-box">
                <strong>No Card Selected</strong><br>
                <?php if (empty($cards)): ?>
                    You don't have any business cards yet. <a href="/user/cards/create.php">Create one now</a>!
                <?php else: ?>
                    Please select a card from your <a href="/user/dashboard.php">Dashboard</a>.
                <?php endif; ?>
            </div>
        <?php else: ?>
            
            <!-- Card Selector and Period Filter -->
            <div class="analytics-filters">
                <div class="filter-group">
                    <label for="card-select">Viewing Analytics For:</label>
                    <div style="padding: 8px; background: #f0f0f0; border-radius: 5px; font-weight: bold;">
                        <?php echo htmlspecialchars($selectedCard['first_name'] . ' ' . $selectedCard['last_name']); ?>
                    </div>
                </div>
                
                <div class="filter-group">
                    <label for="period-select">Time Period:</label>
                    <select id="period-select" onchange="window.location.href='?card_id=<?php echo urlencode($selectedCardId); ?>&period=' + this.value">
                        <option value="7" <?php echo $period === '7' ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="30" <?php echo $period === '30' ? 'selected' : ''; ?>>Last 30 Days</option>
                        <option value="90" <?php echo $period === '90' ? 'selected' : ''; ?>>Last 90 Days</option>
                        <option value="all" <?php echo $period === 'all' ? 'selected' : ''; ?>>All Time</option>
                    </select>
                </div>
                
                <button onclick="exportToCSV()" class="btn" style="margin-left: auto;">📥 Export CSV</button>
            </div>
            
            <!-- Summary Cards -->
            <div class="analytics-summary" id="summary-cards">
                <div class="stat-card">
                    <div class="stat-value" id="total-views">-</div>
                    <div class="stat-label">Total Views</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="unique-views">-</div>
                    <div class="stat-label">Unique Visitors</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="total-clicks">-</div>
                    <div class="stat-label">Link Clicks</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="total-downloads">-</div>
                    <div class="stat-label">vCard Downloads</div>
                </div>
            </div>
            
            <!-- Charts Section -->
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
                
                <!-- Top Links Clicked -->
                <div class="chart-container half-width">
                    <h3>Top Links Clicked</h3>
                    <canvas id="linksChart"></canvas>
                </div>
                
                <!-- Geographic Distribution -->
                <div class="chart-container">
                    <h3>Geographic Distribution</h3>
                    <div id="geo-table"></div>
                </div>
                
                <!-- Browser & OS -->
                <div class="chart-container half-width">
                    <h3>Browsers</h3>
                    <canvas id="browserChart"></canvas>
                </div>
                
                <div class="chart-container half-width">
                    <h3>Operating Systems</h3>
                    <canvas id="osChart"></canvas>
                </div>
            </div>
            
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const cardId = '<?php echo $selectedCardId; ?>';
        const period = '<?php echo $period; ?>';
        let analyticsData = null;
        
        // Fetch analytics data
        async function loadAnalytics() {
            try {
                const response = await fetch(`/api/analytics/stats.php?card_id=${cardId}&period=${period}`);
                const result = await response.json();
                
                if (result.success) {
                    analyticsData = result.data;
                    updateSummaryCards();
                    createViewsChart();
                    createDeviceChart();
                    createLinksChart();
                    createGeoTable();
                    createBrowserChart();
                    createOSChart();
                } else {
                    console.error('Failed to load analytics:', result.message);
                }
            } catch (error) {
                console.error('Error loading analytics:', error);
            }
        }
        
        // Update summary cards
        function updateSummaryCards() {
            document.getElementById('total-views').textContent = analyticsData.summary.total_views.toLocaleString();
            document.getElementById('unique-views').textContent = analyticsData.summary.unique_views.toLocaleString();
            document.getElementById('total-clicks').textContent = analyticsData.summary.total_clicks.toLocaleString();
            document.getElementById('total-downloads').textContent = analyticsData.summary.total_downloads.toLocaleString();
        }
        
        // Create views over time chart
        function createViewsChart() {
            const ctx = document.getElementById('viewsChart').getContext('2d');
            const dates = analyticsData.time_series.map(d => d.date);
            const views = analyticsData.time_series.map(d => parseInt(d.views));
            const uniqueViews = analyticsData.time_series.map(d => parseInt(d.unique_views));
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [
                        {
                            label: 'Total Views',
                            data: views,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            tension: 0.4
                        },
                        {
                            label: 'Unique Views',
                            data: uniqueViews,
                            borderColor: '#764ba2',
                            backgroundColor: 'rgba(118, 75, 162, 0.1)',
                            tension: 0.4
                        }
                    ]
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
        }
        
        // Create device type chart
        function createDeviceChart() {
            const ctx = document.getElementById('deviceChart').getContext('2d');
            const deviceData = analyticsData.devices.types;
            
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: deviceData.map(d => d.device_type || 'Unknown'),
                    datasets: [{
                        data: deviceData.map(d => parseInt(d.count)),
                        backgroundColor: ['#667eea', '#764ba2', '#f093fb', '#4facfe']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        
        // Create links chart
        function createLinksChart() {
            const ctx = document.getElementById('linksChart').getContext('2d');
            const linksData = analyticsData.top_links.slice(0, 5); // Top 5
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: linksData.map(l => {
                        const url = l.url || '';
                        return url.length > 30 ? url.substring(0, 30) + '...' : url;
                    }),
                    datasets: [{
                        label: 'Clicks',
                        data: linksData.map(l => parseInt(l.clicks)),
                        backgroundColor: '#667eea'
                    }]
                },
                options: {
                    responsive: true,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
        
        // Create geographic table
        function createGeoTable() {
            const countries = analyticsData.geography.countries;
            const cities = analyticsData.geography.cities;
            
            let html = '<div class="geo-columns">';
            
            // Countries column
            html += '<div class="geo-column"><h4>Top Countries</h4><table class="geo-table">';
            html += '<tr><th>Country</th><th>Views</th></tr>';
            countries.forEach(c => {
                html += `<tr><td>${c.country || 'Unknown'}</td><td>${c.count}</td></tr>`;
            });
            if (countries.length === 0) {
                html += '<tr><td colspan="2">No data</td></tr>';
            }
            html += '</table></div>';
            
            // Cities column
            html += '<div class="geo-column"><h4>Top Cities</h4><table class="geo-table">';
            html += '<tr><th>City</th><th>Views</th></tr>';
            cities.forEach(c => {
                html += `<tr><td>${c.city || 'Unknown'}${c.country ? ', ' + c.country : ''}</td><td>${c.count}</td></tr>`;
            });
            if (cities.length === 0) {
                html += '<tr><td colspan="2">No data</td></tr>';
            }
            html += '</table></div>';
            
            html += '</div>';
            document.getElementById('geo-table').innerHTML = html;
        }
        
        // Create browser chart
        function createBrowserChart() {
            const ctx = document.getElementById('browserChart').getContext('2d');
            const browserData = analyticsData.devices.browsers;
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: browserData.map(b => b.browser || 'Unknown'),
                    datasets: [{
                        data: browserData.map(b => parseInt(b.count)),
                        backgroundColor: ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#00f2fe', '#43e97b']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        
        // Create OS chart
        function createOSChart() {
            const ctx = document.getElementById('osChart').getContext('2d');
            const osData = analyticsData.devices.operating_systems;
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: osData.map(o => o.os || 'Unknown'),
                    datasets: [{
                        data: osData.map(o => parseInt(o.count)),
                        backgroundColor: ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#00f2fe']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        
        // Export to CSV
        function exportToCSV() {
            if (!analyticsData) return;
            
            let csv = 'Date,Total Views,Unique Views,Clicks,Downloads\n';
            analyticsData.time_series.forEach(row => {
                csv += `${row.date},${row.views},${row.unique_views},${row.clicks},${row.downloads}\n`;
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `analytics_${cardId}_${period}.csv`;
            a.click();
        }
        
        // Load analytics on page load
        if (cardId) {
            loadAnalytics();
        }
    </script>
    
    <style>
        .analytics-filters {
            display: flex;
            gap: 20px;
            align-items: flex-end;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            min-width: 200px;
        }
        
        .analytics-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .analytics-charts {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
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
        }
        
        .geo-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .geo-column h4 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #667eea;
        }
        
        .geo-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .geo-table th {
            background: #f5f5f5;
            padding: 8px;
            text-align: left;
            font-size: 14px;
            border-bottom: 2px solid #ddd;
        }
        
        .geo-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        
        .geo-table tr:hover {
            background: #f9f9f9;
        }
    </style>
</body>
</html>

