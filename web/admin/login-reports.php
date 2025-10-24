<?php
/**
 * Admin Login Reports
 * View and filter user login activity
 */

require_once __DIR__ . '/includes/AdminAuth.php';
require_once __DIR__ . '/../api/includes/Database.php';
require_once __DIR__ . '/../api/includes/LoginTracker.php';

AdminAuth::requireAuth();

$db = Database::getInstance();
$loginTracker = new LoginTracker();

// Handle filters
$filters = [
    'date_range' => $_GET['date_range'] ?? 'last_7_days',
    'user_id' => $_GET['user_id'] ?? '',
    'email' => $_GET['email'] ?? '',
    'browser' => $_GET['browser'] ?? '',
    'device' => $_GET['device'] ?? ''
];

// Get login data
$logins = $loginTracker->getLoginStats($filters);

// Get all users for filter dropdown
$allUsers = $db->query("SELECT id, email, first_name, last_name FROM users ORDER BY email");

// Get available browsers and devices for filter dropdowns
$availableBrowsers = $db->query("SELECT DISTINCT browser_name FROM user_logins WHERE browser_name IS NOT NULL ORDER BY browser_name");
$availableDevices = $db->query("SELECT DISTINCT device_type FROM user_logins WHERE device_type IS NOT NULL ORDER BY device_type");

// Debug: Log the raw data
error_log("LOGIN REPORTS DEBUG: Raw logins data count: " . count($logins));
error_log("LOGIN REPORTS DEBUG: Raw logins data: " . json_encode($logins));

// Get statistics based on filtered data
$userIds = array_column($logins, 'user_id');
$uniqueUserIds = array_unique(array_filter($userIds)); // Filter out null/empty values

$stats = [
    'total_logins' => count($logins),
    'unique_users' => count($uniqueUserIds),
    'mobile_logins' => count(array_filter($logins, function($login) { return isset($login['device_type']) && $login['device_type'] === 'Mobile'; })),
    'desktop_logins' => count(array_filter($logins, function($login) { return isset($login['device_type']) && $login['device_type'] === 'Desktop'; })),
    'tablet_logins' => count(array_filter($logins, function($login) { return isset($login['device_type']) && $login['device_type'] === 'Tablet'; })),
    'chrome_logins' => count(array_filter($logins, function($login) { return isset($login['browser_name']) && $login['browser_name'] === 'Chrome'; })),
    'firefox_logins' => count(array_filter($logins, function($login) { return isset($login['browser_name']) && $login['browser_name'] === 'Firefox'; })),
    'safari_logins' => count(array_filter($logins, function($login) { return isset($login['browser_name']) && $login['browser_name'] === 'Safari'; }))
];

