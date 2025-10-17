<?php
/**
 * User Authentication Helper
 * Manages user sessions and authentication
 */

session_start();

require_once __DIR__ . '/../../api/includes/Database.php';

class UserAuth {
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
    }
    
    /**
     * Get current user ID
     */
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user email
     */
    public static function getEmail() {
        return $_SESSION['user_email'] ?? null;
    }
    
    /**
     * Get current user data
     */
    public static function getUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        $db = Database::getInstance();
        return $db->querySingle(
            "SELECT id, email, is_active, created_at FROM users WHERE id = ?",
            [self::getUserId()]
        );
    }
    
    /**
     * Login user (set session)
     */
    public static function login($userId, $email) {
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_login_time'] = time();
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        // Check if this is an impersonation session
        if (isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating']) {
            // During impersonation, just clear the impersonation session
            // The admin session remains active in the original window
            unset($_SESSION['user_id']);
            unset($_SESSION['user_email']);
            unset($_SESSION['user_login_time']);
            unset($_SESSION['is_admin']);
            unset($_SESSION['login_time']);
            unset($_SESSION['is_impersonating']);
            unset($_SESSION['impersonating_admin_id']);
            unset($_SESSION['impersonating_admin_email']);
            unset($_SESSION['impersonating_from_time']);
            
            // Redirect to home page (admin session remains in original window)
            header('Location: /');
            exit;
        } else {
            // Normal logout - clear all session data
            unset($_SESSION['user_id']);
            unset($_SESSION['user_email']);
            unset($_SESSION['user_login_time']);
            session_destroy();
        }
    }
    
    /**
     * Require user authentication (redirect if not logged in)
     */
    public static function requireAuth() {
        if (!self::isLoggedIn()) {
            // Check if headers have already been sent
            if (!headers_sent()) {
                header('Location: /user/login.php');
                exit;
            } else {
                // Headers already sent, use JavaScript redirect
                echo '<script>window.location.href = "/user/login.php";</script>';
                echo '<noscript><meta http-equiv="refresh" content="0;url=/user/login.php"></noscript>';
                exit;
            }
        }
    }
    
    /**
     * Check if session is expired (4 hour timeout)
     */
    public static function isSessionExpired() {
        if (!isset($_SESSION['user_login_time'])) {
            return true;
        }
        
        $timeout = 4 * 60 * 60; // 4 hours
        return (time() - $_SESSION['user_login_time']) > $timeout;
    }
}

