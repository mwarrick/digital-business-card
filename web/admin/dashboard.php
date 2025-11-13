<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/includes/AdminAuth.php';

// Require authentication
AdminAuth::requireAuth();

// Check session expiry
if (AdminAuth::isSessionExpired()) {
    AdminAuth::logout();
    header('Location: /admin/login.php');
    exit;
}

$db = Database::getInstance();

// Get statistics
$stats = [];

try {
    // Total users
    $result = $db->querySingle("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
    $stats['total_users'] = $result['count'];
    
    // Total admins
    $result = $db->querySingle("SELECT COUNT(*) as count FROM users WHERE is_admin = 1 AND is_active = 1");
    $stats['total_admins'] = $result['count'];
    
    // Total business cards (excluding deleted)
    $result = $db->querySingle("SELECT COUNT(*) as count FROM business_cards WHERE is_active = 1 AND is_deleted = 0");
    $stats['total_cards'] = $result['count'];
    
    // Recent users (last 7 days)
    $result = $db->querySingle("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['recent_users'] = $result['count'];
    
    // Recently active users (logged in within last 7 days)
    $result = $db->querySingle("SELECT COUNT(*) as count FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND is_active = 1");
    $stats['recently_active'] = $result['count'];
    
    // Inactive users (no login in 30+ days)
    $result = $db->querySingle("SELECT COUNT(*) as count FROM users WHERE (last_login IS NULL OR last_login < DATE_SUB(NOW(), INTERVAL 30 DAY)) AND is_active = 1");
    $stats['inactive_users'] = $result['count'];
    
    // Users who never logged in
    $result = $db->querySingle("SELECT COUNT(*) as count FROM users WHERE last_login IS NULL AND is_active = 1");
    $stats['never_logged_in'] = $result['count'];
    
    // Total logins today
    $result = $db->querySingle("SELECT COUNT(*) as count FROM users WHERE DATE(last_login) = CURDATE()");
    $stats['logins_today'] = $result['count'];
    
    // Note: Recent users list removed - replaced with admin navigation tools
    
} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
}

$current_admin = AdminAuth::getEmail();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ShareMyCard</title>
    <link rel="stylesheet" href="/admin/includes/admin-style.css">
    <style>
        .badge-demo {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        /* Clickable Statistics Cards */
        .stat-card.clickable {
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            cursor: pointer;
            display: block;
        }
        
        .stat-card.clickable:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            text-decoration: none;
            color: inherit;
        }
        
        .stat-card.clickable:hover .stat-icon {
            transform: scale(1.1);
        }
        
        .stat-card.clickable:hover .stat-value {
            color: #3498db;
        }
        
        .stat-card.clickable:active {
            transform: translateY(-2px);
        }
        
        /* Ensure stat-card maintains its styling when clickable */
        .stat-card.clickable {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        /* Admin Navigation Grid */
        .admin-nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .admin-nav-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            display: block;
        }
        
        .admin-nav-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: #3498db;
            text-decoration: none;
            color: inherit;
        }
        
        .admin-nav-card:hover .nav-icon {
            transform: scale(1.1);
        }
        
        .admin-nav-card:hover .nav-title {
            color: #3498db;
        }
        
        .nav-icon {
            font-size: 2.5em;
            margin-bottom: 12px;
            transition: transform 0.3s ease;
        }
        
        .nav-title {
            font-size: 1.2em;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
            transition: color 0.3s ease;
        }
        
        .nav-description {
            font-size: 0.9em;
            color: #7f8c8d;
            line-height: 1.4;
        }
        
        /* Placeholder card styling */
        .admin-nav-card.placeholder {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            cursor: default;
            opacity: 0.7;
        }
        
        .admin-nav-card.placeholder:hover {
            transform: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-color: #dee2e6;
        }
        
        .admin-nav-card.placeholder:hover .nav-icon,
        .admin-nav-card.placeholder:hover .nav-title {
            transform: none;
            color: inherit;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <h1>📊 Admin Dashboard</h1>
        <p class="subtitle">Overview of ShareMyCard system</p>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <a href="/admin/users.php?filter=all" class="stat-card clickable">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                <div class="stat-label">Total Users</div>
            </a>
            
            <a href="/admin/users.php?filter=admins" class="stat-card clickable">
                <div class="stat-icon">🛡️</div>
                <div class="stat-value"><?php echo number_format($stats['total_admins']); ?></div>
                <div class="stat-label">Admin Users</div>
            </a>
            
            <a href="/admin/cards.php" class="stat-card clickable">
                <div class="stat-icon">📇</div>
                <div class="stat-value"><?php echo number_format($stats['total_cards']); ?></div>
                <div class="stat-label">Business Cards</div>
            </a>
            
            <a href="/admin/users.php?filter=recent" class="stat-card clickable">
                <div class="stat-icon">🆕</div>
                <div class="stat-value"><?php echo number_format($stats['recent_users']); ?></div>
                <div class="stat-label">New Users (7 days)</div>
            </a>
            
            <a href="/admin/users.php?filter=recently_active" class="stat-card clickable">
                <div class="stat-icon">🟢</div>
                <div class="stat-value"><?php echo number_format($stats['recently_active']); ?></div>
                <div class="stat-label">Recently Active (7 days)</div>
            </a>
            
            <a href="/admin/users.php?filter=inactive" class="stat-card clickable">
                <div class="stat-icon">🔴</div>
                <div class="stat-value"><?php echo number_format($stats['inactive_users']); ?></div>
                <div class="stat-label">Inactive (30+ days)</div>
            </a>
            
            <a href="/admin/users.php?filter=never_logged_in" class="stat-card clickable">
                <div class="stat-icon">❌</div>
                <div class="stat-value"><?php echo number_format($stats['never_logged_in']); ?></div>
                <div class="stat-label">Never Logged In</div>
            </a>
            
            <a href="/admin/users.php?filter=logins_today" class="stat-card clickable">
                <div class="stat-icon">📊</div>
                <div class="stat-value"><?php echo number_format($stats['logins_today']); ?></div>
                <div class="stat-label">Logins Today</div>
            </a>
            
            
        </div>
        
        <!-- Admin Navigation -->
        <div class="section">
            <h2>🚀 Admin Tools</h2>
            <div class="admin-nav-grid">
                <a href="/admin/users.php" class="admin-nav-card">
                    <div class="nav-icon">👥</div>
                    <div class="nav-title">User Management</div>
                    <div class="nav-description">Manage users, roles, and permissions</div>
                </a>
                
                <a href="/admin/cards.php" class="admin-nav-card">
                    <div class="nav-icon">📇</div>
                    <div class="nav-title">Business Cards</div>
                    <div class="nav-description">View and manage all business cards</div>
                </a>
                
                <a href="/admin/analytics.php" class="admin-nav-card">
                    <div class="nav-icon">📊</div>
                    <div class="nav-title">Analytics</div>
                    <div class="nav-description">View system usage statistics</div>
                </a>
                
                <a href="/admin/leads/index.php" class="admin-nav-card">
                    <div class="nav-icon">📋</div>
                    <div class="nav-title">All Leads</div>
                    <div class="nav-description">View and manage all captured leads</div>
                </a>
                
                <a href="/admin/contacts/index.php" class="admin-nav-card">
                    <div class="nav-icon">👥</div>
                    <div class="nav-title">All Contacts</div>
                    <div class="nav-description">View and manage all contacts</div>
                </a>
                
                <a href="/admin/login-reports.php" class="admin-nav-card">
                    <div class="nav-icon">🔐</div>
                    <div class="nav-title">Login Reports</div>
                    <div class="nav-description">View user login activity and browser data</div>
                </a>
                
                <a href="/admin/image-creation-history.php" class="admin-nav-card">
                    <div class="nav-icon">🖼️</div>
                    <div class="nav-title">Image Creation History</div>
                    <div class="nav-description">Track when and where images are created</div>
                </a>
                
                <a href="/admin/tools/resend-verification.php" class="admin-nav-card">
                    <div class="nav-icon">✉️</div>
                    <div class="nav-title">Resend Verification</div>
                    <div class="nav-description">Send a new verification code to a user</div>
                </a>
                
                <a href="/admin/debug-log.php" class="admin-nav-card">
                    <div class="nav-icon">🐛</div>
                    <div class="nav-title">Debug Log</div>
                    <div class="nav-description">View system error logs</div>
                </a>
                
                <a href="/admin/impersonate.php?user_id=<?php echo AdminAuth::getUserId(); ?>" class="admin-nav-card" target="_blank">
                    <div class="nav-icon">👤</div>
                    <div class="nav-title">Login As Me</div>
                    <div class="nav-description">Access your personal account in a new window</div>
                </a>
                
                <div class="admin-nav-card placeholder">
                    <div class="nav-icon">🔮</div>
                    <div class="nav-title">Future Tool</div>
                    <div class="nav-description">Reserved for future functionality</div>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Account Security Modal -->
    <?php 
    $userId = AdminAuth::getUserId();
    include __DIR__ . '/includes/account-security-modal.php'; 
    ?>
</body>
</html>

