<?php
/**
 * Rate Limiter Class
 * Simple rate limiting implementation using file-based storage
 * 
 * For production with multiple servers, consider using Redis or Memcached
 */

class RateLimiter {
    private static $storageDir = null;
    
    /**
     * Initialize storage directory
     */
    private static function getStorageDir() {
        if (self::$storageDir === null) {
            self::$storageDir = __DIR__ . '/../../storage/rate-limits/';
            if (!is_dir(self::$storageDir)) {
                mkdir(self::$storageDir, 0755, true);
            }
        }
        return self::$storageDir;
    }
    
    /**
     * Check rate limit for an identifier
     * 
     * @param string $identifier - Unique identifier (IP, user_id, etc.)
     * @param int $maxRequests - Maximum requests allowed
     * @param int $windowSeconds - Time window in seconds
     * @param string $endpoint - Optional endpoint name for separate limits
     * @return array - ['allowed' => bool, 'remaining' => int, 'reset_at' => int]
     */
    public static function check($identifier, $maxRequests = 100, $windowSeconds = 3600, $endpoint = 'global') {
        $storageFile = self::getStorageFile($identifier, $endpoint);
        
        // Get current requests
        $data = self::loadData($storageFile);
        $now = time();
        
        // Clean old requests outside the window
        $data['requests'] = array_filter($data['requests'], function($timestamp) use ($now, $windowSeconds) {
            return ($now - $timestamp) < $windowSeconds;
        });
        
        // Count current requests
        $currentRequests = count($data['requests']);
        $remaining = max(0, $maxRequests - $currentRequests);
        $allowed = $currentRequests < $maxRequests;
        
        // Calculate reset time (oldest request + window)
        $oldestRequest = !empty($data['requests']) ? min($data['requests']) : $now;
        $resetAt = $oldestRequest + $windowSeconds;
        
        if ($allowed) {
            // Add current request
            $data['requests'][] = $now;
            self::saveData($storageFile, $data);
        }
        
        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'reset_at' => $resetAt,
            'retry_after' => $allowed ? 0 : ($resetAt - $now)
        ];
    }
    
    /**
     * Get storage file path for identifier
     */
    private static function getStorageFile($identifier, $endpoint) {
        $hash = md5($identifier . ':' . $endpoint);
        return self::getStorageDir() . $hash . '.json';
    }
    
    /**
     * Load data from storage file
     */
    private static function loadData($file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            if ($data) {
                return $data;
            }
        }
        
        return ['requests' => []];
    }
    
    /**
     * Save data to storage file
     */
    private static function saveData($file, $data) {
        file_put_contents($file, json_encode($data));
    }
    
    /**
     * Rate limit middleware for API
     * 
     * @param string $identifier - Unique identifier (IP, user_id, etc.)
     * @param int $maxRequests - Maximum requests allowed
     * @param int $windowSeconds - Time window in seconds
     * @param string $endpoint - Optional endpoint name
     * @return void - Exits with 429 if limit exceeded
     */
    public static function middleware($identifier, $maxRequests = 100, $windowSeconds = 3600, $endpoint = 'global') {
        $result = self::check($identifier, $maxRequests, $windowSeconds, $endpoint);
        
        // Add rate limit headers
        header('X-RateLimit-Limit: ' . $maxRequests);
        header('X-RateLimit-Remaining: ' . $result['remaining']);
        header('X-RateLimit-Reset: ' . $result['reset_at']);
        
        if (!$result['allowed']) {
            header('Retry-After: ' . $result['retry_after']);
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => 'Rate limit exceeded. Please try again later.',
                'errors' => [
                    'rate_limit' => [
                        'max_requests' => $maxRequests,
                        'window_seconds' => $windowSeconds,
                        'retry_after' => $result['retry_after'],
                        'reset_at' => $result['reset_at']
                    ]
                ]
            ], JSON_PRETTY_PRINT);
            exit();
        }
    }
    
    /**
     * Get rate limit identifier from request
     * Prefers user_id from auth, falls back to IP
     * 
     * @param string|null $userId - Optional user ID from authentication
     * @return string
     */
    public static function getIdentifier($userId = null) {
        if ($userId) {
            return 'user:' . $userId;
        }
        
        // Get IP address
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Check for proxy headers
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        
        return 'ip:' . $ip;
    }
    
    /**
     * Clean old rate limit files (run periodically)
     * 
     * @param int $olderThan - Delete files older than this many seconds (default: 24 hours)
     */
    public static function cleanup($olderThan = 86400) {
        $dir = self::getStorageDir();
        $now = time();
        $count = 0;
        
        foreach (glob($dir . '*.json') as $file) {
            if (($now - filemtime($file)) > $olderThan) {
                unlink($file);
                $count++;
            }
        }
        
        return $count;
    }
}

