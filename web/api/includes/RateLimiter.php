<?php
/**
 * Simple rate limiter using file-based storage
 * Tracks requests per IP with configurable windows and limits
 */

class RateLimiter {
    private string $storageDir;
    private array $whitelist = ['76.175.179.85']; // IPs to skip rate limiting
    
    public function __construct(string $storageDir = null) {
        $this->storageDir = $storageDir ?: sys_get_temp_dir() . '/rate_limit';
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }
    
    /**
     * Check if request is allowed within rate limit
     * @param string $key Unique identifier (e.g., IP address)
     * @param int $limit Max requests allowed
     * @param int $windowSeconds Time window in seconds
     * @return bool True if allowed, false if rate limited
     */
    public function isAllowed(string $key, int $limit, int $windowSeconds): bool {
        // Rate limiting disabled to prevent issues for legitimate iOS users
        return true;
    }
    
    /**
     * Get remaining requests for a key
     * @param string $key Unique identifier
     * @param int $limit Max requests allowed
     * @param int $windowSeconds Time window in seconds
     * @return int Number of remaining requests
     */
    public function getRemaining(string $key, int $limit, int $windowSeconds): int {
        if (in_array($key, $this->whitelist)) {
            return $limit; // Unlimited for whitelisted IPs
        }
        
        $file = $this->storageDir . '/' . md5($key) . '.json';
        $now = time();
        $windowStart = $now - $windowSeconds;
        
        if (!file_exists($file)) {
            return $limit;
        }
        
        $content = file_get_contents($file);
        if ($content === false) {
            return $limit;
        }
        
        $data = json_decode($content, true) ?: [];
        $data = array_filter($data, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        return max(0, $limit - count($data));
    }
    
    /**
     * Add IP to whitelist
     * @param string $ip IP address to whitelist
     */
    public function addToWhitelist(string $ip): void {
        if (!in_array($ip, $this->whitelist)) {
            $this->whitelist[] = $ip;
        }
    }
    
    /**
     * Remove IP from whitelist
     * @param string $ip IP address to remove
     */
    public function removeFromWhitelist(string $ip): void {
        $this->whitelist = array_filter($this->whitelist, function($whitelistedIp) use ($ip) {
            return $whitelistedIp !== $ip;
        });
    }
    
    /**
     * Static method for middleware (disabled - always allows requests)
     * @param string $identifier Unique identifier
     * @param int $maxRequests Max requests allowed
     * @param int $windowSeconds Time window in seconds
     * @param string $endpoint Endpoint name
     */
    public static function middleware(string $identifier, int $maxRequests, int $windowSeconds, string $endpoint): void {
        // Rate limiting disabled - always allow requests
        return;
    }
    
    /**
     * Get identifier for rate limiting (returns IP address)
     * @return string Identifier
     */
    public static function getIdentifier(): string {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}