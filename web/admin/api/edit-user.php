<?php
/**
 * Edit User - Session Authenticated
 * POST /admin/api/edit-user.php
 * Edits a user using session authentication
 */

require_once __DIR__ . '/../includes/AdminAuth.php';
require_once __DIR__ . '/../../api/includes/Database.php';

// Require authentication
AdminAuth::requireAuth();

$adminId = AdminAuth::getUserId();
$db = Database::getInstance();

// Validate required fields
$userId = $_POST['user_id'] ?? null;
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
$isAdmin = isset($_POST['is_admin']) ? (int)$_POST['is_admin'] : 0;
$role = $_POST['role'] ?? 'user';

if (!$userId) {
    error_log("Edit user request failed: User ID required. Admin ID: $adminId");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

if (empty($email)) {
    error_log("Edit user request failed: Email required. Admin ID: $adminId, User ID: $userId");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    error_log("Edit user request failed: Invalid email format. Admin ID: $adminId, User ID: $userId, Email: $email");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Validate role
if (!in_array($role, ['user', 'admin', 'demo'])) {
    error_log("Edit user request failed: Invalid role. Admin ID: $adminId, User ID: $userId, Role: $role");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

try {
    // Debug logging
    error_log("Edit user request - User ID: $userId, Admin ID: $adminId, Email: $email, Role: $role");
    
    // Check if user exists
    $user = $db->querySingle(
        "SELECT id, email, role FROM users WHERE id = ?",
        [$userId]
    );
    
    if (!$user) {
        error_log("User not found for ID: $userId. Admin ID: $adminId");
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Prevent editing demo user
    if ($user['role'] === 'demo') {
        error_log("Attempt to edit demo user blocked. Admin ID: $adminId, User ID: $userId");
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Demo user cannot be edited']);
        exit;
    }
    
    // Prevent self-demotion (admin cannot demote themselves)
    if ($userId === $adminId && $isAdmin == 0) {
        error_log("Admin self-demotion blocked. Admin ID: $adminId, User ID: $userId");
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You cannot demote yourself']);
        exit;
    }
    
    // Prevent self-deactivation (admin cannot deactivate themselves)
    if ($userId === $adminId && $isActive == 0) {
        error_log("Admin self-deactivation blocked. Admin ID: $adminId, User ID: $userId");
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You cannot deactivate yourself']);
        exit;
    }
    
    // Check if email is already taken by another user
    $existingUser = $db->querySingle(
        "SELECT id FROM users WHERE email = ? AND id != ?",
        [$email, $userId]
    );
    
    if ($existingUser) {
        error_log("Email already taken. Admin ID: $adminId, User ID: $userId, Email: $email");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email is already taken by another user']);
        exit;
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Prepare update query
    $updateFields = [
        'email = ?',
        'is_active = ?',
        'is_admin = ?',
        'role = ?',
        'updated_at = NOW()'
    ];
    $updateParams = [$email, $isActive, $isAdmin, $role];
    
    // Add password if provided
    if (!empty($password)) {
        $updateFields[] = 'password_hash = ?';
        $updateParams[] = password_hash($password, PASSWORD_DEFAULT);
    }
    
    $updateParams[] = $userId; // Add user ID for WHERE clause
    
    $updateQuery = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
    
    $db->execute($updateQuery, $updateParams);
    
    $db->commit();
    
    error_log("User updated successfully - User ID: $userId, Email: $email, Role: $role");
    
    echo json_encode([
        'success' => true,
        'message' => 'User updated successfully',
        'user' => [
            'id' => $userId,
            'email' => $email,
            'is_active' => $isActive,
            'is_admin' => $isAdmin,
            'role' => $role
        ]
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("User update error for user ID $userId, admin ID $adminId: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update user: ' . $e->getMessage()]);
}
