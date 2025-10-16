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
    
    // Total business cards
    $result = $db->querySingle("SELECT COUNT(*) as count FROM business_cards WHERE is_active = 1");
    $stats['total_cards'] = $result['count'];
    
    // Recent users (last 7 days)
    $result = $db->querySingle("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['recent_users'] = $result['count'];
    
    
    // Get recent users list
    $recent_users = $db->query(
        "SELECT id, email, is_admin, created_at FROM users 
         WHERE is_active = 1 
         ORDER BY created_at DESC 
         LIMIT 10"
    );
    
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
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <h1>📊 Admin Dashboard</h1>
        <p class="subtitle">Overview of ShareMyCard system</p>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🛡️</div>
                <div class="stat-value"><?php echo number_format($stats['total_admins']); ?></div>
                <div class="stat-label">Admin Users</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📇</div>
                <div class="stat-value"><?php echo number_format($stats['total_cards']); ?></div>
                <div class="stat-label">Business Cards</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🆕</div>
                <div class="stat-value"><?php echo number_format($stats['recent_users']); ?></div>
                <div class="stat-label">New Users (7 days)</div>
            </div>
            
            
        </div>
        
        <!-- Recent Users -->
        <div class="section">
            <h2>Recent Users</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_users as $user): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($user['email']); ?></strong>
                            </td>
                            <td>
                                <?php if ($user['is_admin']): ?>
                                    <span class="badge badge-admin">Admin</span>
                                <?php else: ?>
                                    <span class="badge badge-user">User</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="/admin/users.php?view=<?php echo $user['id']; ?>" class="btn-small">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 20px;">
                <a href="/admin/users.php" class="btn">View All Users →</a>
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

