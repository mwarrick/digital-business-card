<?php
/**
 * Users Management Page
 */

require_once __DIR__ . '/includes/AdminAuth.php';
require_once __DIR__ . '/../api/includes/EmailTemplates.php';
require_once __DIR__ . '/../api/includes/GmailClient.php';

// Require authentication
AdminAuth::requireAuth();

$db = Database::getInstance();
$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    
    try {
        if ($action === 'toggle_admin') {
            $user = $db->querySingle("SELECT is_admin FROM users WHERE id = ?", [$user_id]);
            $new_status = $user['is_admin'] ? 0 : 1;
            $db->execute("UPDATE users SET is_admin = ? WHERE id = ?", [$new_status, $user_id]);
            $message = 'User admin status updated successfully';
        }
        
        else if ($action === 'toggle_active') {
            $user = $db->querySingle("SELECT is_active FROM users WHERE id = ?", [$user_id]);
            $new_status = $user['is_active'] ? 0 : 1;
            $db->execute("UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ?", [$new_status, $user_id]);
            $message = 'User status updated successfully';
        }
        
        else if ($action === 'delete') {
            // Soft delete - just deactivate
            $db->execute("UPDATE users SET is_active = 0, updated_at = NOW() WHERE id = ?", [$user_id]);
            $message = 'User deactivated successfully';
        }
        
        else if ($action === 'resend_welcome') {
            // Fetch user email
            $user = $db->querySingle("SELECT email FROM users WHERE id = ?", [$user_id]);
            if (!$user || empty($user['email'])) {
                throw new Exception('User not found or missing email');
            }
            $emailData = EmailTemplates::welcome(null);
            GmailClient::sendEmail(
                $user['email'],
                $emailData['subject'],
                $emailData['html'],
                $emailData['text']
            );
            $message = 'Welcome email re-sent to ' . htmlspecialchars($user['email']);
        }
    } catch (Exception $e) {
        error_log("User action error: " . $e->getMessage());
        $error = 'Action failed. Please try again.';
    }
}

// Get filters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where = ["1=1"];
$params = [];

if ($filter === 'admins') {
    $where[] = "is_admin = 1";
} else if ($filter === 'users') {
    $where[] = "is_admin = 0";
} else if ($filter === 'inactive') {
    $where[] = "is_active = 0";
} else {
    $where[] = "is_active = 1";
}

if (!empty($search)) {
    $where[] = "email LIKE ?";
    $params[] = "%{$search}%";
}

$whereClause = implode(' AND ', $where);

// Get users
$users = $db->query(
    "SELECT id, email, is_admin, is_active, created_at, updated_at 
     FROM users 
     WHERE {$whereClause}
     ORDER BY created_at DESC",
    $params
);

// Get counts
$total_users = $db->querySingle("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'];
$total_admins = $db->querySingle("SELECT COUNT(*) as count FROM users WHERE is_admin = 1 AND is_active = 1")['count'];
$inactive_users = $db->querySingle("SELECT COUNT(*) as count FROM users WHERE is_active = 0")['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - ShareMyCard Admin</title>
    <link rel="stylesheet" href="/admin/includes/admin-style.css">
    <style>
        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        
        .filter-btn.active {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }
        
        .filter-btn:hover {
            border-color: #667eea;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
        }
        
        .search-box input {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
        }
        
        .user-actions {
            display: flex;
            gap: 5px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <div class="container">
        <h1>👥 User Management</h1>
        <p class="subtitle">Manage all ShareMyCard users</p>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($total_users); ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($total_admins); ?></div>
                <div class="stat-label">Admins</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($inactive_users); ?></div>
                <div class="stat-label">Inactive Users</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="section">
            <form method="GET" action="">
                <div class="filters">
                    <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                        All Active
                    </a>
                    <a href="?filter=admins" class="filter-btn <?php echo $filter === 'admins' ? 'active' : ''; ?>">
                        Admins
                    </a>
                    <a href="?filter=users" class="filter-btn <?php echo $filter === 'users' ? 'active' : ''; ?>">
                        Regular Users
                    </a>
                    <a href="?filter=inactive" class="filter-btn <?php echo $filter === 'inactive' ? 'active' : ''; ?>">
                        Inactive
                    </a>
                    
                    <div class="search-box">
                        <input 
                            type="text" 
                            name="search" 
                            placeholder="Search by email..." 
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                    </div>
                    <button type="submit" class="btn-small">Search</button>
                </div>
            </form>
        </div>
        
        <!-- Users Table -->
        <div class="section">
            <h2>Users (<?php echo count($users); ?>)</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($user['email']); ?></strong></td>
                            <td>
                                <?php if ($user['is_admin']): ?>
                                    <span class="badge badge-admin">Admin</span>
                                <?php else: ?>
                                    <span class="badge badge-user">User</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <span class="badge badge-active">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="user-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="toggle_admin">
                                        <button type="submit" class="btn-small">
                                            <?php echo $user['is_admin'] ? 'Remove Admin' : 'Make Admin'; ?>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="toggle_active">
                                        <button type="submit" class="btn-small">
                                            <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>

                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="resend_welcome">
                                        <button type="submit" class="btn-small" title="Re-send Welcome Email">
                                            Re-send Welcome Email
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #999;">
                                No users found
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

