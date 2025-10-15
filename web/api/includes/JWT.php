<?php
/**
 * JWT (JSON Web Token) Helper Class
 * Simple JWT implementation for API authentication
 * 
 * Note: For production, consider using a library like firebase/php-jwt
 */

class JWT {
    // Secret key - should be stored in environment variable in production
    private static $secretKey = null;
    
    /**
     * Initialize secret key
     */
    private static function getSecretKey() {
        if (self::$secretKey === null) {
            // In production, load from environment variable or config
            // For now, generate a consistent key based on database config
            self::$secretKey = hash('sha256', DB_NAME . DB_USER . 'ShareMyCard-JWT-Secret-2025');
        }
        return self::$secretKey;
    }
    
    /**
     * Create JWT token
     * 
     * @param array $payload - Data to encode in token
     * @param int $expiresIn - Seconds until expiration (default: 30 days)
     * @return string - JWT token
     */
    public static function encode($payload, $expiresIn = 2592000) {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        
        $payload['iat'] = time(); // Issued at
        $payload['exp'] = time() + $expiresIn; // Expiration time
        
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', 
            $headerEncoded . '.' . $payloadEncoded, 
            self::getSecretKey(), 
            true
        );
        $signatureEncoded = self::base64UrlEncode($signature);
        
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }
    
    /**
     * Decode and verify JWT token
     * 
     * @param string $token - JWT token to decode
     * @return array|false - Decoded payload or false if invalid
     */
    public static function decode($token) {
        if (!$token) {
            return false;
        }
        
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        // Verify signature
        $signature = self::base64UrlDecode($signatureEncoded);
        $expectedSignature = hash_hmac('sha256', 
            $headerEncoded . '.' . $payloadEncoded, 
            self::getSecretKey(), 
            true
        );
        
        if (!hash_equals($signature, $expectedSignature)) {
            return false; // Invalid signature
        }
        
        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        
        if (!$payload) {
            return false;
        }
        
        // Check expiration
        if (isset($payload['exp']) && time() > $payload['exp']) {
            return false; // Token expired
        }
        
        return $payload;
    }
    
    /**
     * Verify if token is valid and not expired
     * 
     * @param string $token - JWT token to verify
     * @return bool
     */
    public static function verify($token) {
        return self::decode($token) !== false;
    }
    
    /**
     * Get payload from token without full verification (use carefully!)
     * 
     * @param string $token - JWT token
     * @return array|null - Payload or null if invalid format
     */
    public static function getPayload($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        
        $payload = json_decode(self::base64UrlDecode($parts[1]), true);
        return $payload;
    }
    
    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    /**
     * Create token for user
     * 
     * @param string $userId - User ID
     * @param string $email - User email
     * @param bool $isAdmin - Is admin user
     * @param int $expiresIn - Seconds until expiration
     * @return string - JWT token
     */
    public static function createUserToken($userId, $email, $isAdmin = false, $expiresIn = 2592000) {
        return self::encode([
            'user_id' => $userId,
            'email' => $email,
            'is_admin' => $isAdmin
        ], $expiresIn);
    }
}