// Debug: Log the calculated statistics
error_log("LOGIN REPORTS DEBUG: Calculated stats: " . json_encode($stats));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Reports - ShareMyCard Admin</title>
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
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filter-row {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-weight: bold;
            font-size: 0.9em;
            color: #333;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .login-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .login-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .login-table th,
        .login-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .login-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        
        .login-table tr:hover {
            background: #f8f9fa;
        }
        
        .browser-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .browser-chrome { background: #4285f4; color: white; }
        .browser-firefox { background: #ff9500; color: white; }
        .browser-safari { background: #007aff; color: white; }
        .browser-edge { background: #0078d4; color: white; }
        .browser-opera { background: #ff1b2d; color: white; }
        .browser-unknown { background: #6c757d; color: white; }
        
        .device-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .device-mobile { background: #28a745; color: white; }
        .device-desktop { background: #007bff; color: white; }
        .device-tablet { background: #ffc107; color: black; }
        
        .status-success { color: #28a745; font-weight: bold; }
        .status-failed { color: #dc3545; font-weight: bold; }
        
        .login-as-btn {
            background: #17a2b8;
            color: white;
            padding: 4px 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8em;
            text-decoration: none;
            display: inline-block;
        }
        
        .login-as-btn:hover {
            background: #138496;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .pagination a {
            padding: 8px 12px;
            background: #f8f9fa;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .pagination a:hover {
            background: #e9ecef;
        }
        
        .pagination .current {
            background: #667eea;
            color: white;
        }
        
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="main-container">
        <h1>📊 Login Reports</h1>
        <p style="color: #666; margin-bottom: 30px;">View user login activity and browser data</p>
        
        <!-- Debug info (remove in production) -->
        <?php if (isset($_GET['debug'])): ?>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-family: monospace; font-size: 12px;">
            <strong>Debug Info:</strong><br>
            Active Filters: <?php echo htmlspecialchars(json_encode($filters)); ?><br>
            Total Records: <?php echo count($logins); ?><br>
            Statistics: <?php echo htmlspecialchars(json_encode($stats)); ?><br>
            Sample Data: <?php echo htmlspecialchars(json_encode(array_slice($logins, 0, 2))); ?><br>
            <br>
            <strong>Database Check:</strong><br>
            User Logins Count: <?php 
                $totalLogins = $db->query("SELECT COUNT(*) as count FROM user_logins")->fetch();
                echo $totalLogins['count'];
            ?><br>
            Last 7 Days Count: <?php 
                $last7Days = $db->query("SELECT COUNT(*) as count FROM user_logins WHERE login_timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch();
                echo $last7Days['count'];
            ?><br>
            All Time Count: <?php 
                $allTime = $db->query("SELECT COUNT(*) as count FROM user_logins")->fetch();
                echo $allTime['count'];
            ?><br>
            <br>
            <strong>Raw Query Results:</strong><br>
            <?php 
                $rawQuery = $db->query("SELECT user_id, email, login_timestamp, browser_name, device_type FROM user_logins ORDER BY login_timestamp DESC LIMIT 10");
                foreach ($rawQuery as $row) {
                    echo "User: {$row['email']}, Date: {$row['login_timestamp']}, Browser: {$row['browser_name']}, Device: {$row['device_type']}<br>";
                }
            ?>
        </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_logins']; ?></div>
                <div class="stat-label">Total Logins</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['unique_users']; ?></div>
                <div class="stat-label">Unique Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['mobile_logins']; ?></div>
                <div class="stat-label">Mobile Logins</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['desktop_logins']; ?></div>
                <div class="stat-label">Desktop Logins</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['tablet_logins']; ?></div>
                <div class="stat-label">Tablet Logins</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['chrome_logins']; ?></div>
                <div class="stat-label">Chrome Logins</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['firefox_logins']; ?></div>
                <div class="stat-label">Firefox Logins</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['safari_logins']; ?></div>
                <div class="stat-label">Safari Logins</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <h3>🔍 Filters</h3>
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="date_range">Date Range</label>
                        <select name="date_range" id="date_range">
                            <option value="today" <?php echo $filters['date_range'] === 'today' ? 'selected' : ''; ?>>Today</option>
                            <option value="last_7_days" <?php echo $filters['date_range'] === 'last_7_days' ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="last_2_weeks" <?php echo $filters['date_range'] === 'last_2_weeks' ? 'selected' : ''; ?>>Last 2 Weeks</option>
                            <option value="last_30_days" <?php echo $filters['date_range'] === 'last_30_days' ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="last_3_months" <?php echo $filters['date_range'] === 'last_3_months' ? 'selected' : ''; ?>>Last 3 Months</option>
                            <option value="last_6_months" <?php echo $filters['date_range'] === 'last_6_months' ? 'selected' : ''; ?>>Last 6 Months</option>
                            <option value="all_time" <?php echo $filters['date_range'] === 'all_time' ? 'selected' : ''; ?>>All Time</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="user_id">User</label>
                        <select name="user_id" id="user_id">
                            <option value="">All Users</option>
                            <?php foreach ($allUsers as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $filters['user_id'] == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['email']); ?>
                                    <?php if (!empty($user['first_name']) || !empty($user['last_name'])): ?>
                                        (<?php echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="email">Email Search</label>
                        <input type="text" name="email" id="email" value="<?php echo htmlspecialchars($filters['email']); ?>" placeholder="Search by email...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="browser">Browser</label>
                        <select name="browser" id="browser">
                            <option value="">All Browsers</option>
                            <?php foreach ($availableBrowsers as $browser): ?>
                                <option value="<?php echo htmlspecialchars($browser['browser_name']); ?>" <?php echo $filters['browser'] === $browser['browser_name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($browser['browser_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="device">Device</label>
                        <select name="device" id="device">
                            <option value="">All Devices</option>
                            <?php foreach ($availableDevices as $device): ?>
                                <option value="<?php echo htmlspecialchars($device['device_type']); ?>" <?php echo $filters['device'] === $device['device_type'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($device['device_type']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn">Apply Filters</button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Login Data Table -->
        <div class="login-table">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Browser</th>
                        <th>Device</th>
                        <th>IP Address</th>
                        <th>Location</th>
                        <th>Login Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logins)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #666;">
                                No login data found for the selected filters.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logins as $login): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($login['user_email']); ?></strong>
                                        <?php if (!empty($login['first_name']) || !empty($login['last_name'])): ?>
                                            <br><small><?php echo htmlspecialchars(trim($login['first_name'] . ' ' . $login['last_name'])); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($login['is_admin']): ?>
                                        <span class="browser-badge browser-chrome" style="background: #dc3545;">Admin</span>
                                    <?php else: ?>
                                        <span class="browser-badge browser-unknown" style="background: #6c757d;">User</span>
                                    <?php endif; ?>
                                    <?php if ($login['is_active'] == 0): ?>
                                        <br><small style="color: #dc3545;">Inactive</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="browser-badge browser-<?php echo strtolower($login['browser_name']); ?>">
                                        <?php echo htmlspecialchars($login['browser_name']); ?>
                                        <?php if ($login['browser_version'] !== 'Unknown'): ?>
                                            <?php echo $login['browser_version']; ?>
                                        <?php endif; ?>
                                    </span>
                                    <br><small><?php echo htmlspecialchars($login['operating_system']); ?></small>
                                </td>
                                <td>
                                    <span class="device-badge device-<?php echo strtolower($login['device_type']); ?>">
                                        <?php echo htmlspecialchars($login['device_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <code><?php echo htmlspecialchars($login['ip_address']); ?></code>
                                </td>
                                <td>
                                    <?php if ($login['country'] !== 'Unknown'): ?>
                                        <?php echo htmlspecialchars($login['country']); ?>
                                        <?php if ($login['city'] !== 'Unknown'): ?>
                                            <br><small><?php echo htmlspecialchars($login['city']); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #999;">Unknown</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('M j, Y g:i A', strtotime($login['login_timestamp'])); ?>
                                    <br><small><?php 
                                        $timeAgo = time() - strtotime($login['login_timestamp']);
                                        if ($timeAgo < 60) echo 'Just now';
                                        elseif ($timeAgo < 3600) echo floor($timeAgo/60) . ' minutes ago';
                                        elseif ($timeAgo < 86400) echo floor($timeAgo/3600) . ' hours ago';
                                        else echo floor($timeAgo/86400) . ' days ago';
                                    ?></small>
                                </td>
                                <td>
                                    <?php if ($login['is_successful'] && !empty($login['user_id'])): ?>
                                        <a href="/admin/impersonate.php?user_id=<?php echo $login['user_id']; ?>" 
                                           class="login-as-btn" 
                                           target="_blank"
                                           onclick="return confirm('Are you sure you want to login as this user?')">
                                            Login As
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        // Auto-submit form when date range changes
        document.getElementById('date_range').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>
</html>

<?php
/**
 * Helper function to show time ago
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}
?>
