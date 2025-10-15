<?php
/**
 * Admin Authentication Helper
 * Manages admin sessions and authentication
 */

session_start();

require_once __DIR__ . '/../../api/includes/Database.php';

class AdminAuth {
    
    /**
     * Check if user is logged in as admin
     */
    public static function isLoggedIn() {
        return isset($_SESSION['admin_user_id']) && isset($_SESSION['admin_email']);
    }
    
    /**
     * Get current admin user ID
     */
    public static function getUserId() {
        return $_SESSION['admin_user_id'] ?? null;
    }
    
    /**
     * Get current admin email
     */
    public static function getEmail() {
        return $_SESSION['admin_email'] ?? null;
    }
    
    /**
     * Login admin user
     */
    public static function login($email) {
        $db = Database::getInstance();
        
        try {
            $user = $db->querySingle(
                "SELECT id, email, is_admin, is_active FROM users WHERE email = ?",
                [$email]
            );
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            if (!$user['is_active']) {
                return ['success' => false, 'message' => 'Account is not active'];
            }
            
            if (!$user['is_admin']) {
                return ['success' => false, 'message' => 'Access denied. Admin privileges required.'];
            }
            
            // Set session
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['admin_login_time'] = time();
            
            return ['success' => true, 'user' => $user];
            
        } catch (Exception $e) {
            error_log("Admin login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed'];
        }
    }
    
    /**
     * Logout admin user
     */
    public static function logout() {
        unset($_SESSION['admin_user_id']);
        unset($_SESSION['admin_email']);
        unset($_SESSION['admin_login_time']);
        session_destroy();
    }
    
    /**
     * Require admin authentication (redirect if not logged in)
     */
    public static function requireAuth() {
        if (!self::isLoggedIn()) {
            header('Location: /admin/login.php');
            exit;
        }
    }
    
    /**
     * Check if session is expired (4 hour timeout)
     */
    public static function isSessionExpired() {
        if (!isset($_SESSION['admin_login_time'])) {
            return true;
        }
        
        $timeout = 4 * 60 * 60; // 4 hours
        return (time() - $_SESSION['admin_login_time']) > $timeout;
    }
}

