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
} else if ($filter === 'recent') {
    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} else if ($filter === 'recently_active') {
    $where[] = "last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} else if ($filter === 'inactive_users') {
    $where[] = "(last_login IS NULL OR last_login < DATE_SUB(NOW(), INTERVAL 30 DAY))";
} else if ($filter === 'never_logged_in') {
    $where[] = "last_login IS NULL";
} else if ($filter === 'logins_today') {
    $where[] = "DATE(last_login) = CURDATE()";
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
    "SELECT id, email, is_admin, is_active, role, created_at, updated_at, last_login, login_count 
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
            flex-wrap: wrap;
            min-width: 200px;
        }
        
        .badge-demo {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
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
                    <a href="?filter=recent" class="filter-btn <?php echo $filter === 'recent' ? 'active' : ''; ?>">
                        Recent (7 days)
                    </a>
                    <a href="?filter=recently_active" class="filter-btn <?php echo $filter === 'recently_active' ? 'active' : ''; ?>">
                        Recently Active
                    </a>
                    <a href="?filter=inactive_users" class="filter-btn <?php echo $filter === 'inactive_users' ? 'active' : ''; ?>">
                        Inactive Users
                    </a>
                    <a href="?filter=never_logged_in" class="filter-btn <?php echo $filter === 'never_logged_in' ? 'active' : ''; ?>">
                        Never Logged In
                    </a>
                    <a href="?filter=logins_today" class="filter-btn <?php echo $filter === 'logins_today' ? 'active' : ''; ?>">
                        Logins Today
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
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($user['email']); ?></strong></td>
                            <td>
                                <?php 
                                $role = $user['role'] ?? 'user';
                                if ($role === 'admin'): ?>
                                    <span class="badge badge-admin">Admin</span>
                                <?php elseif ($role === 'demo'): ?>
                                    <span class="badge badge-demo">Demo</span>
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
                            <td>
                                <?php if ($user['last_login']): ?>
                                    <span class="last-login" title="Login count: <?php echo $user['login_count']; ?>">
                                        <?php 
                                        // Database stores timestamps in EDT, so create DateTime with EDT timezone
                                        $loginTime = new DateTime($user['last_login'], new DateTimeZone('America/New_York'));
                                        echo '<span style="color: #2c3e50; font-size: 0.9em;">' . $loginTime->format('M d, Y g:i A T') . '</span>';
                                        ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #95a5a6;">Never</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="user-actions">
                                    <button onclick="openEditModal('<?php echo $user['id']; ?>', '<?php echo htmlspecialchars($user['email']); ?>', <?php echo $user['is_active'] ? 'true' : 'false'; ?>, <?php echo $user['is_admin'] ? 'true' : 'false'; ?>, '<?php echo $user['role'] ?? 'user'; ?>', '<?php echo date('M d, Y', strtotime($user['created_at'])); ?>')" class="btn-small btn-edit">
                                        Edit
                                    </button>
                                    
                                    <?php if ($user['role'] !== 'demo'): ?>
                                    <a href="/admin/impersonate.php?user_id=<?php echo $user['id']; ?>" target="_blank" class="btn-small btn-impersonate" title="Login as this user">
                                        Login As
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($user['role'] !== 'demo' && $user['id'] !== AdminAuth::getUserId()): ?>
                                    <button onclick="openDeleteModal('<?php echo $user['id']; ?>', '<?php echo htmlspecialchars($user['email']); ?>')" class="btn-small btn-delete">
                                        Delete
                                    </button>
                                    <?php endif; ?>

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

    <!-- Edit User Modal -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Edit User</h3>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="editUserId" name="user_id">
                    
                    <div class="form-group">
                        <label for="editEmail">Email Address</label>
                        <input type="email" id="editEmail" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editPassword">New Password (optional)</label>
                        <div style="position: relative;">
                            <input type="password" id="editPassword" name="password" placeholder="Leave blank to keep current password">
                            <button type="button" onclick="togglePasswordVisibility('editPassword')" class="password-toggle">
                                👁️
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editIsActive">Status</label>
                        <select id="editIsActive" name="is_active">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="editRole">Role</label>
                        <select id="editRole" name="role">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                        <div id="selfDemotionWarning" style="display: none; color: #e74c3c; font-size: 12px; margin-top: 5px;">
                            ⚠️ You cannot change your own role
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Created Date</label>
                        <input type="text" id="editCreatedDate" readonly style="background: #f5f5f5;">
                    </div>
                    
                    <div id="editError" class="error-message" style="display: none;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
                <button onclick="saveUserChanges()" class="btn btn-primary" id="saveUserBtn">
                    <span id="saveBtnText">Save Changes</span>
                    <span id="saveBtnSpinner" style="display: none;">⏳ Saving...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div id="deleteModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🗑️ Delete User</h3>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p><strong>Are you sure you want to delete this user?</strong></p>
                <p id="deleteUserEmail" style="color: #e74c3c; font-weight: bold;"></p>
                <p><strong>This action cannot be undone.</strong></p>
                
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 15px 0;">
                    <h4 style="margin: 0 0 10px 0; color: #856404;">⚠️ The following data will be permanently deleted:</h4>
                    <ul style="margin: 0; padding-left: 20px; color: #856404;">
                        <li>User account and login credentials</li>
                        <li>All business cards created by this user</li>
                        <li>All contact information (emails, phones, websites, addresses)</li>
                        <li>All analytics data (views, clicks, downloads)</li>
                        <li>All media files (profile photos, logos, cover graphics)</li>
                        <li>All verification codes</li>
                    </ul>
                </div>
                
                <div id="deleteError" class="error-message" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
                <button onclick="confirmDeleteUser()" class="btn btn-danger" id="deleteUserBtn">
                    <span id="deleteBtnText">Delete User</span>
                    <span id="deleteBtnSpinner" style="display: none;">⏳ Deleting...</span>
                </button>
            </div>
        </div>
    </div>

    <style>
        /* Modal Styles */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 20px 30px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
        }

        .close {
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
            line-height: 1;
        }

        .close:hover {
            color: #000;
        }

        .modal-body {
            padding: 30px;
        }

        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-edit {
            background: #3498db;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        .btn-edit:hover {
            background: #2980b9;
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        .btn-delete:hover {
            background: #c0392b;
        }
        
        .btn-impersonate {
            background: #27ae60;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            white-space: nowrap;
        }
        
        .btn-impersonate:hover {
            background: #229954;
            color: white;
            text-decoration: none;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            border: 1px solid #fcc;
        }
    </style>

    <script>
        let currentEditUserId = null;
        let currentAdminId = '<?php echo AdminAuth::getUserId(); ?>';

        function openEditModal(userId, email, isActive, isAdmin, role, createdDate) {
            currentEditUserId = userId;
            
            // Populate form fields
            document.getElementById('editUserId').value = userId;
            document.getElementById('editEmail').value = email;
            document.getElementById('editIsActive').value = isActive ? '1' : '0';
            document.getElementById('editRole').value = role;
            document.getElementById('editCreatedDate').value = createdDate;
            
            // Clear password field
            document.getElementById('editPassword').value = '';
            
            // Check for self-protection (demotion and deactivation)
            const selfDemotionWarning = document.getElementById('selfDemotionWarning');
            const roleSelect = document.getElementById('editRole');
            const statusSelect = document.getElementById('editIsActive');
            
            if (userId === currentAdminId) {
                // Admin is editing themselves - disable role and status changes
                roleSelect.disabled = true;
                roleSelect.style.background = '#f5f5f5';
                statusSelect.disabled = true;
                statusSelect.style.background = '#f5f5f5';
                selfDemotionWarning.style.display = 'block';
                selfDemotionWarning.textContent = '⚠️ You cannot change your own role or status';
            } else {
                // Admin is editing someone else - allow all changes
                roleSelect.disabled = false;
                roleSelect.style.background = 'white';
                statusSelect.disabled = false;
                statusSelect.style.background = 'white';
                selfDemotionWarning.style.display = 'none';
            }
            
            // Show modal
            document.getElementById('editModal').style.display = 'flex';
            document.getElementById('editError').style.display = 'none';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            currentEditUserId = null;
        }

        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
            } else {
                input.type = 'password';
            }
        }

        function saveUserChanges() {
            if (!currentEditUserId) return;
            
            const saveBtn = document.getElementById('saveUserBtn');
            const btnText = document.getElementById('saveBtnText');
            const btnSpinner = document.getElementById('saveBtnSpinner');
            const errorDiv = document.getElementById('editError');
            
            // Show loading state
            saveBtn.disabled = true;
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline';
            errorDiv.style.display = 'none';
            
            // Get form data
            const formData = new FormData();
            formData.append('user_id', document.getElementById('editUserId').value);
            formData.append('email', document.getElementById('editEmail').value);
            formData.append('password', document.getElementById('editPassword').value);
            formData.append('is_active', document.getElementById('editIsActive').value);
            formData.append('is_admin', document.getElementById('editRole').value === 'admin' ? '1' : '0');
            formData.append('role', document.getElementById('editRole').value);
            
            // Submit to API
            fetch('/admin/api/edit-user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Success - close modal and reload page
                    closeEditModal();
                    location.reload();
                } else {
                    // Show error in modal
                    errorDiv.textContent = 'Error: ' + (data.message || 'Failed to update user');
                    errorDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Edit user error:', error);
                errorDiv.textContent = 'Error updating user. Please try again.';
                errorDiv.style.display = 'block';
            })
            .finally(() => {
                // Reset button state
                saveBtn.disabled = false;
                btnText.style.display = 'inline';
                btnSpinner.style.display = 'none';
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target === editModal) {
                closeEditModal();
            } else if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }

        // Delete User Functions
        let currentDeleteUserId = null;

        function openDeleteModal(userId, email) {
            currentDeleteUserId = userId;
            
            // Populate modal with user email
            document.getElementById('deleteUserEmail').textContent = email;
            
            // Show modal
            document.getElementById('deleteModal').style.display = 'flex';
            document.getElementById('deleteError').style.display = 'none';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            currentDeleteUserId = null;
        }

        function confirmDeleteUser() {
            if (!currentDeleteUserId) return;
            
            const deleteBtn = document.getElementById('deleteUserBtn');
            const btnText = document.getElementById('deleteBtnText');
            const btnSpinner = document.getElementById('deleteBtnSpinner');
            const errorDiv = document.getElementById('deleteError');
            
            // Show loading state
            deleteBtn.disabled = true;
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline';
            errorDiv.style.display = 'none';
            
            // Submit to API
            const formData = new FormData();
            formData.append('user_id', currentDeleteUserId);
            
            fetch('/admin/api/delete-user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Success - close modal and reload page
                    closeDeleteModal();
                    location.reload();
                } else {
                    // Show error in modal
                    errorDiv.textContent = 'Error: ' + (data.message || 'Failed to delete user');
                    errorDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Delete user error:', error);
                errorDiv.textContent = 'Error deleting user. Please try again.';
                errorDiv.style.display = 'block';
            })
            .finally(() => {
                // Reset button state
                deleteBtn.disabled = false;
                btnText.style.display = 'inline';
                btnSpinner.style.display = 'none';
            });
        }
    </script>
    
    <!-- Account Security Modal -->
    <?php 
    $userId = AdminAuth::getUserId();
    include __DIR__ . '/includes/account-security-modal.php'; 
    ?>
</body>
</html>

