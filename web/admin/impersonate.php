<?php
/**
 * Admin User Impersonation Endpoint
 * GET /admin/impersonate.php?user_id=USER_ID
 * Allows admin to impersonate a user in a new window while maintaining admin session
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/AdminAuth.php';
require_once __DIR__ . '/../api/includes/Database.php';
require_once __DIR__ . '/../api/includes/DemoUserHelper.php';

// Require admin authentication
AdminAuth::requireAuth();

$adminId = AdminAuth::getUserId();
$adminEmail = AdminAuth::getEmail();
$db = Database::getInstance();

// Get user ID from URL parameter
$userId = $_GET['user_id'] ?? null;

if (!$userId) {
    error_log("Impersonation failed: No user ID provided. Admin ID: $adminId");
    die("Error: No user ID provided");
}

try {
    error_log("Starting impersonation - Admin ID: $adminId, Target User ID: $userId");
    
    // Fetch the user to impersonate
    $user = $db->querySingle("SELECT id, email, is_active, role FROM users WHERE id = ?", [$userId]);
    
    if (!$user) {
        error_log("Impersonation failed: User not found. Admin ID: $adminId, Target User ID: $userId");
        die("Error: User not found");
    }
    
    // Prevent impersonating demo user
    if (DemoUserHelper::isDemoUser($user['email'])) {
        error_log("Admin attempted to impersonate demo user. Admin ID: $adminId, Demo User ID: $userId");
        die("Error: Cannot impersonate demo user");
    }
    
    // Check if user is active
    if (!$user['is_active']) {
        error_log("Impersonation failed: User is inactive. Admin ID: $adminId, Target User ID: $userId");
        die("Error: Cannot impersonate inactive user");
    }
    
    // Session should already be started by AdminAuth::requireAuth()
    // Just ensure we have a session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Store original admin session data
    $_SESSION['impersonating_admin_id'] = $adminId;
    $_SESSION['impersonating_admin_email'] = $adminEmail;
    $_SESSION['impersonating_from_time'] = time();
    $_SESSION['is_impersonating'] = true;
    
    // Create user session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['is_admin'] = false; // User is not admin during impersonation
    $_SESSION['login_time'] = time();
    
    // Log the impersonation
    error_log("Admin impersonation started - Admin ID: $adminId, Admin Email: $adminEmail, Target User ID: $userId, Target Email: " . $user['email']);
    
    // Redirect to user dashboard
    header('Location: /user/dashboard.php');
    exit;
    
} catch (Exception $e) {
    error_log("Impersonation error (Admin ID: $adminId, Target User ID: $userId): " . $e->getMessage());
    die("Error: Failed to start impersonation - " . $e->getMessage());
}
?>
