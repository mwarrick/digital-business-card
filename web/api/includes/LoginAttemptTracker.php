<?php
/**
 * Login Attempt Tracker
 * Handles rate limiting for failed login attempts
 */

require_once __DIR__ . '/Database.php';

class LoginAttemptTracker {
    
    const MAX_ATTEMPTS = 5;
    const LOCKOUT_DURATION = 900; // 15 minutes in seconds
    
    /**
     * Record a failed login attempt
     */
    public static function recordFailedAttempt($userId, $ipAddress = null) {
        $db = Database::getInstance();
        
        $attemptId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        $db->execute(
            "INSERT INTO failed_login_attempts (id, user_id, ip_address, attempted_at) 
             VALUES (?, ?, ?, NOW())",
            [$attemptId, $userId, $ipAddress]
        );
    }
    
    /**
     * Check if user is locked out
     */
    public static function isLockedOut($userId) {
        $db = Database::getInstance();
        
        // Count recent failed attempts
        $count = $db->querySingle(
            "SELECT COUNT(*) as count 
             FROM failed_login_attempts 
             WHERE user_id = ? 
             AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)",
            [$userId, self::LOCKOUT_DURATION]
        )['count'];
        
        return $count >= self::MAX_ATTEMPTS;
    }
    
    /**
     * Get remaining lockout time in seconds
     */
    public static function getRemainingLockoutTime($userId) {
        $db = Database::getInstance();
        
        $oldestAttempt = $db->querySingle(
            "SELECT attempted_at 
             FROM failed_login_attempts 
             WHERE user_id = ? 
             AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
             ORDER BY attempted_at ASC 
             LIMIT 1",
            [$userId, self::LOCKOUT_DURATION]
        );
        
        if (!$oldestAttempt) {
            return 0;
        }
        
        $lockoutEnd = strtotime($oldestAttempt['attempted_at']) + self::LOCKOUT_DURATION;
        $remaining = $lockoutEnd - time();
        
        return max(0, $remaining);
    }
    
    /**
     * Clear failed attempts for user (on successful login)
     */
    public static function clearFailedAttempts($userId) {
        $db = Database::getInstance();
        
        $db->execute(
            "DELETE FROM failed_login_attempts WHERE user_id = ?",
            [$userId]
        );
    }
    
    /**
     * Get failed attempt count for user
     */
    public static function getFailedAttemptCount($userId) {
        $db = Database::getInstance();
        
        $count = $db->querySingle(
            "SELECT COUNT(*) as count 
             FROM failed_login_attempts 
             WHERE user_id = ? 
             AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)",
            [$userId, self::LOCKOUT_DURATION]
        )['count'];
        
        return $count;
    }
}
