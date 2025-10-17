<?php
/**
 * End Admin Impersonation Endpoint
 * GET /admin/end-impersonation.php
 * Ends the current impersonation session and returns admin to their dashboard
 */

require_once __DIR__ . '/includes/AdminAuth.php';

// Start session to access impersonation data
session_start();

// Check if currently impersonating
if (!isset($_SESSION['is_impersonating']) || !$_SESSION['is_impersonating']) {
    // Not impersonating, redirect to admin dashboard
    header('Location: /admin/dashboard.php');
    exit;
}

// Get impersonation data
$adminId = $_SESSION['impersonating_admin_id'] ?? null;
$adminEmail = $_SESSION['impersonating_admin_email'] ?? null;
$impersonatedUserEmail = $_SESSION['user_email'] ?? 'Unknown';

// Log the end of impersonation
error_log("Admin impersonation ended - Admin ID: $adminId, Admin Email: $adminEmail, Was impersonating: $impersonatedUserEmail");

// Clear impersonation session data
unset($_SESSION['is_impersonating']);
unset($_SESSION['impersonating_admin_id']);
unset($_SESSION['impersonating_admin_email']);
unset($_SESSION['impersonating_from_time']);

// Clear user session data
unset($_SESSION['user_id']);
unset($_SESSION['user_email']);
unset($_SESSION['is_admin']);
unset($_SESSION['login_time']);

// Redirect to admin dashboard
header('Location: /admin/dashboard.php');
exit;
?>
