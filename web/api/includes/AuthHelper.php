<?php
/**
 * Authentication Helper
 * Provides static methods for authentication in API endpoints
 */

require_once __DIR__ . '/JWT.php';

class AuthHelper {
    
    /**
     * Check if user is authenticated
     * @return bool
     */
    public static function isAuthenticated() {
        $token = self::getAuthToken();
        if (!$token) {
            return false;
        }
        
        $payload = JWT::decode($token);
        return $payload !== null;
    }
    
    /**
     * Get authenticated user ID
     * @return string|null
     */
    public static function getUserId() {
        $token = self::getAuthToken();
        if (!$token) {
            return null;
        }
        
        $payload = JWT::decode($token);
        if (!$payload) {
            return null;
        }
        
        return $payload['user_id'] ?? null;
    }
    
    /**
     * Get full authentication payload
     * @return array|null
     */
    public static function getAuthPayload() {
        $token = self::getAuthToken();
        if (!$token) {
            return null;
        }
        
        return JWT::decode($token);
    }
    
    /**
     * Get authorization token from headers
     * @return string|null
     */
    private static function getAuthToken() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}
?>
